@extends('kundli.layout')
@section('title', 'My account')
@section('width', '560px')
@section('body')
<div class="card">
  <div class="row" style="justify-content:space-between"><h1>My account</h1>@if ($user->canUseApp())<a class="btn ghost" href="{{ url('/') }}">← Back to the software</a>@endif</div>
  <p>{{ $user->name }} · {{ $user->email }}@if ($user->isAdmin()) · <b>Admin</b> · <a href="{{ url('/admin') }}">Admin panel</a>@endif</p>
</div>
@unless ($user->isAdmin())
<div class="card">
  <h2>Licence key</h2>
  @if ($key)
    <p><span class="chip good">Active</span> <code>{{ $key->code }}</code>@if ($key->plan) · {{ $key->plan }}@endif · {{ $key->expires_at ? 'valid until ' . $key->expires_at->format('d M Y') : 'no expiry' }}</p>
  @elseif ($soon = $user->upcomingKey())
    <p><span class="chip warn">Starts later</span> <code>{{ $soon->code }}</code> starts on {{ $soon->starts_at->format('d M Y') }}. You can open the software from that day.</p>
  @else
    <p class="{{ session('need_key') ? 'err' : 'muted' }}">You need an active licence key to open the software. Enter the key the admin gave you.</p>
  @endif
  <form method="post" action="{{ url('/account/key') }}" class="row">@csrf
    <input name="code" placeholder="KS-XXXX-XXXX-XXXX" required maxlength="40" style="text-transform:uppercase"><button type="submit">Activate key</button>
  </form>
  @error('code')<p class="err">{{ $message }}</p>@enderror
</div>
@endunless
<form class="card" method="post" action="{{ url('/account/password') }}">@csrf
  <h2>Change password</h2>
  @if ($errors->hasAny(['current_password', 'password']) && ! old('sq1'))<p class="err">{{ $errors->first('current_password') ?: $errors->first('password') }}</p>@endif
  <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
  <label>New password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
  <label>Confirm new password<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
  <button type="submit">Change password</button>
</form>
<form class="card" method="post" action="{{ url('/account/questions') }}">@csrf
  <h2>Security questions</h2>
  @if (old('sq1') && $errors->any())<p class="err">{{ $errors->first() }}</p>@endif
  @include('kundli.questions', ['current' => [$user->sq1, $user->sq2]])
  <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
  <button type="submit">Save questions</button>
</form>
<form method="post" action="{{ url('/logout') }}">@csrf<button type="submit" class="ghost">Log out</button></form>
@endsection
