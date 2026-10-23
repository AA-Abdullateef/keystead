@extends('layouts.app')
@section('title', 'Properties')
@section('content')
<x-page-head title="Properties" sub="The asset register. Investment properties feed funding projects; loan properties feed loan products." />
<div class="grid split">
    <div class="card">
        <form class="filters" method="GET" action="{{ route('admin.properties.index') }}">
            <div class="field"><label>Type</label><select name="type"><option value="">All</option>@foreach (['investment','loan'] as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
            <button class="btn ghost" type="submit">Filter</button>
        </form>
        <div class="tbl-wrap"><table>
            <thead><tr><th>Property</th><th>Type</th><th>Status</th><th class="num">Purchase price</th><th></th></tr></thead>
            <tbody>
            @forelse ($properties as $p)
                <tr><td><a href="{{ route('admin.properties.show', $p) }}"><strong>{{ $p->name }}</strong></a><div class="muted small">{{ $p->location }}</div></td>
                    <td>{{ ucfirst($p->type) }}</td><td><x-status :value="$p->status" /></td><td><x-money :value="$p->purchase_price" /></td>
                    <td><div class="row-actions"><a class="btn ghost sm" href="{{ route('admin.properties.edit', $p) }}">Edit</a></div></td></tr>
            @empty <tr><td colspan="5"><x-empty /></td></tr> @endforelse
            </tbody></table></div>
        {{ $properties->links() }}
    </div>
    <div class="card">
        <div class="card-h"><h2>{{ $property ? 'Edit property' : 'New property' }}</h2>@if($property)<a class="small" href="{{ route('admin.properties.index') }}">Cancel</a>@endif</div>
        <div class="card-b">
            <form method="POST" action="{{ $property ? route('admin.properties.update', $property) : route('admin.properties.store') }}">@csrf @if($property) @method('PUT') @endif
                <x-field name="name" label="Name" :value="$property?->name" required />
                <x-field name="type" label="Type" type="select" required>@foreach (['investment','loan'] as $t)<option value="{{ $t }}" @selected(old('type', $property?->type) === $t)>{{ ucfirst($t) }}</option>@endforeach</x-field>
                <x-field name="location" label="Location" :value="$property?->location" required />
                <x-field name="purchase_price" label="Purchase price" type="number" step="0.01" :value="$property?->purchase_price" />
                <x-field name="status" label="Status" type="select" required>@foreach (['available','active','completed'] as $t)<option value="{{ $t }}" @selected(old('status', $property?->status ?? 'available') === $t)>{{ ucfirst($t) }}</option>@endforeach</x-field>
                <x-field name="description" label="Description" type="textarea" :value="$property?->description" />
                <button class="btn" type="submit">{{ $property ? 'Save changes' : 'Create property' }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
