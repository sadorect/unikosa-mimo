<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #F59E0B; padding-bottom: 20px; }
        .header h1 { color: #F59E0B; font-size: 28px; margin: 0; }
        .header p { color: #666; margin: 5px 0 0; }
        .receipt-title { text-align: center; font-size: 18px; font-weight: bold; margin: 20px 0; color: #555; }
        .details { margin: 20px 0; }
        .details table { width: 100%; border-collapse: collapse; }
        .details td { padding: 8px 0; border-bottom: 1px solid #eee; }
        .details td:first-child { font-weight: bold; width: 40%; color: #555; }
        .amount { font-size: 24px; font-weight: bold; color: #F59E0B; text-align: center; margin: 20px 0; }
        .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; color: #999; font-size: 10px; }
        .status { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .status-successful { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $appName }}</h1>
        <p>Alumni Association Payment Receipt</p>
    </div>

    <div class="receipt-title">PAYMENT RECEIPT</div>

    <div class="amount">
        {{ $payment->currency }} {{ number_format($payment->amount / 100, 2) }}
    </div>

    <div class="details">
        <table>
            <tr>
                <td>Receipt Number</td>
                <td>{{ $payment->payment_reference }}</td>
            </tr>
            <tr>
                <td>Date</td>
                <td>{{ $payment->paid_at?->format('F j, Y \a\t g:i A') ?? 'Pending' }}</td>
            </tr>
            <tr>
                <td>Status</td>
                <td>
                    <span class="status status-successful">{{ ucfirst($payment->status) }}</span>
                </td>
            </tr>
            <tr>
                <td>Payment Method</td>
                <td>{{ ucfirst($payment->payment_method) }}</td>
            </tr>
            <tr>
                <td>Payer Name</td>
                <td>{{ $payment->user->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Payer Email</td>
                <td>{{ $payment->user->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Payment For</td>
                <td>
                    @if($payment->payable)
                        {{ class_basename($payment->payable_type) }}
                        — {{ $payment->payable->name ?? $payment->payable->title ?? 'N/A' }}
                    @else
                        N/A
                    @endif
                </td>
            </tr>
            <tr>
                <td>Currency</td>
                <td>{{ $payment->currency }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>This is a computer-generated receipt. No signature required.</p>
        <p>{{ $appName }} &mdash; {{ $appUrl }}</p>
    </div>
</body>
</html>
