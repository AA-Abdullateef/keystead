@extends('layouts.app')
@section('title', $user->name.' — finances')
@section('content')
<x-page-head :title="$user->name" :sub="$user->email">
    <a class="btn ghost" href="{{ route('admin.user.profile', $user) }}">Personal profile</a>
</x-page-head>
<div class="grid g4" style="margin-bottom:18px">
    <div class="card stat accent"><div class="k">Wallet</div><div class="v"><x-money :value="$wallet->balance" /></div></div>
    <div class="card stat"><div class="k">Invested</div><div class="v"><x-money :value="$portfolio['total_invested']" /></div></div>
    <div class="card stat"><div class="k">Profit earned</div><div class="v"><x-money :value="$portfolio['total_earnings']" /></div></div>
    <div class="card stat"><div class="k">Loan balance</div><div class="v"><x-money :value="$loans['balance_remaining']" /></div><div class="s">paid <x-money :value="$loans['total_paid']" /></div></div>
</div>

<div class="card" style="margin-bottom:18px">
    <div class="card-h"><h2>Investments</h2></div>
    <div class="tbl-wrap"><table>
        <thead><tr><th>Property</th><th>Status</th><th class="num">Invested</th><th class="num">Ownership</th></tr></thead>
        <tbody>
        @forelse ($investments as $i)
            <tr><td>{{ $i->project->property->name }}</td><td><x-status :value="$i->project->status" /></td><td><x-money :value="$i->total_invested" /></td><td class="num">{{ rtrim(rtrim($i->ownership_percent, '0'), '.') }}%</td></tr>
        @empty <tr><td colspan="4"><x-empty /></td></tr> @endforelse
        </tbody></table></div>
</div>

<div class="card" style="margin-bottom:18px">
    <div class="card-h"><h2>Loans</h2></div>
    <div class="tbl-wrap"><table>
        <thead><tr><th>Type</th><th>Property</th><th>Status</th><th class="num">Paid</th><th class="num">Remaining</th><th></th></tr></thead>
        <tbody>
        @forelse ($mortgage_loans->concat($property_loans) as $l)
            <tr><td>{{ ucfirst($l->loanType()->value) }}</td><td>{{ $l->loanProject?->property?->name }}</td><td><x-status :value="$l->status" /></td><td><x-money :value="$l->total_paid" /></td><td><x-money :value="$l->balance_remaining" /></td>
                <td><a class="btn ghost sm" href="{{ route('admin.loans.show', [$l->loanType(), $l->id]) }}">Open</a></td></tr>
        @empty <tr><td colspan="6"><x-empty /></td></tr> @endforelse
        </tbody></table></div>
</div>

<div class="card">
    <div class="card-h"><h2>Recent ledger entries</h2><a class="small" href="{{ route('admin.transactions.index', ['user_id' => $user->id]) }}">All →</a></div>
    <div class="tbl-wrap"><table>
        <thead><tr><th>When</th><th>Type</th><th>Status</th><th class="num">Amount</th><th class="num">Balance after</th></tr></thead>
        <tbody>
        @forelse ($transactions as $t)
            <tr><td class="muted small">{{ $t->created_at->format('d M Y H:i') }}</td><td><x-status :value="$t->type" /></td><td><x-status :value="$t->status" /></td>
                <td class="num {{ $t->direction->value === 'credit' ? 'pos' : 'neg' }}">{{ $t->direction->value === 'credit' ? '+' : '−' }}<x-money :value="$t->amount" /></td><td><x-money :value="$t->balance_after" /></td></tr>
        @empty <tr><td colspan="5"><x-empty /></td></tr> @endforelse
        </tbody></table></div>
</div>
@endsection
