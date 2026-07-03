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
            ['key' => 'elections_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'blog_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'gallery_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'mentorship_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'transparency_report_enabled', 'value' => 'true', 'group' => 'modules'],
            ['key' => 'social_facebook', 'value' => '', 'group' => 'social'],
            ['key' => 'social_twitter', 'value' => '', 'group' => 'social'],
            ['key' => 'social_instagram', 'value' => '', 'group' => 'social'],
            ['key' => 'social_linkedin', 'value' => '', 'group' => 'social'],
            ['key' => 'social_youtube', 'value' => '', 'group' => 'social'],
            ['key' => 'legal_privacy_policy', 'value' => $this->privacyPolicyDraft(), 'group' => 'legal'],
            ['key' => 'legal_terms_of_service', 'value' => $this->termsOfServiceDraft(), 'group' => 'legal'],
            ['key' => 'contact_page_content', 'value' => $this->contactPageDraft(), 'group' => 'legal'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }

    /**
     * Generic draft copy for the Legal Pages admin screen. Not legal advice —
     * intended as a starting point for the association's own review before
     * the site relies on it.
     */
    protected function privacyPolicyDraft(): string
    {
        return <<<'HTML'
            <p>This Privacy Policy explains what personal information the UNIKOSA alumni platform collects, how it is used, and the choices available to members.</p>
            <h2>Information we collect</h2>
            <p>When you register, claim a profile, or use the platform, we may collect your name, email address, phone number, date of birth, profession, location (city/country), skills, social media links, and any content you post (forum posts, blog posts, job listings, business listings, event RSVPs).</p>
            <h2>Payment information</h2>
            <p>Dues, campaign contributions, and event tickets are processed by Paystack and Stripe. We do not store your card details; payment processors handle that data under their own privacy policies.</p>
            <h2>How we use your information</h2>
            <ul>
                <li>To operate your account and display your profile to other approved members, according to the privacy settings you choose.</li>
                <li>To process payments for dues, campaigns, and events.</li>
                <li>To send you notifications about events, dues, and platform activity.</li>
            </ul>
            <h2>Your choices</h2>
            <p>You can control which profile fields (email, phone, date of birth, profession, skills, social links, location) are visible to other members from your Profile settings. You can request an export of your data or contact us to request deletion.</p>
            <h2>Contact</h2>
            <p>Questions about this policy can be sent via our <a href="/contact">contact page</a>.</p>
            HTML;
    }

    protected function termsOfServiceDraft(): string
    {
        return <<<'HTML'
            <p>These Terms of Service govern your use of the UNIKOSA alumni platform.</p>
            <h2>Eligibility</h2>
            <p>The platform is intended for verified alumni and approved members of the association. Accounts may be reviewed before approval.</p>
            <h2>Member conduct</h2>
            <p>Content posted to the forum, blog, job board, or business directory must be accurate and respectful. Moderators may remove content that violates these terms.</p>
            <h2>Payments and dues</h2>
            <p>Dues and campaign contributions are processed via third-party payment providers (Paystack, Stripe). Payments are generally non-refundable except where required by law or at the association's discretion.</p>
            <h2>Changes</h2>
            <p>These terms may be updated from time to time; continued use of the platform after changes constitutes acceptance.</p>
            HTML;
    }

    protected function contactPageDraft(): string
    {
        return <<<'HTML'
            <p>Have a question, need help with your account, or want to report an issue? Reach us at <a href="mailto:noreply@sadorect.com">noreply@sadorect.com</a>.</p>
            HTML;
    }
}
