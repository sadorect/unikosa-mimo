<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = User::where('status', 'approved')
            ->select('id', 'name', 'profession', 'city', 'country', 'avatar')
            ->with('graduatingSet:id,name', 'chapter:id,name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('profession', 'ilike', "%{$search}%");
            });
        }

        if ($setId = $request->input('set_id')) {
            $query->where('graduating_set_id', $setId);
        }

        if ($chapterId = $request->input('chapter_id')) {
            $query->where('chapter_id', $chapterId);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function show(User $user): JsonResponse
    {
        if ($user->status !== 'approved') {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($user->load('graduatingSet', 'chapter'));
    }
}
