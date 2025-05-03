<?php 
namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{

    /**
 * Create a log entry
 *
 * @param string $actionType      // What kind of action is being performed (e.g., 'User Created', 'Payment Completed')
 * @param Model|null $performedOn // The Eloquent model the action was performed on (optional, can be null)
 * @param string $description     // A human-readable description of the action
 * @param array $data             // Additional data you want to store (stored as JSON)
 * @return Log
 */
    public static function log($actionType, $description = null, $performedOn = null, $data = [], $performed_by = null)
    {
        $performed_by = $performed_by ?? (Auth::check() ? Auth::id() : null);
        return Log::create([
            'action_type'      => $actionType,                    // Example: 'User Created'
            'description'      => $description,                   // Example: 'New user signed up with email xyz@example.com'
            'performed_on_id'  => $performedOn?->id,              // ID of the model the action was performed on
            'data'             => $data,                          // Example: ['referral_code' => 'abc123']
            'performed_by'     => $performed_by,                     // Logged-in user ID (nullable if not logged in)
            'performed_on_type'=> $performedOn ? get_class($performedOn) : null, // Model class name (e.g., App\Models\User)
        ]);
    }
}
