<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $limit = (int)$request->query('limit', 50);
        $unreadOnly = $request->query('unread_only') === 'true';

        try {
            $query = Notification::where('user_id', $userId);
            if ($unreadOnly) {
                $query->where('is_read', 0);
            }

            $notifications = $query->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();

            $unreadCount = Notification::where('user_id', $userId)
                ->where('is_read', 0)
                ->count();

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
                'total_count' => $notifications->count()
            ]);
        } catch (Exception $e) {
            Log::error('Get Notifications Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function markRead(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $notificationId = $request->input('notification_id');

        try {
            if ($notificationId) {
                Notification::where('user_id', $userId)
                    ->where('notification_id', $notificationId)
                    ->update([
                        'is_read' => 1,
                        'read_at' => now()
                    ]);
            } else {
                Notification::where('user_id', $userId)
                    ->where('is_read', 0)
                    ->update([
                        'is_read' => 1,
                        'read_at' => now()
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notifications marked as read'
            ]);
        } catch (Exception $e) {
            Log::error('Mark Read Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function dismiss(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $notificationId = $request->input('notification_id');

        try {
            Notification::where('user_id', $userId)
                ->where('notification_id', $notificationId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification dismissed'
            ]);
        } catch (Exception $e) {
            Log::error('Dismiss Notification Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function clear(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            Notification::where('user_id', $userId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'All notifications cleared'
            ]);
        } catch (Exception $e) {
            Log::error('Clear Notifications Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
