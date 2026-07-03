<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDirectMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Message $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->message->sender->name . ' sent you a message on ' . config('app.name', 'Unikosa'),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    protected function buildHtml(): string
    {
        $sender = e($this->message->sender->name);
        $preview = e(\Illuminate\Support\Str::limit($this->message->body, 300));
        $url = route('messages.show', $this->message->sender_id);

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="utf-8"></head>
        <body style="font-family: sans-serif; padding: 20px; color: #1f2937;">
            <h2>New message from {$sender}</h2>
            <blockquote style="border-left: 4px solid #f59e0b; margin: 20px 0; padding: 8px 16px; color: #4b5563; background: #f9fafb;">
                {$preview}
            </blockquote>
            <p><a href="{$url}" style="display:inline-block;background:#f59e0b;color:#fff;text-decoration:none;padding:10px 20px;border-radius:8px;">Reply</a></p>
            <p style="color:#9ca3af;font-size:12px;margin-top:24px;">You're receiving this because you have email notifications enabled for direct messages. You can turn this off in your profile settings.</p>
        </body>
        </html>
        HTML;
    }
}
