<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\Member;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('dashboard');
        }
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.login');
    }

    public function loginMember(Request $request)
    {
        $request->validate([
            'nik' => 'required',
            'password' => 'required',
        ]);

        $member = Member::where('nik', $request->nik)->first();

        if ($member && $member->password === $request->password) {
            Auth::guard('member')->login($member);
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'nik' => 'The provided credentials do not match our records.',
        ]);
    }

    public function loginAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'password' => 'required',
        ]);

        $admin = Admin::where('name', $request->name)->first();

        if ($admin && $admin->password === $request->password) {
            Auth::guard('admin')->login($admin);
            $request->session()->regenerate();
            return redirect()->intended('/admin/dashboard');
        }

        return back()->withErrors([
            'name' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
