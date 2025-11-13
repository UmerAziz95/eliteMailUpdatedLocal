<?php

namespace App\Services;

use App\Models\OrderPanel;
use App\Models\OrderEmail;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
// when 
class BulkEmailImportService
{
    /**
     * Process bulk email import for order panel
     * 
     * @param array $data Request data containing file, order_panel_id, etc.
     * @param int|null $contractorId Contractor ID (null for admin imports)
     * @param bool $saveFile Whether to save the uploaded file (for contractor)
     * @return array Response data
     */
    public function import(array $data, ?int $contractorId = null, bool $saveFile = false): array
    {
        try {
            // dd($data);
            // Clean up batch_id before validation - remove if null, empty, or 0
            if (isset($data['batch_id'])) {
                $batchIdValue = $data['batch_id'];
                if ($batchIdValue === null || $batchIdValue === '' || $batchIdValue === '0' || $batchIdValue === 0 || $batchIdValue == 0) {
                    unset($data['batch_id']);
                }
            }

            // Validate the request
            $validationRules = [
                'bulk_file' => 'required|file|mimes:csv,txt|max:2048',
                'order_panel_id' => 'required|exists:order_panel,id',
                'customized_note' => 'nullable|string|max:1000',
                'batch_id' => 'nullable|integer|min:1',
                'expected_count' => 'nullable|integer|min:1',
                'overwrite' => 'nullable|boolean',
            ];

            // Add split_total_inboxes validation for contractor imports
            if ($contractorId !== null) {
                $validationRules['split_total_inboxes'] = 'required|integer';
            }

            $validator = Validator::make($data, $validationRules);

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all()),
                    'status_code' => 422
                ];
            }

            $orderPanelId = $data['order_panel_id'];
            $file = $data['bulk_file'];
            $requestedBatchId = (isset($data['batch_id']) && $data['batch_id'] > 0) ? (int)$data['batch_id'] : null;
            $isOverwrite = isset($data['overwrite']) && $data['overwrite'] == '1';

            // Get the order panel
            $orderPanelQuery = OrderPanel::with(['order', 'orderPanelSplits']);
            
            // For contractor, verify access
            if ($contractorId !== null) {
                // Check if panel exists first
                if (!OrderPanel::where('id', $orderPanelId)->exists()) {
                    return [
                        'success' => false,
                        'message' => 'Order panel not found. The panel may have been deleted or the ID is incorrect.',
                        'status_code' => 404
                    ];
                }

                $orderPanel = $orderPanelQuery->where('id', $orderPanelId)->first();

                if (!$orderPanel) {
                    return [
                        'success' => false,
                        'message' => 'Access denied. You do not have permission to import emails for this order panel.',
                        'status_code' => 403
                    ];
                }
            } else {
                // Admin access
                $orderPanel = $orderPanelQuery->findOrFail($orderPanelId);
            }

            // Get the first order panel split
            $orderPanelSplit = $orderPanel->orderPanelSplits->first();
            
            if (!$orderPanelSplit) {
                return [
                    'success' => false,
                    'message' => 'No order panel split found for this panel.',
                    'status_code' => 404
                ];
            }

            // Parse CSV file
            $parseResult = $this->parseCSVFile($file);
            if (!$parseResult['success']) {
                return $parseResult;
            }

            $csv = $parseResult['csv'];
            $headers = $parseResult['headers'];
            $headerRowIndex = $parseResult['headerRowIndex'];

            // Validate split total inboxes limit
            $splitTotalInboxes = $data['split_total_inboxes'] ?? 0;
            if ($splitTotalInboxes > 0 && count($csv) > $splitTotalInboxes) {
                return [
                    'success' => false,
                    'message' => "Cannot import " . count($csv) . " emails. Maximum allowed for this panel: {$splitTotalInboxes}",
                    'status_code' => 422
                ];
            }

            // Validate batch-wise import
            $validationResult = $this->validateBatchImport(
                $orderPanel, 
                $csv, 
                $requestedBatchId, 
                $isOverwrite
            );
            
            if (!$validationResult['success']) {
                return $validationResult;
            }

            // Parse email data from CSV
            $parseEmailsResult = $this->parseEmailsFromCSV($csv, $headers, $headerRowIndex, $orderPanel, $orderPanelSplit, $contractorId);
            
            if (!$parseEmailsResult['success']) {
                return $parseEmailsResult;
            }

            $emailsToImport = $parseEmailsResult['emails'];
            $errors = $parseEmailsResult['errors'];

            // Begin transaction
            DB::beginTransaction();

            try {
                $savedFilePath = null;

                // Save file if requested (for contractor)
                if ($saveFile) {
                    $savedFilePath = $this->saveUploadedFile($file, $orderPanel, $orderPanelSplit);
                    $orderPanelSplit->update(['uploaded_file_path' => $savedFilePath]);
                }

                // Insert emails with batch assignment
                $this->insertEmailsWithBatchAssignment(
                    $emailsToImport,
                    $orderPanel,
                    $requestedBatchId,
                    $isOverwrite
                );

                // Update customized note if provided
                if (isset($data['customized_note']) && !empty(trim($data['customized_note']))) {
                    $orderPanel->update(['customized_note' => trim($data['customized_note'])]);
                }

                // Update order assignment if contractor import and not assigned
                if ($contractorId !== null && $orderPanel->order->assigned_to == null) {
                    $orderPanel->order->assigned_to = $contractorId;
                    $orderPanel->order->save();
                }

                // Create customer notification
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

                // Send Slack notification
                try {
                    SlackNotificationService::sendCustomizedEmailCreatedNotification(
                        $orderPanel,
                        count($emailsToImport),
                        $data['customized_note'] ?? null
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send Slack notification for customized email creation', [
                        'error' => $e->getMessage(),
                        'order_panel_id' => $orderPanelId,
                        'user_id' => $contractorId ?? 'admin'
                    ]);
                }

                DB::commit();

                $userType = $contractorId !== null ? 'Contractor' : 'Admin';
                Log::info("{$userType} bulk email import successful", [
                    'order_panel_id' => $orderPanelId,
                    'imported_count' => count($emailsToImport),
                    'user_id' => $contractorId ?? 'admin',
                    'is_overwrite' => $isOverwrite,
                    'batch_id' => $requestedBatchId,
                    'file_saved' => $savedFilePath
                ]);

                $successMessage = $isOverwrite 
                    ? count($emailsToImport) . ' emails overwritten successfully for batch ' . $requestedBatchId . '.'
                    : count($emailsToImport) . ' emails imported successfully.';

                $response = [
                    'success' => true,
                    'message' => $successMessage,
                    'imported_count' => count($emailsToImport),
                    'count' => count($emailsToImport),
                    'errors' => $errors,
                    'is_overwrite' => $isOverwrite,
                    'status_code' => 200
                ];

                if ($savedFilePath) {
                    $response['file_saved'] = $savedFilePath;
                }

                return $response;

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            $userType = $contractorId !== null ? 'Contractor' : 'Admin';
            Log::error("{$userType} bulk email import failed", [
                'error' => $e->getMessage(),
                'order_panel_id' => $data['order_panel_id'] ?? null,
                'user_id' => $contractorId ?? 'admin',
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * Parse CSV file and extract headers
     */
    private function parseCSVFile(UploadedFile $file): array
    {
        $filePath = $file->getRealPath();
        
        if (!is_readable($filePath)) {
            return [
                'success' => false,
                'message' => 'The uploaded file is not readable.',
                'status_code' => 400
            ];
        }

        $csv = array_map('str_getcsv', file($filePath));
        
        if (empty($csv)) {
            return [
                'success' => false,
                'message' => 'CSV file is empty or invalid.',
                'status_code' => 422
            ];
        }

        // Find the header row
        $headerRowIndex = -1;
        $headers = [];
        
        for ($i = 0; $i < count($csv); $i++) {
            $row = array_map('trim', $csv[$i]);
            
            // Check for admin format (name, email, password)
            if (in_array('name', array_map('strtolower', $row)) && 
                in_array('email', array_map('strtolower', $row)) && 
                in_array('password', array_map('strtolower', $row))) {
                $headerRowIndex = $i;
                $headers = array_map('strtolower', $row);
                break;
            }
            // Check for contractor format (First Name, Last Name, Email address, Password)
            elseif (in_array('First Name', $row) && 
                     in_array('Last Name', $row) && 
                     in_array('Email address', $row) && 
                     in_array('Password', $row)) {
                $headerRowIndex = $i;
                $headers = $row;
                break;
            }
            // Check for old format (Domain, Email, Password)
            elseif (in_array('Domain', $row) && 
                    in_array('Email', $row) && 
                    in_array('Password', $row)) {
                $headerRowIndex = $i;
                $headers = $row;
                break;
            }
        }
        
        if ($headerRowIndex === -1) {
            return [
                'success' => false,
                'message' => 'File format is incorrect. Could not find header row with required columns.',
                'required_format' => 'CSV file must contain columns: name, email, password OR First Name, Last Name, Email address, Password OR Domain, Email, Password',
                'status_code' => 400
            ];
        }

        // Remove all rows up to and including the header
        $csv = array_slice($csv, $headerRowIndex + 1);
        
        if (empty($csv)) {
            return [
                'success' => false,
                'message' => 'No data rows found after header.',
                'status_code' => 400
            ];
        }

        return [
            'success' => true,
            'csv' => $csv,
            'headers' => $headers,
            'headerRowIndex' => $headerRowIndex
        ];
    }

    /**
     * Validate batch import constraints
     */
    private function validateBatchImport(OrderPanel $orderPanel, array $csv, ?int $requestedBatchId, bool $isOverwrite): array
    {
        $spaceAssigned = $orderPanel->space_assigned ?? 0;
        
        if ($spaceAssigned <= 0) {
            return ['success' => true];
        }

        $splitIds = $orderPanel->orderPanelSplits->pluck('id');
        $existingEmailCount = OrderEmail::whereIn('order_split_id', $splitIds)->count();
        
        if ($requestedBatchId) {
            // Validate for specific batch import
            $totalBatches = ceil($spaceAssigned / 200);
            $expectedBatchSize = ($requestedBatchId < $totalBatches) ? 200 : ($spaceAssigned % 200 ?: 200);
            
            // Validate CSV count matches expected batch size
            if (count($csv) != $expectedBatchSize) {
                return [
                    'success' => false,
                    'message' => "Batch {$requestedBatchId} requires exactly {$expectedBatchSize} emails. Your CSV contains " . count($csv) . " emails.",
                    'status_code' => 422
                ];
            }
            
            // Check if importing this batch would exceed space_assigned
            $totalAfterImport = $existingEmailCount + count($csv);
            if ($totalAfterImport > $spaceAssigned) {
                return [
                    'success' => false,
                    'message' => "Cannot import batch {$requestedBatchId}. Total would be {$totalAfterImport} emails, but space assigned is only {$spaceAssigned}.",
                    'status_code' => 422
                ];
            }

            // Check if batch already has emails and overwrite not enabled
            if (!$isOverwrite) {
                $existingBatchEmails = OrderEmail::whereIn('order_split_id', $splitIds)
                    ->where('batch_id', $requestedBatchId)
                    ->count();
                
                if ($existingBatchEmails > 0) {
                    return [
                        'success' => false,
                        'message' => "Batch {$requestedBatchId} already has {$existingBatchEmails} emails. Use overwrite option to replace them or choose a different batch.",
                        'status_code' => 422
                    ];
                }
            }
        } else {
            // No specific batch - validate general import
            $totalAfterImport = $existingEmailCount + count($csv);
            
            if ($totalAfterImport > $spaceAssigned) {
                return [
                    'success' => false,
                    'message' => "Cannot import " . count($csv) . " emails. Current emails: {$existingEmailCount}, Space assigned: {$spaceAssigned}. You can import up to " . ($spaceAssigned - $existingEmailCount) . " more emails.",
                    'status_code' => 422
                ];
            }
            
            // Validate batch size (200 emails per batch, except last batch can be less)
            $remainingSpace = $spaceAssigned - $existingEmailCount;
            if (count($csv) > 200 && count($csv) != $remainingSpace) {
                return [
                    'success' => false,
                    'message' => "Please import in batches of 200 emails. For this import, you can upload up to 200 emails (or {$remainingSpace} emails to complete the panel).",
                    'status_code' => 422
                ];
            }
        }

        return ['success' => true];
    }

    /**
     * Parse emails from CSV rows
     */
    private function parseEmailsFromCSV(array $csv, array $headers, int $headerRowIndex, OrderPanel $orderPanel, $orderPanelSplit, ?int $contractorId): array
    {
        $emailsToImport = [];
        $errors = [];
        $rowNumber = $headerRowIndex + 1;

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
                $lastName = '';
                $email = trim($data['email'] ?? '');
                $password = trim($data['password'] ?? '');
            }
            // Contractor format (First Name, Last Name, Email address, Password)
            elseif (isset($data['First Name']) && isset($data['Last Name'])) {
                $firstName = trim($data['First Name'] ?? '');
                $lastName = trim($data['Last Name'] ?? '');
                $email = trim($data['Email address'] ?? '');
                $password = trim($data['Password'] ?? '');
            }
            // Old format (Domain, Email, Password)
            elseif (isset($data['Domain'])) {
                $firstName = trim($data['Domain'] ?? '');
                $lastName = 'N/A';
                $email = trim($data['Email'] ?? '');
                $password = trim($data['Password'] ?? '');
            }

            // Validate required fields
            if (empty($firstName)) {
                $errors[] = "Row {$rowNumber}: Name is required";
                continue;
            }
            
            if (empty($password)) {
                $errors[] = "Row {$rowNumber}: Password is required";
                continue;
            }

            $emailsToImport[] = [
                'order_id' => $orderPanel->order_id,
                'user_id' => $orderPanel->order->user_id,
                'order_split_id' => $orderPanelSplit->id,
                'contractor_id' => $contractorId,
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
            return [
                'success' => false,
                'message' => 'File contains validation errors.',
                'errors' => $errors,
                'valid_rows' => count($emailsToImport),
                'total_rows' => count($csv),
                'status_code' => 400
            ];
        }

        if (empty($emailsToImport)) {
            return [
                'success' => false,
                'message' => 'No valid email records found in CSV file.',
                'status_code' => 422
            ];
        }

        return [
            'success' => true,
            'emails' => $emailsToImport,
            'errors' => $errors
        ];
    }

    /**
     * Insert emails with batch assignment
     */
    private function insertEmailsWithBatchAssignment(array $emailsToImport, OrderPanel $orderPanel, ?int $requestedBatchId, bool $isOverwrite): void
    {
        $splitIds = $orderPanel->orderPanelSplits->pluck('id');

        if ($requestedBatchId) {
            // User selected a specific batch
            $startingBatchNumber = $requestedBatchId;
            
            // If overwrite is enabled, delete existing emails in this batch
            if ($isOverwrite) {
                $deletedCount = OrderEmail::whereIn('order_split_id', $splitIds)
                    ->where('batch_id', $requestedBatchId)
                    ->delete();
                
                Log::info('Overwrite mode: Deleted existing emails', [
                    'batch_id' => $requestedBatchId,
                    'deleted_count' => $deletedCount
                ]);
            }
        } else {
            // No specific batch requested - find the next available unique batch_id
            // Get the highest batch_id currently in the database
            $maxBatchId = OrderEmail::whereIn('order_split_id', $splitIds)->max('batch_id');
            
            // If no batches exist, start from 1, otherwise increment the max
            $startingBatchNumber = $maxBatchId ? ($maxBatchId + 1) : 1;
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
    }

    /**
     * Save uploaded file to storage
     */
    private function saveUploadedFile(UploadedFile $file, OrderPanel $orderPanel, $orderPanelSplit): string
    {
        $uploadedFileName = 'order_' . $orderPanel->order_id . '_panel_' . $orderPanel->id . '_split_' . $orderPanelSplit->id . '_' . time() . '.csv';
        $uploadPath = 'uploads/order_files/';
        
        // Create directory if it doesn't exist
        if (!file_exists(storage_path('app/public/' . $uploadPath))) {
            mkdir(storage_path('app/public/' . $uploadPath), 0755, true);
        }
        
        // Save file to storage
        $savedFilePath = $uploadPath . $uploadedFileName;
        $file->storeAs('public/' . $uploadPath, $uploadedFileName);
        
        return $savedFilePath;
    }
}
