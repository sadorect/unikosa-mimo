<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => route('blog.index'), 'priority' => '0.7'],
            ['loc' => route('transparency.index'), 'priority' => '0.8'],
            ['loc' => route('legal.contact'), 'priority' => '0.5'],
            ['loc' => route('legal.privacy'), 'priority' => '0.3'],
            ['loc' => route('legal.terms'), 'priority' => '0.3'],
        ];

        BlogPost::where('status', 'published')
            ->latest('published_at')
            ->limit(500)
            ->get(['slug'])
            ->each(function (BlogPost $post) use (&$urls) {
                $urls[] = ['loc' => route('blog.show', $post->slug), 'priority' => '0.6'];
            });

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'text/xml');
    }
}
