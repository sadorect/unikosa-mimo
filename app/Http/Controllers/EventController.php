<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\Set;
use App\Models\UnikosaNotification;
use App\Models\User;
use App\Notifications\EventRsvpConfirmation;
use App\Services\PaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            ->where('status', 'approved')
            ->latest('start_at');

        if ($request->input('type') === 'past') {
            $query->where('start_at', '<', now());
        } else {
            $query->where(fn ($q) => $q->where('start_at', '>=', now())->orWhereNull('start_at'));
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
        abort_unless(
            $event->status === 'approved'
                || $event->created_by === auth()->id()
                || auth()->user()?->can('moderate events'),
            404
        );

        $event->load(['chapter', 'creator']);
        $event->loadCount(['rsvps as rsvps_going_count' => fn ($query) => $query->where('event_rsvps.status', 'going')]);

        $seen = session()->get('viewed_events', []);
        if (! in_array($event->id, $seen, true)) {
            $event->increment('views_count');
            $seen[] = $event->id;
            // Cap the session-stored list so it can't grow without bound over a long session.
            session()->put('viewed_events', array_slice($seen, -200));
        }

        $userRsvp = EventRsvp::where('event_id', $event->id)
            ->where('user_id', auth()->id())
            ->first();

        // The event creator and moderators may see the attendee roster (name + whether they
        // hold a paid ticket) — needed for check-in on paid events. Everyone else only ever
        // sees the aggregate going-count, preserving attendee privacy.
        $canManage = $event->created_by === auth()->id() || auth()->user()?->can('moderate events');
        $attendees = $canManage
            ? EventRsvp::where('event_id', $event->id)
                ->where('status', 'going')
                ->with('user:id,name')
                ->get()
                ->map(fn (EventRsvp $r) => ['name' => $r->user?->name, 'paid' => $r->paid])
                ->values()
            : null;

        return Inertia::render('Events/Show', [
            'event' => $event,
            'userRsvp' => $userRsvp,
            'hasPaidTicket' => (bool) ($userRsvp?->paid),
            'canManage' => $canManage,
            'attendees' => $attendees,
        ]);
    }

    public function create()
    {
        return Inertia::render('Events/Create', [
            'chapters' => Chapter::orderBy('name')->get(['id', 'name']),
            'sets' => Set::orderBy('year', 'desc')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateEvent($request);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'pending';

        Event::create($validated);

        return redirect()->route('events.mine')->with('success', 'Event submitted for review.');
    }

    public function mine(Request $request)
    {
        return Inertia::render('Events/Mine', [
            'events' => Event::where('created_by', $request->user()->id)
                ->latest()
                ->paginate(12),
        ]);
    }

    public function edit(Event $event)
    {
        abort_unless($event->created_by === auth()->id(), 403);
        abort_unless(in_array($event->status, ['pending', 'changes_requested'], true), 403);

        return Inertia::render('Events/Edit', [
            'event' => $event,
            'chapters' => Chapter::orderBy('name')->get(['id', 'name']),
            'sets' => Set::orderBy('year', 'desc')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Event $event)
    {
        abort_unless($event->created_by === $request->user()->id, 403);
        abort_unless(in_array($event->status, ['pending', 'changes_requested'], true), 403);

        $validated = $this->validateEvent($request);
        $validated['slug'] = Str::slug($validated['title']);
        $validated['status'] = 'pending';
        // Clear the prior moderator feedback so a resubmitted event doesn't keep showing
        // stale "changes requested" text while it's back in the pending queue.
        $validated['feedback'] = null;

        $event->update($validated);

        return redirect()->route('events.mine')->with('success', 'Event resubmitted for review.');
    }

    protected function validateEvent(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'cover_image' => 'nullable|image|max:5120',
            'location' => 'nullable|string',
            'is_virtual' => 'boolean',
            'livestream_url' => 'nullable|url',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after:start_at',
            'is_paid' => 'boolean',
            'ticket_price' => 'nullable|integer|min:1|required_if:is_paid,1,true',
            'ticket_currency' => 'nullable|string',
            'chapter_id' => 'nullable|exists:chapters,id',
            'set_id' => 'nullable|exists:sets,id',
            'capacity' => 'nullable|integer|min:1',
        ]);

        if ($request->hasFile('cover_image')) {
            $disk = config('filesystems.media_disk');
            $path = $request->file('cover_image')->store('events', $disk);
            $validated['cover_image'] = Storage::disk($disk)->url($path);
        } else {
            unset($validated['cover_image']);
        }

        return $validated;
    }

    public function rsvp(Request $request, Event $event)
    {
        abort_unless($event->status === 'approved', 404);
        if ($event->start_at && $event->start_at->isPast()) {
            return back()->with('error', 'This event has already started; RSVPs are closed.');
        }

        $request->validate([
            'status' => 'required|in:going,maybe,not_going',
        ]);

        $existing = EventRsvp::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->first();

        $wasGoing = $existing && $existing->status === 'going';

        // Only block a *new* "going" when the event is full; someone already going can
        // freely switch their status, and switching away never needs a capacity check.
        if ($request->status === 'going' && ! $wasGoing && $this->eventIsFull($event)) {
            return back()->with('error', 'This event has reached capacity.');
        }

        try {
            // updateOrCreate only writes `status`, leaving any paid/payment_reference intact.
            EventRsvp::updateOrCreate(
                ['event_id' => $event->id, 'user_id' => $request->user()->id],
                ['status' => $request->status],
            );
        } catch (QueryException $e) {
            // Concurrent first-time RSVP race on the unique(event_id,user_id) constraint —
            // the row now exists, so apply as a plain update instead of failing.
            EventRsvp::where('event_id', $event->id)
                ->where('user_id', $request->user()->id)
                ->update(['status' => $request->status]);
        }

        // Confirm attendance the first time the member marks themselves "going".
        if ($request->status === 'going' && ! $wasGoing) {
            $this->sendRsvpConfirmation($request->user(), $event);
        }

        return back()->with('success', 'RSVP updated.');
    }

    public function purchaseTicket(Request $request, Event $event)
    {
        abort_unless($event->status === 'approved', 404);
        if ($event->start_at && $event->start_at->isPast()) {
            return back()->with('error', 'This event has already started; tickets are no longer on sale.');
        }

        $request->validate([
            'payment_method' => 'required|in:paystack,stripe',
        ]);

        if (! $event->is_paid || ! $event->ticket_price || $event->ticket_price < 1) {
            return back()->with('error', 'This event does not require a paid ticket.');
        }

        $existing = EventRsvp::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing?->paid) {
            return back()->with('error', 'You already have a ticket for this event.');
        }

        if ($this->eventIsFull($event)) {
            return back()->with('error', 'This event has reached capacity.');
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

    /**
     * True when the event has a capacity and its confirmed ("going") attendee count has
     * reached it. Paid tickets set status="going" on payment, so this covers both free
     * RSVPs and paid attendance.
     */
    protected function eventIsFull(Event $event): bool
    {
        if (! $event->capacity) {
            return false;
        }

        $going = EventRsvp::where('event_id', $event->id)->where('status', 'going')->count();

        return $going >= $event->capacity;
    }

    protected function sendRsvpConfirmation(User $user, Event $event): void
    {
        // Email via the notification's mail channel; the in-app bell entry is created
        // directly so it carries the app's standard {type, message, url} shape (and so its
        // link actually works, unlike the native database channel's class-name payload).
        $user->notify(new EventRsvpConfirmation($event));

        UnikosaNotification::create([
            'user_id' => $user->id,
            'type' => 'event_rsvp_confirmation',
            'data' => [
                'type' => 'event_rsvp_confirmation',
                'message' => "Your RSVP for \"{$event->title}\" is confirmed.",
                'url' => route('events.show', $event),
            ],
        ]);
    }
}
