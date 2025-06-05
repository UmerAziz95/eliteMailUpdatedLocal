<?php
// Temporary debug script to check assignments
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\UserOrderPanelAssignment;
use App\Models\OrderPanel;

echo "=== DEBUG: Checking UserOrderPanelAssignment Records ===\n";

// Get all assignments
$assignments = UserOrderPanelAssignment::with(['orderPanel', 'contractor'])->get();
echo "Total assignments: " . $assignments->count() . "\n\n";

foreach ($assignments as $assignment) {
    echo "Assignment ID: " . $assignment->id . "\n";
    echo "Order Panel ID: " . $assignment->order_panel_id . "\n";
    echo "Contractor ID: " . $assignment->contractor_id . "\n";
    echo "Order ID: " . $assignment->order_id . "\n";
    echo "Contractor Name: " . ($assignment->contractor ? $assignment->contractor->name : 'N/A') . "\n";
    echo "Order Panel Status: " . ($assignment->orderPanel ? $assignment->orderPanel->status : 'N/A') . "\n";
    echo "---\n";
}

// Check order panels
echo "\n=== DEBUG: Checking OrderPanel Records ===\n";
$orderPanels = OrderPanel::with(['userOrderPanelAssignments'])->get();
echo "Total order panels: " . $orderPanels->count() . "\n\n";

foreach ($orderPanels->take(10) as $panel) {
    echo "Order Panel ID: " . $panel->id . "\n";
    echo "Order ID: " . $panel->order_id . "\n";
    echo "Status: " . $panel->status . "\n";
    echo "Assignments count: " . $panel->userOrderPanelAssignments->count() . "\n";
    if ($panel->userOrderPanelAssignments->count() > 0) {
        foreach ($panel->userOrderPanelAssignments as $assignment) {
            echo "  - Contractor ID: " . $assignment->contractor_id . "\n";
        }
    }
    echo "---\n";
}
