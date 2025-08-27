<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderEmail;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\Notification;
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

    public function bulkImport(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'bulk_file' => 'required|file|mimes:csv,txt|max:2048',
                'order_panel_id' => 'required|exists:order_panel,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all())
                ], 422);
            }

            $orderPanelId = $request->order_panel_id;
            $file = $request->file('bulk_file');

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
                // Delete existing emails for this specific order panel split
                OrderEmail::where('order_id', $orderPanel->order_id)
                    ->where('order_split_id', $orderPanelSplit->id)
                    ->delete();

                // Insert new emails in batches
                $batchSize = 500;
                for ($i = 0; $i < count($emailsToImport); $i += $batchSize) {
                    $batch = array_slice($emailsToImport, $i, $batchSize);
                    OrderEmail::insert($batch);
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

                DB::commit();

                Log::info('Admin bulk email import successful', [
                    'order_panel_id' => $orderPanelId,
                    'imported_count' => count($emailsToImport),
                    'admin_id' => auth()->id()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => count($emailsToImport) . ' emails imported successfully.',
                    'imported_count' => count($emailsToImport),
                    'errors' => $errors
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
