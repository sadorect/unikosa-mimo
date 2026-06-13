<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ForumPost;
use App\Models\Set;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(): \Inertia\Response
    {
        $upcomingEvents = Event::with('chapter')
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(5)
            ->get(['id', 'title', 'start_at', 'location', 'is_virtual', 'chapter_id']);

        $recentPosts = ForumPost::with('author:id,name')
            ->withCount('replies')
            ->latest()
            ->limit(5)
            ->get(['id', 'title', 'user_id', 'created_at', 'views_count']);

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'total_members'   => User::count(),
                'total_sets'      => Set::count(),
                'total_events'    => Event::count(),
                'pending_members' => User::where('status', 'pending')->count(),
            ],
            'upcomingEvents' => $upcomingEvents,
            'recentPosts'    => $recentPosts,
        ]);
    }
}
