@extends('layouts.app')
@section('title', 'Profit distribution')
@section('content')
<x-page-head title="Profit distribution" sub="Preview first. Distribution credits each investor wallet through the ledger and cannot be repeated for a period." />
@php $admin = auth()->user()->isAdmin(); @endphp

<div class="card" style="margin-bottom:18px">
    <form class="filters" method="GET" action="{{ route('admin.profit.distribution') }}">
        <div class="field" style="flex:2"><label>Project</label>
            <select name="investment_project_id" required><option value="">Select…</option>
                @foreach ($projects as $p)<option value="{{ $p->id }}" @selected($project?->id === $p->id)>{{ $p->property->name }}</option>@endforeach</select></div>
        <div class="field"><label>Month</label><input type="number" name="month" min="1" max="12" value="{{ $month }}" required></div>
        <div class="field"><label>Year</label><input type="number" name="year" value="{{ $year }}" required></div>
        <button class="btn" type="submit" data-keep>Preview</button>
    </form>
</div>

@if ($project && $previewError)
    <div class="flash bad">{{ $previewError }}</div>
@elseif ($preview)
    @php $t = $preview['totals']; @endphp
    <div class="grid g4" style="margin-bottom:18px">
        <div class="card stat"><div class="k">Rental income</div><div class="v"><x-money :value="$t['total_income']" /></div></div>
        <div class="card stat"><div class="k">Expenses</div><div class="v"><x-money :value="$t['total_expenses']" /></div></div>
        <div class="card stat"><div class="k">Net profit</div><div class="v"><x-money :value="$t['net_profit']" /></div></div>
        <div class="card stat accent"><div class="k">Distributable</div><div class="v"><x-money :value="$t['distributable_profit']" /></div><div class="s">after {{ $t['management_fee_percent'] }}% fee (<x-money :value="$t['management_fee_amount']" />)</div></div>
    </div>
    <div class="card">
        <div class="card-h"><h2>{{ $project->property->name }} · {{ sprintf('%02d/%d', $month, $year) }}</h2>
            @if ($alreadyDistributed)<x-status value="distributed" />@endif</div>
        <div class="tbl-wrap"><table>
            <thead><tr><th>Investor</th><th class="num">Invested</th><th class="num">Share of target</th><th class="num">Payout</th></tr></thead>
            <tbody>
            @foreach ($preview['investors'] as $r)
                <tr><td>{{ $r['name'] }}</td><td><x-money :value="$r['invested']" /></td><td class="num">{{ rtrim(rtrim($r['share_percent'], '0'), '.') }}%</td><td><x-money :value="$r['amount']" /></td></tr>
            @endforeach
                <tr><td class="muted">{{ $preview['company']['name'] }}</td><td></td><td class="num muted">{{ rtrim(rtrim($preview['company']['share_percent'], '0'), '.') }}%</td><td><x-money :value="$preview['company']['amount']" /></td></tr>
            </tbody>
            <tfoot><tr><td colspan="3">Investors total</td><td><x-money :value="$t['investor_total']" /></td></tr></tfoot>
        </table></div>
        <div class="card-b">
            @if ($alreadyDistributed)
                <div class="callout">Profit for this period has already been distributed.</div>
            @elseif ($admin)
                <form method="POST" action="{{ route('admin.profit.distribute') }}" data-confirm="Credit investor wallets now? This cannot be undone.">@csrf
                    <input type="hidden" name="investment_project_id" value="{{ $project->id }}"><input type="hidden" name="month" value="{{ $month }}"><input type="hidden" name="year" value="{{ $year }}">
                    <button class="btn brass" type="submit">Distribute profit</button>
                </form>
            @else
                <div class="callout">Only administrators can distribute profit.</div>
            @endif
        </div>
    </div>
@endif

<div class="card" style="margin-top:18px">
    <div class="card-h"><h2>Recent distributions</h2></div>
    <div class="tbl-wrap"><table>
        <thead><tr><th>Period</th><th>Property</th><th class="num">Net</th><th class="num">Fee</th><th class="num">To investors</th><th class="num">Company</th></tr></thead>
        <tbody>
        @forelse ($history as $d)
            <tr><td>{{ sprintf('%04d-%02d', $d->year, $d->month) }}</td><td>{{ $d->project->property->name }}</td><td><x-money :value="$d->net_profit" /></td><td><x-money :value="$d->management_fee_amount" /></td><td><x-money :value="$d->investor_total" /></td><td><x-money :value="$d->company_share" /></td></tr>
        @empty <tr><td colspan="6"><x-empty /></td></tr> @endforelse
        </tbody></table></div>
</div>
@endsection
