<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderEmail;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\Notification;
use App\Services\SlackNotificationService;
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
            $emailsByBatch = OrderEmail::whereIn('order_split_id', $splitIds)
                ->select('id', 'name', 'last_name', 'email', 'password', 'batch_id')
                ->orderBy('batch_id')
                ->get()
                ->groupBy('batch_id');
            // dd($emailsByBatch);
            // Generate all batches (even empty ones)
            $batches = [];
            for ($i = 1; $i <= $totalBatches; $i++) {
                // Calculate expected count for this batch
                $expectedCount = ($i < $totalBatches) ? 200 : ($spaceAssigned % 200 ?: 200);
                
                // Check if this batch has emails
                $batchEmails = $emailsByBatch->get($i, collect());
                
                $batches[] = [
                    'batch_id' => $i,
                    'batch_number' => $i,
                    'email_count' => $batchEmails->count(),
                    'expected_count' => $expectedCount,
                    'emails' => $batchEmails->values()
                ];
            }

            // Calculate total emails across all batches
            $totalEmails = $emailsByBatch->flatten()->count();

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
    
    public function bulkImport(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'bulk_file' => 'required|file|mimes:csv,txt|max:2048',
                'order_panel_id' => 'required|exists:order_panel,id',
                'customized_note' => 'nullable|string|max:1000',
                'batch_id' => 'nullable|integer|min:1',
                'expected_count' => 'nullable|integer|min:1',
                'overwrite' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all())
                ], 422);
            }

            $orderPanelId = $request->order_panel_id;
            $file = $request->file('bulk_file');
            $requestedBatchId = $request->batch_id; // Get the specific batch_id from the request
            $isOverwrite = $request->has('overwrite') && $request->overwrite == '1';

            // Get the order panel with its splits
            $orderPanel = OrderPanel::with(['order', 'orderPanelSplits'])->findOrFail($orderPanelId);
            // Get the first order panel split (assuming one split per panel for now)
            $orderPanelSplit = $orderPanel->orderPanelSplits->first();
            
            if (!$orderPanelSplit) {
                return response()->json([
                    'success' => false,
                    'message' => 'No order panel split found for this panel.'
                ], 404);
            }

            // Read and parse CSV file
            $filePath = $file->getRealPath();
            
            if (!is_readable($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file is not readable.'
                ], 400);
            }

            $csv = array_map('str_getcsv', file($filePath));
            
            if (empty($csv)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSV file is empty or invalid.'
                ], 422);
            }

            // Find the header row
            $headerRowIndex = -1;
            $headers = [];
            
            for ($i = 0; $i < count($csv); $i++) {
                $row = array_map('trim', $csv[$i]);
                // Check for new format (name, email, password)
                if (in_array('name', array_map('strtolower', $row)) && in_array('email', array_map('strtolower', $row)) && in_array('password', array_map('strtolower', $row))) {
                    $headerRowIndex = $i;
                    $headers = array_map('strtolower', $row);
                    break;
                }
                // Check for contractor format (First Name, Last Name, Email, Password)
                elseif (in_array('First Name', $row) && in_array('Last Name', $row) && in_array('Email address', $row) && in_array('Password', $row)) {
                    $headerRowIndex = $i;
                    $headers = $row;
                    break;
                }
            }
            
            if ($headerRowIndex === -1) {
                return response()->json([
                    'success' => false,
                    'message' => 'File format is incorrect. Could not find header row with required columns.',
                    'required_format' => 'CSV file must contain columns: name, email, password OR First Name, Last Name, Email address, Password'
                ], 400);
            }

            // Remove all rows up to and including the header
            $csv = array_slice($csv, $headerRowIndex + 1);
            
            if (empty($csv)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data rows found after header.'
                ], 400);
            }

            // Check for split total inboxes limit
            $splitTotalInboxes = $request->split_total_inboxes ?? 0;
            if ($splitTotalInboxes > 0 && count($csv) > $splitTotalInboxes) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot import " . count($csv) . " emails. Maximum allowed for this panel: {$splitTotalInboxes}"
                ], 422);
            }

            // Check if CSV count is valid for batch-wise import
            $spaceAssigned = $orderPanel->space_assigned ?? 0;
            if ($spaceAssigned > 0) {
                // Get existing email count for this panel
                $splitIds = $orderPanel->orderPanelSplits->pluck('id');
                $existingEmailCount = OrderEmail::whereIn('order_split_id', $splitIds)->count();
                
                // If a specific batch is requested, validate against that batch
                if ($requestedBatchId) {
                    // Calculate expected size for this batch
                    $totalBatches = ceil($spaceAssigned / 200);
                    $expectedBatchSize = ($requestedBatchId < $totalBatches) ? 200 : ($spaceAssigned % 200 ?: 200);
                    
                    // Validate CSV count matches expected batch size
                    if (count($csv) != $expectedBatchSize) {
                        return response()->json([
                            'success' => false,
                            'message' => "Batch {$requestedBatchId} requires exactly {$expectedBatchSize} emails. Your CSV contains " . count($csv) . " emails."
                        ], 422);
                    }
                    
                    // Check if importing this batch would exceed space_assigned
                    $totalAfterImport = $existingEmailCount + count($csv);
                    if ($totalAfterImport > $spaceAssigned) {
                        return response()->json([
                            'success' => false,
                            'message' => "Cannot import batch {$requestedBatchId}. Total would be {$totalAfterImport} emails, but space assigned is only {$spaceAssigned}."
                        ], 422);
                    }
                } else {
                    // No specific batch - validate general import
                    $totalAfterImport = $existingEmailCount + count($csv);
                    
                    // Allow imports in batches of 200 or less (for the last batch)
                    // But ensure total doesn't exceed space_assigned
                    if ($totalAfterImport > $spaceAssigned) {
                        return response()->json([
                            'success' => false,
                            'message' => "Cannot import " . count($csv) . " emails. Current emails: {$existingEmailCount}, Space assigned: {$spaceAssigned}. You can import up to " . ($spaceAssigned - $existingEmailCount) . " more emails."
                        ], 422);
                    }
                    
                    // Validate batch size (200 emails per batch, except last batch can be less)
                    $remainingSpace = $spaceAssigned - $existingEmailCount;
                    if (count($csv) > 200 && count($csv) != $remainingSpace) {
                        return response()->json([
                            'success' => false,
                            'message' => "Please import in batches of 200 emails. For this import, you can upload up to 200 emails (or {$remainingSpace} emails to complete the panel)."
                        ], 422);
                    }
                }
            }

            $emailsToImport = [];
            $errors = [];
            $rowNumber = $headerRowIndex + 1; // Start from header row + 1

            foreach ($csv as $row) {
                $rowNumber++;
                
                if (count($row) !== count($headers)) {
                    $errors[] = "Row {$rowNumber}: Column count mismatch. Expected " . count($headers) . " columns, got " . count($row);
                    continue;
                }

                $data = array_combine($headers, $row);
                
                // Handle different formats
                $firstName = '';
                $lastName = '';
                $email = '';
                $password = '';
                
                // Admin format (name, email, password)
                if (isset($data['name'])) {
                    $firstName = trim($data['name'] ?? '');
                    $lastName = ''; // Admin format doesn't have last name
                    $email = trim($data['email'] ?? '');
                    $password = trim($data['password'] ?? '');
                }
                // Contractor format (First Name, Last Name, Email, Password)
                elseif (isset($data['First Name'])) {
                    $firstName = trim($data['First Name'] ?? '');
                    $lastName = trim($data['Last Name'] ?? '');
                    $email = trim($data['Email address'] ?? '');
                    $password = trim($data['Password'] ?? '');
                }

                // Validate required fields
                if (empty($firstName)) {
                    $errors[] = "Row {$rowNumber}: Name is required";
                    continue;
                }
                
                // if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                //     $errors[] = "Row {$rowNumber}: Valid email is required";
                //     continue;
                // }
                
                if (empty($password)) {
                    $errors[] = "Row {$rowNumber}: Password is required";
                    continue;
                }

                $emailsToImport[] = [
                    'order_id' => $orderPanel->order_id,
                    'user_id' => $orderPanel->order->user_id,
                    'order_split_id' => $orderPanelSplit->id,
                    'contractor_id' => null, // Admin import, no contractor
                    'name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => $password,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // If there are validation errors, return them
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File contains validation errors.',
                    'errors' => $errors,
                    'valid_rows' => count($emailsToImport),
                    'total_rows' => count($csv)
                ], 400);
            }

            if (empty($emailsToImport)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid email records found in CSV file.'
                ], 422);
            }

            // Begin transaction
            DB::beginTransaction();

            try {
                // Determine which batch_id to use
                if ($requestedBatchId) {
                    // User selected a specific batch - use that batch_id
                    $startingBatchNumber = $requestedBatchId;
                    
                    // Check if this batch already has emails
                    $splitIds = $orderPanel->orderPanelSplits->pluck('id');
                    $existingBatchEmails = OrderEmail::whereIn('order_split_id', $splitIds)
                        ->where('batch_id', $requestedBatchId)
                        ->count();
                    
                    // If batch has emails and overwrite is not enabled, reject
                    if ($existingBatchEmails > 0 && !$isOverwrite) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Batch {$requestedBatchId} already has {$existingBatchEmails} emails. Use overwrite option to replace them or choose a different batch."
                        ], 422);
                    }
                    
                    // If overwrite is enabled, delete existing emails in this batch
                    if ($isOverwrite && $existingBatchEmails > 0) {
                        $deletedCount = OrderEmail::whereIn('order_split_id', $splitIds)
                            ->where('batch_id', $requestedBatchId)
                            ->delete();
                        
                        Log::info('Admin overwrite mode: Deleted existing emails', [
                            'batch_id' => $requestedBatchId,
                            'deleted_count' => $deletedCount,
                            'admin_id' => auth()->id()
                        ]);
                    }
                } else {
                    // No specific batch requested - calculate next available batch
                    $splitIds = $orderPanel->orderPanelSplits->pluck('id');
                    $existingEmailCount = OrderEmail::whereIn('order_split_id', $splitIds)->count();
                    $startingBatchNumber = floor($existingEmailCount / 200) + 1;
                }
                
                // Insert new emails in batches with batch_id assignment
                // Each batch contains 200 emails
                $batchSize = 200;
                $batchNumber = $startingBatchNumber;
                
                for ($i = 0; $i < count($emailsToImport); $i += $batchSize) {
                    $batch = array_slice($emailsToImport, $i, $batchSize);
                    
                    // Assign batch_id to each email in the current batch
                    foreach ($batch as &$email) {
                        $email['batch_id'] = $batchNumber;
                    }
                    
                    // Insert batch into database
                    OrderEmail::insert($batch);
                    
                    $batchNumber++;
                }

                // Update the order panel with customized note if provided
                if ($request->has('customized_note') && !empty(trim($request->customized_note))) {
                    $orderPanel->update([
                        'customized_note' => trim($request->customized_note)
                    ]);
                }

                // Create notification for customer
                Notification::create([
                    'user_id' => $orderPanel->order->user_id,
                    'type' => 'email_created',
                    'title' => 'Bulk Email Accounts Created',
                    'message' => 'Bulk email accounts have been imported for your order #' . $orderPanel->order_id . ' panel #' . $orderPanel->id,
                    'data' => [
                        'order_id' => $orderPanel->order_id,
                        'order_panel_id' => $orderPanel->id,
                        'email_count' => count($emailsToImport)
                    ]
                ]);

                // Send Slack notification to inbox-setup channel
                try {
                    SlackNotificationService::sendCustomizedEmailCreatedNotification(
                        $orderPanel, 
                        count($emailsToImport), 
                        $request->customized_note
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send Slack notification for customized email creation', [
                        'error' => $e->getMessage(),
                        'order_panel_id' => $orderPanelId,
                        'admin_id' => auth()->id()
                    ]);
                }

                DB::commit();

                Log::info('Admin bulk email import successful', [
                    'order_panel_id' => $orderPanelId,
                    'imported_count' => count($emailsToImport),
                    'admin_id' => auth()->id(),
                    'is_overwrite' => $isOverwrite,
                    'batch_id' => $requestedBatchId
                ]);

                $successMessage = $isOverwrite 
                    ? count($emailsToImport) . ' emails overwritten successfully for batch ' . $requestedBatchId . '.'
                    : count($emailsToImport) . ' emails imported successfully.';

                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'imported_count' => count($emailsToImport),
                    'errors' => $errors,
                    'is_overwrite' => $isOverwrite
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Admin bulk email import failed', [
                'error' => $e->getMessage(),
                'order_panel_id' => $request->order_panel_id ?? null,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
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
