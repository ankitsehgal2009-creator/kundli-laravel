<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KundliAccess
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if ($u->status === 'disabled') {
            Auth::logout();

            return redirect('/login')->withErrors(['email' => 'This account is disabled. Please contact the admin.']);
        }
        if ($u->status !== 'active') {
            return $request->expectsJson() ? response()->json(['error' => 'pending'], 403) : redirect('/pending');
        }
        if (! $u->isAdmin() && ! $u->activeKey()) {
            return $request->expectsJson() ? response()->json(['error' => 'key'], 403) : redirect('/account')->with('need_key', true);
        }

        return $next($request);
    }
}
