{{-- Rendered once into loan_default_publications.published_payload and posted
     to the members-only forum. Everything here comes from the frozen payload,
     never from the live loan. --}}
<p><strong>This is a formal notice from the {{ $notice['fund'] }}.</strong></p>

<p>
    A loan made to <strong>{{ $notice['borrower_name'] }}</strong>@if ($notice['set']) ({{ $notice['set'] }})@endif
    has passed its repayment date and its grace period, and is recorded as being in default.
</p>

<ul>
    <li>Reference: {{ $notice['reference'] }}</li>
    <li>Amount borrowed: {{ $notice['currency'] }} {{ number_format($notice['principal']) }}</li>
    <li>Outstanding: {{ $notice['currency'] }} {{ number_format($notice['outstanding']) }}</li>
    <li>Was due on: {{ $notice['due_on'] }}</li>
    <li>Days overdue: {{ $notice['days_overdue'] }}</li>
</ul>

@if (! empty($notice['guarantors']))
    <p>The loan was guaranteed by:</p>
    <ul>
        @foreach ($notice['guarantors'] as $guarantor)
            <li>{{ $guarantor['name'] }}@if (! empty($guarantor['set'])) ({{ $guarantor['set'] }})@endif</li>
        @endforeach
    </ul>
@endif

<p>
    This notice is removed as soon as the balance is cleared — by the borrower or by a guarantor —
    and expires automatically in any case. If you can help resolve it, please contact the fund's
    welfare committee.
</p>
