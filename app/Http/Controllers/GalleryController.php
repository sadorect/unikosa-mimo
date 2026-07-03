<?php

namespace App\Http\Controllers;

use App\Models\GalleryAlbum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function index()
    {
        return Inertia::render('Gallery/Index', [
            'albums' => GalleryAlbum::with('event')
                ->withCount('media')
                ->with(['media' => fn ($query) => $query->orderByRaw("type = 'video'")->oldest()->limit(1)])
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
        $imageMaxKb = 20480;  // 20MB
        $videoMaxKb = 204800; // 200MB

        $validated = $request->validate([
            'files' => 'required|array',
            'files.*' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm,mkv',
                function ($attribute, $file, $fail) use ($imageMaxKb, $videoMaxKb) {
                    $isVideo = str_starts_with($file->getMimeType(), 'video');
                    $limitKb = $isVideo ? $videoMaxKb : $imageMaxKb;

                    if ($file->getSize() > $limitKb * 1024) {
                        $fail(sprintf(
                            '"%s" exceeds the %dMB limit for %s.',
                            $file->getClientOriginalName(),
                            intdiv($limitKb, 1024),
                            $isVideo ? 'videos' : 'images'
                        ));
                    }
                },
            ],
            'captions' => 'nullable|array',
        ]);

        $disk = config('filesystems.media_disk');

        foreach ($request->file('files') as $index => $file) {
            $path = $file->store('gallery/' . $album->slug, $disk);
            $mimeType = $file->getMimeType();
            $type = str_starts_with($mimeType, 'video') ? 'video' : 'image';

            $album->media()->create([
                'path' => Storage::disk($disk)->url($path),
                'caption' => $validated['captions'][$index] ?? null,
                'type' => $type,
                'user_id' => $request->user()->id,
            ]);
        }

        return back()->with('success', 'Media uploaded successfully.');
    }
}
