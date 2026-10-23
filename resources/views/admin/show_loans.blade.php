@extends('layouts.app')
@section('title', 'Loan')
@section('content')
<x-page-head :title="ucfirst($type->value).' loan'" :sub="$loan->user?->name.' · '.$loan->loanProject?->property?->name">
    <a class="btn ghost" href="{{ route('admin.loans.index', $type->value) }}">← All {{ $type->value }} loans</a>
</x-page-head>
<div class="grid g2" style="margin-bottom:18px">
    <div class="card"><div class="card-h"><h2>Terms</h2><x-status :value="$loan->status" /></div><div class="card-b"><dl class="kv">
        <dt>Property value</dt><dd><x-money :value="$loan->property_value" /></dd>
        <dt>Initial payment</dt><dd><x-money :value="$loan->initial_payment" /></dd>
        <dt>Financed amount</dt><dd><x-money :value="$loan->loan_amount" /></dd>
        <dt>Interest (flat)</dt><dd>{{ $loan->interest_rate }}%</dd>
        <dt>Duration</dt><dd>{{ $loan->duration_months }} months</dd>
        <dt>Total payable</dt><dd><x-money :value="$loan->total_payable" /></dd>
        <dt>Monthly installment</dt><dd><x-money :value="$loan->monthly_payment" /></dd>
    </dl></div></div>
    <div class="card"><div class="card-h"><h2>Progress</h2></div><div class="card-b">
        @php $pct = (float) bcmul(bcdiv((string) $loan->total_paid, (string) $loan->total_payable ?: '1', 4), '100', 2); @endphp
        <div class="bar"><i style="width:{{ min(100, $pct) }}%"></i></div>
        <dl class="kv" style="margin-top:14px">
            <dt>Paid</dt><dd><x-money :value="$loan->total_paid" /></dd>
            <dt>Remaining</dt><dd><x-money :value="$loan->balance_remaining" /></dd>
            <dt>Start</dt><dd>{{ $loan->start_date?->format('d M Y') ?? '—' }}</dd>
            <dt>End</dt><dd>{{ $loan->end_date?->format('d M Y') ?? '—' }}</dd>
            <dt>Approved by</dt><dd>{{ $loan->approver?->name ?? '—' }}</dd>
        </dl>
        @if (auth()->user()->isAdmin() && $loan->status === \App\Enums\LoanStatus::Pending)
            <div class="row-actions" style="margin-top:16px;justify-content:flex-start">
                <form method="POST" action="{{ route('admin.loans.approve', [$type->value, $loan->id]) }}" data-confirm="Approve this loan?">@csrf<button class="btn" type="submit">Approve</button></form>
                <form method="POST" action="{{ route('admin.loans.reject', [$type->value, $loan->id]) }}" data-confirm="Reject and refund the initial payment?">@csrf<button class="btn danger" type="submit">Reject</button></form>
            </div>
        @endif
    </div></div>
</div>
<div class="card"><div class="card-h"><h2>Payments</h2></div><div class="tbl-wrap"><table>
    <thead><tr><th>Date</th><th>Period</th><th>Method</th><th>Status</th><th class="num">Amount</th></tr></thead>
    <tbody>@forelse ($loan->payments as $p)<tr><td>{{ $p->payment_date->format('d M Y') }}</td><td>{{ $p->period }}</td><td>{{ $p->method }}</td><td><x-status :value="$p->status" /></td><td><x-money :value="$p->amount" /></td></tr>
    @empty <tr><td colspan="5"><x-empty>No payments yet.</x-empty></td></tr>@endforelse</tbody></table></div></div>
@endsection
