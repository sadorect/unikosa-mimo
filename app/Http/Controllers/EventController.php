<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\Set;
use App\Services\PaymentService;
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
            session()->put('viewed_events', [...$seen, $event->id]);
        }

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
            'ticket_price' => 'nullable|integer',
            'ticket_currency' => 'nullable|string',
            'chapter_id' => 'nullable|exists:chapters,id',
            'set_id' => 'nullable|exists:sets,id',
            'capacity' => 'nullable|integer',
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
        $request->validate([
            'status' => 'required|in:going,maybe,not_going',
        ]);

        $existing = EventRsvp::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->first();

        $wasGoing = $existing && $existing->status === 'going';

        if ($existing) {
            $existing->update(['status' => $request->status]);
        } else {
            EventRsvp::create([
                'event_id' => $event->id,
                'user_id' => $request->user()->id,
                'status' => $request->status,
            ]);
        }

        // Confirm attendance the first time the member marks themselves "going".
        if ($request->status === 'going' && ! $wasGoing) {
            $request->user()->notify(new \App\Notifications\EventRsvpConfirmation($event));
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
