<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Event;
use App\Models\EventRsvp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $events = Event::with('chapter')
            ->where('start_at', '>=', now())
            ->latest('start_at')
            ->paginate(20);

        return response()->json($events);
    }

    public function show(Event $event): JsonResponse
    {
        $event->load(['chapter', 'creator:id,name', 'rsvps']);
        return response()->json($event);
    }

    public function rsvp(Request $request, Event $event): JsonResponse
    {
        $request->validate(['status' => 'required|in:going,maybe,not_going']);

        EventRsvp::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $request->user()->id],
            ['status' => $request->status]
        );

        return response()->json(['message' => 'RSVP updated.']);
    }
}
