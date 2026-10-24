@extends('kundli.layout')
@section('title', 'Admin')
@section('width', '1080px')
@section('body')
<div class="card">
  <div class="row" style="justify-content:space-between"><h1>Admin</h1><a class="btn ghost" href="{{ url('/') }}">← Back to the software</a></div>
  <nav class="tabs">
    <a href="{{ url('/admin?tab=users') }}" @if ($tab === 'users') aria-current="page" @endif>Users ({{ $users->where('status', 'pending')->count() }} waiting)</a>
    <a href="{{ url('/admin?tab=keys') }}" @if ($tab === 'keys') aria-current="page" @endif>Licence keys</a>
    <a href="{{ url('/admin?tab=brand') }}" @if ($tab === 'brand') aria-current="page" @endif>Default logo & name</a>
    <a href="{{ url('/admin?tab=logs') }}" @if ($tab === 'logs') aria-current="page" @endif>Report log</a>
  </nav>
  @if ($errors->any())<p class="err">{{ $errors->first() }}</p>@endif
</div>

@if ($tab === 'users')
<div class="card">
  <h2>Users</h2>
  <div class="tablewrap"><table>
    <thead><tr><th>Name</th><th>Email / mobile</th><th>Status</th><th>Licence</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach ($users as $u)
      @php($k = $u->activeKey() ?? $u->upcomingKey())
      <tr>
        <td><b>{{ $u->name }}</b>@if ($u->isAdmin()) <span class="chip warn">Admin</span>@endif<br><span class="muted">joined {{ $u->created_at?->format('d M Y') }}</span></td>
        <td>{{ $u->email }}@if ($u->phone)<br><span class="muted">{{ $u->phone }}</span>@endif</td>
        <td><span class="chip {{ $u->status === 'active' ? 'good' : ($u->status === 'pending' ? 'warn' : 'bad') }}">{{ ucfirst($u->status) }}</span></td>
        <td>@if ($u->isAdmin())<span class="muted">not needed</span>@elseif ($k)<code>{{ $k->code }}</code><br><span class="muted">{{ $k->starts_at?->isFuture() ? 'starts ' . $k->starts_at->format('d M Y') : '' }}{{ $k->expires_at ? ' until ' . $k->expires_at->format('d M Y') : ' no expiry' }}</span>@if ($k->brand_name)<br><span class="muted">{{ $k->brand_name }}</span>@endif @else<span class="chip bad">No key</span>@endif</td>
        <td>@unless ($u->isAdmin())
          <div class="row">
            @foreach (($u->status === 'pending' ? ['approve' => 'Approve'] : []) + ($u->status === 'active' ? ['disable' => 'Disable'] : []) + ($u->status === 'disabled' ? ['enable' => 'Enable'] : []) + ['reset' => 'Reset password'] as $act => $label)
              <form method="post" action="{{ url('/admin/users/' . $u->id) }}">@csrf<input type="hidden" name="action" value="{{ $act }}"><button type="submit" class="{{ $act === 'approve' ? '' : 'ghost' }}">{{ $label }}</button></form>
            @endforeach
            @if (! $k && $free->count())
              <form method="post" action="{{ url('/admin/keys/' . $free->first()->id) }}">@csrf<input type="hidden" name="action" value="assign"><input type="hidden" name="user_id" value="{{ $u->id }}"><button type="submit" class="ghost">Give a free key</button></form>
            @endif
            <form method="post" action="{{ url('/admin/users/' . $u->id) }}" onsubmit="return confirm('Delete {{ addslashes($u->name) }} and all their saved clients?')">@csrf<input type="hidden" name="action" value="delete"><button type="submit" class="danger">Delete</button></form>
          </div>
        @endunless</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  <p class="muted">New sign-ups wait here until you approve them. Each approved user also needs a licence key (Licence keys tab) to open the software.</p>
</div>
@endif

@if ($tab === 'keys')
@php($giveTo = $users->where('role', '!=', 'admin'))
<form class="card" method="post" action="{{ url('/admin/keys') }}" enctype="multipart/form-data">@csrf
  <h2>Create keys</h2>
  <div class="row">
    <label>How many<input type="number" name="count" value="{{ old('count', 1) }}" min="1" max="100" required></label>
    <label>Plan<input name="plan" maxlength="60" placeholder="Yearly" value="{{ old('plan') }}"></label>
  </div>
  <fieldset class="grp"><legend>Validity</legend>
    <div class="row">
      <label class="inline"><input type="radio" name="start_mode" value="date" @checked(old('start_mode', 'date') === 'date')> Start on</label>
      <input type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" aria-label="Start date">
    </div>
    <label class="inline"><input type="radio" name="start_mode" value="on_give" @checked(old('start_mode') === 'on_give')> Start when the key is given or activated</label>
    <div class="row">
      <label>Period<input type="number" name="period" min="1" max="1000" value="{{ old('period', 1) }}" placeholder="Empty = no expiry"></label>
      <label>Unit<select name="period_unit">@foreach (\App\Models\LicenseKey::UNITS as $v => $l)<option value="{{ $v }}" @selected(old('period_unit', 'years') === $v)>{{ $l }}</option>@endforeach</select></label>
    </div>
    <p class="muted">Leave Period empty for a key that never expires.</p>
  </fieldset>
  <fieldset class="grp"><legend>Branding for this key's user (optional)</legend>
    <div class="row">
      <label>Software name<input name="brand_name" maxlength="60" placeholder="{{ $brand }}" value="{{ old('brand_name') }}"></label>
      <label>Logo (PNG, JPG or WebP, up to 1 MB)<input type="file" name="brand_logo" accept="image/png,image/jpeg,image/webp"></label>
    </div>
    <p class="muted">The user of this key sees this name and logo in the software, on report covers and on their login pages. Leave empty to use the default logo and name.</p>
  </fieldset>
  <div class="row">
    <label>Give the first key to<select name="user_id"><option value="">Nobody yet</option>@foreach ($giveTo as $u)<option value="{{ $u->id }}">{{ $u->name }} · {{ $u->email }}</option>@endforeach</select></label>
    <label>Note<input name="note" maxlength="190" placeholder="Paid by UPI on…" value="{{ old('note') }}"></label>
  </div>
  <button type="submit">Create keys</button>
</form>
<div class="card">
  <h2>All keys</h2>
  <div class="tablewrap"><table>
    <thead><tr><th>Key</th><th>Branding</th><th>User</th><th>Validity</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse ($keys as $k)
      @php($st = $k->state())
      <tr>
        <td><code>{{ $k->code }}</code><br><span class="muted">{{ $k->plan ?: '—' }} · {{ $k->periodLabel() }}</span>@if ($k->note)<br><span class="muted">{{ $k->note }}</span>@endif</td>
        <td>@if ($k->brand_logo)<img src="{{ asset($k->brand_logo) }}" alt="" style="max-height:32px;max-width:90px;display:block;background:#fff;border-radius:4px;margin-bottom:4px">@endif{{ $k->brand_name ?: 'Default' }}</td>
        <td>{{ $k->user?->name ?? '—' }}@if ($k->user)<br><span class="muted">{{ $k->user->email }}</span>@endif</td>
        <td><span class="chip {{ ['in use' => 'good', 'unused' => 'warn', 'starts later' => 'warn'][$st] ?? 'bad' }}">{{ ucfirst($st) }}</span><br><span class="muted">{{ $k->starts_at ? $k->starts_at->format('d M Y') : 'starts when given' }} → {{ $k->expires_at ? $k->expires_at->format('d M Y') : (($k->period || $k->days) && ! $k->starts_at ? '…' : 'no expiry') }}</span></td>
        <td><div class="row">
          @if (! $k->user_id)
            <form method="post" action="{{ url('/admin/keys/' . $k->id) }}" class="row">@csrf<input type="hidden" name="action" value="assign"><select name="user_id" required><option value="">Give to…</option>@foreach ($giveTo as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select><button type="submit" class="ghost">Give</button></form>
          @else
            <form method="post" action="{{ url('/admin/keys/' . $k->id) }}">@csrf<input type="hidden" name="action" value="unassign"><button type="submit" class="ghost">Take back</button></form>
          @endif
          <form method="post" action="{{ url('/admin/keys/' . $k->id) }}" class="row">@csrf<input type="hidden" name="action" value="extend"><input type="number" name="period" min="1" max="1000" value="1" style="width:4.5em" aria-label="Amount to add"><select name="period_unit" aria-label="Unit">@foreach (\App\Models\LicenseKey::UNITS as $v => $l)<option value="{{ $v }}" @selected($v === 'months')>{{ $l }}</option>@endforeach</select><button type="submit" class="ghost">Extend</button></form>
          <form method="post" action="{{ url('/admin/keys/' . $k->id) }}">@csrf<input type="hidden" name="action" value="{{ $k->status === 'active' ? 'revoke' : 'restore' }}"><button type="submit" class="ghost">{{ $k->status === 'active' ? 'Revoke' : 'Restore' }}</button></form>
          <form method="post" action="{{ url('/admin/keys/' . $k->id) }}" onsubmit="return confirm('Delete key {{ $k->code }}?')">@csrf<input type="hidden" name="action" value="delete"><button type="submit" class="danger">Delete</button></form>
        </div>
        <details class="edit"><summary>Edit dates and branding</summary>
          <form method="post" action="{{ url('/admin/keys/' . $k->id) }}" enctype="multipart/form-data">@csrf<input type="hidden" name="action" value="edit">
            <div class="row"><label>Start date<input type="date" name="starts_at" value="{{ $k->starts_at?->toDateString() }}"></label><label>End date<input type="date" name="expires_at" value="{{ $k->expires_at?->toDateString() }}"></label></div>
            <div class="row"><label>Plan<input name="plan" maxlength="60" value="{{ $k->plan }}"></label><label>Note<input name="note" maxlength="190" value="{{ $k->note }}"></label></div>
            <div class="row"><label>Software name<input name="brand_name" maxlength="60" value="{{ $k->brand_name }}" placeholder="{{ $brand }}"></label><label>New logo<input type="file" name="brand_logo" accept="image/png,image/jpeg,image/webp"></label></div>
            @if ($k->brand_logo)<label class="inline"><input type="checkbox" name="remove_logo" value="1"> Remove this key's logo</label>@endif
            <button type="submit">Save changes</button>
          </form>
        </details></td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted">No keys yet.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  <p class="muted">An empty end date means no expiry. A key without a start date starts counting the day it is given or activated.</p>
</div>
@endif

@if ($tab === 'brand')
<form class="card" method="post" action="{{ url('/admin/brand') }}" enctype="multipart/form-data">@csrf
  <h2>Default logo and software name</h2>
  <p class="muted">Used for you and for every user whose licence key has no branding of its own. Shown in the app header, on report covers and on the login pages.</p>
  @if ($logo)<p><img src="{{ asset($logo) }}" alt="Current logo" style="max-height:80px;max-width:240px;background:#fff;border-radius:8px;padding:6px"></p>@endif
  <label>Software name<input name="brand_name" value="{{ $brand }}" maxlength="60"></label>
  <label>Logo (PNG, JPG or WebP, up to 1 MB; a square or wide image works best)<input type="file" name="logo" accept="image/png,image/jpeg,image/webp"></label>
  @if ($logo)<label class="inline"><input type="checkbox" name="remove_logo" value="1"> Remove the logo and use the default symbol</label>@endif
  <button type="submit">Save</button>
</form>
@endif

@if ($tab === 'logs')
<div class="card">
  <h2>Report log</h2>
  <p class="muted">Every report downloaded (PDF or image) or printed, with the user, client, date, time and IP address. Times are in {{ config('kundli.timezone', 'Asia/Kolkata') }}.</p>
  <form method="get" action="{{ url('/admin') }}" class="row" style="flex-wrap:wrap;gap:8px;align-items:flex-end">
    <input type="hidden" name="tab" value="logs">
    <label>User<select name="user"><option value="">All users</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected(($f['user'] ?? '') == $u->id)>{{ $u->name }}</option>@endforeach</select></label>
    <label>Action<select name="action"><option value="">All</option>@foreach (\App\Models\ReportLog::ACTIONS as $k => $v)<option value="{{ $k }}" @selected(($f['action'] ?? '') === $k)>{{ $v }}</option>@endforeach</select></label>
    <label>From<input type="date" name="from" value="{{ $f['from'] ?? '' }}"></label>
    <label>To<input type="date" name="to" value="{{ $f['to'] ?? '' }}"></label>
    <label>Client, report or IP<input name="q" value="{{ $f['q'] ?? '' }}" maxlength="80"></label>
    <button type="submit">Filter</button>
    <a class="btn ghost" href="{{ url('/admin/logs.csv') . '?' . http_build_query(array_filter($f ?? [])) }}">Download CSV</a>
  </form>
  <div class="tablewrap"><table>
    <thead><tr><th>Date &amp; time</th><th>User</th><th>Action</th><th>Report</th><th>Client</th><th>IP address</th></tr></thead>
    <tbody>
    @forelse ($logs as $r)
      <tr><td>{{ $r->created_at?->timezone(config('kundli.timezone', 'Asia/Kolkata'))->format('d M Y, h:i:s A') }}</td><td>{{ $r->user?->name ?? 'Deleted user' }}<br><span class="muted">{{ $r->user_email }}</span></td><td>{{ \App\Models\ReportLog::ACTIONS[$r->action] ?? $r->action }}</td><td>{{ $r->report }}</td><td>{{ $r->client_name }}</td><td>{{ $r->ip }}@if ($r->remote_ip)<br><span class="muted">via {{ $r->remote_ip }}</span>@endif</td></tr>
    @empty
      <tr><td colspan="6" class="muted">No downloads or prints match.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  {{ $logs->links() }}
</div>
@endif
@endsection
