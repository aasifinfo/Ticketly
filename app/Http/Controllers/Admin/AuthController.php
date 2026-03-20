<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminAuth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (AdminAuth::user()) {
            return redirect()->route('admin.dashboard');
        }

        return view('organiser.auth.login', [
            'loginContext' => 'admin',
            'loginAction' => route('organiser.login'),
        ]);
    }

    public function logout(Request $request)
    {
        AdminAuth::logout();
        return redirect()->route('organiser.login')->with('info', 'You have been logged out.');
    }
}
