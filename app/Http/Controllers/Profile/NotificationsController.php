<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\AppNotificationRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationsController extends Controller
{
    public function recent(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $recent = AppNotificationRecord::where('user_id', $userId)
            ->with('app:id,name')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'app' => ['name' => $n->app?->name],
                'subject' => $n->subject,
                'created_at' => $n->created_at,
                'read_at' => $n->read_at,
            ]);

        $unreadCount = AppNotificationRecord::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'recent' => $recent,
        ]);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Notifications/Index', [
            'notifications' => AppNotificationRecord::where('user_id', $request->user()->id)
                ->with('app:id,name')
                ->orderByDesc('created_at')
                ->paginate(50),
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $record = AppNotificationRecord::where('user_id', $request->user()->id)->find($id);
        abort_if(! $record, 404);

        $record->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        AppNotificationRecord::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function clearAll(Request $request): JsonResponse
    {
        AppNotificationRecord::where('user_id', $request->user()->id)->delete();

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $record = AppNotificationRecord::where('user_id', $request->user()->id)->find($id);
        abort_if(! $record, 404);

        $record->delete();

        return response()->json(['ok' => true]);
    }
}
