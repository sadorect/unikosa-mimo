<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Set;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DirectoryController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('status', 'approved')
            ->with(['graduatingSet', 'chapter'])
            ->whereNull('deleted_at');

        if ($search = $request->input('search')) {
            $query->whereRaw(
                "search_vector @@ websearch_to_tsquery('english', ?)",
                [$search]
            );
        }

        if ($setId = $request->input('set_id')) {
            $query->where('graduating_set_id', $setId);
        }

        if ($chapterId = $request->input('chapter_id')) {
            $query->where('chapter_id', $chapterId);
        }

        if ($profession = $request->input('profession')) {
            $query->where('profession', 'ilike', "%{$profession}%");
        }

        if ($country = $request->input('country')) {
            $query->where('country', $country);
        }

        $members = $search
            ? $query->orderByRaw("ts_rank(search_vector, websearch_to_tsquery('english', ?)) DESC", [$search])->paginate(20)->withQueryString()
            : $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Directory/Index', [
            'members' => $members,
            'sets' => Set::orderBy('year', 'desc')->get(),
            'chapters' => Chapter::orderBy('name')->get(),
            'filters' => $request->only(['search', 'set_id', 'chapter_id', 'profession', 'country']),
        ]);
    }
}
