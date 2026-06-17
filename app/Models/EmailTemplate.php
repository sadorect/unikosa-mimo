<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['key', 'name', 'subject', 'body', 'variables'];

    protected function casts(): array
    {
        return ['variables' => 'array'];
    }

    /**
     * Render a template's subject and body, substituting {{ placeholders }}.
     * `site_name` is always available. Returns null if the template is missing,
     * so callers can fall back to a built-in default.
     *
     * @return array{subject: string, body: string}|null
     */
    public static function render(string $key, array $data = []): ?array
    {
        $template = static::where('key', $key)->first();

        if (! $template) {
            return null;
        }

        $data = array_merge(['site_name' => Setting::get('site_name', 'UNIKOSA')], $data);

        return [
            'subject' => static::substitute($template->subject, $data),
            'body' => static::substitute($template->body, $data),
        ];
    }

    protected static function substitute(string $text, array $data): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($matches) use ($data) {
            return array_key_exists($matches[1], $data) ? (string) $data[$matches[1]] : $matches[0];
        }, $text);
    }
}
