<?php

namespace App\Http\Controllers;

use App\Mail\NewContactMessage;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function show()
    {
        return Inertia::render('Contact/Show', [
            'intro' => Setting::get('contact_page_content', ''),
            'whatsappNumber' => Setting::get('contact_whatsapp_number'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $contactMessage = ContactMessage::create([
            ...$validated,
            'ip_address' => $request->ip(),
        ]);

        $notifyEmail = Setting::get('contact_email');

        if ($notifyEmail) {
            try {
                Mail::to($notifyEmail)->send(new NewContactMessage($contactMessage));
            } catch (\Throwable $e) {
                // Email delivery is best-effort; the message is always saved for review in the admin panel.
                Log::warning('Failed to send contact form notification email', [
                    'contact_message_id' => $contactMessage->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('legal.contact')->with('success', "Thanks for reaching out — we'll get back to you soon.");
    }
}
