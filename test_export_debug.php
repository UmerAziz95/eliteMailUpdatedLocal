<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Services\EmailExportService;

// Get order 1055
$order = Order::with('reorderInfo')->find(1055);

if (!$order) {
    echo "Order 1055 not found\n";
    exit(1);
}

echo "Order ID: {$order->id}\n";
echo "Provider Type: {$order->provider_type}\n\n";

// Get reorder info
$reorderInfo = $order->reorderInfo->first();

if ($reorderInfo) {
    echo "Raw prefix_variants type: " . gettype($reorderInfo->prefix_variants) . "\n";
    echo "Raw prefix_variants:\n";
    print_r($reorderInfo->prefix_variants);
    echo "\n";
    
    echo "Raw prefix_variants_details type: " . gettype($reorderInfo->prefix_variants_details) . "\n";
    echo "Raw prefix_variants_details:\n";
    print_r($reorderInfo->prefix_variants_details);
    echo "\n";
    
    // Decode prefix_variants
    $prefixVariants = [];
    if (is_string($reorderInfo->prefix_variants)) {
        $decoded = json_decode($reorderInfo->prefix_variants, true);
        if (is_array($decoded)) {
            $prefixVariants = array_values($decoded);
        }
    } elseif (is_array($reorderInfo->prefix_variants)) {
        $prefixVariants = array_values($reorderInfo->prefix_variants);
    }
    
    echo "Parsed prefix variants:\n";
    print_r($prefixVariants);
    echo "\n";
    
    // Decode prefix_variants_details
    $prefixVariantDetails = [];
    if (is_string($reorderInfo->prefix_variants_details)) {
        $details = json_decode($reorderInfo->prefix_variants_details, true);
        if (is_array($details)) {
            $prefixVariantDetails = $details;
        }
    } elseif (is_array($reorderInfo->prefix_variants_details)) {
        $prefixVariantDetails = $reorderInfo->prefix_variants_details;
    }
    
    echo "Parsed prefix variant details:\n";
    print_r($prefixVariantDetails);
    echo "\n";
    
    // Test matching logic
    echo "Testing prefix matching:\n";
    foreach ($prefixVariants as $index => $prefix) {
        echo "\n--- Prefix #{$index}: '{$prefix}' ---\n";
        
        // Try with "prefix_variant_X" key format (1-based)
        $variantKey = 'prefix_variant_' . ($index + 1);
        echo "Trying key: {$variantKey}\n";
        
        if (isset($prefixVariantDetails[$variantKey])) {
            $details = $prefixVariantDetails[$variantKey];
            $firstName = $details['first_name'] ?? '';
            $lastName = $details['last_name'] ?? '';
            echo "  Found! First: '{$firstName}', Last: '{$lastName}'\n";
            
            // Check if they're the same and try smart splitting
            if ($firstName === $lastName && !empty($prefix)) {
                echo "  Names are identical, trying smart split on prefix '{$prefix}'...\n";
                
                // Try different regex patterns
                if (preg_match('/^([A-Z][a-z]+)([A-Z][a-z]*)$/', $prefix, $matches)) {
                    echo "  Pattern 1 matched (PascalCase short): First='{$matches[1]}', Last='" . ($matches[2] ?: $matches[1]) . "'\n";
                } elseif (preg_match('/^([a-z]+)([A-Z].*)$/', $prefix, $matches)) {
                    echo "  Pattern 2 matched (camelCase): First='" . ucfirst($matches[1]) . "', Last='{$matches[2]}'\n";
                } elseif (preg_match('/^([A-Z][a-z]+)([A-Z][a-z]+.*)$/', $prefix, $matches)) {
                    echo "  Pattern 3 matched (PascalCase long): First='{$matches[1]}', Last='{$matches[2]}'\n";
                } else {
                    echo "  No pattern matched\n";
                }
            }
        } else {
            echo "  Not found with key {$variantKey}\n";
        }
    }
}

echo "\n=== DONE ===\n";
