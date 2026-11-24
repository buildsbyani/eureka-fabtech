<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit(Request $request)
    {
        return view('auth.password', ['first' => $request->user()->must_change_password]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $rules = ['password' => ['required', 'confirmed', Password::defaults()]];
        if (! $user->must_change_password) {
            $rules['current_password'] = ['required', 'current_password'];
        }
        $data = $request->validate($rules, [
            'current_password.current_password' => 'Your current password is not right.',
            'password.confirmed' => 'The two new passwords do not match.',
        ]);

        $user->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();

        return redirect()->route($user->isStaff() ? 'reception.today' : 'home')->with('ok', 'Password saved.');
    }
}
