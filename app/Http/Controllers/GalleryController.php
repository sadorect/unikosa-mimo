<?php

namespace App\Http\Controllers;

use App\Models\GalleryAlbum;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function index()
    {
        return Inertia::render('Gallery/Index', [
            'albums' => GalleryAlbum::with(['event', 'media'])
                ->latest()
                ->paginate(12),
        ]);
    }

    public function show(GalleryAlbum $album)
    {
        $album->load(['event', 'media.uploader']);

        return Inertia::render('Gallery/Show', [
            'album' => $album,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_id' => 'nullable|exists:events,id',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['user_id'] = $request->user()->id;

        $album = GalleryAlbum::create($validated);

        return redirect()->route('gallery.show', $album)->with('success', 'Album created.');
    }

    public function uploadMedia(Request $request, GalleryAlbum $album)
    {
        $validated = $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|max:10240',
            'captions' => 'nullable|array',
        ]);

        foreach ($request->file('files') as $index => $file) {
            $path = $file->store('gallery/' . $album->slug, 'r2');
            $mimeType = $file->getMimeType();
            $type = str_starts_with($mimeType, 'video') ? 'video' : 'image';

            $album->media()->create([
                'path' => $path,
                'caption' => $validated['captions'][$index] ?? null,
                'type' => $type,
                'user_id' => $request->user()->id,
            ]);
        }

        return back()->with('success', 'Media uploaded successfully.');
    }
}
