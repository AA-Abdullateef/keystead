@extends('layouts.guest')
@section('title', 'Confirm your email')
@section('content')
    <h1>Confirm your email</h1>
    <p class="muted" style="margin:6px 0 22px">One click and your account is fully active.</p>
    <form method="POST" action="{{ route('verification.verify') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" label="Email" type="email" :value="$email" required />
        <button class="btn" type="submit" style="width:100%;justify-content:center">Confirm email address</button>
    </form>
    <p class="small" style="margin-top:16px"><a href="{{ route('login') }}">← Back to sign in</a></p>
@endsection
