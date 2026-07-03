<?php

namespace App\Http\Controllers;

use App\Mail\NewDirectMessage;
use App\Models\Message;
use App\Models\UnikosaNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $otherPartyIds = Message::where('sender_id', $userId)->pluck('recipient_id')
            ->merge(Message::where('recipient_id', $userId)->pluck('sender_id'))
            ->unique();

        $conversations = User::whereIn('id', $otherPartyIds)
            ->get(['id', 'name', 'avatar'])
            ->map(function (User $other) use ($userId) {
                $lastMessage = Message::betweenUsers($userId, $other->id)->latest()->first();

                return [
                    'user' => $other,
                    'last_message' => $lastMessage,
                    'unread_count' => Message::where('sender_id', $other->id)
                        ->where('recipient_id', $userId)
                        ->whereNull('read_at')
                        ->count(),
                ];
            })
            ->sortByDesc(fn ($conversation) => $conversation['last_message']?->created_at)
            ->values();

        return Inertia::render('Messages/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Request $request, User $user)
    {
        $me = $request->user();
        abort_if($user->id === $me->id, 404);

        $messages = Message::betweenUsers($me->id, $user->id)->oldest()->get();

        Message::where('sender_id', $user->id)
            ->where('recipient_id', $me->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Inertia::render('Messages/Show', [
            'otherUser' => $user->only(['id', 'name', 'avatar']),
            'messages' => $messages,
        ]);
    }

    public function store(Request $request, User $user)
    {
        $me = $request->user();
        abort_if($user->id === $me->id, 422);
        abort_unless($user->status === 'approved', 404);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $message = Message::create([
            'sender_id' => $me->id,
            'recipient_id' => $user->id,
            'body' => $validated['body'],
        ]);

        $this->notifyRecipient($message);

        return back();
    }

    private function notifyRecipient(Message $message): void
    {
        $recipient = $message->recipient;

        // Bell notification: always sent regardless of channel preferences.
        UnikosaNotification::create([
            'user_id' => $recipient->id,
            'type' => 'direct_message',
            'data' => [
                'type' => 'direct_message',
                'message' => $message->sender->name . ' sent you a message',
                'url' => route('messages.show', $message->sender_id),
            ],
        ]);

        $wantsEmail = $recipient->profilePrivacy?->notify_email_messages ?? true;

        if ($wantsEmail) {
            try {
                Mail::to($recipient->email)->send(new NewDirectMessage($message));
            } catch (\Throwable $e) {
                // Email delivery is best-effort; the bell notification above always succeeds.
                Log::warning('Failed to send direct message email notification', [
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
