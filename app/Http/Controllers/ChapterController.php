<?php

namespace App\Http\Controllers;

use App\Models\Chapter;

class ChapterController extends Controller
{
    public function index()
    {
        return \Inertia\Inertia::render('Chapters/Index', [
            'chapters' => Chapter::withCount('members')->orderBy('name')->get(),
        ]);
    }

    public function show(Chapter $chapter)
    {
        $chapter->loadCount('members');
        $chapter->load('head');

        $members = $chapter->members()
            ->where('status', 'approved')
            ->select('id', 'name', 'profession', 'city', 'country', 'avatar')
            ->paginate(20);

        return \Inertia\Inertia::render('Chapters/Show', [
            'chapter' => $chapter,
            'members' => $members,
        ]);
    }
}
