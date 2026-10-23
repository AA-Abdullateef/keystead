@extends('layouts.app')
@section('title', 'Ledger')
@section('content')
<x-page-head title="Ledger" sub="Append-only record of every wallet movement." />
<div class="card">
    <form class="filters" method="GET" action="{{ route('admin.transactions.index') }}">
        <div class="field"><label>Type</label><select name="type"><option value="">All</option>@foreach ($types as $t)<option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ str_replace('_', ' ', $t->value) }}</option>@endforeach</select></div>
        <div class="field"><label>Status</label><select name="status"><option value="">All</option>@foreach (['pending','completed','rejected','failed'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
        <div class="field"><label>Investor</label><select name="user_id"><option value="">All</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected(request('user_id') === $u->id)>{{ $u->name }}</option>@endforeach</select></div>
        <button class="btn ghost" type="submit">Filter</button>
    </form>
    <div class="tbl-wrap"><table>
        <thead><tr><th>When</th><th>Investor</th><th>Type</th><th>Status</th><th class="num">Amount</th><th class="num">Balance after</th><th></th></tr></thead>
        <tbody>
        @forelse ($transactions as $t)
            <tr><td class="muted small">{{ $t->created_at->format('d M Y H:i') }}</td><td>{{ $t->user?->name }}</td><td><x-status :value="$t->type" /></td><td><x-status :value="$t->status" /></td>
                <td class="num {{ $t->direction->value === 'credit' ? 'pos' : 'neg' }}">{{ $t->direction->value === 'credit' ? '+' : '−' }}<x-money :value="$t->amount" /></td><td><x-money :value="$t->balance_after" /></td>
                <td><a class="btn ghost sm" href="{{ route('admin.transactions.show', $t->id) }}">Open</a></td></tr>
        @empty <tr><td colspan="7"><x-empty /></td></tr> @endforelse
        </tbody></table></div>{{ $transactions->links() }}
</div>
@endsection
