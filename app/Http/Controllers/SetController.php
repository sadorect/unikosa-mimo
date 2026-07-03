<?php

namespace App\Http\Controllers;

use App\Models\Set;
use App\Models\Chapter;
use App\Support\Celebrations;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SetController extends Controller
{
    public function index()
    {
        return Inertia::render('Sets/Index', [
            'sets' => Set::withCount('members')->orderBy('year', 'desc')->get(),
        ]);
    }

    public function show(Set $set)
    {
        $set->loadCount('members');
        $set->load('rep');

        $members = $set->members()
            ->where('status', 'approved')
            ->select('id', 'name', 'profession', 'city', 'country', 'avatar')
            ->paginate(20);

        return Inertia::render('Sets/Show', [
            'set' => $set,
            'members' => $members,
            'celebrations' => Celebrations::upcoming(30, setId: $set->id),
        ]);
    }
}
