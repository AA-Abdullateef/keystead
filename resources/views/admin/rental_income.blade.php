@extends('layouts.app')
@section('title', 'Rental income')
@section('content')
<x-page-head title="Rental income" sub="Entered per project and month. A period locks once its profit has been distributed." />
<div class="grid split">
    <div class="card">
        <form class="filters" method="GET" action="{{ route('admin.rental.index') }}">
            <div class="field"><label>Project</label><select name="project"><option value="">All</option>@foreach ($projects as $p)<option value="{{ $p->id }}" @selected(request('project') === $p->id)>{{ $p->property->name }}</option>@endforeach</select></div>
            <button class="btn ghost" type="submit">Filter</button>
        </form>
        <div class="tbl-wrap"><table>
            <thead><tr><th>Period</th><th>Property</th><th class="num">Amount</th><th></th></tr></thead>
            <tbody>
            @forelse ($records as $r)
                <tr><td>{{ sprintf('%04d-%02d', $r->year, $r->month) }}</td><td>{{ $r->project->property->name }}</td><td><x-money :value="$r->amount" /></td>
                    <td><div class="row-actions"><a class="btn ghost sm" href="{{ route('admin.rental.edit', $r) }}">Edit</a>
                    <form method="POST" action="{{ route('admin.rental.destroy', $r) }}" data-confirm="Remove this record?">@csrf @method('DELETE')<button class="btn danger sm" type="submit">Delete</button></form></div></td></tr>
            @empty <tr><td colspan="5"><x-empty /></td></tr> @endforelse
            </tbody></table></div>
        {{ $records->links() }}
    </div>
    <div class="card">
        <div class="card-h"><h2>{{ $edit ? 'Edit entry' : 'New entry' }}</h2>@if($edit)<a class="small" href="{{ route('admin.rental.index') }}">Cancel</a>@endif</div>
        <div class="card-b">
            <form method="POST" action="{{ $edit ? route('admin.rental.update', $edit) : route('admin.rental.store') }}">@csrf @if($edit) @method('PUT') @endif
                @if ($edit)
                    <p class="small muted">{{ $edit->project->property->name }} · {{ sprintf('%04d-%02d', $edit->year, $edit->month) }} (period cannot be changed)</p>
                @else
                    <x-field name="investment_project_id" label="Project" type="select" required><option value="">Select…</option>@foreach ($projects as $p)<option value="{{ $p->id }}" @selected(old('investment_project_id') === $p->id)>{{ $p->property->name }}</option>@endforeach</x-field>
                    <div class="form-row">
                        <x-field name="month" label="Month" type="number" min="1" max="12" :value="now()->subMonth()->format('n')" required />
                        <x-field name="year" label="Year" type="number" :value="now()->subMonth()->format('Y')" required />
                    </div>
                @endif
                
                <x-field name="amount" label="Amount" type="number" step="0.01" :value="$edit?->amount" required />
                
                <button class="btn" type="submit">{{ $edit ? 'Save changes' : 'Record' }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
