<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class MemberController extends Controller
{
    public function show(User $user)
    {
        abort_unless($user->status === 'approved', 404);

        $user->load(['graduatingSet', 'chapter']);
        $privacy = $user->profilePrivacy ?? $user->profilePrivacy()->create([]);

        return Inertia::render('Members/Show', [
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'graduating_set' => $user->graduatingSet,
                'chapter' => $user->chapter,
                'email' => $privacy->show_email ? $user->email : null,
                'phone' => $privacy->show_phone ? $user->phone : null,
                'date_of_birth' => $privacy->show_dob ? $user->date_of_birth : null,
                'profession' => $privacy->show_profession ? $user->profession : null,
                'bio' => $privacy->show_profession ? $user->bio : null,
                'skills' => $privacy->show_skills ? $user->skills : null,
                'social_links' => $privacy->show_social_links ? $user->social_links : null,
                'country' => $privacy->show_location ? $user->country : null,
                'city' => $privacy->show_location ? $user->city : null,
            ],
            'relatedMembers' => $this->relatedMembers($user),
        ]);
    }

    /**
     * Other approved members from the same graduating set or chapter, for
     * the "similar members" strip on the profile page.
     */
    private function relatedMembers(User $user): array
    {
        if (! $user->graduating_set_id && ! $user->chapter_id) {
            return [];
        }

        return User::where('status', 'approved')
            ->where('id', '!=', $user->id)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($user) {
                $query->where('graduating_set_id', -1); // always-false base so orWhere below is safe
                if ($user->graduating_set_id) {
                    $query->orWhere('graduating_set_id', $user->graduating_set_id);
                }
                if ($user->chapter_id) {
                    $query->orWhere('chapter_id', $user->chapter_id);
                }
            })
            ->with(['graduatingSet', 'chapter'])
            ->inRandomOrder()
            ->limit(10)
            ->get(['id', 'name', 'avatar', 'profession', 'account_claimed', 'graduating_set_id', 'chapter_id', 'city', 'country'])
            ->toArray();
    }
}
