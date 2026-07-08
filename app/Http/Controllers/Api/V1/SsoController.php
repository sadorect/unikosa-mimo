<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SsoController extends ApiController
{
    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load('chapter');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => $user->email_verified_at !== null,
            'status' => $user->status,
            'chapter' => $user->chapter?->only('id', 'name', 'country', 'city'),
            'avatar' => $user->avatar,
        ]);
    }
}
