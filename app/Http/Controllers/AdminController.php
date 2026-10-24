<?php

namespace App\Http\Controllers;

use App\Models\LicenseKey;
use App\Models\ReportLog;
use App\Models\Setting;
use App\Models\User;
use App\Support\KundliDb;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        KundliDb::ensure();
        $users = User::orderByRaw("case status when 'pending' then 0 when 'active' then 1 else 2 end")->orderBy('name')->get();
        $keys = LicenseKey::with('user')->latest()->get();
        $tab = in_array($request->query('tab'), ['users', 'keys', 'brand', 'logs']) ? $request->query('tab') : 'users';
        $logs = $tab === 'logs' ? $this->logQuery($request)->paginate(50)->withQueryString() : null;

        return view('kundli.admin', [
            'users' => $users, 'keys' => $keys, 'tab' => $tab, 'logs' => $logs, 'f' => $request->only(['user', 'action', 'from', 'to', 'q']),
            'free' => $keys->filter(fn ($k) => $k->state() === 'unused'),
            'logo' => Setting::get('logo'), 'brand' => Setting::get('brand_name', 'Kundli Software'),
        ]);
    }

    // report log filtered by user, action, date range (in the app's time zone) and client or report text
    private function logQuery(Request $request)
    {
        $q = ReportLog::with('user')->latest('created_at')->latest('id');
        if ($request->filled('user')) {
            $q->where('user_id', (int) $request->query('user'));
        }
        if (array_key_exists($request->query('action'), ReportLog::ACTIONS)) {
            $q->where('action', $request->query('action'));
        }
        foreach (['from' => 'startOfDay', 'to' => 'endOfDay'] as $k => $edge) {
            if ($request->filled($k)) {
                try {
                    $q->where('created_at', $k === 'from' ? '>=' : '<=', Carbon::parse($request->query($k))->$edge());
                } catch (\Throwable $e) {
                }
            }
        }
        if ($request->filled('q')) {
            $t = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $request->query('q')) . '%';
            $q->where(fn ($w) => $w->where('client_name', 'like', $t)->orWhere('report', 'like', $t)->orWhere('ip', 'like', $t));
        }

        return $q;
    }

    public function logsCsv(Request $request)
    {
        $rows = $this->logQuery($request)->limit(50000)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Date', 'Time', 'User', 'Email', 'Action', 'Report', 'Client', 'IP address', 'Proxy IP', 'Browser']);
            foreach ($rows as $r) {
                $t = $r->created_at?->timezone(config('kundli.timezone', 'Asia/Kolkata'));
                fputcsv($out, [$t?->format('Y-m-d'), $t?->format('H:i:s'), $r->user?->name, $r->user_email, ReportLog::ACTIONS[$r->action] ?? $r->action, $r->report, $r->client_name, $r->ip, $r->remote_ip, $r->user_agent]);
            }
            fclose($out);
        }, 'report-log-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    private function notAdmin(User $user): void
    {
        abort_if($user->isAdmin(), 403, 'The admin account cannot be changed here.');
    }

    public function userAction(Request $request, User $user)
    {
        $this->notAdmin($user);
        $act = $request->validate(['action' => 'required|in:approve,disable,enable,reset,delete'])['action'];
        if ($act === 'approve' || $act === 'enable') {
            $user->forceFill(['status' => 'active', 'approved_at' => $user->approved_at ?? now()])->save();
            $msg = "{$user->name} can now log in." . ($user->activeKey() ? '' : ' Give them a licence key so they can open the software.');
        } elseif ($act === 'disable') {
            $user->forceFill(['status' => 'disabled', 'remember_token' => Str::random(60)])->save();
            $msg = "{$user->name} is disabled.";
        } elseif ($act === 'reset') {
            $temp = Str::password(10, symbols: false);
            $user->forceFill(['password' => $temp, 'remember_token' => Str::random(60)])->save();
            $msg = "Temporary password for {$user->email}: {$temp} (shown once; ask them to change it from Account).";
        } else {
            $name = $user->name;
            $user->delete();
            $msg = "{$name} and their saved clients were deleted.";
        }

        return redirect('/admin?tab=users')->with('ok', $msg);
    }

    // store an uploaded logo under public/kundli/brand and return its path
    private function saveLogo(Request $request, string $field, string $prefix): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }
        $dir = public_path('kundli/brand');
        File::ensureDirectoryExists($dir);
        $name = $prefix . '-' . time() . '-' . Str::lower(Str::random(6)) . '.' . $request->file($field)->extension();
        $request->file($field)->move($dir, $name);

        return 'kundli/brand/' . $name;
    }

    private const LOGO_RULE = 'nullable|file|mimes:png,jpg,jpeg,webp|max:1024';

    public function generate(Request $request)
    {
        $d = $request->validate([
            'count' => 'required|integer|min:1|max:100',
            'start_mode' => 'required|in:date,on_give',
            'start_date' => 'required_if:start_mode,date|nullable|date',
            'period' => 'nullable|integer|min:1|max:1000',
            'period_unit' => 'required|in:days,months,years',
            'plan' => 'nullable|max:60', 'note' => 'nullable|max:190',
            'user_id' => 'nullable|exists:users,id',
            'brand_name' => 'nullable|max:60', 'brand_logo' => self::LOGO_RULE,
        ]);
        $start = $d['start_mode'] === 'date' ? Carbon::parse($d['start_date'])->startOfDay() : null;
        $logo = $this->saveLogo($request, 'brand_logo', 'key');
        $made = [];
        for ($i = 0; $i < $d['count']; $i++) {
            $k = LicenseKey::create([
                'code' => LicenseKey::newCode(), 'plan' => $d['plan'] ?? null, 'note' => $d['note'] ?? null,
                'period' => $d['period'] ?? null, 'period_unit' => ($d['period'] ?? null) ? $d['period_unit'] : null,
                'starts_at' => $start, 'expires_at' => $start ? LicenseKey::addPeriod($start, $d['period'] ?? null, $d['period_unit']) : null,
                'brand_name' => $d['brand_name'] ?? null, 'brand_logo' => $logo,
            ]);
            if (! empty($d['user_id']) && $i === 0) {
                $k->assignTo(User::find($d['user_id']));
            }
            $made[] = $k->code;
        }

        return redirect('/admin?tab=keys')->with('ok', count($made) . ' key(s) created: ' . implode(', ', $made));
    }

    public function keyAction(Request $request, LicenseKey $key)
    {
        $d = $request->validate([
            'action' => 'required|in:assign,unassign,revoke,restore,extend,edit,delete',
            'user_id' => 'required_if:action,assign|nullable|exists:users,id',
            'period' => 'required_if:action,extend|nullable|integer|min:1|max:1000',
            'period_unit' => 'nullable|in:days,months,years',
            'starts_at' => 'nullable|date', 'expires_at' => 'nullable|date|after:starts_at',
            'plan' => 'nullable|max:60', 'note' => 'nullable|max:190',
            'brand_name' => 'nullable|max:60', 'brand_logo' => self::LOGO_RULE, 'remove_logo' => 'nullable|boolean',
        ]);
        switch ($d['action']) {
            case 'assign':
                $key->assignTo(User::findOrFail($d['user_id']));
                $msg = "{$key->code} given to {$key->user->name}.";
                break;
            case 'unassign':
                $key->forceFill(['user_id' => null, 'activated_at' => null])->save();
                $msg = "{$key->code} is free again.";
                break;
            case 'revoke':
                $key->forceFill(['status' => 'revoked'])->save();
                $msg = "{$key->code} revoked.";
                break;
            case 'restore':
                $key->forceFill(['status' => 'active'])->save();
                $msg = "{$key->code} restored.";
                break;
            case 'extend':
                $from = $key->expires_at && $key->expires_at->isFuture() ? $key->expires_at : now();
                $key->forceFill(['expires_at' => LicenseKey::addPeriod($from, (int) $d['period'], $d['period_unit'] ?? 'days')])->save();
                $msg = "{$key->code} now valid until " . $key->expires_at->format('d M Y') . '.';
                break;
            case 'edit':
                $old = $key->brand_logo;
                $key->fill([
                    'starts_at' => ! empty($d['starts_at']) ? Carbon::parse($d['starts_at'])->startOfDay() : null,
                    'expires_at' => ! empty($d['expires_at']) ? Carbon::parse($d['expires_at'])->endOfDay() : null,
                    'plan' => $d['plan'] ?? null, 'note' => $d['note'] ?? null, 'brand_name' => $d['brand_name'] ?? null,
                ]);
                if ($new = $this->saveLogo($request, 'brand_logo', 'key')) {
                    $key->brand_logo = $new;
                } elseif (! empty($d['remove_logo'])) {
                    $key->brand_logo = null;
                }
                $key->save();
                if ($old !== $key->brand_logo) {
                    LicenseKey::dropLogo($old);
                }
                $msg = "{$key->code} updated.";
                break;
            default:
                $msg = "{$key->code} deleted.";
                $old = $key->brand_logo;
                $key->delete();
                LicenseKey::dropLogo($old);
        }

        return redirect('/admin?tab=keys')->with('ok', $msg);
    }

    public function brand(Request $request)
    {
        $request->validate(['brand_name' => 'nullable|max:60', 'logo' => self::LOGO_RULE, 'remove_logo' => 'nullable|boolean']);
        if ($request->filled('brand_name')) {
            Setting::put('brand_name', $request->input('brand_name'));
        }
        $old = Setting::get('logo');
        if ($request->boolean('remove_logo')) {
            Setting::put('logo', null);
        } elseif ($new = $this->saveLogo($request, 'logo', 'logo')) {
            Setting::put('logo', $new);
        }
        if ($old && $old !== Setting::get('logo')) {
            LicenseKey::dropLogo($old);
        }

        return redirect('/admin?tab=brand')->with('ok', 'Branding saved.');
    }
}
