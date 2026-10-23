@extends('layouts.app')
@section('title', ucfirst($type->value).' loans')
@section('content')
<x-page-head :title="ucfirst($type->value).' loans'" sub="Applications and active loans. Approving keeps the initial payment; rejecting refunds it.">
    <a class="btn ghost" href="{{ route('admin.loans.index', $type->value === 'mortgage' ? 'property' : 'mortgage') }}">Switch to {{ $type->value === 'mortgage' ? 'property' : 'mortgage' }} loans</a>
</x-page-head>
@php $admin = auth()->user()->isAdmin(); @endphp
<div class="card">
    <form class="filters" method="GET" action="{{ route('admin.loans.index', $type->value) }}">
        <div class="field"><label>Status</label><select name="status"><option value="">All</option>@foreach ($statuses as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ ucfirst($s->value) }}</option>@endforeach</select></div>
        <button class="btn ghost" type="submit">Filter</button>
    </form>
    <div class="tbl-wrap"><table>
        <thead><tr><th>Borrower</th><th>Property</th><th>Status</th><th class="num">Loan</th><th class="num">Monthly</th><th class="num">Remaining</th><th></th></tr></thead>
        <tbody>
        @forelse ($loans as $l)
            <tr><td>{{ $l->user?->name }}<div class="muted small">{{ $l->created_at->format('d M Y') }}</div></td><td>{{ $l->loanProject?->property?->name }}</td><td><x-status :value="$l->status" /></td>
                <td><x-money :value="$l->loan_amount" /></td><td><x-money :value="$l->monthly_payment" /></td><td><x-money :value="$l->balance_remaining" /></td>
                <td><div class="row-actions"><a class="btn ghost sm" href="{{ route('admin.loans.show', [$type->value, $l->id]) }}">Open</a>
                @if ($admin && $l->status === \App\Enums\LoanStatus::Pending)
                    <form method="POST" action="{{ route('admin.loans.approve', [$type->value, $l->id]) }}" data-confirm="Approve this loan?">@csrf<button class="btn sm" type="submit">Approve</button></form>
                    <form method="POST" action="{{ route('admin.loans.reject', [$type->value, $l->id]) }}" data-confirm="Reject and refund the initial payment?">@csrf<button class="btn danger sm" type="submit">Reject</button></form>
                @endif</div></td></tr>
        @empty <tr><td colspan="7"><x-empty /></td></tr> @endforelse
        </tbody></table></div>{{ $loans->links() }}
</div>
@endsection
