<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->input('filter', 'all');
        $page = max(1, (int) $request->input('page', 1));
        $limit = min(100, max(1, (int) $request->input('limit', 20)));

        $query = Notification::where('user_id', $user->id);

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $total = $query->count();
        $notifications = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'result' => 'success',
            'data' => $notifications,
            'pagination' => [
                'totalRecords' => $total,
                'totalPages' => ceil($total / $limit),
                'currentPage' => $page,
                'limit' => $limit,
            ],
        ]);
    }

    public function page(Request $request)
    {
        return view('notifications.index');
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markRead(Request $request, $id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $notification) {
            return response()->json(['result' => 'error', 'message' => 'Not found'], 404);
        }

        $notification->markAsRead();

        return response()->json(['result' => 'success']);
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['result' => 'success']);
    }
}
