<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Notifications\MemberPendingApprovalNotification;
use App\Services\MembershipReviewNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends ApiController
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!auth()->attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = auth()->user()->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => auth()->user()->only('id', 'name', 'email', 'status'),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'graduating_set_id' => ['required', 'exists:sets,id'],
        ]);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'status' => 'pending',
        ]);

        $user->assignRole('member');
        $user->notify(new MemberPendingApprovalNotification());
        MembershipReviewNotifier::notify($user);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only('id', 'name', 'email', 'status'),
            'message' => 'Registration successful. Your account is pending approval.',
        ], 201);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user()->only('id', 'name', 'email', 'status', 'phone', 'profession', 'country', 'city'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json($request->user()->load(['graduatingSet', 'chapter']));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'nullable|string',
            'profession' => 'nullable|string',
            'bio' => 'nullable|string',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
        ]);

        $request->user()->update($validated);

        return response()->json(['message' => 'Profile updated.', 'user' => $request->user()]);
    }
}
