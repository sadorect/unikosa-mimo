<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class PwaController extends Controller
{
    public function manifest()
    {
        $siteName = Setting::get('site_name', 'Unikosa');
        $accentColor = Setting::get('accent_color', '#F59E0B');

        $icons = [
            ['src' => asset('icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => asset('icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            // Dedicated maskable icon: accent background bleeds to every edge with the logo
            // inside the ~80% safe zone, so Android's shape mask never crops artwork.
            ['src' => asset('icons/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ];

        $logoPath = Setting::get('logo_path');
        if ($logoPath) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
                default => 'image/png',
            };

            array_unshift($icons, [
                'src' => Storage::disk(config('filesystems.media_disk'))->url($logoPath),
                'sizes' => 'any',
                'type' => $mime,
                'purpose' => 'any',
            ]);
        }

        return response()->json([
            'name' => $siteName . ' Alumni Network',
            'short_name' => $siteName,
            'description' => $siteName . ' is the official global alumni network — connecting graduates for events, mentorship, jobs, and giving back.',
            'start_url' => '/',
            'id' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => $accentColor,
            'icons' => $icons,
        ])->header('Content-Type', 'application/manifest+json');
    }

    /**
     * Served from a route (not a static file) so the cache name can embed the current
     * build hash: every deploy changes the SW bytes, which triggers a browser update and
     * purges the previous deploy's cache on activate — bounding cache growth automatically.
     */
    public function serviceWorker()
    {
        $version = $this->buildVersion();
        $js = $this->serviceWorkerScript($version);

        return response($js, 200, [
            'Content-Type' => 'text/javascript',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * A self-contained offline fallback — no external CSS/JS/fonts/images, and no
     * user-specific data, so it renders correctly from cache and is safe to serve to
     * anyone on a shared device.
     */
    public function offline()
    {
        $siteName = e(Setting::get('site_name', 'UNIKOSA'));
        $accent = e(Setting::get('accent_color', '#F59E0B'));

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Offline — {$siteName}</title>
            <style>
                body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
                    font-family: system-ui, -apple-system, sans-serif; background:#f9fafb; color:#1f2937; padding:24px; }
                .card { text-align:center; max-width:360px; }
                .dot { width:56px; height:56px; border-radius:9999px; background:{$accent}; margin:0 auto 20px; }
                h1 { font-size:20px; margin:0 0 8px; }
                p { color:#6b7280; font-size:14px; line-height:1.5; margin:0 0 20px; }
                button { background:{$accent}; color:#fff; border:0; border-radius:8px; padding:10px 20px;
                    font-size:14px; font-weight:600; cursor:pointer; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="dot"></div>
                <h1>You're offline</h1>
                <p>{$siteName} needs an internet connection for this page. Check your connection and try again.</p>
                <button onclick="location.reload()">Retry</button>
            </div>
        </body>
        </html>
        HTML;

        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    protected function buildVersion(): string
    {
        $manifest = public_path('build/manifest.json');

        return is_file($manifest) ? substr(md5_file($manifest), 0, 8) : 'dev';
    }

    protected function serviceWorkerScript(string $version): string
    {
        return <<<JS
        const CACHE_NAME = 'unikosa-cache-{$version}';
        const OFFLINE_URL = '/offline';

        self.addEventListener('install', (event) => {
            event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.add(OFFLINE_URL)));
            self.skipWaiting();
        });

        self.addEventListener('activate', (event) => {
            event.waitUntil(
                caches.keys()
                    .then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
                    .then(() => self.clients.claim())
            );
        });

        self.addEventListener('fetch', (event) => {
            const { request } = event;

            if (request.method !== 'GET' || !request.url.startsWith(self.location.origin)) {
                return;
            }

            // Navigations are network-only with an offline fallback. We deliberately never
            // cache navigation responses: an Inertia page's HTML embeds that user's data,
            // so caching it could leak one member's data to another on a shared device.
            if (request.mode === 'navigate') {
                event.respondWith(
                    fetch(request).catch(() => caches.match(OFFLINE_URL))
                );
                return;
            }

            // Content-hashed build assets and images carry no user data — cache-first,
            // refreshed in the background. The per-deploy CACHE_NAME purges stale ones.
            if (/\\/build\\/|\\.(?:png|jpg|jpeg|svg|webp|ico|woff2?)\$/.test(request.url)) {
                event.respondWith(
                    caches.match(request).then((cached) => {
                        const network = fetch(request)
                            .then((response) => {
                                const copy = response.clone();
                                caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                                return response;
                            })
                            .catch(() => cached);
                        return cached || network;
                    })
                );
            }
        });
        JS;
    }
}
