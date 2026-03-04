<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderEmail;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\Notification;
use App\Services\SlackNotificationService;
use App\Services\BulkEmailImportService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminOrderEmailController extends Controller
{
    public function getEmails($orderId)
    {
        try {
            $emails = OrderEmail::where('order_id', $orderId)->get();

            return response()->json([
                'success' => true,
                'data' => $emails
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching emails: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPanelEmails($orderPanelId)
    {
        try {
            // Get the order panel with its splits
            $orderPanel = OrderPanel::with('orderPanelSplits')->findOrFail($orderPanelId);
            
            // Get emails for all splits of this panel
            $splitIds = $orderPanel->orderPanelSplits->pluck('id');
            
            $emails = OrderEmail::whereIn('order_split_id', $splitIds)
                ->select('id', 'name', 'last_name', 'email', 'password')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $emails
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching panel emails: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get emails for a panel grouped by batch_id
     * Each batch contains up to 200 emails
     */
    public function getPanelEmailsByBatch($orderPanelId)
    {
        try {
            // Get the order panel with its splits
            $orderPanel = OrderPanel::with('orderPanelSplits')->findOrFail($orderPanelId);
            // dd($orderPanel);
            // Get space_assigned to determine total batches
            $spaceAssigned = $orderPanel->space_assigned ?? 0;
            
            if ($spaceAssigned == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No space assigned for this panel.'
                ], 400);
            }
            
            // Calculate total batches needed
            $totalBatches = ceil($spaceAssigned / 200);
            
            // Get emails for all splits of this panel grouped by batch_id
            $splitIds = $orderPanel->orderPanelSplits->pluck('id');
            // dd($splitIds);
            $emailsByBatch = OrderEmail::whereIn('order_split_id', $splitIds)
                ->select('id', 'name', 'last_name', 'email', 'password', 'batch_id')
                ->orderBy('batch_id')
                ->get()
                ->groupBy('batch_id');
            // dd($emailsByBatch);
            
            // Generate all batches (even empty ones) based on space_assigned
            $batches = [];
            for ($i = 1; $i <= $totalBatches; $i++) {
                // Calculate expected count for this batch
                $expectedCount = ($i < $totalBatches) ? 200 : ($spaceAssigned % 200 ?: 200);
                
                // Get emails for this batch_id directly from the grouped collection
                $batchEmails = $emailsByBatch->get($i, collect());
                
                $batches[] = [
                    'batch_id' => $i,
                    'batch_number' => $i,
                    'actual_batch_id' => $batchEmails->isNotEmpty() ? $i : null,
                    'email_count' => $batchEmails->count(),
                    'expected_count' => $expectedCount,
                    'emails' => $batchEmails->values()
                ];
            }

            // Calculate total emails across all batches
            $totalEmails = $emailsByBatch->sum(function($batch) { 
                return $batch->count(); 
            });

            return response()->json([
                'success' => true,
                'total_batches' => $totalBatches,
                'total_emails' => $totalEmails,
                'space_assigned' => $spaceAssigned,
                'batches' => $batches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching panel emails by batch: ' . $e->getMessage()
            ], 500);
        }
    }
    // when 
    public function bulkImport(Request $request)
    {
        $importService = new BulkEmailImportService();
        
        $result = $importService->import(
            $request->all(),
            null, // null = admin import (no contractor_id)
            false // don't save file for admin imports
        );

        return response()->json(
            array_filter($result, function($key) {
                return $key !== 'status_code';
            }, ARRAY_FILTER_USE_KEY),
            $result['status_code'] ?? 200
        );
    }

    /**
     * Check if emails exist for a specific order panel
     */
    public function checkEmailsExist($orderPanelId)
    {
        try {
            // Get the order panel with its splits
            $orderPanel = OrderPanel::with('orderPanelSplits')->findOrFail($orderPanelId);
            
            // Get emails for all splits of this panel
            $splitIds = $orderPanel->orderPanelSplits->pluck('id');
            
            $emailCount = OrderEmail::whereIn('order_split_id', $splitIds)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'exists' => $emailCount > 0,
                    'count' => $emailCount,
                    'order_panel_id' => $orderPanelId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking email data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download CSV file with emails for a specific order panel
     */
    public function downloadCsv($orderPanelId)
    {
        try {
            // Get the order panel with its splits and order
            $orderPanel = OrderPanel::with(['order', 'orderPanelSplits'])->findOrFail($orderPanelId);
            
            // Get emails for all splits of this panel
            $splitIds = $orderPanel->orderPanelSplits->pluck('id');
            
            // Check if any emails exist for this panel
            $emails = OrderEmail::whereIn('order_split_id', $splitIds)
                ->select('name', 'last_name', 'email', 'password')
                ->get();

            if ($emails->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No email data available for this order panel.'
                ], 404);
            }

            // Generate CSV filename
            $filename = 'order_' . $orderPanel->order_id . '_panel_' . $orderPanel->id . '_emails_' . date('Y_m_d_H_i_s') . '.csv';

            // Set headers for CSV download
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Content-Description' => 'File Transfer',
                'Expires' => '0',
                'Pragma' => 'public',
            ];

            // Generate CSV content using the same format as contractor's export
            $callback = function() use ($emails, $orderPanel) {
                $file = fopen('php://output', 'w');
                
                // Add CSV headers matching Google Workspace bulk upload format
                fputcsv($file, [
                    'First Name', 
                    'Last Name',
                    'Email address', 
                    'Password',
                    'Org Unit Path [Required]'
                ]);
                
                // Add data rows
                foreach ($emails as $email) {
                    $firstName = $email->name ?: 'User';
                    $lastName = $email->last_name ?: '';
                    
                    fputcsv($file, [
                        $firstName,
                        $lastName,
                        $email->email,
                        $email->password,
                        '/' // Default org unit path
                    ]);
                }
                
                fclose($file);
            };

            Log::info('Admin CSV download initiated', [
                'order_panel_id' => $orderPanelId,
                'email_count' => $emails->count(),
                'admin_id' => auth()->id()
            ]);

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Admin CSV download failed', [
                'error' => $e->getMessage(),
                'order_panel_id' => $orderPanelId,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate CSV: ' . $e->getMessage()
            ], 500);
        }
    }
}
