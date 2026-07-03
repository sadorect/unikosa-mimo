<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New contact form message: ' . ($this->contactMessage->subject ?: 'No subject'),
            replyTo: [$this->contactMessage->email => $this->contactMessage->name],
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
        $name = e($this->contactMessage->name);
        $email = e($this->contactMessage->email);
        $subject = e($this->contactMessage->subject ?: 'No subject');
        $body = nl2br(e($this->contactMessage->message));

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="utf-8"></head>
        <body style="font-family: sans-serif; padding: 20px; color: #1f2937;">
            <h2>New contact form message</h2>
            <p><strong>From:</strong> {$name} ({$email})</p>
            <p><strong>Subject:</strong> {$subject}</p>
            <blockquote style="border-left: 4px solid #f59e0b; margin: 20px 0; padding: 8px 16px; color: #4b5563; background: #f9fafb;">
                {$body}
            </blockquote>
            <p style="color:#9ca3af;font-size:12px;margin-top:24px;">Reply directly to this email to respond to {$name}.</p>
        </body>
        </html>
        HTML;
    }
}
