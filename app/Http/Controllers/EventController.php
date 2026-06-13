<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRsvp;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EventController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    public function index(Request $request)
    {
        $query = Event::with(['chapter', 'creator'])
            ->latest('start_at');

        if ($request->input('type') === 'past') {
            $query->where('start_at', '<', now());
        } else {
            $query->where('start_at', '>=', now())->orWhereNull('start_at');
        }

        if ($chapterId = $request->input('chapter_id')) {
            $query->where('chapter_id', $chapterId);
        }

        return Inertia::render('Events/Index', [
            'events' => $query->paginate(12),
            'filters' => $request->only(['type', 'chapter_id']),
        ]);
    }

    public function show(Event $event)
    {
        $event->load(['chapter', 'creator', 'rsvps']);
        $event->increment('views_count');

        $userRsvp = null;
        if (auth()->check()) {
            $userRsvp = EventRsvp::where('event_id', $event->id)
                ->where('user_id', auth()->id())
                ->first();
        }

        return Inertia::render('Events/Show', [
            'event' => $event,
            'userRsvp' => $userRsvp,
        ]);
    }

    public function create()
    {
        return Inertia::render('Events/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'nullable|string',
            'is_virtual' => 'boolean',
            'livestream_url' => 'nullable|url',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after:start_at',
            'is_paid' => 'boolean',
            'ticket_price' => 'nullable|integer',
            'ticket_currency' => 'nullable|string',
            'chapter_id' => 'nullable|exists:chapters,id',
            'capacity' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['created_by'] = $request->user()->id;

        Event::create($validated);

        return redirect()->route('events.index')->with('success', 'Event created successfully.');
    }

    public function rsvp(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|in:going,maybe,not_going',
        ]);

        $existing = EventRsvp::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            $existing->update(['status' => $request->status]);
        } else {
            EventRsvp::create([
                'event_id' => $event->id,
                'user_id' => $request->user()->id,
                'status' => $request->status,
            ]);
        }

        return back()->with('success', 'RSVP updated.');
    }

    public function purchaseTicket(Request $request, Event $event)
    {
        $request->validate([
            'payment_method' => 'required|in:paystack,stripe',
        ]);

        if (!$event->is_paid || !$event->ticket_price) {
            return back()->with('error', 'This event does not require a paid ticket.');
        }

        $result = $this->paymentService->initiatePayment(
            $event,
            $request->payment_method,
            $event->ticket_price,
            $event->ticket_currency ?? 'NGN',
            $request->user()->email,
        );

        if (isset($result['authorization_url'])) {
            return redirect($result['authorization_url']);
        }
        if (isset($result['url'])) {
            return redirect($result['url']);
        }

        return back()->with('error', 'Payment initialization failed.');
    }
}
