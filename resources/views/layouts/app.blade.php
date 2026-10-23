<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/keystead.css') }}">
</head>
<body>
@php
    $user = auth()->user();
    $is = fn (string $p) => request()->routeIs($p) ? 'on' : '';
    $groups = [
        'Investments' => ['admin.properties.*' => ['Properties', 'admin.properties.index'], 'admin.investments.*' => ['Projects & investors', 'admin.investments.index'], 'admin.rental.*' => ['Rental income', 'admin.rental.index'], 'admin.expenses.*' => ['Expenses', 'admin.expenses.index']],
        'Finance' => ['admin.profit.*' => ['Profit distribution', 'admin.profit.distribution'], 'admin.earnings.*' => ['Earnings', 'admin.earnings.index'], 'admin.withdrawals.*' => ['Withdrawals', 'admin.withdrawals.index'], 'admin.transactions.*' => ['Ledger', 'admin.transactions.index']],
        'Lending' => ['admin.loan.*' => ['Loan products', 'admin.loan.projects'], 'admin.loans.*' => ['Mortgage & property loans', 'admin.loans.index', 'mortgage']],
    ];
@endphp
<header class="topbar">
    <div class="wrap">
        <a class="brand" href="{{ route('admin.index') }}">
            <svg viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="7" fill="#f6f2e9"/><path d="M7 15 16 7l9 8v10h-6v-6h-6v6H7z" fill="#1f4d3a"/><circle cx="16" cy="13.5" r="1.8" fill="#b08d57"/></svg>
            {{ config('app.name') }}
        </a>
        <button class="burger" type="button" aria-label="Menu">☰</button>
        <nav class="nav">
            <a class="{{ $is('admin.index') }}" href="{{ route('admin.index') }}">Overview</a>
            @foreach ($groups as $label => $items)
                <div class="dd {{ collect(array_keys($items))->contains(fn ($p) => request()->routeIs($p)) ? 'on' : '' }}">
                    <button type="button">{{ $label }}</button>
                    <div class="dd-menu">
                        @foreach ($items as $pattern => $item)
                            <a class="{{ $is($pattern) }}" href="{{ route($item[1], $item[2] ?? []) }}">{{ $item[0] }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <a class="{{ $is('admin.users.*') . $is('admin.user.*') }}" href="{{ route('admin.users.index') }}">People</a>
            @if ($user->isAdmin())<a class="{{ $is('admin.audit.*') }}" href="{{ route('admin.audit.index') }}">Audit</a>@endif
        </nav>
        <div class="who">
            <span>{{ $user->name }} <span class="role">{{ $user->isAdmin() ? 'admin' : 'manager' }}</span></span>
            <form method="POST" action="{{ route('logout') }}">@csrf <button class="btn ghost sm" style="color:#fff;border-color:rgba(255,255,255,.4)">Sign out</button></form>
        </div>
    </div>
</header>

<main>
    <div class="wrap">
        @include('admin.partials.flash')
        @yield('content')
    </div>
</main>
<script src="{{ asset('assets/js/keystead.js') }}" defer></script>
</body>
</html>
