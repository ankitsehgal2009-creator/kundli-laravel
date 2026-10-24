@extends('kundli.layout')
@section('title', 'Create an account')
@section('body')
<form class="card" method="post" action="{{ url('/register') }}">@csrf
  <h1>Create an account</h1>
  <p class="muted">The admin approves new accounts. You can log in after approval.</p>
  @if ($errors->any())<p class="err">{{ $errors->first() }}</p>@endif
  <label>Full name<input name="name" value="{{ old('name') }}" required maxlength="100"></label>
  <label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"></label>
  <label>Mobile (optional)<input type="tel" name="phone" value="{{ old('phone') }}" maxlength="30"></label>
  <label>Password (8 or more characters)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
  <label>Confirm password<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
  @include('kundli.questions')
  <button type="submit">Create account</button>
  <div class="links"><a href="{{ url('/login') }}">I already have an account</a></div>
</form>
@endsection
