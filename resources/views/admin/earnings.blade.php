@extends('layouts.app')
@section('title', 'Earnings')
@section('content')
<x-page-head title="Investor earnings" sub="Profit credited to investors, one row per distribution." />
<div class="card"><div class="tbl-wrap"><table>
    <thead><tr><th>Credited</th><th>Investor</th><th>Period</th><th class="num">Amount</th></tr></thead>
    <tbody>
    @forelse ($earnings as $e)
        <tr><td class="muted small">{{ $e->created_at->format('d M Y H:i') }}</td><td>{{ $e->user?->name }}</td><td>{{ sprintf('%04d-%02d', $e->year, $e->month) }}</td><td><x-money :value="$e->amount" /></td></tr>
    @empty <tr><td colspan="4"><x-empty /></td></tr> @endforelse
    </tbody></table></div>{{ $earnings->links() }}</div>
@endsection
