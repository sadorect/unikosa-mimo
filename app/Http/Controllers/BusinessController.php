<?php

namespace App\Http\Controllers;

use App\Models\AlumniJob;
use App\Models\BusinessListing;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        $query = BusinessListing::with('owner')
            ->where('status', 'approved')
            ->latest();

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%")
                  ->orWhere('category', 'ilike', "%{$search}%");
            });
        }

        $categories = BusinessListing::where('status', 'approved')
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get();

        return Inertia::render('Business/Index', [
            'businesses' => $query->paginate(15),
            'categories' => $categories,
            'filters' => $request->only(['category', 'search']),
        ]);
    }

    public function show(BusinessListing $business)
    {
        $business->load('owner');
        $business->increment('views_count');

        return Inertia::render('Business/Show', [
            'business' => $business,
        ]);
    }

    public function create()
    {
        return Inertia::render('Business/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'website' => 'nullable|url',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'pending';

        BusinessListing::create($validated);

        return redirect()->route('business.index')->with('success', 'Business listing submitted for review.');
    }
}
