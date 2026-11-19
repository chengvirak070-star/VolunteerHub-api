<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /** DELETE /events/{event} (admin) — registrations, hour logs and saves are removed with it. */
    public function destroy(Event $event): JsonResponse
    {
        if ($event->cover_path) {
            Storage::disk('public')->delete($event->cover_path);
        }
        $event->delete();

        return response()->json(['data' => ['message' => 'Event deleted.']]);
    }
}
