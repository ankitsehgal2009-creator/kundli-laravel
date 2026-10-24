@extends('kundli.layout')
@section('title', 'Reset password')
@section('body')
<form class="card" method="post" action="{{ url('/reset-password') }}">@csrf
  <h1>Choose a new password</h1>
  @if ($errors->any())<p class="err">{{ $errors->first() }}</p>@endif
  <input type="hidden" name="token" value="{{ $token }}">
  <label>Email<input type="email" name="email" value="{{ old('email', $email) }}" required></label>
  <label>New password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
  <label>Confirm new password<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
  <button type="submit">Change password</button>
</form>
@endsection
