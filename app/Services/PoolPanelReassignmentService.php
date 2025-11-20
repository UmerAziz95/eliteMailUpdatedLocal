<?php

namespace App\Services;

 use App\Models\PoolPanel;
 use App\Models\PoolPanelSplit;
 use App\Models\PoolPanelReassignmentHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PoolPanelReassignmentService
{
    public function getAvailablePoolPanelsForReassignment(int $poolId, int $currentPoolPanelId): array
    {
        try {
            $splits = PoolPanelSplit::where('pool_id', $poolId)
                ->where('pool_panel_id', $currentPoolPanelId)
                ->get();

            if ($splits->isEmpty()) {
                throw new Exception('No pool panel splits found for this pool and panel');
            }

            $spaceNeeded = $splits->sum(function (PoolPanelSplit $split) {
                $domainCount = is_array($split->domains) ? count($split->domains) : 0;
                if ($domainCount === 0 && is_array($split->getDomainDetails())) {
                    $domainCount = count($split->getDomainDetails());
                }

                $calculated = $split->inboxes_per_domain * $domainCount;
                return $calculated > 0 ? $calculated : ($split->assigned_space ?? 0);
            });

            $usedPanelIds = PoolPanelSplit::where('pool_id', $poolId)
                ->pluck('pool_panel_id')
                ->unique()
                ->toArray();

            $availablePanels = PoolPanel::where('is_active', true)
                ->whereNotIn('id', $usedPanelIds)
                ->where('remaining_limit', '>=', $spaceNeeded)
                ->get();

            $panels = $availablePanels->map(function (PoolPanel $panel) use ($spaceNeeded) {
                return [
                    'panel_id' => $panel->id,
                    'panel_title' => $panel->title ?? 'N/A',
                    'panel_limit' => $panel->limit ?? 0,
                    'panel_remaining_limit' => $panel->remaining_limit ?? 0,
                    'status' => 'available',
                    'space_needed' => $spaceNeeded,
                    'created_at' => optional($panel->created_at)->format('Y-m-d H:i:s'),
                    'is_reassignable' => true,
                    'total_splits' => $panel->poolPanelSplits()->count(),
                ];
            })->values();

            return [
                'success' => true,
                'panels' => $panels,
                'total_count' => $panels->count(),
                'space_needed' => $spaceNeeded,
                'current_pool_panel_id' => $currentPoolPanelId,
            ];
        } catch (Exception $e) {
            Log::error('Error getting available pool panels for reassignment', [
                'pool_id' => $poolId,
                'current_pool_panel_id' => $currentPoolPanelId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get available pool panels: ' . $e->getMessage(),
                'panels' => [],
                'total_count' => 0,
            ];
        }
    }

    public function reassignPoolPanelSplit(int $fromPoolPanelId, int $toPoolPanelId, ?int $splitId = null, ?int $userId = null, ?string $reason = null): array
    {
        try {
            DB::beginTransaction();

            $fromPoolPanel = PoolPanel::with(['poolPanelSplits'])->findOrFail($fromPoolPanelId);
            $toPoolPanel = PoolPanel::where('id', $toPoolPanelId)
                ->where('is_active', true)
                ->firstOrFail();

            $splitsQuery = PoolPanelSplit::where('pool_panel_id', $fromPoolPanelId);
            if ($splitId) {
                $splitsQuery->where('id', $splitId);
            }
            $splitsToMove = $splitsQuery->get();

            if ($splitsToMove->isEmpty()) {
                throw new Exception('No pool panel splits found to reassign');
            }

            $spaceToTransfer = 0;
            $poolIds = [];
            foreach ($splitsToMove as $split) {
                $domainCount = is_array($split->domains) ? count($split->domains) : 0;
                if ($domainCount === 0 && is_array($split->getDomainDetails())) {
                    $domainCount = count($split->getDomainDetails());
                }

                $calculatedSpace = $split->inboxes_per_domain * $domainCount;
                $spaceForSplit = $calculatedSpace > 0 ? $calculatedSpace : ($split->assigned_space ?? 0);
                $spaceToTransfer += $spaceForSplit;
                $poolIds[$split->pool_id] = true;
            }

            if ($toPoolPanel->remaining_limit < $spaceToTransfer) {
                throw new Exception('Destination pool panel does not have enough capacity');
            }

            foreach (array_keys($poolIds) as $poolId) {
                $exists = PoolPanelSplit::where('pool_panel_id', $toPoolPanelId)
                    ->where('pool_id', $poolId)
                    ->exists();

                if ($exists) {
                    throw new Exception('Destination pool panel already contains a split for this pool');
                }
            }

            $movedSplitsCount = 0;
            $movedSplitIds = [];
            $poolId = $splitsToMove->first()->pool_id;

            foreach ($splitsToMove as $split) {
                $split->update([
                    'pool_panel_id' => $toPoolPanel->id,
                ]);
                $movedSplitsCount++;
                $movedSplitIds[] = $split->id;
            }

            $this->createReassignmentHistory([
                'pool_id' => $poolId,
                'pool_panel_id' => $fromPoolPanel->id,
                'from_pool_panel_id' => $fromPoolPanel->id,
                'to_pool_panel_id' => $toPoolPanel->id,
                'pool_panel_split_id' => $splitId,
                'reassigned_by' => $userId ?? auth()->id(),
                'space_transferred' => $spaceToTransfer,
                'splits_count' => $movedSplitsCount,
                'split_ids' => $movedSplitIds,
                'reason' => $reason ?? 'Pool panel reassignment via service',
            ]);

            $fromPoolPanel->increment('remaining_limit', $spaceToTransfer);
            $fromPoolPanel->decrement('used_limit', $spaceToTransfer);
            $toPoolPanel->decrement('remaining_limit', $spaceToTransfer);
            $toPoolPanel->increment('used_limit', $spaceToTransfer);

            if ($fromPoolPanel->used_limit < 0) {
                $fromPoolPanel->update(['used_limit' => 0]);
            }

            if ($toPoolPanel->used_limit > $toPoolPanel->limit) {
                $toPoolPanel->update(['used_limit' => $toPoolPanel->limit]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Successfully reassigned {$movedSplitsCount} split(s) with {$spaceToTransfer} inbox capacity to pool panel {$toPoolPanel->title}",
                'splits_moved' => $movedSplitsCount,
                'space_transferred' => $spaceToTransfer,
                'pool_id' => $poolId,
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Pool panel split reassignment failed', [
                'from_pool_panel_id' => $fromPoolPanelId,
                'to_pool_panel_id' => $toPoolPanelId,
                'split_id' => $splitId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function createReassignmentHistory(array $data): void
    {
        $baseData = [
            'pool_id' => $data['pool_id'],
            'pool_panel_id' => $data['pool_panel_id'],
            'pool_panel_split_id' => $data['pool_panel_split_id'] ?? null,
            'reassigned_by' => $data['reassigned_by'],
            'reassignment_date' => now(),
            'space_transferred' => $data['space_transferred'],
            'splits_count' => $data['splits_count'],
            'split_ids' => $data['split_ids'],
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'status' => 'pending',
        ];

        if (!empty($data['from_pool_panel_id'])) {
            $existingRemovalRecord = PoolPanelReassignmentHistory::where('status', 'pending')
                ->where('pool_id', $data['pool_id'])
                ->where('pool_panel_id', $data['pool_panel_id'])
                ->where('action_type', 'removed')
                ->first();

            if ($existingRemovalRecord) {
                $existingRemovalRecord->update(array_merge($baseData, [
                    'from_pool_panel_id' => $data['from_pool_panel_id'],
                    'to_pool_panel_id' => $data['to_pool_panel_id'] ?? null,
                ]));
            } else {
                PoolPanelReassignmentHistory::create(array_merge($baseData, [
                    'from_pool_panel_id' => $data['from_pool_panel_id'],
                    'to_pool_panel_id' => $data['to_pool_panel_id'] ?? null,
                    'action_type' => 'removed',
                ]));
            }
        }

        if (!empty($data['to_pool_panel_id'])) {
            $existingAdditionRecord = PoolPanelReassignmentHistory::where('status', 'pending')
                ->where('pool_id', $data['pool_id'])
                ->where('pool_panel_id', $data['pool_panel_id'])
                ->where('action_type', 'added')
                ->first();

            if ($existingAdditionRecord) {
                $existingAdditionRecord->update(array_merge($baseData, [
                    'from_pool_panel_id' => $data['from_pool_panel_id'] ?? null,
                    'to_pool_panel_id' => $data['to_pool_panel_id'],
                    'action_type' => 'added',
                ]));
            } else {
                PoolPanelReassignmentHistory::create(array_merge($baseData, [
                    'from_pool_panel_id' => $data['from_pool_panel_id'] ?? null,
                    'to_pool_panel_id' => $data['to_pool_panel_id'],
                    'action_type' => 'added',
                ]));
            }
        }
    }

    public function getReassignmentHistory(int $poolId): array
    {
        try {
            $history = PoolPanelReassignmentHistory::with([
                'poolPanel',
                'fromPoolPanel',
                'toPoolPanel',
                'reassignedBy',
                'assignedTo',
            ])
                ->forPool($poolId)
                ->orderBy('reassignment_date', 'desc')
                ->get();

            $formatted = $history->map(function (PoolPanelReassignmentHistory $record) {
                return [
                    'id' => $record->id,
                    'pool_panel_id' => $record->pool_panel_id,
                    'pool_id' => $record->pool_id,
                    'action_type' => $record->action_type,
                    'from_pool_panel' => $record->fromPoolPanel ? [
                        'id' => $record->fromPoolPanel->id,
                        'title' => $record->fromPoolPanel->title,
                    ] : null,
                    'to_pool_panel' => $record->toPoolPanel ? [
                        'id' => $record->toPoolPanel->id,
                        'title' => $record->toPoolPanel->title,
                    ] : null,
                    'reassigned_by' => $record->reassignedBy ? [
                        'id' => $record->reassignedBy->id,
                        'name' => $record->reassignedBy->name,
                        'email' => $record->reassignedBy->email,
                    ] : null,
                    'assigned_to' => $record->assignedTo ? [
                        'id' => $record->assignedTo->id,
                        'name' => $record->assignedTo->name,
                        'email' => $record->assignedTo->email,
                    ] : null,
                    'status' => $record->status,
                    'space_transferred' => $record->space_transferred,
                    'splits_count' => $record->splits_count,
                    'reason' => $record->reason,
                    'notes' => $record->notes,
                    'reassignment_date' => optional($record->reassignment_date)->format('Y-m-d H:i:s'),
                    'task_started_at' => optional($record->task_started_at)->format('Y-m-d H:i:s'),
                    'task_completed_at' => optional($record->task_completed_at)->format('Y-m-d H:i:s'),
                    'completion_notes' => $record->completion_notes,
                ];
            });

            return [
                'success' => true,
                'history' => $formatted,
                'total_count' => $history->count(),
            ];
        } catch (Exception $e) {
            Log::error('Error getting pool panel reassignment history', [
                'pool_id' => $poolId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'history' => collect(),
                'total_count' => 0,
            ];
        }
    }

    public function getPendingTasks(?int $assignedTo = null): array
    {
        try {
            $query = PoolPanelReassignmentHistory::with([
                'pool',
                'poolPanel',
                'fromPoolPanel',
                'toPoolPanel',
                'reassignedBy',
                'assignedTo',
            ])->pending();

            if ($assignedTo) {
                $query->where('assigned_to', $assignedTo);
            }

            $tasks = $query->orderBy('reassignment_date', 'asc')->get();

            return [
                'success' => true,
                'tasks' => $tasks,
                'total_count' => $tasks->count(),
            ];
        } catch (Exception $e) {
            Log::error('Error getting pending pool panel reassignment tasks', [
                'assigned_to' => $assignedTo,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tasks' => collect(),
                'total_count' => 0,
            ];
        }
    }

    public function startPoolPanelReassignmentTask(PoolPanelReassignmentHistory $task, int $userId): bool
    {
        try {
            DB::beginTransaction();

            $task->update([
                'status' => 'in-progress',
                'assigned_to' => $userId,
                'updated_at' => now(),
            ]);

            if ($task->action_type === 'removed') {
                $pairedTask = PoolPanelReassignmentHistory::where('pool_id', $task->pool_id)
                    ->where('from_pool_panel_id', $task->from_pool_panel_id)
                    ->where('to_pool_panel_id', $task->to_pool_panel_id)
                    ->where('action_type', 'added')
                    ->where('reassignment_date', $task->reassignment_date)
                    ->first();

                if ($pairedTask) {
                    $pairedTask->update([
                        'status' => 'in-progress',
                        'assigned_to' => $userId,
                        'updated_at' => now(),
                    ]);
                }
            } elseif ($task->action_type === 'added') {
                $pairedTask = PoolPanelReassignmentHistory::where('pool_id', $task->pool_id)
                    ->where('from_pool_panel_id', $task->from_pool_panel_id)
                    ->where('to_pool_panel_id', $task->to_pool_panel_id)
                    ->where('action_type', 'removed')
                    ->where('reassignment_date', $task->reassignment_date)
                    ->first();

                if ($pairedTask) {
                    $pairedTask->update([
                        'status' => 'in-progress',
                        'assigned_to' => $userId,
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error starting pool panel reassignment task', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function updatePoolPanelReassignmentTaskStatus(PoolPanelReassignmentHistory $task, string $status, array $additionalData = []): bool
    {
        try {
            DB::beginTransaction();

            $updateData = array_merge([
                'status' => $status,
                'updated_at' => now(),
            ], $additionalData);

            $task->update($updateData);

            if ($task->action_type === 'removed') {
                $pairedTask = PoolPanelReassignmentHistory::where('pool_id', $task->pool_id)
                    ->where('from_pool_panel_id', $task->from_pool_panel_id)
                    ->where('to_pool_panel_id', $task->to_pool_panel_id)
                    ->where('action_type', 'added')
                    ->where('reassignment_date', $task->reassignment_date)
                    ->first();

                if ($pairedTask) {
                    $pairedTask->update($updateData);
                }
            } elseif ($task->action_type === 'added') {
                $pairedTask = PoolPanelReassignmentHistory::where('pool_id', $task->pool_id)
                    ->where('from_pool_panel_id', $task->from_pool_panel_id)
                    ->where('to_pool_panel_id', $task->to_pool_panel_id)
                    ->where('action_type', 'removed')
                    ->where('reassignment_date', $task->reassignment_date)
                    ->first();

                if ($pairedTask) {
                    $pairedTask->update($updateData);
                }
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating pool panel reassignment task status', [
                'task_id' => $task->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
