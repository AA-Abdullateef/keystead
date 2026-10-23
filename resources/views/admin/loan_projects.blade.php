@extends('layouts.app')
@section('title', 'Loan products')
@section('content')
<x-page-head title="Loan products" sub="Terms borrowers apply against. Flat interest on the financed amount, repaid in equal monthly installments." />
<div class="grid split">
    <div class="card">
        <form class="filters" method="GET" action="{{ route('admin.loan.projects') }}">
            <div class="field"><label>Type</label><select name="type"><option value="">All</option>@foreach (['mortgage','property'] as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
            <button class="btn ghost" type="submit">Filter</button>
        </form>
        <div class="tbl-wrap"><table>
            <thead><tr><th>Property</th><th>Type</th><th class="num">Value</th><th class="num">Initial</th><th class="num">Rate</th><th class="num">Months</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($products as $p)
                <tr><td>{{ $p->property->name }}</td><td>{{ ucfirst($p->loan_type->value) }}</td><td><x-money :value="$p->property_value" /></td><td><x-money :value="$p->min_initial_payment" /></td><td class="num">{{ $p->interest_rate }}%</td><td class="num">{{ $p->duration_months }}</td><td><x-status :value="$p->status" /></td>
                    <td><a class="btn ghost sm" href="{{ route('admin.loan.projects.edit', $p) }}">Edit</a></td></tr>
            @empty <tr><td colspan="8"><x-empty /></td></tr> @endforelse
            </tbody></table></div>
    </div>
    <div class="card">
        <div class="card-h"><h2>{{ $edit ? 'Edit product' : 'New product' }}</h2>@if($edit)<a class="small" href="{{ route('admin.loan.projects') }}">Cancel</a>@endif</div>
        <div class="card-b">
            <form method="POST" action="{{ $edit ? route('admin.loan.projects.update', $edit) : route('admin.loan.projects.store') }}">@csrf @if($edit) @method('PUT') @endif
                @if ($edit)
                    <p class="small muted">{{ $edit->property->name }} · {{ ucfirst($edit->loan_type->value) }}. @if ($edit->loans()->exists())Applications exist, so only the status can change.@endif</p>
                @else
                    <x-field name="property_id" label="Property" type="select" required><option value="">Select…</option>@foreach ($properties as $pr)<option value="{{ $pr->id }}" @selected(old('property_id') === $pr->id)>{{ $pr->name }}</option>@endforeach</x-field>
                    <x-field name="loan_type" label="Loan type" type="select" required>@foreach (['mortgage','property'] as $t)<option value="{{ $t }}" @selected(old('loan_type') === $t)>{{ ucfirst($t) }}</option>@endforeach</x-field>
                @endif
                <x-field name="property_value" label="Property value" type="number" step="0.01" :value="$edit?->property_value" required />
                <x-field name="min_initial_payment" label="Initial payment" type="number" step="0.01" :value="$edit?->min_initial_payment" required hint="Borrowers must pay exactly this amount at application." />
                <div class="form-row">
                    <x-field name="interest_rate" label="Interest %" type="number" step="0.01" :value="$edit?->interest_rate" required />
                    <x-field name="duration_months" label="Months" type="number" :value="$edit?->duration_months" required />
                </div>
                <x-field name="status" label="Status" type="select" required>@foreach (['active','inactive'] as $s)<option value="{{ $s }}" @selected(old('status', $edit?->status ?? 'active') === $s)>{{ ucfirst($s) }}</option>@endforeach</x-field>
                <button class="btn" type="submit">{{ $edit ? 'Save product' : 'Create product' }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
