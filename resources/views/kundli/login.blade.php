@extends('kundli.layout')
@section('title', 'Log in')
@section('body')
<form class="card" method="post" action="{{ url('/login') }}">@csrf
  <h1>Log in</h1>
  @if ($errors->any())<p class="err">{{ $errors->first() }}</p>@endif
  <label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"></label>
  <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
  <button type="submit">Log in</button>
  <div class="links"><a href="{{ url('/forgot') }}">Forgot password?</a><a href="{{ url('/register') }}">Create an account</a></div>
</form>
@endsection
