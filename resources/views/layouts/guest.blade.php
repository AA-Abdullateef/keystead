<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/keystead.css') }}">
</head>
<body>
<div class="guest">
    <aside class="guest-art">
        <a class="brand" href="/">
            <svg viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="7" fill="#f6f2e9"/><path d="M7 15 16 7l9 8v10h-6v-6h-6v6H7z" fill="#1f4d3a"/><circle cx="16" cy="13.5" r="1.8" fill="#b08d57"/></svg>
            {{ config('app.name') }}
        </a>
        <div>
            <h2>Real estate investment, on the record.</h2>
            <ul>
                <li>Every cent traced through an append-only ledger</li>
                <li>Profit distributions that reconcile to the wallet</li>
                <li>Mortgage and property loans with strict lifecycles</li>
            </ul>
        </div>
        <span class="small" style="color:#9fb8aa">Staff console · demo project, no real payments</span>
    </aside>
    <section class="guest-form">
        <div class="box">
            @include('admin.partials.flash')
            @yield('content')
        </div>
    </section>
</div>
<script src="{{ asset('assets/js/keystead.js') }}" defer></script>
</body>
</html>
