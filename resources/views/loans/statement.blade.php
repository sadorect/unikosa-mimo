{{-- Inline CSS only: dompdf will not fetch remote stylesheets or images. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #F59E0B; padding-bottom: 16px; }
        .header h1 { color: #F59E0B; font-size: 26px; margin: 0; }
        .header p { color: #666; margin: 4px 0 0; }
        .title { text-align: center; font-size: 16px; font-weight: bold; margin: 16px 0; color: #555; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; }
        .details td { padding: 7px 0; border-bottom: 1px solid #eee; }
        .details td:first-child { font-weight: bold; width: 40%; color: #555; }
        .ledger { margin-top: 8px; }
        .ledger th { text-align: left; padding: 7px 4px; border-bottom: 2px solid #ddd; color: #555; font-size: 11px; }
        .ledger td { padding: 7px 4px; border-bottom: 1px solid #eee; }
        .ledger .num { text-align: right; }
        .section { margin: 22px 0 8px; font-weight: bold; color: #555; font-size: 13px; }
        .balance { margin-top: 16px; padding: 12px; background: #fafafa; border: 1px solid #eee; }
        .balance table td { padding: 5px 0; }
        .balance .total { font-size: 16px; font-weight: bold; color: #F59E0B; }
        .muted { color: #999; font-size: 10px; }
        .footer { text-align: center; margin-top: 32px; padding-top: 16px; border-top: 1px solid #eee; color: #999; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $appName }}</h1>
        <p>{{ $loan->program->name }}{{ $loan->set?->name ? ' — '.$loan->set->name : '' }}</p>
    </div>

    <div class="title">LOAN STATEMENT</div>

    <table class="details">
        <tr><td>Reference</td><td>{{ $loan->reference }}</td></tr>
        <tr><td>Borrower</td><td>{{ $loan->applicant->name }}</td></tr>
        <tr><td>Status</td><td>{{ ucwords(str_replace('_', ' ', $loan->status)) }}</td></tr>
        <tr><td>Amount disbursed</td><td>{{ $loan->currency }} {{ number_format((int) $loan->disbursed_amount) }}</td></tr>
        <tr><td>Interest ({{ number_format($loan->interest_rate_bps / 100, 2) }}% flat)</td><td>{{ $loan->currency }} {{ number_format((int) $loan->interest_amount) }}</td></tr>
        @if ($loan->penalty_amount > 0)
            <tr><td>Penalty</td><td>{{ $loan->currency }} {{ number_format((int) $loan->penalty_amount) }}</td></tr>
        @endif
        <tr><td>Disbursed on</td><td>{{ $loan->disbursed_at?->toFormattedDayDateString() ?? '—' }}</td></tr>
        <tr><td>Due on</td><td>{{ $loan->due_at?->toFormattedDayDateString() ?? '—' }}</td></tr>
    </table>

    <div class="section">Repayments received</div>
    <table class="ledger">
        <thead>
            <tr>
                <th>Date</th>
                <th>Paid by</th>
                <th>Method</th>
                <th>Reference</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($repayments as $repayment)
                <tr>
                    <td>{{ $repayment->paid_on?->toFormattedDateString() }}</td>
                    <td>
                        {{ $repayment->payer?->name ?? $loan->applicant->name }}
                        {{-- A guarantor's payment is named as such: the borrower's own repayment history has to stay honest. --}}
                        @if ($repayment->isOnBehalfOfBorrower())
                            <span class="muted">(guarantor)</span>
                        @endif
                    </td>
                    <td>{{ ucwords(str_replace('_', ' ', $repayment->method)) }}</td>
                    <td>{{ $repayment->reference ?? '—' }}</td>
                    <td class="num">{{ $loan->currency }} {{ number_format($repayment->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No confirmed repayments yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="balance">
        <table>
            <tr><td>Total repayable</td><td class="num">{{ $loan->currency }} {{ number_format((int) $loan->total_repayable + (int) $loan->penalty_amount) }}</td></tr>
            <tr><td>Confirmed repayments</td><td class="num">{{ $loan->currency }} {{ number_format($loan->confirmedRepaid()) }}</td></tr>
            <tr><td class="total">Outstanding</td><td class="num total">{{ $loan->currency }} {{ number_format($loan->outstanding_amount) }}</td></tr>
        </table>
    </div>

    @if ($loan->guarantors->where('status', 'accepted')->count())
        <div class="section">Guarantors</div>
        <p>{{ $loan->guarantors->where('status', 'accepted')->map(fn ($guarantor) => $guarantor->displayName())->implode(', ') }}</p>
    @endif

    <div class="footer">
        Generated {{ now()->toDayDateTimeString() }} · {{ $appUrl }}<br>
        Only repayments confirmed by the fund appear on this statement.
    </div>
</body>
</html>
