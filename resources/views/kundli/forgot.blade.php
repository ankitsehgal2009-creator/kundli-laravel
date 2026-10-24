@extends('kundli.layout')
@section('title', 'Forgot password')
@section('body')
@if ($step === 'questions')
<form class="card" method="post" action="{{ url('/forgot/answer') }}">@csrf
  <h1>Answer your security questions</h1>
  <input type="hidden" name="email" value="{{ $email }}">
  <label>{{ $questions[0] }}<input name="sa1" required autocomplete="off"></label>
  <label>{{ $questions[1] }}<input name="sa2" required autocomplete="off"></label>
  <label>New password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
  <label>Confirm new password<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
  <button type="submit">Change password</button>
</form>
@else
<div class="card">
  <h1>Forgot password</h1>
  @if ($errors->any())<p class="err">{{ $errors->first() }}</p>@endif
  <form method="post" action="{{ url('/forgot/questions') }}">@csrf
    <h2>Use security questions</h2>
    <label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"></label>
    <button type="submit">Show my questions</button>
  </form>
  <form method="post" action="{{ url('/forgot/email') }}">@csrf
    <h2>Or get a reset link by email</h2>
    <label>Email<input type="email" name="email" required autocomplete="username"></label>
    <button type="submit" class="ghost">Email me a link</button>
  </form>
  <p class="muted">Still stuck? The admin can set a temporary password for you.</p>
  <div class="links"><a href="{{ url('/login') }}">Back to log in</a></div>
</div>
@endif
@endsection
