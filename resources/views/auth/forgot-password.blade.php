@extends('layouts.guest')
@section('title', 'Reset password')
@section('content')
    <h1>Reset your password</h1>
    <p class="muted" style="margin:6px 0 22px">We'll email a reset link if the address belongs to a staff account.</p>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <x-field name="email" label="Email" type="email" required autofocus />
        <button class="btn" type="submit" style="width:100%;justify-content:center">Send reset link</button>
    </form>
    <p class="small" style="margin-top:16px"><a href="{{ route('login') }}">← Back to sign in</a></p>
@endsection
