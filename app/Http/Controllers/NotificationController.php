<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Log;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        try {
            $notification = Notification::findOrFail($id);
            
            // Uncommenting and enhancing the authorization check
            // if ($notification->user_id !== auth()->id()) {
            //     return response()->json(['error' => 'You are not authorized to mark this notification as read'], 403);
            // }

            
$notification->update(['is_read' => !$notification->is_read]);
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Notification marked as read successfully'
                ]);
            }

            return redirect()->back()->with('success', 'Notification marked as read');
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error marking notification as read: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error marking notification as read: ' . $e->getMessage());
        }
    }

    public function markAllAsRead()
    {
        try {
            Notification::where('user_id', auth()->id())
                ->where('is_read', false)
                ->update(['is_read' => true]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'All notifications marked as read successfully'
                ]);
            }

            return redirect()->back()->with('success', 'All notifications marked as read');
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error marking all notifications as read: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error marking all notifications as read: ' . $e->getMessage());
        }
    }

    public function getUnreadCount()
    {
       
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $count = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function getNotificationsList()
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $notifications = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'user_profile_photo' => auth()->user()->profile_image 
                        ? asset('storage/profile_images/' . auth()->user()->profile_image)
                        : null
                ];
            });

        return response()->json(['notifications' => $notifications]);
    }

    public function getNotificationsListAll()
    {
        $notifications = Notification::
            orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'user_profile_photo' => auth()->user()->profile_image 
                        ? asset('storage/profile_images/' . auth()->user()->profile_image)
                        : null
                ];
            });

        return response()->json(['notifications' => $notifications]);
    }


    public function markAllAsReadNoti(Request $request){
        $userId = auth()->id();
        $notifications = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

     
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read successfully'
            ]);
        

       
    }
    public function markAllAsUnReadNoti(Request $request){
        $userId = auth()->id();
        $notifications = Notification::where('user_id', $userId)
            ->where('is_read', true)
            ->update(['is_read' => false]);

      
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as unread successfully'
            ]);
        

       
    }

    /**
     * Get notifications for a specific order
     */
    // 
    public function getOrderNotifications($orderId)
    {
        try {
            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Get logs related to the specific order
            $notifications = Log::where(function($query) use ($orderId) {
                    $query->where('data->order_id', $orderId)
                          ->orWhere('data->order_panel_id', $orderId);
                })
                ->orWhere(function($query) use ($orderId) {
                    $query->where('performed_on_type', 'App\\Models\\Order')
                          ->where('performed_on_id', $orderId);
                })
                ->with('user') // Load user relationship
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($log) {
                    $data = $log->data ?? [];
                    
                    // Determine the actor/role based on performed_by instead of user_id
                    $actor = 'system';
                    $icon = 'fa-solid fa-bell';
                    
                    // Get user info from performed_by relationship
                    if ($log->performed_by && $log->user) {
                        $user = $log->user;
                        
                        if ($user->role_id == 1 || $user->role_id == 2) {
                            $actor = 'admin';
                            $icon = 'fa-solid fa-user-shield';
                        } elseif ($user->role_id == 4) {
                            $actor = 'contractor'; 
                            $icon = 'fa-solid fa-hard-hat';
                        } else {
                            $actor = 'customer';
                            $icon = 'fa-solid fa-user';
                        }
                    } else {
                        // Fallback to action_type if no user relationship
                        if (strpos($log->action_type, 'order-status') !== false) {
                            $actor = 'admin';
                            $icon = 'fa-solid fa-user-shield';
                        } elseif (strpos($log->action_type, 'order-created') !== false) {
                            $actor = 'customer';
                            $icon = 'fa-solid fa-user';
                        } elseif (strpos($log->action_type, 'order-assigned') !== false) {
                            $actor = 'admin';
                            $icon = 'fa-solid fa-user-shield';
                        }
                    }

                    // Create title and message from log data
                    $title = ucfirst(str_replace('-', ' ', $log->action_type));
                    $message = $log->description ?? $title;

                    return [
                        'id' => $log->id,
                        'title' => $title,
                        'message' => preg_replace('/:\s*\d+$/', '', $message),
                        'actor' => $actor,
                        'icon' => $icon,
                        'is_read' => true, // Logs don't have read status, so default to true
                        'created_at' => $log->created_at->format('M j, Y — h:i A'),
                        'created_at_human' => $log->created_at->diffForHumans(),
                        'user_id' => $log->performed_by,
                        'user_name' => $log->user ? $log->user->name : $actor,
                        'user' => $log->user ? [
                            'id' => $log->user->id,
                            'name' => $log->user->name,
                            'role_id' => $log->user->role_id
                        ] : null,
                        'action_type' => $log->action_type,
                        'data' => $data
                    ];
                });

            return response()->json(['notifications' => $notifications]);

        } catch (\Exception $e) {
            \Log::error('Error fetching order notifications from logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching order notifications: ' . $e->getMessage()
            ], 500);
        }
    }
    public function oldgetOrderNotifications($orderId)
    {
        try {
            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Get notifications related to the specific order
            $notifications = Notification::where(function($query) use ($orderId) {
                    $query->where('data->order_id', $orderId)
                          ->orWhere('data->order_panel_id', $orderId);
                })
                ->with('user') // Load user relationship
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($notification) {
                    $data = $notification->data ?? [];
                    
                    // Determine the actor/role based on user_id instead of data
                    $actor = 'system';
                    $icon = 'fa-solid fa-bell';
                    
                    // Get user info from user_id relationship
                    if ($notification->user_id && $notification->user) {
                        $user = $notification->user;
                        
                        if ($user->role_id == 1 || $user->role_id == 2) {
                            $actor = 'admin';
                            $icon = 'fa-solid fa-user-shield';
                        } elseif ($user->role_id == 4) {
                            $actor = 'contractor'; 
                            $icon = 'fa-solid fa-hard-hat';
                        } else {
                            $actor = 'customer';
                            $icon = 'fa-solid fa-user';
                        }
                    } else {
                        // Fallback to notification type if no user relationship
                        if (strpos($notification->type, 'order_status') !== false) {
                            $actor = 'admin';
                            $icon = 'fa-solid fa-user-shield';
                        } elseif (strpos($notification->type, 'order_created') !== false) {
                            $actor = 'customer';
                            $icon = 'fa-solid fa-user';
                        } elseif (strpos($notification->type, 'order_panel') !== false) {
                            $actor = 'contractor';
                            $icon = 'fa-solid fa-hard-hat';
                        }
                    }

                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'actor' => $actor,
                        'icon' => $icon,
                        'is_read' => $notification->is_read,
                        'created_at' => $notification->created_at->format('M j, Y — h:i A'),
                        'created_at_human' => $notification->created_at->diffForHumans(),
                        'user_id' => $notification->user_id,
                        'user' => $notification->user ? [
                            'id' => $notification->user->id,
                            'name' => $notification->user->name,
                            'role_id' => $notification->user->role_id
                        ] : null
                    ];
                });

            return response()->json(['notifications' => $notifications]);

        } catch (\Exception $e) {
            \Log::error('Error fetching order notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching order notifications: ' . $e->getMessage()
            ], 500);
        }
    }
}