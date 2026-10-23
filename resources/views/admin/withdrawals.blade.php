@extends('layouts.app')
@section('title', 'Withdrawals')
@section('content')
<x-page-head title="Withdrawal requests" sub="Funds are reserved when requested. Approving completes the payout; rejecting returns them to the wallet." />
@php $admin = auth()->user()->isAdmin(); @endphp
<div class="card">
    <form class="filters" method="GET" action="{{ route('admin.withdrawals.index') }}">
        <div class="field"><label>Status</label><select name="status"><option value="">All</option>@foreach (['pending','completed','rejected'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
        <button class="btn ghost" type="submit">Filter</button>
    </form>
    <div class="tbl-wrap"><table>
        <thead><tr><th>Requested</th><th>Investor</th><th>Status</th><th class="num">Amount</th><th></th></tr></thead>
        <tbody>
        @forelse ($withdrawals as $w)
            <tr><td class="muted small">{{ $w->created_at->format('d M Y H:i') }}</td><td>{{ $w->user?->name }}</td><td><x-status :value="$w->status" /></td><td><x-money :value="$w->amount" /></td>
                <td><div class="row-actions">
                @if ($admin && $w->status === \App\Enums\TransactionStatus::Pending)
                    <form method="POST" action="{{ route('admin.withdrawals.approve', $w->id) }}" data-confirm="Approve this withdrawal?">@csrf<button class="btn sm" type="submit">Approve</button></form>
                    <form method="POST" action="{{ route('admin.withdrawals.reject', $w->id) }}" data-confirm="Reject and refund the wallet?">@csrf<button class="btn danger sm" type="submit">Reject</button></form>
                @endif</div></td></tr>
        @empty <tr><td colspan="5"><x-empty /></td></tr> @endforelse
        </tbody></table></div>{{ $withdrawals->links() }}
</div>
@endsection
