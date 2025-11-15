<?php

namespace App\Services;

use App\Models\OrderEmail;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class EmailExportService
{
    /**
     * Export emails as ZIP (batch-wise + domain-wise for missing batches)
     * Combines batch CSV files with domain-generated CSV files for empty batches
     * 
     * @param int $splitId
     * @param int $orderPanelId
     * @param object $order
     * @param object $orderPanelSplit
     * @return \Illuminate\Http\Response
     */
    
    public function exportSmartZip($splitId, $orderPanelId, $order, $orderPanelSplit)
    {
        try {
            // Get all splits for this order panel
            $splitIds = OrderPanel::find($orderPanelId)->orderPanelSplits->pluck('id');
            
            // Check if order_emails data exists with batch_id
            $emailsWithBatch = OrderEmail::whereIn('order_split_id', $splitIds)
                ->whereNotNull('batch_id')
                ->exists();

            if ($emailsWithBatch) {
                // Export mixed: batch-wise for filled batches + domain-wise for empty batches
                return $this->exportMixedBatchAndDomainZip($orderPanelId, $splitId, $order, $orderPanelSplit);
            } else {
                // Export domain-wise chunked (200 emails per file) if no batch data at all
                return $this->exportDomainWiseChunkedZip($splitId, $orderPanelSplit, $order);
            }

        } catch (\Exception $e) {
            Log::error('Error in smart ZIP export: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export mixed: batch CSV for filled batches + domain-generated CSV for empty batches
     * All files combined in a single ZIP
     * 
     * @param int $orderPanelId
     * @param int $splitId
     * @param object $order
     * @param object $orderPanelSplit
     * @return \Illuminate\Http\Response
     */
    private function exportMixedBatchAndDomainZip($orderPanelId, $splitId, $order, $orderPanelSplit)
    {
        try {
            // Get the order panel with its splits
            $orderPanel = OrderPanel::with('orderPanelSplits')->findOrFail($orderPanelId);
            
            // Get space_assigned to determine total batches
            $spaceAssigned = $orderPanel->space_assigned ?? 0;
            
            if ($spaceAssigned == 0) {
                throw new \Exception('No space assigned for this panel.');
            }
            
            // Calculate total batches needed
            $totalBatches = ceil($spaceAssigned / 200);
            
            // Get emails grouped by batch_id
            $splitIds = $orderPanel->orderPanelSplits->pluck('id');
            $emailsByBatch = OrderEmail::whereIn('order_split_id', $splitIds)
                ->select('id', 'name', 'last_name', 'email', 'password', 'batch_id')
                ->orderBy('batch_id')
                ->get()
                ->groupBy('batch_id');

            // Prepare domain-based email generation for missing batches
            $domainEmailData = $this->generateDomainBasedEmails($orderPanelSplit, $order);

            // Create temporary directory for CSV files
            $tempDir = storage_path('app/temp/export_' . time() . '_' . uniqid());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $csvFiles = [];
            $domainEmailIndex = 0; // Track position in domain email array

            // Generate CSV for each batch
            for ($i = 1; $i <= $totalBatches; $i++) {
                $batchEmails = $emailsByBatch->get($i, collect());
                $expectedCount = ($i < $totalBatches) ? 200 : ($spaceAssigned % 200 ?: 200);
                
                if ($batchEmails->count() > 0) {
                    // Batch has data - export from database
                    $filename = "Batch_{$i}_Customized.csv";
                    $filepath = $tempDir . '/' . $filename;
                    
                    $file = fopen($filepath, 'w');
                    
                    // Add CSV headers based on provider type
                    $headers = $this->getHeadersByProviderType($order->provider_type ?? 'Google');
                    fputcsv($file, $headers);
                    
                    // Add email data from database
                    foreach ($batchEmails as $email) {
                        $row = $this->formatRowByProviderType($email, $order->provider_type ?? 'Google');
                        fputcsv($file, $row);
                    }
                    
                    fclose($file);
                    $csvFiles[] = $filepath;
                } else {
                    // Batch is empty - generate from domains
                    if (!empty($domainEmailData)) {
                        $filename = "Batch_{$i}_Default.csv";
                        $filepath = $tempDir . '/' . $filename;
                        
                        $file = fopen($filepath, 'w');
                        
                        // Add CSV headers based on provider type
                        $headers = $this->getHeadersByProviderType($order->provider_type ?? 'Google');
                        fputcsv($file, $headers);
                        
                        // Add emails from domain generation (up to expectedCount)
                        $emailsToWrite = array_slice($domainEmailData, $domainEmailIndex, $expectedCount);
                        foreach ($emailsToWrite as $email) {
                            $row = $this->formatRowByProviderType($email, $order->provider_type ?? 'Google', true);
                            fputcsv($file, $row);
                        }
                        
                        $domainEmailIndex += count($emailsToWrite);
                        fclose($file);
                        $csvFiles[] = $filepath;
                    }
                }
            }

            if (empty($csvFiles)) {
                // Clean up temp directory
                @rmdir($tempDir);
                throw new \Exception('No email data found to export.');
            }

            // Create ZIP file
            $zipFilename = "order_{$order->id}_split_{$splitId}_complete_batches_" . date('Y_m_d_H_i_s') . ".zip";
            $zipPath = storage_path('app/temp/' . $zipFilename);

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($csvFiles as $csvFile) {
                    $zip->addFile($csvFile, basename($csvFile));
                }
                $zip->close();
            } else {
                throw new \Exception('Failed to create ZIP archive.');
            }

            // Clean up CSV files
            foreach ($csvFiles as $csvFile) {
                @unlink($csvFile);
            }
            @rmdir($tempDir);

            Log::info('Mixed batch and domain ZIP export completed', [
                'order_id' => $order->id,
                'split_id' => $splitId,
                'total_files' => count($csvFiles),
                'total_batches' => $totalBatches,
                'filename' => $zipFilename
            ]);

            // Return ZIP file for download and delete after sending
            return response()->download($zipPath, $zipFilename, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error exporting mixed batch and domain ZIP: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate domain-based email data for filling empty batches
     * 
     * @param object $orderPanelSplit
     * @param object $order
     * @return array
     */
    private function generateDomainBasedEmails($orderPanelSplit, $order)
    {
        try {
            // Get prefix variants and their details from reorder info
            $prefixVariants = [];
            $prefixVariantDetails = [];
            $reorderInfo = $order->reorderInfo->first();
            
            if ($reorderInfo) {
                if ($reorderInfo->prefix_variants) {
                    // Handle both string and array formats
                    if (is_string($reorderInfo->prefix_variants)) {
                        $prefixVariants = explode(',', $reorderInfo->prefix_variants);
                        $prefixVariants = array_map('trim', $prefixVariants);
                    } elseif (is_array($reorderInfo->prefix_variants)) {
                        $prefixVariants = $reorderInfo->prefix_variants;
                    }
                }
                
                if ($reorderInfo->prefix_variants_details) {
                    // Handle both JSON string and array formats
                    if (is_string($reorderInfo->prefix_variants_details)) {
                        $details = json_decode($reorderInfo->prefix_variants_details, true);
                        if (is_array($details)) {
                            $prefixVariantDetails = $details;
                        }
                    } elseif (is_array($reorderInfo->prefix_variants_details)) {
                        $prefixVariantDetails = $reorderInfo->prefix_variants_details;
                    }
                }
            }

            // Default prefixes if none found
            if (empty($prefixVariants)) {
                $prefixVariants = ['info', 'contact'];
            }

            // Get domains from the split
            $domains = [];
            if ($orderPanelSplit->domains) {
                // Handle both JSON string and array
                $domainsData = is_string($orderPanelSplit->domains) 
                    ? json_decode($orderPanelSplit->domains, true) 
                    : $orderPanelSplit->domains;
                    
                if (is_array($domainsData)) {
                    foreach ($domainsData as $domain) {
                        if (is_array($domain) && isset($domain['domain'])) {
                            $domains[] = $domain['domain'];
                        } elseif (is_string($domain)) {
                            $domains[] = $domain;
                        }
                    }
                }
            }

            if (empty($domains)) {
                return [];
            }

            // Generate emails with prefixes
            $emailData = [];
            foreach ($domains as $domain) {
                $counter = 0;
                foreach ($prefixVariants as $index => $prefix) {
                    $counter++;
                    $variantNumber = is_numeric($index) ? ($index + 1) : $counter;
                    $emailAddress = $prefix . '@' . $domain;
                    
                    $firstName = '';
                    $lastName = '';
                    
                    if (isset($prefixVariantDetails[$variantNumber])) {
                        $details = $prefixVariantDetails[$variantNumber];
                        $firstName = $details['first_name'] ?? $prefix;
                        $lastName = $details['last_name'] ?? ucfirst($prefix);
                    } else {
                        $firstName = $prefix;
                        $lastName = ucfirst($prefix);
                    }
                    
                    $emailData[] = [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $emailAddress,
                        'password' => $this->customEncrypt($order->id),
                    ];
                }
            }

            return $emailData;

        } catch (\Exception $e) {
            Log::error('Error generating domain-based emails: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Export emails batch-wise (uses getPanelEmailsByBatch logic)
     * Creates separate CSV files for each batch and returns them in a ZIP
     * 
     * @param int $orderPanelId
     * @param int $splitId
     * @param object $order
     * @return \Illuminate\Http\Response
     */
    public function exportBatchWiseZip($orderPanelId, $splitId, $order)
    {
        try {
            // Get the order panel with its splits
            $orderPanel = OrderPanel::with('orderPanelSplits')->findOrFail($orderPanelId);
            
            // Get space_assigned to determine total batches
            $spaceAssigned = $orderPanel->space_assigned ?? 0;
            
            if ($spaceAssigned == 0) {
                throw new \Exception('No space assigned for this panel.');
            }
            
            // Calculate total batches needed
            $totalBatches = ceil($spaceAssigned / 200);
            
            // Get emails grouped by batch_id
            $splitIds = $orderPanel->orderPanelSplits->pluck('id');
            $emailsByBatch = OrderEmail::whereIn('order_split_id', $splitIds)
                ->select('id', 'name', 'last_name', 'email', 'password', 'batch_id')
                ->orderBy('batch_id')
                ->get()
                ->groupBy('batch_id');

            // Create temporary directory for CSV files
            $tempDir = storage_path('app/temp/export_' . time() . '_' . uniqid());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $csvFiles = [];

            // Generate CSV for each batch
            for ($i = 1; $i <= $totalBatches; $i++) {
                $batchEmails = $emailsByBatch->get($i, collect());
                
                if ($batchEmails->count() > 0) {
                    $filename = "Batch_{$i}_Customized.csv";
                    $filepath = $tempDir . '/' . $filename;
                    
                    $file = fopen($filepath, 'w');
                    
                    // Add CSV headers based on provider type
                    $headers = $this->getHeadersByProviderType($order->provider_type ?? 'Google');
                    fputcsv($file, $headers);
                    
                    // Add email data
                    foreach ($batchEmails as $email) {
                        $row = $this->formatRowByProviderType($email, $order->provider_type ?? 'Google');
                        fputcsv($file, $row);
                    }
                    
                    fclose($file);
                    $csvFiles[] = $filepath;
                }
            }

            if (empty($csvFiles)) {
                // Clean up temp directory
                @rmdir($tempDir);
                throw new \Exception('No email data found to export.');
            }

            // Create ZIP file
            $zipFilename = "order_{$order->id}_split_{$splitId}_batch_wise_" . date('Y_m_d_H_i_s') . ".zip";
            $zipPath = storage_path('app/temp/' . $zipFilename);

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($csvFiles as $csvFile) {
                    $zip->addFile($csvFile, basename($csvFile));
                }
                $zip->close();
            } else {
                throw new \Exception('Failed to create ZIP archive.');
            }

            // Clean up CSV files
            foreach ($csvFiles as $csvFile) {
                @unlink($csvFile);
            }
            @rmdir($tempDir);

            Log::info('Batch-wise ZIP export completed', [
                'order_id' => $order->id,
                'split_id' => $splitId,
                'total_batches' => count($csvFiles),
                'filename' => $zipFilename
            ]);

            // Return ZIP file for download and delete after sending
            return response()->download($zipPath, $zipFilename, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error exporting batch-wise ZIP: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export emails domain-wise in chunks of 200 emails per CSV file
     * Returns all files in a ZIP archive
     * 
     * @param int $splitId
     * @param object $orderPanelSplit
     * @param object $order
     * @return \Illuminate\Http\Response
     */
    public function exportDomainWiseChunkedZip($splitId, $orderPanelSplit, $order)
    {
        try {
            // Generate domain-based emails
            $emailData = $this->generateDomainBasedEmails($orderPanelSplit, $order);

            if (empty($emailData)) {
                throw new \Exception('No email data generated from domains.');
            }

            // Create temporary directory for CSV files
            $tempDir = storage_path('app/temp/export_' . time() . '_' . uniqid());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $csvFiles = [];
            $chunkSize = 200;
            $chunks = array_chunk($emailData, $chunkSize);

            // Generate CSV for each chunk
            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkNumber = $chunkIndex + 1;
                $filename = "Domain_Chunk_{$chunkNumber}_Default.csv";
                $filepath = $tempDir . '/' . $filename;
                
                $file = fopen($filepath, 'w');
                
                // Add CSV headers based on provider type
                $headers = $this->getHeadersByProviderType($order->provider_type ?? 'Google');
                fputcsv($file, $headers);
                
                // Add email data
                foreach ($chunk as $email) {
                    $row = $this->formatRowByProviderType($email, $order->provider_type ?? 'Google', true);
                    fputcsv($file, $row);
                }
                
                fclose($file);
                $csvFiles[] = $filepath;
            }

            // Create ZIP file
            $zipFilename = "order_{$order->id}_split_{$splitId}_domain_chunked_" . date('Y_m_d_H_i_s') . ".zip";
            $zipPath = storage_path('app/temp/' . $zipFilename);

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($csvFiles as $csvFile) {
                    $zip->addFile($csvFile, basename($csvFile));
                }
                $zip->close();
            } else {
                throw new \Exception('Failed to create ZIP archive.');
            }

            // Clean up CSV files
            foreach ($csvFiles as $csvFile) {
                @unlink($csvFile);
            }
            @rmdir($tempDir);

            Log::info('Domain-wise chunked ZIP export completed', [
                'order_id' => $order->id,
                'split_id' => $splitId,
                'total_chunks' => count($csvFiles),
                'total_emails' => count($emailData),
                'filename' => $zipFilename
            ]);

            // Return ZIP file for download and delete after sending
            return response()->download($zipPath, $zipFilename, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error exporting domain-wise chunked ZIP: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Custom encryption function for passwords
     * Generates an 8-character password with uppercase, lowercase, number, and special character
     * 
     * @param int $orderId
     * @return string
     */
    private function customEncrypt($orderId)
    {
        $upperCase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerCase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*';
        
        // Use order ID as seed for consistent password generation
        mt_srand($orderId);
        
        // Generate password with requirements
        $password = '';
        $password .= $upperCase[mt_rand(0, strlen($upperCase) - 1)]; // 1 uppercase
        $password .= $lowerCase[mt_rand(0, strlen($lowerCase) - 1)]; // 1 lowercase
        $password .= $numbers[mt_rand(0, strlen($numbers) - 1)];     // 1 number
        $password .= $specialChars[mt_rand(0, strlen($specialChars) - 1)]; // 1 special char
        
        // Fill remaining 4 characters with mix of all character types
        $allChars = $upperCase . $lowerCase . $numbers . $specialChars;
        for ($i = 4; $i < 8; $i++) {
            $password .= $allChars[mt_rand(0, strlen($allChars) - 1)];
        }
        
        // Shuffle using seeded random generator
        $passwordArray = str_split($password);
        for ($i = count($passwordArray) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            // Swap characters
            $temp = $passwordArray[$i];
            $passwordArray[$i] = $passwordArray[$j];
            $passwordArray[$j] = $temp;
        }
        
        return implode('', $passwordArray);
    }

    /**
     * Get batch emails data (similar to getPanelEmailsByBatch)
     * 
     * @param int $orderPanelId
     * @return array
     */
    public function getBatchEmailsData($orderPanelId)
    {
        try {
            $orderPanel = OrderPanel::with('orderPanelSplits')->findOrFail($orderPanelId);
            
            $spaceAssigned = $orderPanel->space_assigned ?? 0;
            
            if ($spaceAssigned == 0) {
                return [
                    'success' => false,
                    'message' => 'No space assigned for this panel.'
                ];
            }
            
            $totalBatches = ceil($spaceAssigned / 200);
            
            $splitIds = $orderPanel->orderPanelSplits->pluck('id');
            $emailsByBatch = OrderEmail::whereIn('order_split_id', $splitIds)
                ->select('id', 'name', 'last_name', 'email', 'password', 'batch_id')
                ->orderBy('batch_id')
                ->get()
                ->groupBy('batch_id');

            $batches = [];
            for ($i = 1; $i <= $totalBatches; $i++) {
                $expectedCount = ($i < $totalBatches) ? 200 : ($spaceAssigned % 200 ?: 200);
                $batchEmails = $emailsByBatch->get($i, collect());
                
                $batches[] = [
                    'batch_number' => $i,
                    'email_count' => $batchEmails->count(),
                    'expected_count' => $expectedCount,
                    'emails' => $batchEmails->toArray()
                ];
            }

            $totalEmails = $emailsByBatch->flatten()->count();

            return [
                'success' => true,
                'total_batches' => $totalBatches,
                'total_emails' => $totalEmails,
                'space_assigned' => $spaceAssigned,
                'batches' => $batches
            ];

        } catch (\Exception $e) {
            Log::error('Error getting batch emails data: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error fetching batch data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get CSV headers based on provider type
     * 
     * @param string $providerType
     * @return array
     */
    private function getHeadersByProviderType($providerType)
    {
        if ($providerType === 'Microsoft 365') {
            return [
                'First Name',
                'Last Name',
                'Display Name',
                'User Name',
                'Password',
                'Job Title',
                'Department',
                'Office Number',
                'Office Phone',
                'Mobile Phone',
                'Fax',
                'Address',
                'City',
                'State or Province',
                'ZIP or Postal Code',
                'Country or Region'
            ];
        }
        
        // Default: Google Workspace format
        return [
            'First Name',
            'Last Name',
            'Email address',
            'Password',
            // 'Org Unit Path [Required]'
        ];
    }

    /**
     * Format row data based on provider type
     * 
     * @param mixed $email (object or array)
     * @param string $providerType
     * @param bool $isArray Whether email data is array format
     * @return array
     */
    private function formatRowByProviderType($email, $providerType, $isArray = false)
    {
        if ($isArray) {
            $firstName = $email['first_name'] ?? '';
            $lastName = $email['last_name'] ?? '';
            $emailAddress = $email['email'] ?? '';
            $password = $email['password'] ?? '';
        } else {
            $firstName = $email->name ?? '';
            $lastName = $email->last_name ?? '';
            $emailAddress = $email->email ?? '';
            $password = $email->password ?? '';
        }

        if ($providerType === 'Microsoft 365') {
            // Extract username from email (part before @)
            $username = strpos($emailAddress, '@') !== false ? substr($emailAddress, 0, strpos($emailAddress, '@')) : $emailAddress;
            
            return [
                $firstName,                          // First Name
                $lastName,                           // Last Name
                $firstName . ' ' . $lastName,        // Display Name
                $emailAddress,                       // User Name (full email)
                $password,                           // Password
                '',                                  // Job Title
                '',                                  // Department
                '',                                  // Office Number
                '',                                  // Office Phone
                '',                                  // Mobile Phone
                '',                                  // Fax
                '',                                  // Address
                '',                                  // City
                '',                                  // State or Province
                '',                                  // ZIP or Postal Code
                ''                                   // Country or Region
            ];
        }
        
        // Default: Google Workspace format
        return [
            $firstName,                              // First Name
            $lastName,                               // Last Name
            $emailAddress,                           // Email address
            $password,                               // Password
            '/'                                      // Org Unit Path [Required]
        ];
    }
}
