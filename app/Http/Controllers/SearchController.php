<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function global(Request $request)
    {
        $query = $request->input('q');

        if (!$query || strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $tsQuery = "websearch_to_tsquery('english', ?)";
        $limit = 5;

        $users = \App\Models\User::where('status', 'approved')
            ->whereNull('deleted_at')
            ->whereRaw("search_vector @@ {$tsQuery}", [$query])
            ->selectRaw("id, name, profession, avatar, ts_rank(search_vector, {$tsQuery}) AS rank", [$query])
            ->orderByDesc('rank')
            ->limit($limit)
            ->get()
            ->map(fn ($u) => ['type' => 'member', 'id' => $u->id, 'title' => $u->name, 'subtitle' => $u->profession, 'url' => "/directory"]);

        $jobs = \App\Models\AlumniJob::where('status', 'approved')
            ->whereRaw("search_vector @@ {$tsQuery}", [$query])
            ->selectRaw("id, title, company, ts_rank(search_vector, {$tsQuery}) AS rank", [$query])
            ->orderByDesc('rank')
            ->limit($limit)
            ->get()
            ->map(fn ($j) => ['type' => 'job', 'id' => $j->id, 'title' => $j->title, 'subtitle' => $j->company, 'url' => "/jobs/{$j->id}"]);

        $blogPosts = \App\Models\BlogPost::where('status', 'published')
            ->whereRaw("search_vector @@ {$tsQuery}", [$query])
            ->selectRaw("id, title, slug, ts_rank(search_vector, {$tsQuery}) AS rank", [$query])
            ->orderByDesc('rank')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => ['type' => 'blog', 'id' => $p->id, 'title' => $p->title, 'subtitle' => 'Blog Post', 'url' => "/blog/{$p->slug}"]);

        $forumPosts = \App\Models\ForumPost::where('status', 'approved')
            ->whereNull('deleted_at')
            ->whereRaw("search_vector @@ {$tsQuery}", [$query])
            ->selectRaw("id, title, ts_rank(search_vector, {$tsQuery}) AS rank", [$query])
            ->orderByDesc('rank')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => ['type' => 'forum', 'id' => $p->id, 'title' => $p->title, 'subtitle' => 'Forum Post', 'url' => "/forum/{$p->id}"]);

        $events = \App\Models\Event::whereNull('deleted_at')
            ->where('title', 'ilike', "%{$query}%")
            ->where('start_at', '>=', now()->subDays(7))
            ->select('id', 'title', 'start_at')
            ->limit($limit)
            ->get()
            ->map(fn ($e) => ['type' => 'event', 'id' => $e->id, 'title' => $e->title, 'subtitle' => $e->start_at?->format('M j, Y'), 'url' => "/events/{$e->id}"]);

        $businesses = \App\Models\BusinessListing::where('status', 'approved')
            ->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                  ->orWhere('category', 'ilike', "%{$query}%");
            })
            ->select('id', 'name', 'category')
            ->limit($limit)
            ->get()
            ->map(fn ($b) => ['type' => 'business', 'id' => $b->id, 'title' => $b->name, 'subtitle' => $b->category, 'url' => "/business/{$b->id}"]);

        return response()->json([
            'results' => [
                'members' => $users,
                'jobs' => $jobs,
                'events' => $events,
                'blog' => $blogPosts,
                'forum' => $forumPosts,
                'businesses' => $businesses,
            ],
        ]);
    }
}
