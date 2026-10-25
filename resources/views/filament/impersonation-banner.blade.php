{{-- Inline styles on purpose: the panel's compiled CSS carries no app utilities. --}}
@php($impersonator = app(\App\Services\Impersonation::class)->impersonator())
@if ($impersonator)
    <div role="status" style="background:#f59e0b;color:#451a03;font-size:.875rem;padding:.5rem 1rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem;">
        <p style="margin:0;font-weight:500;">
            You are signed in as <strong>{{ auth()->user()->name }}</strong>
            <span style="opacity:.8;">({{ $impersonator->name }})</span>
        </p>
        <form method="POST" action="{{ route('impersonate.stop') }}" style="margin:0;">
            @csrf
            <button type="submit" style="background:#451a03;color:#fffbeb;border:0;border-radius:.375rem;padding:.25rem .75rem;font-size:.75rem;font-weight:600;cursor:pointer;">Stop</button>
        </form>
    </div>
@endif
