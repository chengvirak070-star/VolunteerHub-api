<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** GET /notifications — newest first, 15 per page, unread_count in meta. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $page = $user->notifications()->paginate(15);

        return response()->json([
            'data' => $page->getCollection()->map(fn ($n) => $this->format($n)),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    /** POST /notifications/read-all */
    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['data' => ['unread_count' => 0]]);
    }

    /** POST /notifications/{id}/read */
    public function read(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['data' => $this->format($notification)]);
    }

    private function format($n): array
    {
        return [
            'id' => $n->id,
            'type' => $n->data['type'] ?? $n->type,
            'message' => $n->data['message'] ?? '',
            'link' => $n->data['link'] ?? null,
            'actor' => $n->data['actor'] ?? null,
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at?->toIso8601String(),
        ];
    }
}
