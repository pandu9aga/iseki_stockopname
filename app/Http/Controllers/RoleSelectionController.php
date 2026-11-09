<?php

namespace App\Http\Controllers;

use App\Models\Area;
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

        $session = MemberSession::with('scale', 'partner', 'area')->firstOrCreate(
            ['nik' => $member->nik],
            ['active_role' => 'courier']
        );

        $scales = Scale::where('is_active', true)->orderBy('scale_number')->get();
        $areas  = Area::orderBy('area_letter')->get();

        return view('member.select_role', compact('member', 'session', 'scales', 'areas'));
    }

    public function switchRole(Request $request)
    {
        $request->validate([
            'role'    => 'required|in:courier,scale,pos1,pos2,returner',
            'area_id' => 'required|exists:areas,id',
        ]);

        $area = Area::findOrFail($request->area_id);

        $member = Auth::guard('member')->user();
        $session = MemberSession::firstOrCreate(
            ['nik' => $member->nik],
            ['active_role' => 'courier']
        );

        $session->active_role = $request->role;
        $session->area_id     = $area->id;
        $session->area_letter = strtoupper(trim($area->area_letter));

        // Any role change invalidates the scale setup, so returning to the
        // Timbangan menu requires selecting the partner & scale again.
        $session->scale_partner_nik = null;
        $session->scale_id          = null;
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

        // Map role → named route. Only redirect if the route already exists
        // (some modules are built in later phases). Fallback to select-role.
        $routeMap = [
            'courier'  => 'courier.index',
            'scale'    => 'scale.index',
            'pos1'     => 'manual.pos1.index',
            'pos2'     => 'manual.pos2.index',
            'returner' => 'return.index',
        ];

        $targetRoute = $routeMap[$session->active_role] ?? null;

        if ($targetRoute && \Illuminate\Support\Facades\Route::has($targetRoute)) {
            return redirect()->route($targetRoute);
        }

        // Route not yet implemented → send back to role selector with notice
        return redirect()->route('member.select-role')
            ->with('info', 'Modul untuk peran "' . $session->active_role . '" belum tersedia. Silakan pilih peran lain.');
    }
}

