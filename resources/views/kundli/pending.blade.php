@extends('kundli.layout')
@section('title', 'Waiting for approval')
@section('body')
<div class="card">
  <h1>Waiting for approval</h1>
  <p>Thank you, {{ $user->name }}. Your account ({{ $user->email }}) is waiting for the admin to approve it. Please check back later.</p>
  <form method="post" action="{{ url('/logout') }}">@csrf<button type="submit" class="ghost">Log out</button></form>
</div>
@endsection
