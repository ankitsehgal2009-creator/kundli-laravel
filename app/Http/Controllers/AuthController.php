<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\KundliDb;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function adminExists(): bool
    {
        return User::where('role', 'admin')->exists();
    }

    private function questionRules(): array
    {
        $q = 'required|in:' . implode(',', array_map(fn ($s) => str_replace(',', '\,', $s), config('kundli.questions')));

        return ['sq1' => $q, 'sa1' => 'required|string|min:2|max:100', 'sq2' => $q . '|different:sq1', 'sa2' => 'required|string|min:2|max:100'];
    }

    private function questionData(array $d): array
    {
        return ['sq1' => $d['sq1'], 'sa1' => Hash::make(KundliDb::normAnswer($d['sa1'])), 'sq2' => $d['sq2'], 'sa2' => Hash::make(KundliDb::normAnswer($d['sa2']))];
    }

    // ---------- first run: the admin account ----------
    public function setupForm()
    {
        KundliDb::ensure();
        if ($this->adminExists()) {
            return redirect('/login');
        }

        return view('kundli.setup', ['email' => config('kundli.admin_email')]);
    }

    public function setup(Request $request)
    {
        KundliDb::ensure();
        abort_if($this->adminExists(), 403, 'Setup is already done.');
        $d = $request->validate(['name' => 'required|max:100', 'password' => 'required|min:8|confirmed'] + $this->questionRules());
        $user = User::where('email', config('kundli.admin_email'))->first() ?? new User(['email' => config('kundli.admin_email')]);
        $user->fill(['name' => $d['name'], 'password' => $d['password'], 'role' => 'admin', 'status' => 'active', 'approved_at' => now()] + $this->questionData($d))->save();
        Auth::login($user, true);

        return redirect('/');
    }

    // ---------- login / logout ----------
    public function loginForm()
    {
        KundliDb::ensure();
        if (! $this->adminExists()) {
            return redirect('/setup');
        }

        return view('kundli.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate(['email' => 'required|email', 'password' => 'required']);
        $cred['email'] = strtolower($cred['email']);
        if (! Auth::attempt($cred, $request->boolean('remember', true))) {
            return back()->withErrors(['email' => 'Email or password is not correct.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $u = Auth::user();
        if ($u->status === 'disabled') {
            Auth::logout();

            return back()->withErrors(['email' => 'This account is disabled. Please contact the admin.'])->onlyInput('email');
        }

        return redirect()->intended($u->status === 'pending' ? '/pending' : '/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login?out=1');
    }

    // ---------- sign up (waits for admin approval) ----------
    public function registerForm()
    {
        KundliDb::ensure();

        return view('kundli.register', ['questions' => config('kundli.questions')]);
    }

    public function register(Request $request)
    {
        $request->merge(['email' => strtolower((string) $request->input('email'))]);
        $d = $request->validate([
            'name' => 'required|max:100',
            'email' => 'required|email|max:190|unique:users,email|not_in:' . config('kundli.admin_email'),
            'phone' => 'nullable|max:30',
            'password' => 'required|min:8|confirmed',
        ] + $this->questionRules(), ['email.not_in' => 'This email is reserved for the admin. Use the setup page instead.']);
        $user = User::create(['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?? null, 'password' => $d['password'], 'role' => 'user', 'status' => 'pending'] + $this->questionData($d));
        Auth::login($user);

        return redirect('/pending');
    }

    public function pending(Request $request)
    {
        $u = $request->user();
        if ($u->status === 'active') {
            return redirect('/');
        }

        return view('kundli.pending', ['user' => $u]);
    }

    // ---------- forgot password: email link ----------
    public function forgotForm()
    {
        return view('kundli.forgot', ['step' => 'start', 'questions' => null]);
    }

    public function forgotEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        try {
            Password::sendResetLink(['email' => strtolower($request->input('email'))]);
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('ok', 'If this email has an account, a reset link is on its way. Email must be set up on the server for this to work; otherwise use the security questions.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('kundli.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => 'required|min:8|confirmed']);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect('/login')->with('ok', 'Password changed. Please log in.')
            : back()->withErrors(['email' => __($status)]);
    }

    // ---------- forgot password: security questions ----------
    public function forgotQuestions(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $u = User::where('email', strtolower($request->input('email')))->first();
        if (! $u || ! $u->sq1 || ! $u->sq2) {
            return back()->withErrors(['email' => 'No security questions are set for this email. Use the email link, or ask the admin to reset your password.'])->onlyInput('email');
        }

        return view('kundli.forgot', ['step' => 'questions', 'email' => $u->email, 'questions' => [$u->sq1, $u->sq2]]);
    }

    public function forgotAnswer(Request $request)
    {
        $d = $request->validate(['email' => 'required|email', 'sa1' => 'required', 'sa2' => 'required', 'password' => 'required|min:8|confirmed']);
        $u = User::where('email', strtolower($d['email']))->first();
        $ok = $u && $u->sa1 && $u->sa2
            && Hash::check(KundliDb::normAnswer($d['sa1']), $u->sa1)
            && Hash::check(KundliDb::normAnswer($d['sa2']), $u->sa2);
        if (! $ok) {
            return redirect('/forgot')->withErrors(['email' => 'The answers did not match. Please try again.']);
        }
        $u->forceFill(['password' => $d['password'], 'remember_token' => Str::random(60)])->save();

        return redirect('/login')->with('ok', 'Password changed. Please log in.');
    }
}
