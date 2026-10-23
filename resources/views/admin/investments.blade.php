@extends('layouts.app')
@section('title', 'Investments')
@section('content')
<x-page-head title="Projects & investors" sub="Funding projects and the contributions behind them." />
@php $admin = auth()->user()->isAdmin(); @endphp

<div class="grid g3" style="margin-bottom:18px">
@forelse ($projects as $p)
    @php $pct = (float) bcmul(bcdiv((string) $p->total_raised, (string) $p->target_amount, 4), '100', 2); @endphp
    <div class="card"><div class="card-b">
        <div style="display:flex;justify-content:space-between;gap:8px"><h3>{{ $p->property->name }}</h3><x-status :value="$p->status" /></div>
        <div class="muted small" style="margin:2px 0 12px">{{ $p->property->location }}</div>
        <div class="bar"><i style="width:{{ min(100, $pct) }}%"></i></div>
        <div style="display:flex;justify-content:space-between;margin:6px 0 12px" class="small"><span><x-money :value="$p->total_raised" /></span><span class="muted">{{ $pct }}% of <x-money :value="$p->target_amount" /></span></div>
        <dl class="kv small"><dt>Minimum</dt><dd><x-money :value="$p->min_investment" /></dd><dt>Management fee</dt><dd>{{ $p->management_fee_percent }}%</dd><dt>Investors</dt><dd>{{ $p->investments_count }}</dd></dl>
        <div class="row-actions" style="margin-top:12px;justify-content:flex-start">
            <a class="btn ghost sm" href="{{ route('admin.investments.projects.edit', $p) }}">Edit</a>
            @if ($admin && $p->investments_count === 0)
                <form method="POST" action="{{ route('admin.investments.projects.destroy', $p) }}" data-confirm="Delete this project?">@csrf @method('DELETE')<button class="btn danger sm" type="submit">Delete</button></form>
            @endif
        </div>
    </div></div>
@empty
    <div class="card" style="grid-column:1/-1"><x-empty>No projects yet — create one below.</x-empty></div>
@endforelse
</div>

<div class="grid split" style="margin-bottom:18px">
    <div class="card">
        <div class="card-h"><h2>Investor positions</h2></div>
        <div class="tbl-wrap"><table>
            <thead><tr><th>Investor</th><th>Property</th><th class="num">Invested</th><th class="num">Ownership</th><th></th></tr></thead>
            <tbody>
            @forelse ($investments as $i)
                <tr><td>{{ $i->user->name }}</td><td>{{ $i->project->property->name }}</td><td><x-money :value="$i->total_invested" /></td><td class="num">{{ rtrim(rtrim($i->ownership_percent, '0'), '.') }}%</td>
                    <td><div class="row-actions">
                    @if ($admin)
                        <a class="btn ghost sm" href="{{ route('admin.investments.investors.edit', $i) }}">Top up</a>
                        <form method="POST" action="{{ route('admin.investments.investors.destroy', $i) }}" data-confirm="Reverse this investment and refund the wallet?">@csrf @method('DELETE')<button class="btn danger sm" type="submit">Reverse</button></form>
                    @endif
                    </div></td></tr>
            @empty <tr><td colspan="5"><x-empty /></td></tr> @endforelse
            </tbody></table></div>
        {{ $investments->links() }}
    </div>

    <div class="grid">
        <div class="card">
            <div class="card-h"><h2>{{ $editProject ? 'Edit project' : 'New project' }}</h2>@if($editProject)<a class="small" href="{{ route('admin.investments.index') }}">Cancel</a>@endif</div>
            <div class="card-b">
                <form method="POST" action="{{ $editProject ? route('admin.investments.projects.update', $editProject) : route('admin.investments.projects.store') }}">@csrf @if($editProject) @method('PUT') @endif
                    @if ($editProject)
                        <p class="small muted">Property: <strong>{{ $editProject->property->name }}</strong></p>
                        @if ($editProject->investments_count ?? $editProject->investments()->exists())<div class="callout" style="margin-bottom:12px">Terms are frozen once investors have contributed.</div>@endif
                        <x-field name="status" label="Status" type="select" required>
                            <option value="{{ $editProject->status->value }}" selected>{{ ucfirst($editProject->status->value) }} (current)</option>
                            @foreach ($editProject->status->allowedNext() as $s)<option value="{{ $s->value }}">{{ ucfirst($s->value) }}</option>@endforeach
                        </x-field>
                    @else
                        <x-field name="property_id" label="Property" type="select" required>
                            <option value="">Select…</option>@foreach ($properties as $pr)<option value="{{ $pr->id }}" @selected(old('property_id') === $pr->id)>{{ $pr->name }}</option>@endforeach
                        </x-field>
                    @endif
                    <div class="form-row">
                        <x-field name="target_amount" label="Funding target" type="number" step="0.01" :value="$editProject?->target_amount" required />
                        <x-field name="min_investment" label="Minimum" type="number" step="0.01" :value="$editProject?->min_investment" required />
                    </div>
                    <div class="form-row">
                        <x-field name="management_fee_percent" label="Mgmt fee %" type="number" step="0.01" :value="$editProject?->management_fee_percent" required />
                        <x-field name="expected_roi" label="Expected ROI %" type="number" step="0.01" :value="$editProject?->expected_roi" hint="Informational only; never a guarantee." />
                    </div>
                    <div class="form-row">
                        <x-field name="start_date" label="Start" type="date" :value="$editProject?->start_date?->format('Y-m-d')" required />
                        <x-field name="end_date" label="End" type="date" :value="$editProject?->end_date?->format('Y-m-d')" />
                    </div>
                    <button class="btn" type="submit">{{ $editProject ? 'Save project' : 'Create project' }}</button>
                </form>
            </div>
        </div>

        @if ($admin)
        <div class="card">
            <div class="card-h"><h2>{{ $editInvestment ? 'Top up position' : 'Invest on behalf' }}</h2>@if($editInvestment)<a class="small" href="{{ route('admin.investments.index') }}">Cancel</a>@endif</div>
            <div class="card-b">
                @if ($editInvestment)
                    <p class="small muted">{{ $editInvestment->user->name }} · {{ $editInvestment->project->property->name }}<br>Currently <x-money :value="$editInvestment->total_invested" />. The difference is debited from their wallet.</p>
                    <form method="POST" action="{{ route('admin.investments.investors.update', $editInvestment) }}" data-confirm="Debit the investor wallet?">@csrf @method('PUT')
                        <x-field name="total_invested" label="New total" type="number" step="0.01" required />
                        <button class="btn" type="submit">Update</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.investments.investors.store') }}" data-confirm="Debit the investor wallet?">@csrf
                        <x-field name="user_id" label="Investor" type="select" required><option value="">Select…</option>@foreach ($investors as $u)<option value="{{ $u->id }}" @selected(old('user_id') === $u->id)>{{ $u->name }}</option>@endforeach</x-field>
                        <x-field name="investment_project_id" label="Project" type="select" required><option value="">Select…</option>@foreach ($projects->where('status', \App\Enums\ProjectStatus::Open) as $p)<option value="{{ $p->id }}" @selected(old('investment_project_id') === $p->id)>{{ $p->property->name }}</option>@endforeach</x-field>
                        <x-field name="amount" label="Amount" type="number" step="0.01" required />
                        <button class="btn" type="submit">Record contribution</button>
                    </form>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
