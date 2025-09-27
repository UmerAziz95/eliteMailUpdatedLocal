<?php

namespace App\Services;

use App\Models\OrderPanelSplit;
use App\Models\OrderEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class TextExportService
{
    /**
     * Export TXT file with smart data selection based on order_emails availability
     * If order_emails data exists for order panels, use that data
     * Otherwise, fall back to the existing domain-based generation method
     */
    public function exportTxtSplitDomainsSmartById($splitId)
    {
        try {
            // Find the order panel split
            $orderPanelSplit = OrderPanelSplit::with([
                'orderPanel.order.orderPanels.userOrderPanelAssignments' => function($query) {
                    $query->where('contractor_id', auth()->id());
                },
                'orderPanel.order.reorderInfo',
                'orderPanel.panel'
            ])->findOrFail($splitId);

            $order = $orderPanelSplit->orderPanel->order;
            $orderPanelId = $orderPanelSplit->order_panel_id;

            // Check if order_emails data is available for this order panel
            $orderEmails = OrderEmail::whereHas('orderSplit', function($query) use ($orderPanelId) {
                $query->where('order_panel_id', $orderPanelId);
            })->get();

            // If order_emails data exists, use it for TXT generation
            if ($orderEmails->count() > 0) {
                return $this->exportTxtFromOrderEmails($splitId, $orderEmails);
            }

            // Otherwise, fall back to the domain-based method with prefix generation
            return $this->exportTxtWithGeneratedEmails($splitId);

        } catch (\Exception $e) {
            Log::error('Error exporting TXT with smart selection: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export TXT file with generated emails using prefixes (similar to CSV export logic)
     */
    public function exportTxtWithGeneratedEmails($splitId)
    {
        try {
            $orderPanelSplit = OrderPanelSplit::with([
                'orderPanel.order.reorderInfo',
                'orderPanel.panel'
            ])->findOrFail($splitId);

            $order = $orderPanelSplit->orderPanel->order;
            $reorderInfo = $order->reorderInfo->first();

            if (!$reorderInfo) {
                throw new \Exception('Reorder information not found for this order.');
            }

            // Extract domains and prefix information
            $domains = $this->extractDomains($orderPanelSplit);
            $prefixVariants = $this->extractPrefixVariants($reorderInfo);
            $prefixVariantDetails = $this->extractPrefixVariantDetails($reorderInfo);

            if (empty($domains)) {
                throw new \Exception('No domains found for this split.');
            }

            // Generate email data with prefixes
            $emailData = $this->generateEmailDataWithPrefixes($domains, $prefixVariants, $prefixVariantDetails, $order->id);

            $filename = "order_{$order->id}_split_{$splitId}_generated_emails.txt";

            $headers = [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($emailData, $orderPanelSplit, $order, $domains, $prefixVariants, $reorderInfo) {
                $file = fopen('php://output', 'w');
                
                // Check if master inbox is enabled
                $masterInboxEnabled = $this->isMasterInboxEnabled($reorderInfo);
                
                // Add TXT headers
                // fwrite($file, "Order #{$order->id} - Split #{$orderPanelSplit->id} Generated Email Data\n");
                // fwrite($file, "Generated on: " . date('Y-m-d H:i:s') . "\n");
                // fwrite($file, "Panel: " . ($orderPanelSplit->orderPanel->panel->title ?? 'N/A') . "\n");
                // fwrite($file, "Total Domains: " . count($domains) . "\n");
                // fwrite($file, "Prefix Variants: " . implode(', ', $prefixVariants) . "\n");
                // fwrite($file, "Total Generated Emails: " . count($emailData) . "\n");
                
                if ($masterInboxEnabled) {
                    // fwrite($file, "Master Inbox: " . $reorderInfo->master_inbox_email . " (Enabled)\n");
                    // fwrite($file, "Format: individual_email, master_email\n");
                }
                
                // fwrite($file, str_repeat("=", 50) . "\n\n");
                
                // Output emails based on master inbox configuration
                if ($masterInboxEnabled) {
                    // Master inbox format: individual_email, master_email (one per line)
                    foreach ($emailData as $email) {
                        $emailLine = $this->formatEmailPair($email['email'], $reorderInfo->master_inbox_email);
                        fwrite($file, $emailLine . "\n");
                    }
                } else {
                    // Group emails by domain for better organization (original format)
                    $emailsByDomain = [];
                    foreach ($emailData as $email) {
                        $emailsByDomain[$email['domain']][] = $email;
                    }
                    
                    foreach ($emailsByDomain as $domain => $emails) {
                        fwrite($file, "DOMAIN: " . $domain . "\n");
                        fwrite($file, str_repeat("-", 30) . "\n");
                        
                        foreach ($emails as $index => $email) {
                            fwrite($file, "Email #" . ($index + 1) . "\n");
                            fwrite($file, "  Email Address: " . $email['email'] . "\n");
                            fwrite($file, "  Password: " . $email['password'] . "\n");
                            fwrite($file, "  First Name: " . $email['first_name'] . "\n");
                            fwrite($file, "  Last Name: " . $email['last_name'] . "\n");
                            fwrite($file, "  Prefix: " . $email['prefix'] . "\n");
                            fwrite($file, "\n");
                        }
                        fwrite($file, str_repeat("-", 30) . "\n\n");
                    }
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting TXT with generated emails: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export TXT using existing order_emails data
     */
    private function exportTxtFromOrderEmails($splitId, $orderEmails)
    {
        try {
            $orderPanelSplit = OrderPanelSplit::with([
                'orderPanel.order.reorderInfo',
                'orderPanel.panel'
            ])->findOrFail($splitId);

            $order = $orderPanelSplit->orderPanel->order;
            $reorderInfo = $order->reorderInfo->first();

            $filename = "order_{$order->id}_split_{$splitId}_emails_from_database.txt";

            $headers = [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($orderEmails, $orderPanelSplit, $order, $reorderInfo) {
                $file = fopen('php://output', 'w');
                
                // Check if master inbox is enabled
                $masterInboxEnabled = $this->isMasterInboxEnabled($reorderInfo);
                
                // Add TXT headers
                // fwrite($file, "Order #{$order->id} - Split #{$orderPanelSplit->id} Email Data\n");
                // fwrite($file, "Generated on: " . date('Y-m-d H:i:s') . "\n");
                // fwrite($file, "Total Emails: " . $orderEmails->count() . "\n");
                
                if ($masterInboxEnabled) {
                    // fwrite($file, "Master Inbox: " . $reorderInfo->master_inbox_email . " (Enabled)\n");
                    // fwrite($file, "Format: individual_email, master_email\n");
                }
                
                // fwrite($file, str_repeat("=", 50) . "\n\n");
                
                // Output emails based on master inbox configuration
                if ($masterInboxEnabled) {
                    // Master inbox format: individual_email, master_email (one per line)
                    foreach ($orderEmails as $orderEmail) {
                        $emailLine = $this->formatEmailPair($orderEmail->email, $reorderInfo->master_inbox_email);
                        fwrite($file, $emailLine . "\n");
                    }
                } else {
                    // Add email data in text format with enhanced details (original format)
                    foreach ($orderEmails as $index => $orderEmail) {
                        fwrite($file, "Email #" . ($index + 1) . "\n");
                        fwrite($file, "First Name: " . ($orderEmail->name ?? 'N/A') . "\n");
                        fwrite($file, "Last Name: " . ($orderEmail->last_name ?? 'N/A') . "\n");
                        fwrite($file, "Email Address: " . $orderEmail->email . "\n");
                        fwrite($file, "Password: " . $orderEmail->password . "\n");
                        
                        // Extract domain and prefix from email if possible
                        if (strpos($orderEmail->email, '@') !== false) {
                            list($prefix, $domain) = explode('@', $orderEmail->email, 2);
                            fwrite($file, "Domain: " . $domain . "\n");
                            fwrite($file, "Prefix: " . $prefix . "\n");
                        }
                        
                        fwrite($file, str_repeat("-", 30) . "\n");
                    }
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting TXT from order emails: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export TXT file using domain-based generation method (fallback)
     */
    private function exportTxtSplitDomainsById($splitId)
    {
        try {
            $orderPanelSplit = OrderPanelSplit::with([
                'orderPanel.order.reorderInfo',
                'orderPanel.panel'
            ])->findOrFail($splitId);

            $order = $orderPanelSplit->orderPanel->order;
            $reorderInfo = $order->reorderInfo;

            if (!$reorderInfo) {
                throw new \Exception('Reorder information not found for this order.');
            }

            $filename = "order_{$order->id}_split_{$splitId}_domains.txt";

            $headers = [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($orderPanelSplit, $order, $reorderInfo) {
                $file = fopen('php://output', 'w');
                
                // Add TXT headers
                fwrite($file, "Order #{$order->id} - Split #{$orderPanelSplit->id} Domain Data\n");
                fwrite($file, "Generated on: " . date('Y-m-d H:i:s') . "\n");
                fwrite($file, "Panel: " . ($orderPanelSplit->orderPanel->panel->title ?? 'N/A') . "\n");
                fwrite($file, str_repeat("=", 50) . "\n\n");
                
                // Get domains for this split
                $domains = $this->extractDomains($orderPanelSplit);
                
                if (empty($domains)) {
                    fwrite($file, "No domains found for this split.\n");
                } else {
                    fwrite($file, "Total Domains: " . count($domains) . "\n\n");
                    
                    foreach ($domains as $index => $domain) {
                        fwrite($file, "Domain #" . ($index + 1) . ": " . $domain . "\n");
                    }
                }

                // Add prefix variants and email generation details
                $reorderInfoFirst = $reorderInfo->first();
                if ($reorderInfoFirst) {
                    $prefixVariants = $this->extractPrefixVariants($reorderInfoFirst);
                    $prefixVariantDetails = $this->extractPrefixVariantDetails($reorderInfoFirst);
                    
                    if (!empty($prefixVariants)) {
                        fwrite($file, "\n" . str_repeat("=", 50) . "\n");
                        fwrite($file, "EMAIL GENERATION DETAILS\n");
                        fwrite($file, str_repeat("=", 50) . "\n");
                        fwrite($file, "Prefix Variants: " . implode(', ', $prefixVariants) . "\n");
                        
                        // Show email examples for first domain if available
                        if (!empty($domains)) {
                            $exampleDomain = $domains[0];
                            fwrite($file, "\nExample emails for domain '{$exampleDomain}':\n");
                            foreach ($prefixVariants as $index => $prefix) {
                                $prefixKey = 'prefix_variant_' . ($index + 1);
                                $firstName = 'N/A';
                                $lastName = 'N/A';
                                
                                if (isset($prefixVariantDetails[$prefixKey])) {
                                    $firstName = $prefixVariantDetails[$prefixKey]['first_name'] ?? 'N/A';
                                    $lastName = $prefixVariantDetails[$prefixKey]['last_name'] ?? 'N/A';
                                }
                                
                                $email = $prefix . '@' . $exampleDomain;
                                $password = $this->customEncrypt($order->id);
                                fwrite($file, "  {$email} | {$firstName} {$lastName} | Password: {$password}\n");
                            }
                            
                            $totalEmails = count($domains) * count($prefixVariants);
                            fwrite($file, "\nTotal Generated Emails: {$totalEmails} (" . count($domains) . " domains × " . count($prefixVariants) . " prefixes)\n");
                        }
                    }
                }

                // Add additional information if available
                if ($reorderInfoFirst) {
                    fwrite($file, "\n" . str_repeat("=", 50) . "\n");
                    fwrite($file, "ORDER CONFIGURATION\n");
                    fwrite($file, str_repeat("=", 50) . "\n");
                    fwrite($file, "Hosting Platform: " . ($reorderInfoFirst->hosting_platform ?? 'N/A') . "\n");
                    fwrite($file, "Platform Login: " . ($reorderInfoFirst->platform_login ?? 'N/A') . "\n");
                    fwrite($file, "Forwarding URL: " . ($reorderInfoFirst->forwarding_url ?? 'N/A') . "\n");
                    fwrite($file, "Sending Platform: " . ($reorderInfoFirst->sending_platform ?? 'N/A') . "\n");
                    fwrite($file, "Sequencer Login: " . ($reorderInfoFirst->sequencer_login ?? 'N/A') . "\n");
                    fwrite($file, "Inboxes Per Domain: " . ($reorderInfoFirst->inboxes_per_domain ?? 'N/A') . "\n");
                    fwrite($file, "Total Inboxes: " . ($reorderInfoFirst->total_inboxes ?? 'N/A') . "\n");
                    
                    if ($reorderInfoFirst->master_inbox_email) {
                        fwrite($file, "Master Inbox Email: " . $reorderInfoFirst->master_inbox_email . "\n");
                    }
                    
                    if ($reorderInfoFirst->additional_info) {
                        fwrite($file, "\nAdditional Notes:\n" . $reorderInfoFirst->additional_info . "\n");
                    }
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting TXT from domains: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract prefix variants from reorder info
     */
    private function extractPrefixVariants($reorderInfo)
    {
        $prefixVariants = [];
        
        if ($reorderInfo && $reorderInfo->prefix_variants) {
            if (is_array($reorderInfo->prefix_variants)) {
                $prefixVariants = array_values(array_filter($reorderInfo->prefix_variants));
            } else if (is_string($reorderInfo->prefix_variants)) {
                $decodedPrefixes = json_decode($reorderInfo->prefix_variants, true);
                if (is_array($decodedPrefixes)) {
                    $prefixVariants = array_values(array_filter($decodedPrefixes));
                }
            }
        }
        
        // Default prefixes if none found
        if (empty($prefixVariants)) {
            $prefixVariants = ['pre01', 'pre02', 'pre03'];
        }
        
        return $prefixVariants;
    }

    /**
     * Extract prefix variant details from reorder info
     */
    private function extractPrefixVariantDetails($reorderInfo)
    {
        $prefixVariantDetails = [];
        
        if ($reorderInfo && $reorderInfo->prefix_variants_details) {
            $decodedDetails = is_string($reorderInfo->prefix_variants_details) 
                ? json_decode($reorderInfo->prefix_variants_details, true) 
                : $reorderInfo->prefix_variants_details;
                
            if (is_array($decodedDetails)) {
                $prefixVariantDetails = $decodedDetails;
            }
        }
        
        return $prefixVariantDetails;
    }

    /**
     * Custom encryption function for passwords (matches CSV export logic)
     */
    private function customEncrypt($orderId)
    {
        // Convert order ID to exactly 8 character password with one uppercase, lowercase, special char, and number
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
        
        // Shuffle using seeded random generator instead of str_shuffle
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
     * Extract and flatten domains from split data
     */
    private function extractDomains($orderPanelSplit)
    {
        $domains = [];
        
        if ($orderPanelSplit->domains) {
            if (is_array($orderPanelSplit->domains)) {
                $domains = $orderPanelSplit->domains;
            } else if (is_string($orderPanelSplit->domains)) {
                // Handle case where domains might be stored as comma-separated string
                $domains = array_map('trim', explode(',', $orderPanelSplit->domains));
                $domains = array_filter($domains); // Remove empty values
            }
            
            // Flatten array if it contains nested arrays or objects
            $flatDomains = [];
            foreach ($domains as $domain) {
                if (is_array($domain) || is_object($domain)) {
                    // Handle case where domain data is nested
                    if (is_object($domain) && isset($domain->domain)) {
                        $flatDomains[] = $domain->domain;
                    } else if (is_array($domain) && isset($domain['domain'])) {
                        $flatDomains[] = $domain['domain'];
                    } else if (is_string($domain)) {
                        $flatDomains[] = $domain;
                    }
                } else if (is_string($domain)) {
                    $flatDomains[] = $domain;
                }
            }
            $domains = $flatDomains;
        }
        
        return $domains;
    }

    /**
     * Generate email data with prefixes (matches CSV export logic)
     */
    private function generateEmailDataWithPrefixes($domains, $prefixVariants, $prefixVariantDetails, $orderId)
    {
        $emailData = [];
        
        foreach ($domains as $domain) {
            foreach ($prefixVariants as $index => $prefix) {
                // Get first and last name for this prefix variant
                $prefixKey = 'prefix_variant_' . ($index + 1);
                $firstName = 'N/A';
                $lastName = 'N/A';
                
                if (isset($prefixVariantDetails[$prefixKey])) {
                    $firstName = $prefixVariantDetails[$prefixKey]['first_name'] ?? 'N/A';
                    $lastName = $prefixVariantDetails[$prefixKey]['last_name'] ?? 'N/A';
                }
                
                $emailData[] = [
                    'domain' => $domain,
                    'email' => $prefix . '@' . $domain,
                    'password' => $this->customEncrypt($orderId),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'prefix' => $prefix
                ];
            }
        }
        
        return $emailData;
    }

    /**
     * Check if master inbox is enabled for the given reorder info
     */
    private function isMasterInboxEnabled($reorderInfo)
    {
        return $reorderInfo && 
               $reorderInfo->master_inbox_confirmation == 1 && 
               !empty($reorderInfo->master_inbox_email);
    }

    /**
     * Format email pair for master inbox output
     */
    private function formatEmailPair($individualEmail, $masterEmail)
    {
        return $individualEmail . ', ' . $masterEmail;
    }

    /**
     * Extract domain from email address
     */
    private function extractDomainFromEmail($email)
    {
        if (strpos($email, '@') !== false) {
            return explode('@', $email, 2)[1];
        }
        return null;
    }

    /**
     * Generate a comprehensive text report with all order details
     */
    public function generateComprehensiveTextReport($splitId)
    {
        try {
            $orderPanelSplit = OrderPanelSplit::with([
                'orderPanel.order.reorderInfo',
                'orderPanel.order.user',
                'orderPanel.panel'
            ])->findOrFail($splitId);

            $order = $orderPanelSplit->orderPanel->order;
            $reorderInfo = $order->reorderInfo->first();
            $user = $order->user;

            $filename = "comprehensive_order_{$order->id}_split_{$splitId}_report.txt";

            $headers = [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($orderPanelSplit, $order, $reorderInfo, $user) {
                $file = fopen('php://output', 'w');
                
                // Header
                fwrite($file, str_repeat("=", 80) . "\n");
                fwrite($file, "COMPREHENSIVE ORDER REPORT\n");
                fwrite($file, str_repeat("=", 80) . "\n\n");
                
                // Order Information
                fwrite($file, "ORDER INFORMATION\n");
                fwrite($file, str_repeat("-", 40) . "\n");
                fwrite($file, "Order ID: #{$order->id}\n");
                fwrite($file, "Split ID: #{$orderPanelSplit->id}\n");
                fwrite($file, "Customer: " . ($user->name ?? 'N/A') . "\n");
                fwrite($file, "Customer Email: " . ($user->email ?? 'N/A') . "\n");
                fwrite($file, "Order Status: " . ($order->status_manage_by_admin ?? 'N/A') . "\n");
                fwrite($file, "Created Date: " . ($order->created_at ? $order->created_at->format('Y-m-d H:i:s') : 'N/A') . "\n");
                fwrite($file, "Panel: " . ($orderPanelSplit->orderPanel->panel->title ?? 'N/A') . "\n\n");
                
                if ($reorderInfo) {
                    // Configuration Details
                    fwrite($file, "CONFIGURATION DETAILS\n");
                    fwrite($file, str_repeat("-", 40) . "\n");
                    fwrite($file, "Hosting Platform: " . ($reorderInfo->hosting_platform ?? 'N/A') . "\n");
                    fwrite($file, "Platform Login: " . ($reorderInfo->platform_login ?? 'N/A') . "\n");
                    fwrite($file, "Platform Password: " . ($reorderInfo->platform_password ?? 'N/A') . "\n");
                    fwrite($file, "Forwarding URL: " . ($reorderInfo->forwarding_url ?? 'N/A') . "\n");
                    fwrite($file, "Sending Platform: " . ($reorderInfo->sending_platform ?? 'N/A') . "\n");
                    fwrite($file, "Sequencer Login: " . ($reorderInfo->sequencer_login ?? 'N/A') . "\n");
                    fwrite($file, "Sequencer Password: " . ($reorderInfo->sequencer_password ?? 'N/A') . "\n");
                    fwrite($file, "Inboxes Per Domain: " . ($reorderInfo->inboxes_per_domain ?? 'N/A') . "\n");
                    fwrite($file, "Total Inboxes: " . ($reorderInfo->total_inboxes ?? 'N/A') . "\n");
                    
                    if ($reorderInfo->master_inbox_email) {
                        fwrite($file, "Master Inbox Email: " . $reorderInfo->master_inbox_email . "\n");
                        fwrite($file, "Master Inbox Confirmation: " . ($reorderInfo->master_inbox_confirmation ? 'Enabled' : 'Disabled') . "\n");
                    }
                    
                    if ($reorderInfo->backup_codes) {
                        fwrite($file, "Backup Codes: " . $reorderInfo->backup_codes . "\n");
                    }
                    fwrite($file, "\n");
                }
                
                // Domains
                $domains = $orderPanelSplit->domains ?? [];
                fwrite($file, "DOMAINS (" . count($domains) . " total)\n");
                fwrite($file, str_repeat("-", 40) . "\n");
                
                if (empty($domains)) {
                    fwrite($file, "No domains found for this split.\n");
                } else {
                    foreach ($domains as $index => $domain) {
                        fwrite($file, sprintf("%3d. %s\n", $index + 1, $domain));
                    }
                }
                fwrite($file, "\n");
                
                // Additional Information
                if ($reorderInfo && $reorderInfo->additional_info) {
                    fwrite($file, "ADDITIONAL NOTES\n");
                    fwrite($file, str_repeat("-", 40) . "\n");
                    fwrite($file, $reorderInfo->additional_info . "\n\n");
                }
                
                // Footer
                fwrite($file, str_repeat("=", 80) . "\n");
                fwrite($file, "Report generated on: " . date('Y-m-d H:i:s') . "\n");
                fwrite($file, "Generated by: TextExportService\n");
                fwrite($file, str_repeat("=", 80) . "\n");

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error generating comprehensive text report: ' . $e->getMessage());
            throw $e;
        }
    }
}