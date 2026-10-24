@extends('kundli.layout')
@section('title', 'Set up')
@section('body')
<form class="card" method="post" action="{{ url('/setup') }}">@csrf
  <h1>Set up the admin account</h1>
  <p class="muted">First run. The database tables are ready. This creates the admin account for <b>{{ $email }}</b>. Only this email can be admin.</p>
  @if ($errors->any())<p class="err">{{ $errors->first() }}</p>@endif
  <label>Your name<input name="name" value="{{ old('name') }}" required></label>
  <label>Email<input value="{{ $email }}" disabled></label>
  <label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
  <label>Confirm password<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
  @include('kundli.questions', ['questions' => config('kundli.questions')])
  <button type="submit">Create admin account</button>
</form>
@endsection
