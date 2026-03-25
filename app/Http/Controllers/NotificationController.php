<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        // #region agent log
        @file_put_contents('/tmp/debug-33f870.log', json_encode(['sessionId'=>'33f870','location'=>'NotificationController:index','message'=>'notifications endpoint hit','data'=>['user_id'=>Auth::id()],'timestamp'=>round(microtime(true)*1000),'runId'=>'run1','hypothesisId'=>'C'])."\n", FILE_APPEND);
        // #endregion
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'system',
                'message' => $n->data['message'] ?? '',
                'url' => $n->data['url'] ?? '#',
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(): JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}
