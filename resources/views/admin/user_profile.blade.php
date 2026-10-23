@extends('layouts.app')
@section('title', $user->name.' — profile')
@section('content')
<x-page-head :title="$user->name" :sub="$user->email">
    <a class="btn ghost" href="{{ route('admin.users.financial.profile', $user) }}">Financial profile</a>
</x-page-head>
@php $p = $user->profile; @endphp
<div class="card" style="max-width:760px">
    <div class="card-h"><h2>Personal details</h2></div>
    <div class="card-b">
        <form method="POST" action="{{ route('admin.user.profile.update', $user) }}">@csrf @method('PUT')
            <div class="form-row">
                <x-field name="first_name" label="First name" :value="$p?->first_name" required />
                <x-field name="last_name" label="Last name" :value="$p?->last_name" required />
            </div>
            <div class="form-row">
                <x-field name="gender" label="Gender" type="select">
                    <option value="">—</option>
                    @foreach (['male','female','other'] as $g)<option value="{{ $g }}" @selected(old('gender', $p?->gender) === $g)>{{ ucfirst($g) }}</option>@endforeach
                </x-field>
                <x-field name="dob" label="Date of birth" type="date" :value="$p?->dob?->format('Y-m-d')" />
                <x-field name="phone" label="Phone" :value="$p?->phone" />
            </div>
            <x-field name="occupation" label="Occupation" :value="$p?->occupation" />
            <x-field name="address" label="Address" type="textarea" :value="$p?->address" />
            <div class="form-row">
                <x-field name="city" label="City" :value="$p?->city" />
                <div class="field"><label for="country_id">Country</label>
                    <select id="country_id" name="country_id" data-url="{{ route('admin.country.states', '__ID__') }}">
                        <option value="">—</option>
                        @foreach ($countries as $c)<option value="{{ $c->id }}" @selected(old('country_id', $p?->country_id) === $c->id)>{{ $c->name }}</option>@endforeach
                    </select>@error('country_id')<div class="err">{{ $message }}</div>@enderror</div>
                <div class="field"><label for="state_id">State / region</label>
                    <select id="state_id" name="state_id"><option value="">—</option>
                        @foreach ($states as $s)<option value="{{ $s->id }}" @selected(old('state_id', $p?->state_id) === $s->id)>{{ $s->name }}</option>@endforeach
                    </select>@error('state_id')<div class="err">{{ $message }}</div>@enderror</div>
            </div>
            <button class="btn" type="submit">Save profile</button>
        </form>
    </div>
</div>
@endsection
