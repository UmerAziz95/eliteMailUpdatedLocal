<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Log;

class AppLogController extends Controller
{
    //
    
public function getLogs(Request $request){

    $logs = Log::with(['user', 'performedOn'])->latest()->get();

    foreach ($logs as $log) {
        echo "Action: {$log->action_type}\n";
        echo "Description: {$log->description}\n";
        echo "Performed By: " . ($log->user?->name ?? 'System') . "\n";
    
        if ($log->performedOn) {
            echo "On: " . class_basename($log->performed_on_type) . " (ID #{$log->performed_on_id})\n";
        } else {
            echo "On: N/A\n";
        }
    
        echo "Extra Data: " . json_encode($log->data) . "\n";
    }

exit();


// log creation 
$user=Auth::user();
// Create a new activity log using the custom log service
ActivityLogService::log(
    'user_signup', // 🟢 Action Type: This is a short, unique identifier for the action (e.g. 'user_signup', 'order_created', 'payment_done', etc.)
    
    'New user registered', // 🟢 Description: A human-readable message that describes what happened. This will be shown in logs.
    
    $user, // 🟢 Performed By: The user (model instance) who performed this action. Can be null if action was performed by a system task.
    
    [   // 🟢 Extra Data: (Optional) An array of additional data related to the action. This is stored as JSON.
        'email' => $user->email,
        // You can add any other contextual info here: IP, user role, referral code, etc.
        // You can add any other contextual info here: IP, user role, referral code, etc.
    ]
);
    }
}
