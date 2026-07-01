<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_creates_then_updates_by_key(): void
    {
        Setting::set('accent_color', '#000000', 'theme');
        $this->assertSame('#000000', Setting::get('accent_color'));

        Setting::set('accent_color', '#FF0000', 'theme');
        $this->assertSame('#FF0000', Setting::get('accent_color'));
        $this->assertSame(1, Setting::where('key', 'accent_color')->count());
    }

    public function test_get_returns_default_when_missing(): void
    {
        $this->assertSame('Inter', Setting::get('font_family', 'Inter'));
    }

    public function test_group_returns_key_value_map(): void
    {
        Setting::set('forum_enabled', 'true', 'modules');
        Setting::set('blog_enabled', 'false', 'modules');

        $modules = Setting::group('modules');

        $this->assertSame('true', $modules['forum_enabled']);
        $this->assertSame('false', $modules['blog_enabled']);
    }
}
