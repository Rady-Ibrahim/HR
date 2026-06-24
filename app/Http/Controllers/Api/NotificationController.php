<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController
{
    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', auth()->id());

        if ($request->filled('is_read')) $query->where('is_read', (bool) $request->is_read);

        $notifications = $query->orderByDesc('created_at')->paginate($request->get('per_page', 20));
        $unreadCount   = Notification::where('user_id', auth()->id())->where('is_read', false)->count();

        return response()->json([
            'success'       => true,
            'data'          => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    public function markRead($id): JsonResponse
    {
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        $notification->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true, 'message' => 'تم تحديد الإشعار كمقروء']);
    }

    public function markAllRead(): JsonResponse
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true, 'message' => 'تم تحديد جميع الإشعارات كمقروءة']);
    }

    public function destroy($id): JsonResponse
    {
        Notification::where('user_id', auth()->id())->findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف الإشعار']);
    }

    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', auth()->id())->where('is_read', false)->count();
        return response()->json(['success' => true, 'count' => $count]);
    }
}
