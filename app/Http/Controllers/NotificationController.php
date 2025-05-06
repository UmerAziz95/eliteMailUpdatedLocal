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

        $notification->update(['is_read' => true]);

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Notification marked as read']);
        }

        return redirect()->back()->with('success', 'Notification marked as read');
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->update(['is_read' => true]);

        if (request()->wantsJson()) {
            return response()->json(['message' => 'All notifications marked as read']);
        }

        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    public function getUnreadCount()
    {
        $count = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}