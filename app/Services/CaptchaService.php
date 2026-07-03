<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchaService
{
    protected array $defaults = [
        'captcha_enabled' => 'true',
        'captcha_length' => '5',
        'captcha_case_sensitive' => 'false',
        'captcha_characters' => 'ABCDEFGHJKMNPQRSTUVWXYZ23456789',
        'captcha_noise_lines' => '5',
        'captcha_width' => '160',
        'captcha_height' => '50',
        'captcha_expiry_seconds' => '300',
        'captcha_forms' => 'login,register,forgot_password,reset_password,contact',
    ];

    public function isEnabled(): bool
    {
        return $this->setting('captcha_enabled') !== 'false';
    }

    public function appliesTo(string $form): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $forms = array_filter(array_map('trim', explode(',', $this->setting('captcha_forms'))));

        return in_array($form, $forms, true);
    }

    /**
     * Generate a new captcha challenge and return its id + inline PNG image.
     */
    public function generate(): array
    {
        $length = max(3, min(10, (int) $this->setting('captcha_length')));
        $characters = $this->setting('captcha_characters') ?: $this->defaults['captcha_characters'];

        $text = '';
        for ($i = 0; $i < $length; $i++) {
            $text .= $characters[random_int(0, strlen($characters) - 1)];
        }

        $id = (string) Str::uuid();
        $ttl = max(30, (int) $this->setting('captcha_expiry_seconds'));

        Cache::put("captcha:{$id}", $this->normalize($text), $ttl);

        return ['id' => $id, 'image' => $this->renderImage($text)];
    }

    /**
     * Verify a submitted answer against its challenge. Single-use: the
     * challenge is consumed whether or not the answer was correct.
     */
    public function verify(?string $id, ?string $answer): bool
    {
        if (! $id || $answer === null || $answer === '') {
            return false;
        }

        $expected = Cache::pull("captcha:{$id}");

        if ($expected === null) {
            return false;
        }

        return hash_equals($expected, $this->normalize($answer));
    }

    protected function normalize(string $value): string
    {
        $value = trim($value);

        return $this->setting('captcha_case_sensitive') === 'true' ? $value : strtoupper($value);
    }

    protected function setting(string $key): string
    {
        return (string) Setting::get($key, $this->defaults[$key] ?? '');
    }

    protected function renderImage(string $text): string
    {
        $width = max(80, min(400, (int) $this->setting('captcha_width')));
        $height = max(30, min(150, (int) $this->setting('captcha_height')));
        $noiseLines = max(0, min(15, (int) $this->setting('captcha_noise_lines')));

        $image = imagecreatetruecolor($width, $height);

        $background = imagecolorallocate($image, 245, 247, 250);
        imagefill($image, 0, 0, $background);

        for ($i = 0; $i < $noiseLines; $i++) {
            $lineColor = imagecolorallocate($image, random_int(150, 205), random_int(150, 205), random_int(150, 205));
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $lineColor);
        }

        for ($i = 0; $i < (int) ($width * $height / 25); $i++) {
            $dotColor = imagecolorallocate($image, random_int(180, 220), random_int(180, 220), random_int(180, 220));
            imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $dotColor);
        }

        $length = max(strlen($text), 1);
        $charWidth = $width / $length;

        for ($i = 0; $i < strlen($text); $i++) {
            $textColor = imagecolorallocate($image, random_int(20, 90), random_int(20, 90), random_int(20, 90));
            $font = 5;
            $x = (int) ($i * $charWidth + $charWidth / 2 - imagefontwidth($font) / 2);
            $y = (int) (($height - imagefontheight($font)) / 2) + random_int(-6, 6);
            imagestring($image, $font, max(2, $x), max(2, min($y, $height - imagefontheight($font))), $text[$i], $textColor);
        }

        ob_start();
        imagepng($image);
        $data = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($data);
    }
}
