<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::with(['author', 'categories'])
            ->where('status', 'published')
            ->latest('published_at');

        if ($categoryId = $request->input('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('blog_categories.id', $categoryId));
        }

        return Inertia::render('Blog/Index', [
            'posts' => $query->paginate(10),
            'categories' => BlogCategory::withCount('posts')->orderBy('name')->get(),
            'activeCategory' => $categoryId,
        ]);
    }

    public function show(BlogPost $post)
    {
        $post->load(['author', 'categories']);
        $post->increment('views_count');

        return Inertia::render('Blog/Show', [
            'post' => $post,
        ]);
    }

    public function create()
    {
        return Inertia::render('Blog/Create', [
            'categories' => BlogCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'required|string',
            'featured_image' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:blog_categories,id',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'pending';

        $categories = $validated['categories'] ?? [];
        unset($validated['categories']);

        $post = BlogPost::create($validated);

        if (!empty($categories)) {
            $post->categories()->sync($categories);
        }

        return redirect()->route('blog.index')->with('success', 'Blog post submitted for review.');
    }
}
