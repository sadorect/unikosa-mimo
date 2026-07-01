<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Unikosa', 'group' => 'branding'],
            ['key' => 'site_tagline', 'value' => 'Connecting Alumni Worldwide', 'group' => 'branding'],
            ['key' => 'accent_color', 'value' => '#F59E0B', 'group' => 'theme'],
            ['key' => 'theme_mode', 'value' => 'light', 'group' => 'theme'],
            ['key' => 'font_family', 'value' => 'Inter', 'group' => 'theme'],
            ['key' => 'paystack_key', 'value' => '', 'group' => 'payment'],
            ['key' => 'paystack_secret', 'value' => '', 'group' => 'payment'],
            ['key' => 'stripe_key', 'value' => '', 'group' => 'payment'],
            ['key' => 'stripe_secret', 'value' => '', 'group' => 'payment'],
            ['key' => 'default_currency', 'value' => 'NGN', 'group' => 'payment'],
            ['key' => 'exchange_rate_source', 'value' => 'manual', 'group' => 'currency'],
            ['key' => 'exchange_rate_usd', 'value' => '', 'group' => 'currency'],
            ['key' => 'exchange_rate_gbp', 'value' => '', 'group' => 'currency'],
            ['key' => 'job_board_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'business_directory_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'forum_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'events_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'blog_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'gallery_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'mentorship_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'transparency_report_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'social_facebook', 'value' => '', 'group' => 'social'],
            ['key' => 'social_twitter', 'value' => '', 'group' => 'social'],
            ['key' => 'social_instagram', 'value' => '', 'group' => 'social'],
            ['key' => 'social_linkedin', 'value' => '', 'group' => 'social'],
            ['key' => 'social_youtube', 'value' => '', 'group' => 'social'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
