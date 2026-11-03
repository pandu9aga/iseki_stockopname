<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberSession;
use App\Models\Scale;
use App\Models\SoCountHistory;
use App\Models\SoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ScaleController extends Controller
{
    // ======================================================================
    // ADMIN CRUD — Master Timbangan
    // ======================================================================

    /**
     * Admin: Display scales listing with DataTables.
     */
    public function adminIndex(Request $request)
    {
        if ($request->ajax()) {
            $data = Scale::query();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status_badge', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-secondary">Nonaktif</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-warning btn-sm editScale"
                                data-id="' . $row->id . '"
                                data-number="' . e($row->scale_number) . '"
                                data-name="' . e($row->scale_name) . '"
                                data-location="' . e($row->location) . '"
                                data-active="' . $row->is_active . '">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="' . route('admin.scales.destroy', $row->id) . '" method="POST" style="display:inline">
                                ' . csrf_field() . method_field('DELETE') . '
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Yakin hapus timbangan ini?\')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>';
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }
        return view('admin.scales.index');
    }

    /**
     * Admin: Store a new scale.
     */
    public function adminStore(Request $request)
    {
        $request->validate([
            'scale_number' => 'required|unique:scales,scale_number|max:50',
            'scale_name'   => 'nullable|max:100',
            'location'     => 'nullable|max:100',
            'is_active'    => 'nullable|boolean',
        ]);

        try {
            Scale::create([
                'scale_number' => $request->scale_number,
                'scale_name'   => $request->scale_name,
                'location'     => $request->location,
                'is_active'    => $request->has('is_active') ? 1 : 1, // default active
            ]);
            return redirect()->route('admin.scales.index')->with('success', 'Timbangan berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->route('admin.scales.index')->with('error', 'Gagal menambahkan: ' . $e->getMessage());
        }
    }

    /**
     * Admin: Update an existing scale.
     */
    public function adminUpdate(Request $request, Scale $scale)
    {
        $request->validate([
            'scale_number' => 'required|max:50|unique:scales,scale_number,' . $scale->id,
            'scale_name'   => 'nullable|max:100',
            'location'     => 'nullable|max:100',
        ]);

        try {
            $scale->update([
                'scale_number' => $request->scale_number,
                'scale_name'   => $request->scale_name,
                'location'     => $request->location,
                'is_active'    => $request->has('is_active') ? 1 : 0,
            ]);
            return redirect()->route('admin.scales.index')->with('success', 'Timbangan berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->route('admin.scales.index')->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
    }

    /**
     * Admin: Delete a scale.
     */
    public function adminDestroy(Scale $scale)
    {
        $scale->delete();
        return redirect()->route('admin.scales.index')->with('success', 'Timbangan berhasil dihapus.');
    }

    // ======================================================================
    // MEMBER — Modul Timbangan (Scale Weighing)
    // ======================================================================

    /**
     * Member: Show the scale module main page.
     * If partner/scale not yet set → force setup modal.
     */
    public function index()
    {
        $member  = Auth::guard('member')->user();
        $session = MemberSession::with('scale')->firstOrCreate(
            ['nik' => $member->nik],
            ['active_role' => 'scale']
        );

        $scales = Scale::where('is_active', true)->orderBy('scale_number')->get();

        // Fetch partner info if set
        $partner = null;
        if ($session->scale_partner_nik) {
            $partner = DB::connection('rifa')
                ->table('employees')
                ->where('nik', $session->scale_partner_nik)
                ->first();
        }

        // Get recent items ready for counting (for the list view)
        $readyItems = SoItem::where('current_step', 'READY_TO_COUNT')
            ->orderBy('courier_at', 'desc')
            ->limit(50)
            ->get();

        return view('scale.index', compact('member', 'session', 'scales', 'partner', 'readyItems'));
    }

    /**
     * Member: AJAX — Validate partner NIK against iseki_rifa.employees.
     */
    public function validatePartner(Request $request)
    {
        $request->validate(['partner_nik' => 'required|string|max:50']);

        $employee = DB::connection('rifa')
            ->table('employees')
            ->where('nik', $request->partner_nik)
            ->first();

        if (!$employee) {
            return response()->json([
                'valid'   => false,
                'message' => 'NIK Partner tidak ditemukan di database karyawan.',
            ]);
        }

        // Ensure partner isn't the same user
        $member = Auth::guard('member')->user();
        if ($employee->nik === $member->nik) {
            return response()->json([
                'valid'   => false,
                'message' => 'NIK Partner tidak boleh sama dengan NIK Anda sendiri.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'data'  => [
                'nik'  => $employee->nik,
                'nama' => $employee->nama ?? $employee->nik,
            ],
        ]);
    }

    /**
     * Member: AJAX — Search partners (employees) by name or NIK.
     * Returns Select2-compatible payload: { results: [{id, text, nama, nik}], pagination: {more} }.
     */
    public function searchPartners(Request $request)
    {
        $q       = trim((string) $request->input('q', ''));
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $query = DB::connection('rifa')->table('employees');

        // Do not offer the currently logged-in member as their own partner.
        $member = Auth::guard('member')->user();
        if ($member && $member->nik) {
            $query->where('nik', '!=', $member->nik);
        }

        // Search by NIK or by employee name.
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nik', 'like', '%' . $q . '%')
                    ->orWhere('nama', 'like', '%' . $q . '%');
            });
        }

        // Fetch one extra row to know whether another page exists.
        $rows = $query->orderBy('nama')
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->get(['nik', 'nama']);

        $hasMore = $rows->count() > $perPage;
        $rows    = $rows->take($perPage);

        $results = $rows->map(function ($row) {
            $nama = $row->nama ?: $row->nik;

            return [
                'id'   => $row->nik,
                'text' => $nama . ' — ' . $row->nik,
                'nama' => $nama,
                'nik'  => $row->nik,
            ];
        })->values();

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => $hasMore],
        ]);
    }

    /**
     * Member: Save partner & scale selection into member_sessions.
     */
    public function setupPartner(Request $request)
    {
        $request->validate([
            'partner_nik' => 'required|string|max:50',
            'scale_id'    => 'required|exists:scales,id',
        ]);

        // Validate partner exists
        $employee = DB::connection('rifa')
            ->table('employees')
            ->where('nik', $request->partner_nik)
            ->first();

        if (!$employee) {
            return back()->with('error', 'NIK Partner tidak ditemukan di database karyawan.');
        }

        $member  = Auth::guard('member')->user();
        $session = MemberSession::firstOrCreate(
            ['nik' => $member->nik],
            ['active_role' => 'scale']
        );

        $session->update([
            'scale_partner_nik' => $request->partner_nik,
            'scale_id'          => $request->scale_id,
        ]);

        return redirect()->route('scale.index')->with('success', 'Partner dan timbangan berhasil diatur.');
    }

    /**
     * Member: AJAX — Lookup SO item by tracking_code (scanned QR).
     */
    public function lookupItem(Request $request)
    {
        $request->validate(['tracking_code' => 'required|string']);

        $item = SoItem::where('tracking_code', $request->tracking_code)->first();

        if (!$item) {
            return response()->json([
                'found'   => false,
                'message' => 'Kode tracking tidak ditemukan.',
            ]);
        }

        if ($item->current_step !== 'READY_TO_COUNT') {
            $stepLabels = [
                'COURIER'         => 'Masih di tahap Kurir (belum selesai proses kurir)',
                'POS1_DONE'       => 'Sudah diproses di Pos 1 (gunakan menu Pos 2)',
                'MISMATCH_NG'     => 'Status mismatch NG, perlu koreksi di Pos Manual',
                'READY_TO_RETURN' => 'Sudah selesai dihitung, menunggu pengembalian',
                'RETURNED'        => 'Sudah dikembalikan ke rak',
            ];
            $msg = $stepLabels[$item->current_step] ?? 'Status tidak valid untuk timbangan';

            return response()->json([
                'found'   => true,
                'allowed' => false,
                'message' => 'Tidak bisa diproses: ' . $msg,
                'item'    => $this->formatItemResponse($item),
            ]);
        }

        return response()->json([
            'found'   => true,
            'allowed' => true,
            'item'    => $this->formatItemResponse($item),
        ]);
    }

    /**
     * Member: Submit scale weighing result.
     */
    public function submitWeighing(Request $request)
    {
        $request->validate([
            'so_item_id'         => 'required|exists:so_items,id',
            'weight_no_sample'   => 'required|numeric|min:0.0001',
            'weight_with_sample' => 'required|numeric|min:0.0001',
            'sample_qty'         => 'required|integer|min:1',
        ]);

        $member  = Auth::guard('member')->user();
        $session = MemberSession::where('nik', $member->nik)->first();

        if (!$session || !$session->scale_partner_nik || !$session->scale_id) {
            return response()->json([
                'success' => false,
                'message' => 'Partner dan timbangan belum diatur. Silakan setup terlebih dahulu.',
            ], 422);
        }

        $item = SoItem::findOrFail($request->so_item_id);

        if ($item->current_step !== 'READY_TO_COUNT') {
            return response()->json([
                'success' => false,
                'message' => 'Item ini tidak dalam status siap hitung.',
            ], 422);
        }

        $scale = Scale::find($session->scale_id);

        // ── Margin of Error Calculation ──
        $wNoSample   = (float) $request->weight_no_sample;
        $wWithSample = (float) $request->weight_with_sample;
        $sampleQty   = (int)   $request->sample_qty;

        // Guard: sample_qty
        if ($sampleQty < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah sample minimal 1.',
            ], 422);
        }

        // Weight per piece from sample
        $sampleWeight = $wWithSample - $wNoSample;
        if ($sampleWeight <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Berat dengan sample harus lebih besar dari berat tanpa sample.',
            ], 422);
        }

        $weightPerPiece = $sampleWeight / $sampleQty;

        // Exact count (unrounded), then integer estimate
        $exactCount     = $wNoSample / $weightPerPiece;
        $estimatedCount = (int) round($exactCount);

        // Error margin = |exactCount - estimatedCount| / exactCount * 100
        $errorMargin = $exactCount > 0
            ? abs($exactCount - $estimatedCount) / $exactCount * 100
            : 0;
        $errorMargin = round($errorMargin, 2);

        $status = $errorMargin >= 2.0 ? 'NG' : 'OK';

        // Save count history (always — including NG attempts, for audit trail)
        $history = SoCountHistory::create([
            'so_item_id'           => $item->id,
            'count_type'           => 'SCALE',
            'member_nik'           => $member->nik,
            'partner_nik'          => $session->scale_partner_nik,
            'scale_id'             => $session->scale_id,
            'weight_no_sample'     => $wNoSample,
            'weight_with_sample'   => $wWithSample,
            'sample_qty'           => $sampleQty,
            'error_margin_percent' => $errorMargin,
            'result_count'         => $estimatedCount,
            'status'               => $status,
            'notes'                => $request->notes,
        ]);

        if ($status === 'NG') {
            return response()->json([
                'success'      => false,
                'status'       => 'NG',
                'message'      => 'Margin of Error ≥ 2% (' . $errorMargin . '%). Hasil DITOLAK. Silakan kalibrasi ulang dan coba lagi.',
                'errorMargin'  => $errorMargin,
                'resultCount'  => $estimatedCount,
                'history_id'   => $history->id,
            ]);
        }

        // Status OK → update so_item
        $item->update([
            'process_type'       => 'SCALE',
            'current_step'       => 'READY_TO_RETURN',
            'final_count'        => $estimatedCount,
            'scale_id'           => $session->scale_id,
            'scale_number'       => $scale ? $scale->scale_number : null,
            'scale_user_nik'     => $member->nik,
            'scale_partner_nik'  => $session->scale_partner_nik,
            'count_completed_at' => now(),
        ]);

        // Fetch partner name for label (try common field names)
        $partnerData = DB::connection('rifa')
            ->table('employees')
            ->where('nik', $session->scale_partner_nik)
            ->first();

        $partnerName = $partnerData
            ? ($partnerData->nama ?? $partnerData->name ?? $partnerData->employee_name ?? $session->scale_partner_nik)
            : $session->scale_partner_nik;

        return response()->json([
            'success'      => true,
            'status'       => 'OK',
            'message'      => 'Hasil timbangan OK! Error margin: ' . $errorMargin . '%',
            'errorMargin'  => $errorMargin,
            'resultCount'  => $estimatedCount,
            'history_id'   => $history->id,
            'printData'    => [
                'tracking_code'  => $item->tracking_code,
                'rack_no'        => $item->rack_no,
                'code_part'      => $item->code_part,
                'name_part'      => $item->name_part,
                'final_count'    => $estimatedCount,
                'scale_number'   => $scale ? $scale->scale_number : '-',
                'user_nik'       => $member->nik,
                'user_name'      => $member->nama,
                'partner_nik'    => $session->scale_partner_nik,
                'partner_name'   => $partnerName,
                'completed_at'   => now()->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Member: AJAX — Get weighing history for a specific item.
     */
    public function itemHistory(Request $request)
    {
        $request->validate(['so_item_id' => 'required|exists:so_items,id']);

        $histories = SoCountHistory::where('so_item_id', $request->so_item_id)
            ->where('count_type', 'SCALE')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($h) {
                return [
                    'id'             => $h->id,
                    'member_nik'     => $h->member_nik,
                    'partner_nik'    => $h->partner_nik,
                    'weight_no'      => $h->weight_no_sample,
                    'weight_with'    => $h->weight_with_sample,
                    'sample_qty'     => $h->sample_qty,
                    'error_margin'   => $h->error_margin_percent,
                    'result_count'   => $h->result_count,
                    'status'         => $h->status,
                    'notes'          => $h->notes,
                    'created_at'     => $h->created_at ? $h->created_at->format('d/m/Y H:i:s') : '-',
                ];
            });

        return response()->json(['histories' => $histories]);
    }

    // ── Helper ──

    private function formatItemResponse(SoItem $item): array
    {
        return [
            'id'            => $item->id,
            'tracking_code' => $item->tracking_code,
            'rack_no'       => $item->rack_no,
            'code_rack'     => $item->code_rack,
            'area'          => $item->area,
            'cell'          => $item->cell,
            'code_part'     => $item->code_part,
            'name_part'     => $item->name_part,
            'current_step'  => $item->current_step,
            'courier_nik'   => $item->courier_nik,
            'courier_at'    => $item->courier_at ? $item->courier_at->format('d/m/Y H:i') : '-',
        ];
    }
}
