{{--
    Membership invoice — rendered by Jobs\GenerateInvoice via dompdf.

    Written for dompdf, not for a browser. That constrains it more than it
    looks: no flexbox, no grid, no external stylesheet, no webfont. Tables and
    inline styles are not a stylistic choice here, they are the only layout
    primitives dompdf renders reliably.

    Amounts come from the payment row, never recomputed. An invoice that
    disagrees with the transaction it documents is the one bug in this file
    that would actually matter.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $payment->invoice_number }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 19px; margin: 0 0 2px; }
        .muted { color: #6b7280; }
        .right { text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; padding-bottom: 22px; }
        .items th { background: #f3f4f6; text-align: left; padding: 7px 9px; border-bottom: 1px solid #d1d5db; }
        .items td { padding: 9px; border-bottom: 1px solid #e5e7eb; }
        .totals td { padding: 5px 9px; }
        .totals .grand { font-size: 13px; font-weight: bold; border-top: 2px solid #1f2937; }
        .paid { color: #047857; font-weight: bold; }
        .foot { margin-top: 26px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 9px; }
    </style>
</head>
<body>

<table class="head">
    <tr>
        <td>
            <h1>{{ $seller['name'] }}</h1>
            @if ($seller['address'])
                <div class="muted">{!! nl2br(e($seller['address'])) !!}</div>
            @endif
            @if ($seller['gstin'])
                <div class="muted">GSTIN: {{ $seller['gstin'] }}</div>
            @endif
        </td>
        <td class="right">
            <div style="font-size: 15px; font-weight: bold;">TAX INVOICE</div>
            <div class="muted">{{ $payment->invoice_number }}</div>
            <div class="muted">{{ optional($payment->verified_at)->format('j M Y') }}</div>
        </td>
    </tr>
</table>

<table class="head">
    <tr>
        <td>
            <div class="muted">Billed to</div>
            <div style="font-weight: bold;">{{ $user?->name }}</div>
            {{-- Profile code, not the member's address: we do not collect one,
                 and a matrimonial invoice is read by people the member lives
                 with. --}}
            <div class="muted">{{ $user?->profile_code }}</div>
            <div class="muted">{{ $user?->email }}</div>
        </td>
        <td class="right">
            <div class="muted">Payment status</div>
            <div class="paid">PAID</div>
            <div class="muted">{{ strtoupper($payment->method ?? 'ONLINE') }}</div>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>Description</th>
            <th class="right" style="width: 120px;">Period</th>
            <th class="right" style="width: 110px;">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <strong>{{ $plan?->name }} Membership</strong>
                @if ($subscription)
                    <div class="muted">
                        {{ optional($subscription->starts_at)->format('j M Y') }}
                        &ndash;
                        {{-- A lifetime plan has no end date. Printing a blank
                             range is how a support ticket starts. --}}
                        {{ $subscription->expires_at?->format('j M Y') ?? 'No expiry' }}
                    </div>
                @endif
            </td>
            <td class="right">
                {{ $plan?->duration_days ? $plan->duration_days.' days' : 'Lifetime' }}
            </td>
            <td class="right">
                {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
            </td>
        </tr>
    </tbody>
</table>

<table class="totals">
    <tr>
        <td></td>
        <td class="right" style="width: 130px;">Subtotal</td>
        <td class="right" style="width: 110px;">
            {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
        </td>
    </tr>
    <tr class="grand">
        <td></td>
        <td class="right">Total paid</td>
        <td class="right">
            {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
        </td>
    </tr>
</table>

<div class="foot muted">
    <div>Razorpay payment ID: {{ $payment->razorpay_payment_id }}</div>
    {{-- Price is inclusive of tax, so no separate GST line is broken out. If
         that changes, the split belongs on the payment row at capture time —
         recomputing a historical tax rate here would silently restate old
         invoices. --}}
    <div>All amounts are inclusive of applicable taxes. This is a computer-generated invoice and needs no signature.</div>
    @if ($seller['email'])
        <div>Questions? {{ $seller['email'] }}</div>
    @endif
</div>

</body>
</html>
