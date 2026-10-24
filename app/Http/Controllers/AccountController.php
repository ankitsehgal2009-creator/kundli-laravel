<?php

namespace App\Http\Controllers;

use App\Models\LicenseKey;
use App\Support\KundliDb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function show(Request $request)
    {
        $u = $request->user();

        return view('kundli.account', ['user' => $u, 'key' => $u->activeKey(), 'questions' => config('kundli.questions')]);
    }

    public function password(Request $request)
    {
        $request->validate(['current_password' => 'required|current_password', 'password' => 'required|min:8|confirmed']);
        $request->user()->forceFill(['password' => $request->input('password')])->save();

        return back()->with('ok', 'Password changed.');
    }

    public function questions(Request $request)
    {
        $q = 'required|in:' . implode(',', array_map(fn ($s) => str_replace(',', '\,', $s), config('kundli.questions')));
        $d = $request->validate(['current_password' => 'required|current_password', 'sq1' => $q, 'sa1' => 'required|min:2|max:100', 'sq2' => $q . '|different:sq1', 'sa2' => 'required|min:2|max:100']);
        $request->user()->forceFill(['sq1' => $d['sq1'], 'sa1' => Hash::make(KundliDb::normAnswer($d['sa1'])), 'sq2' => $d['sq2'], 'sa2' => Hash::make(KundliDb::normAnswer($d['sa2']))])->save();

        return back()->with('ok', 'Security questions saved.');
    }

    public function activate(Request $request)
    {
        $code = strtoupper(trim((string) $request->validate(['code' => 'required|max:40'])['code']));
        $key = LicenseKey::where('code', $code)->where('status', 'active')->whereNull('user_id')->first();
        if (! $key || ($key->expires_at && $key->expires_at->isPast())) {
            return back()->withErrors(['code' => 'This key is not valid or is already in use.']);
        }
        $key->assignTo($request->user());

        return redirect('/')->with('ok', 'Licence key activated.');
    }
}
