<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Signs out deactivated users and sends people with a temporary password to set their own. */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->active) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['emp_id' => 'This account is deactivated. Contact your admin.']);
        }
        if ($user && $user->must_change_password && ! $request->routeIs('password.*', 'logout')) {
            return redirect()->route('password.edit');
        }

        return $next($request);
    }
}
