@extends('layouts.guest')
@section('title', 'Choose a new password')
@section('content')
    <h1>Choose a new password</h1>
    <p class="muted" style="margin:6px 0 22px">At least 8 characters.</p>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" label="Email" type="email" :value="$email" required />
        <x-field name="password" label="New password" type="password" required autocomplete="new-password" />
        <x-field name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />
        <button class="btn" type="submit" style="width:100%;justify-content:center">Update password</button>
    </form>
@endsection
