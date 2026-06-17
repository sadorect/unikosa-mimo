<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_keeps_allowed_formatting(): void
    {
        $html = '<p>Hello <strong>world</strong> and <em>friends</em></p><ul><li>one</li></ul>';
        $this->assertSame($html, HtmlSanitizer::clean($html));
    }

    public function test_strips_script_tags(): void
    {
        $clean = HtmlSanitizer::clean('<p>ok</p><script>alert(1)</script>');
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringContainsString('<p>ok</p>', $clean);
    }

    public function test_strips_event_handler_attributes(): void
    {
        $clean = HtmlSanitizer::clean('<p onclick="steal()">hi</p>');
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringContainsString('hi', $clean);
    }

    public function test_strips_javascript_urls_but_keeps_safe_links(): void
    {
        $bad = HtmlSanitizer::clean('<a href="javascript:alert(1)">x</a>');
        $this->assertStringNotContainsString('javascript:', $bad);

        $good = HtmlSanitizer::clean('<a href="https://unikosa.org">site</a>');
        $this->assertStringContainsString('href="https://unikosa.org"', $good);
    }

    public function test_unwraps_disallowed_tags_but_keeps_text(): void
    {
        $clean = HtmlSanitizer::clean('<p>before<iframe src="evil"></iframe>after</p>');
        $this->assertStringNotContainsString('<iframe', $clean);
        $this->assertStringContainsString('before', $clean);
        $this->assertStringContainsString('after', $clean);
    }

    public function test_model_mutator_sanitizes_on_write(): void
    {
        $user = User::factory()->create();

        $post = BlogPost::create([
            'user_id' => $user->id,
            'title' => 'Test',
            'body' => '<p>safe</p><script>alert(1)</script>',
            'status' => 'published',
        ]);

        $this->assertStringNotContainsString('<script', $post->body);
        $this->assertStringContainsString('safe', $post->body);
    }
}
