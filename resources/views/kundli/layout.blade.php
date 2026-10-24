@php($brandInfo = \App\Support\Brand::for(auth()->user()))
@php($brandName = $brandInfo['name'])
@php($brandLogo = $brandInfo['logo'])
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') · {{ $brandName }}</title>
<style>
  :root { --bg:#f4f2ff; --card:#fff; --ink:#1a1f45; --muted:#5d6390; --gold:#b37400; --gold2:#c8891a; --line:#e0e2f2; --bad:#c0392b; --good:#17804f; --soft:#f6f4ff; }
  @media (prefers-color-scheme: dark) { :root { --bg:#0a0f2c; --card:#171e48; --ink:#eef0ff; --muted:#a9b0dc; --gold:#f5b942; --gold2:#f5b942; --line:#2b3366; --bad:#ff8a7a; --good:#5ee0a0; --soft:#1d2557; } }
  * { box-sizing: border-box; }
  body { margin:0; min-height:100vh; background:var(--bg); color:var(--ink); font:16px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; padding:24px 16px; }
  main { margin:auto; max-width:@yield('width', '420px'); display:grid; gap:16px; }
  .brand { display:flex; align-items:center; gap:12px; justify-content:center; text-decoration:none; color:inherit; }
  .brand img { height:44px; width:auto; max-width:160px; object-fit:contain; } .brand b { font-size:1.25rem; }
  .card { background:var(--card); border:1px solid var(--line); border-radius:18px; padding:22px; display:grid; gap:14px; min-width:0; }
  h1, h2 { margin:0; } h1 { font-size:1.4rem; } h2 { font-size:1.1rem; }
  p { margin:0; } .muted { color:var(--muted); font-size:.92rem; }
  form { display:grid; gap:12px; margin:0; } .row { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
  label { display:grid; gap:4px; font-size:.9rem; font-weight:600; }
  input, select, textarea { font:inherit; padding:10px 12px; border-radius:10px; border:1px solid var(--line); background:var(--soft); color:inherit; min-width:0; width:100%; }
  .row input, .row select { width:auto; flex:1 1 140px; }
  button, .btn { font:inherit; font-weight:700; padding:10px 16px; border:0; border-radius:999px; background:var(--gold2); color:#fff; cursor:pointer; text-decoration:none; display:inline-block; text-align:center; }
  button.ghost, .btn.ghost { background:transparent; color:var(--gold); border:1px solid var(--gold2); padding:6px 12px; font-size:.85rem; }
  button.danger { background:transparent; color:var(--bad); border:1px solid var(--bad); padding:6px 12px; font-size:.85rem; }
  a { color:var(--gold); }
  .err { color:var(--bad); font-size:.92rem; } .ok { color:var(--good); font-size:.92rem; word-break:break-word; }
  .links { display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap; font-size:.92rem; }
  .tablewrap { overflow-x:auto; } table { width:100%; border-collapse:collapse; font-size:.9rem; } th, td { text-align:left; padding:8px 6px; border-bottom:1px solid var(--line); vertical-align:top; } th { color:var(--muted); font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; }
  .chip { display:inline-block; padding:2px 10px; border-radius:999px; font-size:.78rem; font-weight:700; background:var(--soft); }
  .chip.good { color:var(--good); } .chip.bad { color:var(--bad); } .chip.warn { color:var(--gold); }
  .tabs { display:flex; gap:6px; flex-wrap:wrap; } .tabs a { padding:8px 14px; border-radius:999px; text-decoration:none; border:1px solid var(--line); color:inherit; } .tabs a[aria-current] { background:var(--gold2); color:#fff; border-color:var(--gold2); }
  td .row input, td .row select { flex:0 0 auto; width:auto; padding:6px 8px; font-size:.88rem; } td > .row { gap:6px; }
  fieldset.grp { border:1px solid var(--line); border-radius:12px; padding:12px 14px; display:grid; gap:10px; margin:0; min-width:0; } legend { font-weight:700; font-size:.92rem; padding:0 6px; }
  label.inline { display:flex; align-items:center; gap:6px; font-weight:500; } label.inline input { width:auto; }
  details.edit { margin-top:8px; } details.edit summary { cursor:pointer; color:var(--gold); font-size:.88rem; font-weight:600; } details.edit form { margin-top:10px; padding:12px; border:1px solid var(--line); border-radius:12px; }
  code { font-family: ui-monospace, Menlo, Consolas, monospace; font-size:.92em; }
</style>
</head>
<body>
<main>
  <a class="brand" href="{{ url('/') }}">@if ($brandLogo)<img src="{{ asset($brandLogo) }}" alt="">@endif<b>{{ $brandName }}</b></a>
  @if (session('ok'))<div class="card"><p class="ok">{{ session('ok') }}</p></div>@endif
  @yield('body')
</main>
<p style="text-align:center;color:#8a8fb5;font-size:13px;margin:18px 0 24px">Designed &amp; Developed By Er. Ankit Sehgal</p>
@if (request('out'))
<script>try { ['pm-clients', 'pm-numx', 'pm-client', 'pm-boy', 'pm-girl'].forEach(k => localStorage.removeItem(k)); } catch (e) {}</script>
@endif
</body>
</html>
