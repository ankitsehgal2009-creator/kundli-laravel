<?php

namespace App\Http\Controllers;

use App\Models\KundliClient;
use App\Models\ReportLog;
use App\Support\Brand;
use App\Support\KundliDb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class KundliController extends Controller
{
    // The app page, with the API address, CSRF token and asset folder filled in.
    public function app()
    {
        KundliDb::ensure();
        $html = File::get(public_path('kundli/app.html'));
        $assets = asset('kundli') . '/';
        $html = str_replace('https://cdn.jsdelivr.net/npm/astronomy-engine@2.1.19/astronomy.browser.min.js', $assets . 'vendor/astronomy.browser.min.js', $html);
        $html = str_replace('https://cdn.jsdelivr.net/npm/html2canvas-pro@2.4.5/dist/html2canvas-pro.min.js', $assets . 'vendor/html2canvas-pro.min.js', $html);
        $html = str_replace('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', $assets . 'vendor/jspdf.umd.min.js', $html);
        $u = auth()->user();
        $key = $u->activeKey();
        $b = Brand::for($u);
        $brand = ['name' => $b['name'], 'logo' => $b['logo'] ? asset($b['logo']) : null];
        $boot = '<script>window.KUNDLI_ASSETS=' . json_encode($assets) . ';window.KUNDLI_API=' . json_encode(['url' => url('/api/clients'), 'log' => url('/api/report-log'), 'csrf' => csrf_token()]) . ';window.KUNDLI_BRAND=' . json_encode($brand, JSON_HEX_TAG | JSON_HEX_AMP) . ';</script>';
        $link = fn ($href, $text) => '<a href="' . e(url($href)) . '" style="color:#f5b942">' . e($text) . '</a>';
        $bar = '<div class="kbar" style="display:flex;justify-content:flex-end;align-items:center;gap:12px;flex-wrap:wrap;padding:6px 16px;font:13px system-ui,sans-serif;background:#1a1f45;color:#eef0ff">'
            . '<span>' . e($u->name) . ($u->isAdmin() ? ' · Admin' : ($key ? ' · Licence ' . ($key->expires_at ? 'valid until ' . $key->expires_at->format('d M Y') : 'lifetime') : '')) . '</span>'
            . $link('/account', 'Account') . ($u->isAdmin() ? $link('/admin', 'Admin') : '')
            . '<form method="post" action="' . e(url('/logout')) . '" style="margin:0"><input type="hidden" name="_token" value="' . e(csrf_token()) . '"><button type="submit" style="font:inherit;padding:3px 12px;border-radius:999px;border:1px solid #f5b942;background:transparent;color:#f5b942;cursor:pointer">Log out</button></form></div>';
        $html = str_replace(['<!--KUNDLI_BOOT-->', '<!--KUNDLI_BAR-->'], [$boot, $bar], $html);

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function index(Request $request)
    {
        return KundliClient::where('user_id', $request->user()->id)->orderByDesc('updated_ms')->get()
            ->map(fn ($c) => json_decode($c->data, true) + ['id' => $c->client_key])->values();
    }

    public function upsert(Request $request, string $key)
    {
        abort_unless(preg_match('/^[A-Za-z0-9_-]{1,64}$/', $key), 422);
        $data = $request->validate(['name' => 'required|string|max:255']) + $request->all();
        $data['id'] = $key;
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        abort_if(strlen($json) > 500000, 413);
        KundliClient::updateOrCreate(
            ['user_id' => $request->user()->id, 'client_key' => $key],
            ['name' => $data['name'], 'data' => $json, 'updated_ms' => (int) ($data['updated'] ?? 0)]
        );

        return $data;
    }

    public function destroy(Request $request, string $key)
    {
        KundliClient::where('user_id', $request->user()->id)->where('client_key', $key)->delete();

        return response()->noContent();
    }

    // one row per report downloaded or printed: who, which report, which client, when and from which IP
    public function logReport(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:' . implode(',', array_keys(ReportLog::ACTIONS)),
            'report' => 'required|string|max:160',
            'client' => 'nullable|string|max:160',
        ]);
        $remote = $request->ip();
        // behind a proxy the visitor's address is the first entry of X-Forwarded-For
        $fwd = trim(explode(',', (string) $request->header('X-Forwarded-For', ''))[0]);
        $ip = filter_var($fwd, FILTER_VALIDATE_IP) ? $fwd : $remote;
        $u = $request->user();
        ReportLog::create([
            'user_id' => $u->id, 'user_email' => $u->email, 'action' => $data['action'], 'report' => $data['report'],
            'client_name' => $data['client'] ?? null, 'ip' => $ip, 'remote_ip' => $remote !== $ip ? $remote : null,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        return response()->noContent();
    }
}
