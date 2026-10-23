@extends('layouts.app')
@section('title', $property->name)
@section('content')
<x-page-head :title="$property->name" :sub="$property->location"><a class="btn ghost" href="{{ route('admin.properties.edit', $property) }}">Edit</a></x-page-head>
<div class="grid g2">
    <div class="card"><div class="card-h"><h2>Details</h2></div><div class="card-b">
        <dl class="kv"><dt>Type</dt><dd>{{ ucfirst($property->type) }}</dd><dt>Status</dt><dd><x-status :value="$property->status" /></dd><dt>Purchase price</dt><dd><x-money :value="$property->purchase_price" /></dd></dl>
        @if ($property->description)<p class="muted" style="margin-top:14px">{{ $property->description }}</p>@endif
    </div></div>
    <div class="card"><div class="card-h"><h2>Funding projects</h2></div><div class="card-b flush">
        @forelse ($projects as $p)
            <div style="padding:12px 18px;border-bottom:1px solid #efe9da">
                <div style="display:flex;justify-content:space-between"><span><x-money :value="$p->total_raised" /> of <x-money :value="$p->target_amount" /></span><x-status :value="$p->status" /></div>
                <div class="bar" style="margin-top:8px"><i style="width:{{ min(100, (float) bcmul(bcdiv($p->total_raised, $p->target_amount ?: 1, 4), '100', 2)) }}%"></i></div>
                <div class="muted small" style="margin-top:4px">{{ $p->investments_count }} investors</div>
            </div>
        @empty <x-empty>No investment project.</x-empty> @endforelse
    </div></div>
</div>
<div class="card" style="margin-top:18px"><div class="card-h"><h2>Loan products</h2></div><div class="tbl-wrap"><table>
    <thead><tr><th>Type</th><th class="num">Value</th><th class="num">Initial payment</th><th class="num">Rate</th><th class="num">Months</th><th>Status</th></tr></thead>
    <tbody>@forelse ($loanProducts as $l)<tr><td>{{ ucfirst($l->loan_type->value) }}</td><td><x-money :value="$l->property_value" /></td><td><x-money :value="$l->min_initial_payment" /></td><td class="num">{{ $l->interest_rate }}%</td><td class="num">{{ $l->duration_months }}</td><td><x-status :value="$l->status" /></td></tr>
    @empty <tr><td colspan="6"><x-empty /></td></tr>@endforelse</tbody></table></div></div>
@endsection
