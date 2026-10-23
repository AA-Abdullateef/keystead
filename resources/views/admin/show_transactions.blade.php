@extends('layouts.app')
@section('title', 'Ledger entry')
@section('content')
<x-page-head title="Ledger entry" :sub="$transaction->id"><a class="btn ghost" href="{{ route('admin.transactions.index') }}">← Ledger</a></x-page-head>
<div class="card" style="max-width:640px"><div class="card-b"><dl class="kv">
    <dt>Investor</dt><dd>{{ $transaction->user?->name }}</dd>
    <dt>Type</dt><dd><x-status :value="$transaction->type" /></dd>
    <dt>Status</dt><dd><x-status :value="$transaction->status" /></dd>
    <dt>Direction</dt><dd><x-status :value="$transaction->direction" /></dd>
    <dt>Amount</dt><dd><x-money :value="$transaction->amount" /></dd>
    <dt>Balance after</dt><dd><x-money :value="$transaction->balance_after" /></dd>
    <dt>Reference</dt><dd class="mono">{{ $transaction->reference_type }} · {{ $transaction->reference_id }}</dd>
    <dt>Description</dt><dd>{{ $transaction->description ?: '—' }}</dd>
    <dt>Recorded</dt><dd>{{ $transaction->created_at->format('d M Y H:i:s') }}</dd>
</dl></div></div>
@endsection
