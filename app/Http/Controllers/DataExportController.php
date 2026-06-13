<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class DataExportController extends Controller
{
    public function index()
    {
        return Inertia::render('Profile/Export');
    }

    public function exportMyData(Request $request)
    {
        $user = $request->user();

        $data = [
            'profile' => $user->only([
                'name', 'email', 'phone', 'date_of_birth', 'gender',
                'graduating_set_id', 'house', 'country', 'city', 'profession',
                'bio', 'skills', 'social_links', 'status', 'created_at',
            ]),
            'graduating_set' => $user->graduatingSet,
            'chapter' => $user->chapter,
            'forum_posts' => $user->forumPosts()->select('id', 'title', 'body', 'status', 'created_at')->get(),
            'forum_replies' => $user->forumReplies()->select('id', 'body', 'created_at')->get(),
            'blog_posts' => $user->blogPosts()->select('id', 'title', 'body', 'status', 'created_at')->get(),
            'events' => $user->events()->select('id', 'title', 'start_at')->get(),
            'payments' => $user->payments()->select('id', 'amount', 'currency', 'payment_method', 'status', 'paid_at')->get(),
            'jobs' => $user->jobs()->select('id', 'title', 'company', 'type', 'status', 'created_at')->get(),
            'business_listings' => $user->businessListings()->select('id', 'name', 'category', 'status', 'created_at')->get(),
        ];

        $filename = 'unikosa_data_export_' . $user->id . '_' . now()->format('Y-m-d') . '.json';
        $content = json_encode($data, JSON_PRETTY_PRINT);

        return response($content, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function adminExportUsers(Request $request)
    {
        $filename = 'unikosa_users_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new UserExport, $filename);
    }
}
