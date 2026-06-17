<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Set;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ClaimProfileController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/ClaimProfile', [
            'sets' => Set::orderBy('year', 'desc')->get(),
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'set_id' => 'nullable|exists:sets,id',
        ]);

        // Note: use input('query'), not $request->query (the reserved InputBag).
        $term = $request->input('query');

        $like = '%' . strtolower($term) . '%';

        $query = User::where('imported', true)
            ->where('account_claimed', false)
            ->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(email) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
            });

        if ($request->set_id) {
            $query->where('graduating_set_id', $request->set_id);
        }

        $results = $query->select('id', 'name', 'email', 'graduating_set_id')
            ->with('graduatingSet:id,name')
            ->limit(10)
            ->get();

        return response()->json(['results' => $results]);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::where('id', $request->user_id)
            ->where('imported', true)
            ->where('account_claimed', false)
            ->firstOrFail();

        $otp = Str::random(6);
        // remember_token is guarded, so forceFill to persist the OTP marker.
        $user->forceFill(['remember_token' => 'claim_' . $otp])->save();

        \Illuminate\Support\Facades\Mail::to($user->email)->send(
            new \App\Mail\ClaimProfileOtp($user, $otp)
        );

        return response()->json(['message' => 'OTP sent to your email.']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('id', $request->user_id)
            ->where('imported', true)
            ->where('account_claimed', false)
            ->firstOrFail();

        if ($user->remember_token !== 'claim_' . $request->otp) {
            return response()->json(['message' => 'Invalid OTP.'], 422);
        }

        // remember_token and email_verified_at are guarded, so forceFill them.
        $user->forceFill([
            'password' => Hash::make($request->password),
            'account_claimed' => true,
            'claimed_at' => now(),
            'remember_token' => null,
            'email_verified_at' => now(),
        ])->save();

        $user->assignRole('member');

        \Illuminate\Support\Facades\Auth::login($user);

        return response()->json(['message' => 'Profile claimed successfully!', 'redirect' => '/dashboard']);
    }
}
