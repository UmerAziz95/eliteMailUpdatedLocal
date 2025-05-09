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
        $notification = Notification::where('user_id', auth()->id())
            ->findOrFail($id);

        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->update(['is_read' => true]);

        if (request()->wantsJson()) {
            return response()->json(['message' => 'success']);
        }

        return redirect()->back()->with('success', 'Notification marked as read');
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if (request()->wantsJson()) {
            return response()->json(['message' => 'success']);
        }

        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    public function getUnreadCount()
    {
        $count = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function getNotificationsList()
    {
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