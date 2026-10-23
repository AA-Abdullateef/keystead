@extends('layouts.app')
@section('title', 'Overview')
@section('content')
<x-page-head title="Overview" sub="Where the book stands today." />

<div class="grid g4" style="margin-bottom:18px">
    <div class="card stat accent"><div class="k">Capital raised</div><div class="v"><x-money :value="$raised" /></div><div class="s">{{ $openProjects }} of {{ $projects }} projects open</div></div>
    <div class="card stat"><div class="k">Investor wallets</div><div class="v"><x-money :value="$walletTotal" /></div><div class="s">{{ $investors }} investors · {{ $investments }} positions</div></div>
    <div class="card stat"><div class="k">Collected this month</div><div class="v"><x-money :value="$collectedThisMonth" /></div><div class="s">loan installments</div></div>
    <div class="card stat"><div class="k">Pending withdrawals</div><div class="v">{{ $pendingWithdrawals }}</div><div class="s"><a href="{{ route('admin.withdrawals.index', ['status' => 'pending']) }}">Review queue →</a></div></div>
</div>

<div class="grid g2" style="margin-bottom:18px">
    @foreach (['mortgage' => $mortgage, 'property' => $property] as $label => $b)
    <div class="card">
        <div class="card-h"><h2>{{ ucfirst($label) }} loans</h2><a class="small" href="{{ route('admin.loans.index', $label) }}">View all →</a></div>
        <div class="card-b">
            <dl class="kv">
                <dt>Applications</dt><dd>{{ $b['count'] }}</dd>
                <dt>Awaiting decision</dt><dd>{{ $b['pending'] }}</dd>
                <dt>Active / overdue</dt><dd>{{ $b['active'] }} / <span class="{{ $b['overdue'] ? 'neg' : '' }}">{{ $b['overdue'] }}</span></dd>
                <dt>Principal financed</dt><dd><x-money :value="$b['principal']" /></dd>
                <dt>Outstanding balance</dt><dd><x-money :value="$b['outstanding']" /></dd>
            </dl>
        </div>
    </div>
    @endforeach
</div>

<div class="grid split">
    <div class="card">
        <div class="card-h"><h2>Latest ledger activity</h2><a class="small" href="{{ route('admin.transactions.index') }}">Full ledger →</a></div>
        <div class="tbl-wrap card-b flush">
            <table>
                <thead><tr><th>When</th><th>Investor</th><th>Type</th><th class="num">Amount</th></tr></thead>
                <tbody>
                @forelse ($recentTransactions as $t)
                    <tr>
                        <td class="muted small">{{ $t->created_at->format('d M, H:i') }}</td>
                        <td>{{ $t->user?->name }}</td>
                        <td><x-status :value="$t->type" /></td>
                        <td class="num {{ $t->direction->value === 'credit' ? 'pos' : 'neg' }}">{{ $t->direction->value === 'credit' ? '+' : '−' }}<x-money :value="$t->amount" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-h"><h2>Recent loan applications</h2></div>
        <div class="card-b flush">
            @forelse ($recentLoans as $l)
                <div style="padding:12px 18px;border-bottom:1px solid #efe9da;display:flex;justify-content:space-between;gap:10px">
                    <div><a href="{{ route('admin.loans.show', [$l->loanType(), $l->id]) }}">{{ $l->user?->name }}</a>
                        <div class="muted small">{{ $l->loanProject?->property?->name }}</div></div>
                    <x-status :value="$l->status" />
                </div>
            @empty
                <x-empty />
            @endforelse
        </div>
    </div>
</div>
@endsection
