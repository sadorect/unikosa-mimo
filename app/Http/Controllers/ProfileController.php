<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Set;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $privacy = $user->profilePrivacy ?? $user->profilePrivacy()->create([]);

        return Inertia::render('Profile/Edit', [
            'user' => $user->only([
                'id', 'name', 'email', 'phone', 'date_of_birth', 'wedding_anniversary', 'gender',
                'graduating_set_id', 'chapter_id', 'country', 'city',
                'profession', 'bio', 'skills', 'social_links', 'avatar',
            ]),
            'sets' => Set::orderBy('year', 'desc')->get(['id', 'name']),
            'chapters' => Chapter::orderBy('name')->get(['id', 'name']),
            'privacy' => $privacy->only([
                'show_email', 'show_phone', 'show_dob',
                'show_profession', 'show_skills', 'show_social_links', 'show_location',
                'notify_email_messages',
            ]),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'wedding_anniversary' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:30'],
            'graduating_set_id' => ['nullable', 'exists:sets,id'],
            'chapter_id' => ['nullable', 'exists:chapters,id'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'profession' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:60'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string', 'url', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $disk = config('filesystems.media_disk');
            $path = $request->file('avatar')->store('avatars', $disk);
            $validated['avatar'] = Storage::disk($disk)->url($path);
        } else {
            unset($validated['avatar']);
        }

        $user->fill($validated)->save();

        return back()->with('success', 'Profile updated.');
    }

    public function updatePrivacy(Request $request)
    {
        $validated = $request->validate([
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'show_dob' => 'boolean',
            'show_profession' => 'boolean',
            'show_skills' => 'boolean',
            'show_social_links' => 'boolean',
            'show_location' => 'boolean',
            'notify_email_messages' => 'boolean',
        ]);

        $request->user()->profilePrivacy()->updateOrCreate([], $validated);

        return back()->with('success', 'Privacy settings updated.');
    }
}
