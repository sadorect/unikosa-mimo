{{-- The last line of defence.

     This page is rendered when the Inertia error page cannot be built — most
     often because the database is the thing that broke, and the shared Inertia
     props read settings on every request. It therefore touches nothing but
     config: no database, no compiled assets, no session. Keep it that way. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 24px; background: #f9fafb; color: #111827;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .card { width: 100%; max-width: 34rem; text-align: center; }
        .brand { font-size: 1.125rem; font-weight: 700; color: #f59e0b; margin-bottom: 2rem; }
        .status { font-size: 3.75rem; font-weight: 700; color: #e5e7eb; line-height: 1; margin-bottom: 1rem; }
        h1 { font-size: 1.25rem; font-weight: 600; margin: 0 0 0.75rem; }
        p { font-size: 0.875rem; color: #6b7280; line-height: 1.6; margin: 0 0 2rem; }
        .actions { display: flex; gap: 1rem; justify-content: center; align-items: center; flex-wrap: wrap; font-size: 0.875rem; }
        .diagnostic { margin-top: 2rem; text-align: left; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #fff; overflow: hidden; }
        .diagnostic h2 { margin: 0; padding: 0.65rem 0.9rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #92400e; background: #fef3c7; border-bottom: 1px solid #fde68a; }
        .diagnostic .body { padding: 0.9rem; }
        .diagnostic .summary { font-size: 0.875rem; margin: 0 0 0.75rem; color: #374151; }
        .diagnostic dt { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af; margin-top: 0.75rem; }
        .diagnostic dd { margin: 0.2rem 0 0; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.78rem; color: #111827; word-break: break-word; }
        .diagnostic ol { margin: 0.3rem 0 0; padding-left: 1.1rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.72rem; color: #4b5563; }
        .diagnostic li { margin-bottom: 0.2rem; }
        @media (prefers-color-scheme: dark) {
            .diagnostic { border-color: #374151; background: #1f2937; }
            .diagnostic h2 { background: #78350f; color: #fde68a; border-bottom-color: #92400e; }
            .diagnostic .summary { color: #d1d5db; }
            .diagnostic dd { color: #f3f4f6; }
            .diagnostic ol { color: #9ca3af; }
        }
        a.primary { background: #f59e0b; color: #fff; text-decoration: none; font-weight: 500; padding: 0.5rem 1.25rem; border-radius: 0.5rem; }
        a.secondary { color: #b45309; text-decoration: none; font-weight: 500; }
        @media (prefers-color-scheme: dark) {
            body { background: #111827; color: #f9fafb; }
            .status { color: #374151; }
            p { color: #9ca3af; }
            a.secondary { color: #fbbf24; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">{{ config('app.name') }}</div>
        <div class="status">{{ $status }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <div class="actions">
            <a class="primary" href="{{ url('/') }}">Go home</a>
            <a class="secondary" href="{{ url('/contact') }}">Contact support</a>
        </div>

        {{-- Only ever populated for a super admin; see ErrorResponder. --}}
        @if (! empty($diagnostic))
            <div class="diagnostic">
                <h2>What went wrong — visible to administrators only</h2>
                <div class="body">
                    <p class="summary">{{ $diagnostic['summary'] }}</p>
                    <dl>
                        <dt>Reported as</dt>
                        <dd>{{ $diagnostic['type'] }}</dd>
                        <dt>Detail</dt>
                        <dd>{{ $diagnostic['message'] }}</dd>
                        @if ($diagnostic['location'])
                            <dt>Where</dt>
                            <dd>{{ $diagnostic['location'] }}</dd>
                        @endif
                        @if (! empty($diagnostic['frames']))
                            <dt>Called from</dt>
                            <dd>
                                <ol>
                                    @foreach ($diagnostic['frames'] as $frame)
                                        <li>{{ $frame }}</li>
                                    @endforeach
                                </ol>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        @endif
    </div>
</body>
</html>
