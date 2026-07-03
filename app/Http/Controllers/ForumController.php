<?php

namespace App\Http\Controllers;

use App\Models\ForumGroup;
use App\Models\ForumPost;
use App\Models\ForumReply;
use App\Support\Celebrations;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ForumController extends Controller
{
    public function index()
    {
        return Inertia::render('Forum/Index', [
            'posts' => ForumPost::with(['author', 'group'])
                ->where('status', 'approved')
                ->latest()
                ->paginate(20),
            'groups' => ForumGroup::whereNull('deleted_at')->get(),
            'celebrations' => Celebrations::upcoming(14),
        ]);
    }

    public function show(ForumPost $post)
    {
        $post->load(['author', 'group', 'replies.author'])->loadCount('replies');
        $this->countView($post);

        return Inertia::render('Forum/Show', [
            'post' => $post,
        ]);
    }

    /**
     * Increment views at most once per session per post, so refreshes and the
     * redirect back after posting a reply don't inflate the count.
     */
    private function countView(ForumPost $post): void
    {
        $seen = session()->get('viewed_forum_posts', []);

        if (! in_array($post->id, $seen, true)) {
            $post->increment('views_count');
            session()->put('viewed_forum_posts', [...$seen, $post->id]);
        }
    }

    public function create()
    {
        return Inertia::render('Forum/Create', [
            'groups' => ForumGroup::whereNull('deleted_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'forum_group_id' => 'required|exists:forum_groups,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'pending';

        ForumPost::create($validated);

        return redirect()->route('forum.index')->with('success', 'Post submitted for moderation.');
    }

    public function storeReply(Request $request, ForumPost $post)
    {
        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $validated['user_id'] = $request->user()->id;

        $post->replies()->create($validated);

        return back()->with('success', 'Reply posted.');
    }

    public function group(ForumGroup $group)
    {
        return Inertia::render('Forum/Group', [
            'group' => $group,
            'posts' => ForumPost::with(['author'])
                ->where('forum_group_id', $group->id)
                ->where('status', 'approved')
                ->latest()
                ->paginate(20),
        ]);
    }
}
