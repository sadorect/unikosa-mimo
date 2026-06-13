<?php

namespace App\Http\Controllers;

use App\Models\AlumniJob;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = AlumniJob::with('author')
            ->where('status', 'approved')
            ->latest();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('company', 'ilike', "%{$search}%")
                  ->orWhere('location', 'ilike', "%{$search}%");
            });
        }

        return Inertia::render('Jobs/Index', [
            'jobs' => $query->paginate(15),
            'filters' => $request->only(['type', 'search']),
        ]);
    }

    public function show(AlumniJob $job)
    {
        $job->load('author');
        $job->increment('views_count');

        $hasApplied = false;
        if (auth()->check()) {
            $hasApplied = JobApplication::where('job_id', $job->id)
                ->where('user_id', auth()->id())
                ->exists();
        }

        return Inertia::render('Jobs/Show', [
            'job' => $job,
            'hasApplied' => $hasApplied,
        ]);
    }

    public function create()
    {
        return Inertia::render('Jobs/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company' => 'nullable|string',
            'description' => 'required|string',
            'type' => 'required|in:full_time,part_time,contract,internship,remote,hire_alumnus',
            'location' => 'nullable|string',
            'is_remote' => 'boolean',
            'salary_min' => 'nullable|integer',
            'salary_max' => 'nullable|integer|gte:salary_min',
            'salary_currency' => 'nullable|string',
            'application_url' => 'nullable|url',
            'contact_email' => 'nullable|email',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'pending';

        AlumniJob::create($validated);

        return redirect()->route('jobs.index')->with('success', 'Job listing submitted for review.');
    }

    public function apply(Request $request, AlumniJob $job)
    {
        $request->validate([
            'cover_letter' => 'nullable|string',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $cvPath = null;
        if ($request->hasFile('cv')) {
            $cvPath = $request->file('cv')->store('cvs/' . auth()->id(), 'r2');
        }

        JobApplication::create([
            'job_id' => $job->id,
            'user_id' => $request->user()->id,
            'cover_letter' => $request->cover_letter,
            'cv_path' => $cvPath,
            'referral_id' => $request->referral_id,
        ]);

        return back()->with('success', 'Application submitted successfully.');
    }

    public function applications(AlumniJob $job)
    {
        $job->load('applications.applicant', 'applications.referrer');

        return Inertia::render('Jobs/Applications', [
            'job' => $job,
        ]);
    }
}
