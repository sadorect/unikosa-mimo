<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Authorize {{ $client->name }} — Unikosa</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #1e293b; border-radius: 12px; padding: 2.5rem; max-width: 420px; width: 90%; box-shadow: 0 20px 40px rgba(0,0,0,.3); }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; }
        p { color: #94a3b8; line-height: 1.5; }
        .scopes { list-style: none; padding: 0; margin: 1rem 0; }
        .scopes li { padding: .4rem 0; border-bottom: 1px solid #334155; color: #cbd5e1; }
        .actions { display: flex; gap: .75rem; margin-top: 1.5rem; }
        button { flex: 1; padding: .65rem 1rem; border-radius: 8px; border: none; font-size: .95rem; cursor: pointer; }
        .approve { background: #6366f1; color: #fff; }
        .deny { background: transparent; color: #94a3b8; border: 1px solid #334155; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $client->name }} wants to access your Unikosa account</h1>
        <p>Signed in as <strong>{{ $user->name }}</strong> ({{ $user->email }})</p>

        @if (count($scopes))
            <ul class="scopes">
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description ?? $scope->id }}</li>
                @endforeach
            </ul>
        @endif

        <div class="actions">
            <form method="post" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="approve">Authorize</button>
            </form>
            <form method="post" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->id }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="deny">Cancel</button>
            </form>
        </div>
    </div>
</body>
</html>
