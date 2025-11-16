<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Services\EmailExportService;

// Get order 1055
$order = Order::with('reorderInfo')->find(1055);

if (!$order) {
    echo "Order 1055 not found\n";
    exit(1);
}

// Get order panel split
$orderPanelSplit = OrderPanelSplit::where('order_id', 1055)->first();

if (!$orderPanelSplit) {
    echo "No order panel split found for order 1055\n";
    exit(1);
}

echo "Order ID: {$order->id}\n";
echo "Provider Type: {$order->provider_type}\n";
echo "Order Panel Split ID: {$orderPanelSplit->id}\n\n";

// Create service and generate domain-based emails
$service = new EmailExportService();

// Use reflection to call private method
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('generateDomainBasedEmails');
$method->setAccessible(true);

$emailData = $method->invoke($service, $orderPanelSplit, $order);

echo "Generated " . count($emailData) . " emails\n\n";
echo "First 10 emails:\n";
echo str_repeat('=', 100) . "\n";
printf("%-15s | %-15s | %-40s | %-10s\n", "First Name", "Last Name", "Email", "Password");
echo str_repeat('=', 100) . "\n";

foreach (array_slice($emailData, 0, 10) as $email) {
    printf("%-15s | %-15s | %-40s | %-10s\n", 
        $email['first_name'], 
        $email['last_name'], 
        $email['email'], 
        $email['password']
    );
}

echo "\n=== DONE ===\n";
