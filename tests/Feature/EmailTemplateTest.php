<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\MemberApprovedNotification;
use Database\Seeders\EmailTemplatesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_render_substitutes_placeholders(): void
    {
        EmailTemplate::create([
            'key' => 'welcome',
            'name' => 'Welcome',
            'subject' => 'Hi {{ name }}',
            'body' => 'Welcome to {{ site_name }}, {{ name }}!',
            'variables' => ['name', 'site_name'],
        ]);
        Setting::set('site_name', 'UNIKOSA', 'branding');

        $rendered = EmailTemplate::render('welcome', ['name' => 'Ada']);

        $this->assertSame('Hi Ada', $rendered['subject']);
        $this->assertSame('Welcome to UNIKOSA, Ada!', $rendered['body']);
    }

    public function test_render_returns_null_for_missing_template(): void
    {
        $this->assertNull(EmailTemplate::render('does_not_exist'));
    }

    public function test_unknown_placeholders_are_left_intact(): void
    {
        EmailTemplate::create([
            'key' => 'x', 'name' => 'X', 'subject' => 'S', 'body' => 'Hello {{ unknown }}',
        ]);

        $this->assertSame('Hello {{ unknown }}', EmailTemplate::render('x')['body']);
    }

    public function test_seeder_loads_the_three_default_templates(): void
    {
        $this->seed(EmailTemplatesSeeder::class);

        $this->assertNotNull(EmailTemplate::where('key', 'welcome')->first());
        $this->assertNotNull(EmailTemplate::where('key', 'dues_reminder')->first());
        $this->assertNotNull(EmailTemplate::where('key', 'event_rsvp_confirmation')->first());
    }

    public function test_approved_notification_uses_template_subject(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(EmailTemplatesSeeder::class);
        Setting::set('site_name', 'UNIKOSA', 'branding');

        $user = User::factory()->create(['name' => 'Ada', 'status' => 'approved']);

        $mail = (new MemberApprovedNotification())->toMail($user);

        $this->assertSame('Your UNIKOSA profile has been approved!', $mail->subject);
        $this->assertContains('Welcome, Ada!', $mail->introLines);
    }
}
