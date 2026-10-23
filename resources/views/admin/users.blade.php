@extends('layouts.app')
@section('title', 'People')
@section('content')
<x-page-head title="People" sub="Staff and investor accounts. Investors sign in through the API." />
@php $admin = auth()->user()->isAdmin(); @endphp
<div class="grid split">
    <div class="card">
        <form class="filters" method="GET" action="{{ route('admin.users.index') }}">
            <div class="field"><label>Search</label><input name="q" value="{{ request('q') }}" placeholder="Name or email"></div>
            <div class="field"><label>Status</label><select name="status"><option value="">All</option>@foreach (['active','suspended'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
            <button class="btn ghost" type="submit">Filter</button>
        </form>
        <div class="tbl-wrap">
            <table>
                <thead><tr><th>Name</th><th>Role</th><th>Status</th><th class="num">Wallet</th><th></th></tr></thead>
                <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td><strong>{{ $u->name }}</strong><div class="muted small">{{ $u->email }}</div></td>
                        <td>{{ $u->roles->pluck('name')->implode(', ') ?: '—' }}</td>
                        <td><x-status :value="$u->status" /></td>
                        <td><x-money :value="$u->wallet?->balance" /></td>
                        <td><div class="row-actions">
                            <a class="btn ghost sm" href="{{ route('admin.users.financial.profile', $u) }}">Finances</a>
                            <a class="btn ghost sm" href="{{ route('admin.user.profile', $u) }}">Profile</a>
                            @if ($admin)<a class="btn ghost sm" href="{{ route('admin.users.edit', $u) }}">Edit</a>@endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>

    <div class="grid">
    @if ($admin)
        <div class="card">
            <div class="card-h"><h2>{{ $editUser ? 'Edit '.$editUser->name : 'New user' }}</h2>@if($editUser)<a class="small" href="{{ route('admin.users.index') }}">Cancel</a>@endif</div>
            <div class="card-b">
            @if ($editUser)
                <form method="POST" action="{{ route('admin.users.update', $editUser) }}">@csrf @method('PUT')
                    <x-field name="name" label="Name" :value="$editUser->name" required />
                    <x-field name="status" label="Status" type="select" required>
                        @foreach (['active','suspended'] as $s)<option value="{{ $s }}" @selected(old('status', $editUser->status) === $s)>{{ ucfirst($s) }}</option>@endforeach
                    </x-field>
                    <p class="hint small muted">Suspending a user also revokes their API tokens.</p>
                    <button class="btn" type="submit">Save changes</button>
                </form>
                <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
                <h3 style="margin-bottom:8px">Record demo deposit</h3>
                <p class="small muted" style="margin:0 0 10px">Writes a <code>deposit</code> row to the ledger. No payment processor is involved.</p>
                <form method="POST" action="{{ route('admin.users.deposit', $editUser) }}" data-confirm="Credit this wallet?">@csrf
                    <x-field name="amount" label="Amount" type="number" step="0.01" required />
                    <x-field name="note" label="Note" />
                    <button class="btn brass" type="submit">Credit wallet</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.store') }}">@csrf
                    <x-field name="name" label="Name" required />
                    <x-field name="email" label="Email" type="email" required />
                    <x-field name="password" label="Password" type="password" required hint="Minimum 8 characters." />
                    <x-field name="role" label="Role" type="select" required>
                        @foreach (['investor','manager','admin'] as $r)<option value="{{ $r }}" @selected(old('role','investor') === $r)>{{ ucfirst($r) }}</option>@endforeach
                    </x-field>
                    <x-field name="status" label="Status" type="select" required>
                        @foreach (['active','suspended'] as $s)<option value="{{ $s }}" @selected(old('status','active') === $s)>{{ ucfirst($s) }}</option>@endforeach
                    </x-field>
                    <button class="btn" type="submit">Create user</button>
                </form>
            @endif
            </div>
        </div>
    @else
        <div class="callout">Creating users, funding wallets and changing status is limited to administrators.</div>
    @endif
    </div>
</div>
@endsection
