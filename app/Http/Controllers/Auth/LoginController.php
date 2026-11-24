<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'emp_id' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $credentials = ['emp_id' => strtolower(trim($data['emp_id'])), 'password' => $data['password']];
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('emp_id'))
                ->withErrors(['emp_id' => 'That ID and password do not match. Check both and try again.']);
        }

        /** @var User $user */
        $user = Auth::user();
        if (! $user->active) {
            Auth::logout();

            return back()->withErrors(['emp_id' => 'This account is deactivated. Contact your admin.']);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->must_change_password) {
            return redirect()->route('password.edit');
        }

        return redirect()->intended($user->isStaff() ? route('reception.today') : route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
