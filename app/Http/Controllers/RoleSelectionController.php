<?php

namespace App\Http\Controllers;

use App\Models\MemberSession;
use App\Models\Scale;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleSelectionController extends Controller
{
    public function index()
    {
        $member = Auth::guard('member')->user();
        if (!$member) {
            return redirect()->route('page.login');
        }

        $session = MemberSession::with('scale', 'partner')->firstOrCreate(
            ['nik' => $member->nik],
            ['active_role' => 'courier']
        );

        $scales = Scale::where('is_active', true)->orderBy('scale_number')->get();

        return view('member.select_role', compact('member', 'session', 'scales'));
    }

    public function switchRole(Request $request)
    {
        $request->validate([
            'role' => 'required|in:courier,scale,pos1,pos2,returner',
        ]);

        $member = Auth::guard('member')->user();
        $session = MemberSession::firstOrCreate(
            ['nik' => $member->nik],
            ['active_role' => 'courier']
        );

        $session->active_role = $request->role;
        $session->save();

        return redirect()->route('member.role.redirect');
    }

    public function redirectByRole()
    {
        $member = Auth::guard('member')->user();
        if (!$member) {
            return redirect()->route('page.login');
        }

        $session = MemberSession::firstOrCreate(
            ['nik' => $member->nik],
            ['active_role' => 'courier']
        );

        return match ($session->active_role) {
            'courier' => redirect()->route('courier.index'),
            'scale' => redirect()->route('scale.index'),
            'pos1' => redirect()->route('manual.pos1.index'),
            'pos2' => redirect()->route('manual.pos2.index'),
            'returner' => redirect()->route('return.index'),
            default => redirect()->route('member.select-role'),
        };
    }
}

