<?php

namespace App\Http\Controllers;

use App\Models\Notification;
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

            $notification->update(['is_read' => true]);

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
}