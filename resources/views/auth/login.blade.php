@extends('layouts.guest')
@section('title', 'Sign in')
@section('content')
    <h1>Staff sign in</h1>
    <p class="muted" style="margin:6px 0 22px">Use your Keystead staff account.</p>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <x-field name="email" label="Email" type="email" required autofocus autocomplete="username" />
        <x-field name="password" label="Password" type="password" required autocomplete="current-password" />
        <button class="btn" type="submit" style="width:100%;justify-content:center">Sign in</button>
    </form>
    <p class="small" style="margin-top:16px"><a href="{{ route('password.request') }}">Forgot your password?</a></p>
@endsection
