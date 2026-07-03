<?php

namespace App\Mail;

use App\Models\Election;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ElectionResultsPublished extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $summaryLines  one "Position: Winner (or 'No winner — threshold not met')" line per position
     */
    public function __construct(
        public Election $election,
        public array $summaryLines,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Results are in: {$this->election->title}",
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
        $title = e($this->election->title);
        $url = route('elections.results', $this->election);
        $rows = collect($this->summaryLines)
            ->map(fn (string $line) => '<li style="margin-bottom:6px;">' . e($line) . '</li>')
            ->implode('');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="utf-8"></head>
        <body style="font-family: sans-serif; padding: 20px; color: #1f2937;">
            <h2>Results are in for "{$title}"</h2>
            <ul style="padding-left: 20px; color: #374151;">
                {$rows}
            </ul>
            <p><a href="{$url}" style="display:inline-block;background:#f59e0b;color:#fff;text-decoration:none;padding:10px 20px;border-radius:8px;">View full results</a></p>
            <p style="color:#9ca3af;font-size:12px;margin-top:24px;">You're receiving this because you were an eligible voter in this election.</p>
        </body>
        </html>
        HTML;
    }
}
