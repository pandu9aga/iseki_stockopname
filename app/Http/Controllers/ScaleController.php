<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberSession;
use App\Models\Scale;
use App\Models\SoCountHistory;
use App\Models\SoItem;
use App\Models\SoPhoto;
use App\Models\SoScaleRound;
use App\Models\SoScaleSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        $code = trim($request->tracking_code);

        // 1. Direct search by tracking_code
        $item = SoItem::where('tracking_code', $code)->first();

        // 2. If not found and input is formatted as "part|name|rak|loc", parse and match
        if (!$item && str_contains($code, '|')) {
            $parts = explode('|', $code);
            $partCode = trim($parts[0] ?? '');
            $rackNo   = trim($parts[2] ?? '');
            $locCell  = trim($parts[3] ?? '');

            $query = SoItem::query();
            if ($partCode !== '') {
                $query->where('code_part', $partCode);
            }
            if ($rackNo !== '') {
                $query->where('rack_no', $rackNo);
            }
            if ($locCell !== '') {
                $locParts = explode('-', $locCell);
                $loc  = trim($locParts[0] ?? '');
                $cell = trim($locParts[1] ?? '');
                if ($loc !== '') $query->where('area', $loc);
                if ($cell !== '') $query->where('cell', $cell);
            }

            // Get latest active item in process
            $item = $query->orderBy('created_at', 'desc')->first();
        }

        if (!$item) {
            return response()->json([
                'found'   => false,
                'message' => 'Data part / kode tracking tidak ditemukan dalam sistem.',
            ]);
        }

        // Sesi terakhir milik item (bisa lebih dari satu jika pernah dihitung ulang).
        $scaleSession = SoScaleSession::with('rounds')
            ->where('so_item_id', $item->id)
            ->orderByDesc('id')
            ->first();

        // Status hitung manual untuk notifikasi lintas pos:
        // baru sampai Pos 1, sudah sampai Pos 2 tetapi belum cocok (NG),
        // atau sudah lengkap Pos 1 & Pos 2.
        $manualStage     = $item->manualCountStage();
        $manualCompleted = $manualStage === 'COMPLETED';

        $manualPos1Count = $item->pos1_count;
        if ($manualPos1Count === null && $manualStage !== 'NONE') {
            $manualPos1Count = SoCountHistory::where('so_item_id', $item->id)
                ->where('count_type', 'POS1')
                ->orderByDesc('id')
                ->value('result_count');
        }

        $manualNote = match ($manualStage) {
            'POS1_ONLY' => 'Item Sudah di hitung di Pos 1 Manual.',
            'MISMATCH'  => 'Item ini sudah dihitung sampai Pos 2 Manual, tetapi hasil Pos 1 dan Pos 2 belum cocok (NG).',
            'COMPLETED' => 'Item ini sudah selesai dihitung di Pos Manual (Pos 1 & Pos 2).',
            default     => null,
        };

        // Hanya pemberitahuan: hitung di timbangan tetap diperbolehkan.
        $manualWarning = $manualNote
            ? $manualNote . ($manualStage === 'POS1_ONLY'
                ? ' Item tetap bisa di hitung di timbangan.'
                : ' Anda tetap bisa menghitung di pos timbangan; hasil manual tetap tersimpan.')
            : null;

        if ($item->current_step === 'RETURNED') {
            // Izinkan cetak ulang label QR final timbangan jika item sudah pernah ditimbang / memiliki final count
            if ($scaleSession || $item->final_count !== null) {
                $printData = $this->buildPrintData($item, $scaleSession, (int) (($scaleSession ? $scaleSession->final_count : null) ?? $item->final_count));

                return response()->json([
                    'found'            => true,
                    'allowed'          => true,
                    'already_counted'  => true,
                    'needs_final_flow' => true,
                    'can_recount'      => false, // RETURNED tidak boleh dihitung ulang
                    'is_reprint'       => true,
                    'is_returned'      => true,
                    'manual_completed' => $manualCompleted,
                    'manual_stage'     => $manualStage,
                    'manual_pos1_count' => $manualPos1Count,
                    'warning'          => 'Item sudah berstatus RETURNED (sudah dikembalikan ke rak). Hanya cetak ulang label QR final yang diizinkan.',
                    'message'          => 'Item sudah berstatus RETURNED. Anda dapat mencetak ulang stiker label final.',
                    'item'             => $this->formatItemResponse($item),
                    'session'          => $scaleSession ? $this->formatScaleSession($scaleSession) : null,
                    'printData'        => $printData,
                    'final_count'      => ($scaleSession ? $scaleSession->final_count : null) ?? $item->final_count,
                ]);
            }

            return response()->json([
                'found'           => true,
                'allowed'         => false,
                'already_counted' => false,
                'manual_completed' => $manualCompleted,
                'manual_stage'    => $manualStage,
                'manual_pos1_count' => $manualPos1Count,
                'message'         => 'Item sudah berstatus RETURNED (sudah dikembalikan ke rak). Tidak dapat dihitung ulang.',
                'item'            => $this->formatItemResponse($item),
            ]);
        }

        // Item sudah pernah menyelesaikan penimbangan (final OK, label sudah
        // tercetak, atau selesai) tetapi belum dikembalikan ke rak. Sama seperti
        // Pos 1 & Pos 2: tetap boleh dihitung ulang, atau hanya cetak ulang QR.
        if ($scaleSession && in_array($scaleSession->status, ['FINAL_WEIGHED', 'PRINTED', 'COMPLETED'], true)) {
            $printData = $this->buildPrintData($item, $scaleSession, (int) ($scaleSession->final_count ?? $item->final_count));

            $warning = 'Item ini sudah selesai ditimbang dan belum dikembalikan ke rak. Anda tetap bisa menghitung ulang di timbangan atau hanya mencetak ulang label QR. Hasil sebelumnya tetap tersimpan sebagai riwayat.';
            if ($manualNote) {
                $warning .= ' ' . $manualNote;
            }

            return response()->json([
                'found'            => true,
                'allowed'          => true,
                'already_counted'  => true,
                'needs_final_flow' => true,
                'can_recount'      => true,
                'is_reprint'       => $scaleSession->status === 'COMPLETED',
                'manual_completed' => $manualCompleted,
                'manual_stage'     => $manualStage,
                'manual_pos1_count' => $manualPos1Count,
                'warning'          => $warning,
                'message'          => 'Item sudah pernah ditimbang. Anda dapat menghitung ulang atau mencetak ulang QR label.',
                'item'             => $this->formatItemResponse($item),
                'session'          => $this->formatScaleSession($scaleSession),
                'printData'        => $printData,
                'final_count'      => $scaleSession->final_count ?? $item->final_count,
            ]);
        }

        // Boleh dihitung di timbangan selama belum RETURNED (misal: READY_TO_COUNT, POS1_DONE, MISMATCH_NG, maupun READY_TO_RETURN dari manual)

        $sessionState = null;
        $printData    = null;

        if ($scaleSession && $scaleSession->status !== 'COMPLETED') {
            $sessionState = $this->formatScaleSession($scaleSession);

            if (in_array($scaleSession->status, ['FINAL_WEIGHED', 'PRINTED'], true)) {
                $printData = $this->buildPrintData($item, $scaleSession, (int) $scaleSession->final_count);
            }
        }

        return response()->json([
            'found'            => true,
            'allowed'          => true,
            'manual_completed' => $manualCompleted,
            'manual_stage'     => $manualStage,
            'manual_pos1_count' => $manualPos1Count,
            'warning'          => $manualWarning,
            'item'             => $this->formatItemResponse($item),
            'session'          => $sessionState,
            'printData'        => $printData,
        ]);
    }

    /**
     * Member: AJAX — Save the sample quantity (jumlah pcs sample).
     * Langkah pertama sebelum penimbangan tanpa sample.
     */
    public function submitSampleQty(Request $request)
    {
        $request->validate([
            'so_item_id' => 'required|exists:so_items,id',
            'sample_qty' => 'required|integer|min:1|max:100000',
        ]);

        $member        = Auth::guard('member')->user();
        $memberSession = MemberSession::where('nik', $member->nik)->first();

        if (!$memberSession || !$memberSession->scale_partner_nik || !$memberSession->scale_id) {
            return response()->json([
                'success' => false,
                'message' => 'Partner dan timbangan belum diatur. Silakan setup terlebih dahulu.',
            ], 422);
        }

        try {
            $scaleSession = DB::transaction(function () use ($request, $member, $memberSession) {
                $item = SoItem::lockForUpdate()->findOrFail($request->so_item_id);

                $isReturnStep = in_array($item->current_step, ['READY_TO_RETURN', 'RETURNED'], true);

                if ($item->current_step === 'RETURNED') {
                    throw new \RuntimeException('Item sudah berstatus RETURNED (sudah dikembalikan ke rak).');
                }

                $scaleSession = SoScaleSession::where('so_item_id', $item->id)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                // Sesi terakhir sudah final / selesai → mulai sesi hitungan ulang
                // yang baru. Sesi lama tetap disimpan sebagai riwayat, sehingga
                // hasil timbangan sebelumnya tidak hilang.
                if ($scaleSession && in_array($scaleSession->status, ['FINAL_WEIGHED', 'PRINTED', 'COMPLETED'], true)) {
                    $scaleSession = null;
                }

                if (!$scaleSession) {
                    $scaleSession = SoScaleSession::create([
                        'so_item_id'  => $item->id,
                        'member_nik'  => $member->nik,
                        'partner_nik' => $memberSession->scale_partner_nik,
                        'scale_id'    => $memberSession->scale_id,
                        'status'      => 'DRAFT',
                        'round_count' => 0,
                    ]);
                }

                // Hitung ulang: kembalikan item ke status siap hitung agar
                // tahap hitungan & final bisa dilanjutkan.
                if ($item->current_step !== 'READY_TO_COUNT') {
                    $item->update([
                        'process_type' => 'SCALE',
                        'current_step' => 'READY_TO_COUNT',
                    ]);
                }

                $scaleSession->update(['sample_qty' => (int) $request->sample_qty]);

                return $scaleSession->fresh('rounds');
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jumlah sample ' . (int) $request->sample_qty . ' pcs tersimpan.',
            'session' => $this->formatScaleSession($scaleSession),
        ]);
    }

    /**
     * Member: AJAX — Save one weighing round without sample (weight + photo).
     * Boleh diulang beberapa kali (hitungan 1, 2, 3, ...).
     */
    public function submitSampleRound(Request $request)
    {
        $request->validate([
            'so_item_id'       => 'required|exists:so_items,id',
            'weight_no_sample' => 'required|integer|min:1',
            'photo'            => 'required|image|max:15360',
        ]);

        $member        = Auth::guard('member')->user();
        $memberSession = MemberSession::where('nik', $member->nik)->first();

        if (!$memberSession || !$memberSession->scale_partner_nik || !$memberSession->scale_id) {
            return response()->json([
                'success' => false,
                'message' => 'Partner dan timbangan belum diatur. Silakan setup terlebih dahulu.',
            ], 422);
        }

        try {
            [$scaleSession, $round] = DB::transaction(function () use ($request, $member, $memberSession) {
                $item = SoItem::lockForUpdate()->findOrFail($request->so_item_id);

                if ($item->current_step === 'RETURNED') {
                    throw new \RuntimeException('Item sudah berstatus RETURNED (sudah dikembalikan ke rak).');
                }

                $scaleSession = SoScaleSession::where('so_item_id', $item->id)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($scaleSession && $scaleSession->status === 'COMPLETED') {
                    throw new \RuntimeException('Sesi sebelumnya sudah selesai. Isi jumlah sample lagi untuk memulai hitungan ulang.');
                }

                if ($scaleSession && in_array($scaleSession->status, ['FINAL_WEIGHED', 'PRINTED'], true)) {
                    throw new \RuntimeException('Sesi item ini sudah masuk tahap final. Selesaikan proses final terlebih dahulu.');
                }

                if (!$scaleSession) {
                    $scaleSession = SoScaleSession::create([
                        'so_item_id'  => $item->id,
                        'member_nik'  => $member->nik,
                        'partner_nik' => $memberSession->scale_partner_nik,
                        'scale_id'    => $memberSession->scale_id,
                        'status'      => 'DRAFT',
                        'round_count' => 0,
                    ]);
                }

                if (!$scaleSession->sample_qty) {
                    throw new \RuntimeException('Isi jumlah sample terlebih dahulu sebelum menimbang.');
                }

                $roundNo = SoScaleRound::where('session_id', $scaleSession->id)
                    ->where('stage', 'SAMPLE')
                    ->count() + 1;

                $round = SoScaleRound::create([
                    'session_id'       => $scaleSession->id,
                    'so_item_id'       => $item->id,
                    'round_no'         => $roundNo,
                    'stage'            => 'SAMPLE',
                    'weight_no_sample' => $request->weight_no_sample,
                    'status'           => 'OK',
                ]);

                $photoPath = $this->saveScalePhoto($request->file('photo'), $item, 'round' . $roundNo);
                $round->update(['photo_path' => $photoPath]);

                SoPhoto::create([
                    'so_item_id'     => $item->id,
                    'scale_round_id' => $round->id,
                    'pos_type'       => 'SCALE',
                    'photo_role'     => 'ROUND',
                    'photo_path'     => $photoPath,
                ]);

                $scaleSession->update(['round_count' => $roundNo]);

                return [$scaleSession->fresh('rounds'), $round->fresh()];
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hitungan ke-' . $round->round_no . ' berhasil disimpan.',
            'round'   => $this->formatScaleRound($round),
            'session' => $this->formatScaleSession($scaleSession),
        ]);
    }


    /**
     * Member: AJAX — Save the final weighing (total dengan sample + foto).
     * MoE dihitung dari total dengan sample terhadap total tanpa sample
     * (gabungan seluruh hitungan tanpa sample). NG boleh diulang.
     */
    public function submitFinalWeighing(Request $request)
    {
        $request->validate([
            'so_item_id'         => 'required|exists:so_items,id',
            'weight_with_sample' => 'required|integer|min:1',
            'photo'              => 'required|image|max:15360',
        ]);

        $member        = Auth::guard('member')->user();
        $memberSession = MemberSession::where('nik', $member->nik)->first();

        if (!$memberSession || !$memberSession->scale_partner_nik || !$memberSession->scale_id) {
            return response()->json([
                'success' => false,
                'message' => 'Partner dan timbangan belum diatur. Silakan setup terlebih dahulu.',
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request, $member, $memberSession) {
                $item = SoItem::lockForUpdate()->findOrFail($request->so_item_id);

                if ($item->current_step === 'RETURNED') {
                    throw new \RuntimeException('Item sudah berstatus RETURNED (sudah dikembalikan ke rak).');
                }

                $scaleSession = SoScaleSession::where('so_item_id', $item->id)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if (!$scaleSession) {
                    throw new \RuntimeException('Belum ada hitungan tanpa sample. Simpan hitungan terlebih dahulu.');
                }

                if ($scaleSession->status === 'COMPLETED') {
                    throw new \RuntimeException('Sesi sebelumnya sudah selesai. Mulai hitungan ulang dari tahap sample.');
                }

                if (in_array($scaleSession->status, ['FINAL_WEIGHED', 'PRINTED'], true)) {
                    throw new \RuntimeException('Hasil final untuk item ini sudah tersimpan. Lanjutkan cetak QR / foto bukti.');
                }

                if (!$scaleSession->sample_qty) {
                    throw new \RuntimeException('Jumlah sample belum diisi. Ulangi dari tahap sample.');
                }

                $sampleQty = (int) $scaleSession->sample_qty;

                $totalWithout = (float) SoScaleRound::where('session_id', $scaleSession->id)
                    ->where('stage', 'SAMPLE')
                    ->sum('weight_no_sample');

                if ($totalWithout <= 0) {
                    throw new \RuntimeException('Belum ada hitungan tanpa sample. Simpan hitungan terlebih dahulu.');
                }

                $wWithSample = (float) $request->weight_with_sample;

                if ($wWithSample <= $totalWithout) {
                    throw new \RuntimeException('Total dengan sample harus lebih besar dari total tanpa sample (' . $totalWithout . ' pcs).');
                }

                // Target total = total tanpa sample + jumlah sample
                $targetTotal = $totalWithout + $sampleQty;
                $selisih     = abs($wWithSample - $targetTotal);

                // Margin of error dihitung dari inputan total dengan sample:
                // MoE = (|totalDenganSample - targetTotal| / totalDenganSample) * 100%
                // Contoh: tanpa sample 101 pcs + sample 50 pcs -> target 151 pcs.
                // Input 152 pcs -> selisih 1 pcs -> margin = (1 / 152) * 100 = 0.66% (< 2% -> OK).
                $finalWithSampleCount = (int) round($wWithSample);
                $errorMargin          = ($selisih / $wWithSample) * 100;
                $errorMargin          = round($errorMargin, 2);

                $status = $errorMargin >= 2.0 ? 'NG' : 'OK';

                $finalNo = SoScaleRound::where('session_id', $scaleSession->id)
                    ->where('stage', 'FINAL')
                    ->count() + 1;

                $round = SoScaleRound::create([
                    'session_id'           => $scaleSession->id,
                    'so_item_id'           => $item->id,
                    'round_no'             => $finalNo,
                    'stage'                => 'FINAL',
                    'weight_no_sample'     => $totalWithout,
                    'weight_with_sample'   => $wWithSample,
                    'sample_qty'           => $sampleQty,
                    'error_margin_percent' => $errorMargin,
                    'result_count'         => $finalWithSampleCount,
                    'status'               => $status,
                ]);

                $photoPath = $this->saveScalePhoto($request->file('photo'), $item, 'final' . $finalNo);
                $round->update(['photo_path' => $photoPath]);

                // Foto final disimpan sebagai draft; hasil resmi baru dibuat saat simpan akhir.
                SoPhoto::create([
                    'so_item_id'     => $item->id,
                    'history_id'     => null,
                    'scale_round_id' => $round->id,
                    'pos_type'       => 'SCALE',
                    'photo_role'     => 'FINAL',
                    'photo_path'     => $photoPath,
                ]);

                if ($status !== 'OK') {
                    // NG: hitungan final tetap tersimpan sebagai riwayat (Final 1, 2, ...).
                    return [
                        'status'  => 'NG',
                        'round'   => $round->fresh(),
                        'session' => $scaleSession->fresh('rounds'),
                    ];
                }

                // OK → draft final. Hasil resmi + perubahan status item
                // baru dilakukan setelah QR dicetak dan foto bukti diunggah.
                $scaleSession->update([
                    'status'               => 'FINAL_WEIGHED',
                    'final_count'          => $finalWithSampleCount,
                    'final_margin'         => $errorMargin,
                    'total_without_sample' => $totalWithout,
                    'total_with_sample'    => $wWithSample,
                ]);

                return [
                    'status'    => 'OK',
                    'round'     => $round->fresh(),
                    'session'   => $scaleSession->fresh('rounds'),
                    'item'      => $item,
                    'printData' => $this->buildPrintData($item, $scaleSession->fresh(), $finalWithSampleCount),
                ];
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $round       = $result['round'];
        $errorMargin = $round->error_margin_percent !== null ? (float) $round->error_margin_percent : 0;
        $estimated   = $round->result_count !== null ? (int) $round->result_count : 0;

        if ($result['status'] === 'NG') {
            $delta      = (float) $round->weight_with_sample - (float) $round->weight_no_sample;
            $sampleDiff = (int) $delta - (int) $round->sample_qty;
            $diffText   = $sampleDiff > 0 ? 'lebih ' . abs($sampleDiff) : 'kurang ' . abs($sampleDiff);

            return response()->json([
                'success'      => false,
                'status'       => 'NG',
                'message'      => 'Margin of Error ≥ 2% (' . $errorMargin . '%). Selisih sample ' . $diffText . ' pcs dari ' . (int) $round->sample_qty . ' pcs. Hitung ulang / periksa hitungan.',
                'errorMargin'  => $errorMargin,
                'resultCount'  => $estimated,
                'round'        => $this->formatScaleRound($round),
                'session'      => $this->formatScaleSession($result['session']),
            ]);
        }

        return response()->json([
            'success'      => true,
            'status'       => 'OK',
            'message'      => 'Hasil timbangan OK! Error margin: ' . $errorMargin . '%',
            'errorMargin'  => $errorMargin,
            'resultCount'  => $estimated,
            'round'        => $this->formatScaleRound($round),
            'session'      => $this->formatScaleSession($result['session']),
            'printData'    => $result['printData'],
        ]);
    }


    /**
     * Member: AJAX — Mark the final QR label as printed.
     */
    public function markPrinted(Request $request)
    {
        $request->validate(['so_item_id' => 'required|exists:so_items,id']);

        $scaleSession = SoScaleSession::where('so_item_id', $request->so_item_id)
            ->orderByDesc('id')
            ->first();

        if (!$scaleSession) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi penimbangan tidak ditemukan.',
            ], 422);
        }

        if ($scaleSession->status === 'COMPLETED') {
            return response()->json(['success' => true, 'status' => 'COMPLETED']);
        }

        if (!in_array($scaleSession->status, ['FINAL_WEIGHED', 'PRINTED'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Hasil final belum tersimpan. Selesaikan timbang final terlebih dahulu.',
            ], 422);
        }

        if ($scaleSession->status === 'FINAL_WEIGHED') {
            $scaleSession->update(['status' => 'PRINTED']);
        }

        return response()->json([
            'success' => true,
            'status'  => $scaleSession->status,
        ]);
    }

    /**
     * Member: AJAX — Upload proof photo after the QR label is printed.
     * Completes the session and moves the item to READY_TO_RETURN.
     */
    public function uploadLabelProof(Request $request)
    {
        $request->validate([
            'so_item_id' => 'required|exists:so_items,id',
            'photo'      => 'required|image|max:15360',
        ]);

        $member = Auth::guard('member')->user();

        try {
            $item = DB::transaction(function () use ($request, $member) {
                $item = SoItem::lockForUpdate()->findOrFail($request->so_item_id);

                $scaleSession = SoScaleSession::where('so_item_id', $item->id)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if (!$scaleSession) {
                    throw new \RuntimeException('Sesi penimbangan tidak ditemukan.');
                }

                if ($scaleSession->status === 'COMPLETED') {
                    throw new \RuntimeException('Penimbangan item ini sudah selesai.');
                }

                if ($scaleSession->status !== 'PRINTED') {
                    throw new \RuntimeException('Label QR belum dicetak. Cetak label final terlebih dahulu.');
                }

                $finalRound = SoScaleRound::where('session_id', $scaleSession->id)
                    ->where('stage', 'FINAL')
                    ->where('status', 'OK')
                    ->orderByDesc('round_no')
                    ->first();

                if (!$finalRound) {
                    throw new \RuntimeException('Data hasil timbang final tidak ditemukan.');
                }

                $photoPath = $this->saveScalePhoto($request->file('photo'), $item, 'label');

                // ── SIMPAN RESMI ──
                // Baru terjadi setelah QR dicetak dan foto bukti diunggah.
                $history = SoCountHistory::create([
                    'so_item_id'           => $item->id,
                    'count_type'           => 'SCALE',
                    'member_nik'           => $member->nik,
                    'partner_nik'          => $scaleSession->partner_nik,
                    'scale_id'             => $scaleSession->scale_id,
                    'weight_no_sample'     => $finalRound->weight_no_sample,
                    'weight_with_sample'   => $finalRound->weight_with_sample,
                    'sample_qty'           => $finalRound->sample_qty,
                    'error_margin_percent' => $finalRound->error_margin_percent,
                    'result_count'         => $finalRound->result_count,
                    'status'               => 'OK',
                    'notes'                => $finalRound->notes,
                ]);

                SoPhoto::where('scale_round_id', $finalRound->id)->update(['history_id' => $history->id]);

                SoPhoto::create([
                    'so_item_id' => $item->id,
                    'pos_type'   => 'SCALE',
                    'photo_role' => 'LABEL_PROOF',
                    'photo_path' => $photoPath,
                ]);

                $scale = Scale::find($scaleSession->scale_id);

                $scaleSession->update([
                    'status'           => 'COMPLETED',
                    'label_photo_path' => $photoPath,
                ]);

                $item->update([
                    'process_type'       => 'SCALE',
                    'current_step'       => 'READY_TO_RETURN',
                    'final_count'        => $scaleSession->final_count,
                    'scale_id'           => $scaleSession->scale_id,
                    'scale_number'       => $scale ? $scale->scale_number : null,
                    'scale_user_nik'     => $member->nik,
                    'scale_partner_nik'  => $scaleSession->partner_nik,
                    'count_completed_at' => now(),
                ]);

                return $item->fresh();
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Penimbangan selesai. Item siap dikembalikan ke rak.',
            'redirect'     => route('scale.index'),
            'final_count'  => (int) $item->final_count,
            'current_step' => $item->current_step,
        ]);
    }

    /**
     * Member: AJAX — Cancel an unfinished draft session (before printing).
     */
    public function cancelSession(Request $request)
    {
        $request->validate(['so_item_id' => 'required|exists:so_items,id']);

        $scaleSession = SoScaleSession::where('so_item_id', $request->so_item_id)
            ->orderByDesc('id')
            ->first();

        if (!$scaleSession) {
            return response()->json(['success' => true]);
        }

        if (in_array($scaleSession->status, ['PRINTED', 'COMPLETED'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Label QR sudah dicetak. Selesaikan foto bukti, tidak bisa dibatalkan.',
            ], 422);
        }

        DB::transaction(function () use ($scaleSession) {
            $itemId = $scaleSession->so_item_id;

            SoPhoto::whereNotNull('scale_round_id')
                ->whereIn('scale_round_id', $scaleSession->rounds()->pluck('id'))
                ->delete();

            $scaleSession->rounds()->delete();
            $scaleSession->delete();

            // Jika draft ini adalah hitungan ulang, kembalikan item ke status
            // sebelumnya. Item hanya dianggap siap kembali ke rak jika sudah
            // ada label yang tercetak (PRINTED/COMPLETED) atau hitungan manual
            // yang lengkap. Sesi FINAL_WEIGHED saja tetap READY_TO_COUNT karena
            // labelnya belum dicetak.
            $hasPrintedScale = SoScaleSession::where('so_item_id', $itemId)
                ->whereIn('status', ['PRINTED', 'COMPLETED'])
                ->exists();

            $item = SoItem::find($itemId);

            if ($hasPrintedScale || ($item && $item->hasCompletedManualCount())) {
                SoItem::where('id', $itemId)->update(['current_step' => 'READY_TO_RETURN']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Draft penimbangan dibatalkan.',
        ]);
    }

    /**
     * Member: AJAX — Get weighing history (hitungan tanpa sample + final) for an item.
     */
    public function itemHistory(Request $request)
    {
        $request->validate(['so_item_id' => 'required|exists:so_items,id']);

        $rounds = SoScaleRound::with('session')
            ->where('so_item_id', $request->so_item_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $roundIds = $rounds->pluck('id');

        // Hasil resmi yang sudah tertaut ke ronde tidak ditampilkan ganda.
        $linkedHistoryIds = SoPhoto::whereNotNull('history_id')
            ->whereIn('scale_round_id', $roundIds)
            ->pluck('history_id')
            ->filter()
            ->unique()
            ->values();

        $histories = SoCountHistory::where('so_item_id', $request->so_item_id)
            ->where('count_type', 'SCALE')
            ->when($linkedHistoryIds->isNotEmpty(), fn($q) => $q->whereNotIn('id', $linkedHistoryIds))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($h) {
                return [
                    'id'             => $h->id,
                    'source'         => 'OFFICIAL',
                    'stage'          => 'OFFICIAL',
                    'round_no'       => null,
                    'member_nik'     => $h->member_nik,
                    'partner_nik'    => $h->partner_nik,
                    'weight_no'      => $h->weight_no_sample,
                    'weight_with'    => $h->weight_with_sample,
                    'sample_qty'     => $h->sample_qty,
                    'error_margin'   => $h->error_margin_percent,
                    'result_count'   => $h->result_count,
                    'status'         => $h->status,
                    'notes'          => $h->notes,
                    'created_at_raw' => $h->created_at ? $h->created_at->timestamp : 0,
                    'created_at'     => $h->created_at ? $h->created_at->format('d/m/Y H:i:s') : '-',
                ];
            });

        $roundRows = $rounds->map(function ($r) {
            return [
                'id'             => $r->id,
                'source'         => 'ROUND',
                'stage'          => $r->stage,
                'round_no'       => (int) $r->round_no,
                'member_nik'     => $r->session->member_nik ?? '-',
                'partner_nik'    => $r->session->partner_nik ?? null,
                'weight_no'      => $r->weight_no_sample,
                'weight_with'    => $r->weight_with_sample,
                'sample_qty'     => $r->sample_qty,
                'error_margin'   => $r->error_margin_percent,
                'result_count'   => $r->result_count,
                'status'         => $r->status,
                'notes'          => $r->notes,
                'created_at_raw' => $r->created_at ? $r->created_at->timestamp : 0,
                'created_at'     => $r->created_at ? $r->created_at->format('d/m/Y H:i:s') : '-',
            ];
        });

        $merged = $histories->concat($roundRows)
            ->sortByDesc('created_at_raw')
            ->values()
            ->take(5)
            ->map(function ($row) {
                unset($row['created_at_raw']);
                return $row;
            });

        return response()->json(['histories' => $merged]);
    }

    /**
     * Member: Show the reprint page for completed items.
     */
    public function reprintIndex(Request $request)
    {
        $member  = Auth::guard('member')->user();
        $session = MemberSession::with('scale')->firstOrCreate(
            ['nik' => $member->nik],
            ['active_role' => 'scale']
        );

        $scales = Scale::where('is_active', true)->orderBy('scale_number')->get();

        // Ambil list part yang sudah selesai dihitung di timbangan
        $completedItems = SoItem::where(function ($q) {
                $q->whereIn('current_step', ['READY_TO_RETURN', 'RETURNED'])
                  ->orWhere('process_type', 'SCALE');
            })
            ->whereNotNull('final_count')
            ->orderBy('count_completed_at', 'desc')
            ->limit(50)
            ->get();

        return view('scale.reprint', compact('member', 'session', 'scales', 'completedItems'));
    }

    /**
     * Member: AJAX — Lookup completed item data for reprinting label.
     */
    public function reprintLookup(Request $request)
    {
        $request->validate(['search' => 'required|string']);

        $keyword = trim($request->search);

        // Cari item berdasarkan ID, tracking_code, code_part, QR barcode, dll.
        $query = SoItem::query();

        if (is_numeric($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('id', (int) $keyword)
                  ->orWhere('code_part', $keyword)
                  ->orWhere('tracking_code', $keyword);
            });
        } elseif (str_contains($keyword, '|')) {
            $parts = explode('|', $keyword);
            $partCode = trim($parts[0] ?? '');
            $rackNo   = trim($parts[2] ?? '');
            if ($partCode !== '') $query->where('code_part', $partCode);
            if ($rackNo !== '') $query->where('rack_no', $rackNo);
        } else {
            $query->where(function ($q) use ($keyword) {
                $q->where('tracking_code', $keyword)
                  ->orWhere('code_part', $keyword)
                  ->orWhere('name_part', 'like', '%' . $keyword . '%')
                  ->orWhere('rack_no', $keyword);
            });
        }

        $item = $query->orderBy('count_completed_at', 'desc')->orderBy('id', 'desc')->first();

        if (!$item) {
            return response()->json([
                'found'   => false,
                'message' => 'Data part / kode tracking tidak ditemukan.',
            ]);
        }

        // Cari sesi dan riwayat hitungan OK terakhir
        $scaleSession = SoScaleSession::where('so_item_id', $item->id)
            ->orderByDesc('id')
            ->first();

        $lastOkFinal = null;
        if ($scaleSession) {
            $lastOkFinal = SoScaleRound::where('session_id', $scaleSession->id)
                ->where('stage', 'FINAL')
                ->where('status', 'OK')
                ->orderByDesc('id')
                ->first();
        }

        $lastHistory = SoCountHistory::where('so_item_id', $item->id)
            ->where('count_type', 'SCALE')
            ->where('status', 'OK')
            ->orderByDesc('id')
            ->first();

        if (!$lastOkFinal && !$lastHistory && $item->final_count === null) {
            return response()->json([
                'found'   => true,
                'allowed' => false,
                'message' => 'Part ini belum pernah selesai dihitung di timbangan (belum ada hasil hitungan OK).',
                'item'    => $this->formatItemResponse($item),
            ]);
        }

        // Hitungan final dengan sample
        $finalCount = (int) ($item->final_count ?? ($lastOkFinal->result_count ?? ($lastHistory->result_count ?? 0)));
        $totalWith  = $lastOkFinal->weight_with_sample ?? ($lastHistory->weight_with_sample ?? ($scaleSession->total_with_sample ?? $finalCount));
        $totalWithout = $lastOkFinal->weight_no_sample ?? ($lastHistory->weight_no_sample ?? ($scaleSession->total_without_sample ?? 0));
        $sampleQty = (int) ($lastOkFinal->sample_qty ?? ($lastHistory->sample_qty ?? ($scaleSession->sample_qty ?? 0)));
        $roundCount = (int) ($scaleSession->round_count ?? 0);

        // Ambil rincian ronde dari sesi
        $roundDetails = [];
        if ($scaleSession) {
            $sampleRounds = SoScaleRound::where('session_id', $scaleSession->id)
                ->where('stage', 'SAMPLE')
                ->orderBy('round_no')
                ->get(['round_no', 'weight_no_sample']);

            $roundDetails = $sampleRounds->map(function ($r) {
                return [
                    'round_no' => (int) $r->round_no,
                    'weight'   => round((float) $r->weight_no_sample, 4),
                ];
            })->values()->toArray();

            if ($roundCount === 0) {
                $roundCount = count($roundDetails);
            }
        }

        // WAKTU HITUNGAN OKE TERAKHIR (tetap diambil dari waktu OK terakhir, bukan waktu saat print ulang)
        $timeOk = null;
        if ($lastOkFinal && $lastOkFinal->created_at) {
            $timeOk = $lastOkFinal->created_at;
        } elseif ($lastHistory && $lastHistory->created_at) {
            $timeOk = $lastHistory->created_at;
        } elseif ($item->count_completed_at) {
            $timeOk = $item->count_completed_at;
        } elseif ($scaleSession && $scaleSession->updated_at) {
            $timeOk = $scaleSession->updated_at;
        }

        $formattedTime = $timeOk ? $timeOk->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s');

        // Operator & Partner
        $scaleUserNik    = $item->scale_user_nik ?? ($scaleSession->member_nik ?? ($lastHistory->member_nik ?? null));
        $scalePartnerNik = $item->scale_partner_nik ?? ($scaleSession->partner_nik ?? ($lastHistory->partner_nik ?? null));

        $operatorUser = $scaleUserNik ? DB::table('members')->where('nik', $scaleUserNik)->first() : null;
        $operatorName = $operatorUser ? ($operatorUser->nama ?? $scaleUserNik) : ($scaleUserNik ?? '-');

        $partnerName = $scalePartnerNik ?? '-';
        if ($scalePartnerNik) {
            $pData = DB::connection('rifa')->table('employees')->where('nik', $scalePartnerNik)->first();
            if ($pData) {
                $partnerName = $pData->nama ?? ($pData->name ?? $scalePartnerNik);
            }
        }

        $scaleNumber = $item->scale_number;
        if (!$scaleNumber && $scaleSession && $scaleSession->scale_id) {
            $scaleObj = Scale::find($scaleSession->scale_id);
            $scaleNumber = $scaleObj ? $scaleObj->scale_number : '-';
        }

        $printData = [
            'tracking_code'        => $item->tracking_code,
            'rack_no'              => $item->rack_no,
            'code_part'            => $item->code_part,
            'name_part'            => $item->name_part,
            'loc'                  => ($item->area || $item->cell) ? (($item->area ?? '-') . '-' . ($item->cell ?? '-')) : '',
            'final_count'          => $finalCount,
            'total_without_sample' => round((float) $totalWithout, 4),
            'total_with_sample'    => round((float) $totalWith, 4),
            'sample_qty'           => $sampleQty,
            'round_count'          => $roundCount,
            'round_details'        => $roundDetails,
            'scale_number'         => $scaleNumber ?: '-',
            'user_nik'             => $scaleUserNik,
            'user_name'            => $operatorName,
            'partner_nik'          => $scalePartnerNik,
            'partner_name'         => $partnerName,
            'completed_at'         => $formattedTime,
            'is_reprint'           => true,
        ];

        return response()->json([
            'found'     => true,
            'allowed'   => true,
            'item'      => $this->formatItemResponse($item),
            'printData' => $printData,
        ]);
    }

    // ── Helpers ──

    /**
     * Persist an uploaded scale photo (resized) and return its public-relative path.
     */
    private function saveScalePhoto($uploadedFile, SoItem $item, string $prefix): string
    {
        $safeFolder = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $item->tracking_code);
        $safeFolder = trim($safeFolder, ' ._') ?: ('item_' . $item->id);

        $targetDir = public_path("uploads/{$safeFolder}/scale");
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $filename = $this->resizeAndSaveImage($uploadedFile, $targetDir, $prefix);

        return "uploads/{$safeFolder}/scale/{$filename}";
    }

    /**
     * Resize uploaded image to max width/height 1280px with 75% JPEG quality.
     */
    private function resizeAndSaveImage($uploadedFile, $targetDir, $prefix)
    {
        $filename   = "{$prefix}_" . time() . '_' . Str::random(6) . '.jpg';
        $fullPath   = "{$targetDir}/{$filename}";
        $imagePath  = $uploadedFile->getRealPath();
        $imageInfo  = @getimagesize($imagePath);

        if (!$imageInfo) {
            $uploadedFile->move($targetDir, $filename);
            return $filename;
        }

        $origWidth  = $imageInfo[0];
        $origHeight = $imageInfo[1];
        $mime       = $imageInfo['mime'];
        $maxDim     = 1280;

        if ($origWidth > $maxDim || $origHeight > $maxDim) {
            $ratio     = min($maxDim / $origWidth, $maxDim / $origHeight);
            $newWidth  = (int) round($origWidth * $ratio);
            $newHeight = (int) round($origHeight * $ratio);
        } else {
            $newWidth  = $origWidth;
            $newHeight = $origHeight;
        }

        $srcImage = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($imagePath),
            'image/png'               => @imagecreatefrompng($imagePath),
            'image/webp'              => @imagecreatefromwebp($imagePath),
            default                   => null,
        };

        if (!$srcImage) {
            $uploadedFile->move($targetDir, $filename);
            return $filename;
        }

        if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/jpg')) {
            $exif = @exif_read_data($imagePath);
            if (!empty($exif['Orientation'])) {
                $srcImage = match ((int) $exif['Orientation']) {
                    3 => imagerotate($srcImage, 180, 0),
                    6 => imagerotate($srcImage, -90, 0),
                    8 => imagerotate($srcImage, 90, 0),
                    default => $srcImage,
                };
                $newWidth  = imagesx($srcImage);
                $newHeight = imagesy($srcImage);
                if ($newWidth > $maxDim || $newHeight > $maxDim) {
                    $ratio     = min($maxDim / $newWidth, $maxDim / $newHeight);
                    $newWidth  = (int) round($newWidth * $ratio);
                    $newHeight = (int) round($newHeight * $ratio);
                }
            }
        }

        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($srcImage), imagesy($srcImage));

        imagejpeg($dstImage, $fullPath, 75);

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $filename;
    }

    /**
     * Build the QR label payload shared by final submit and session resume.
     */
    private function buildPrintData(SoItem $item, SoScaleSession $scaleSession, int $finalCount): array
    {
        $scale  = Scale::find($scaleSession->scale_id);
        $member = Auth::guard('member')->user();

        $partnerData = DB::connection('rifa')
            ->table('employees')
            ->where('nik', $scaleSession->partner_nik)
            ->first();

        $partnerName = $partnerData
            ? ($partnerData->nama ?? $partnerData->name ?? $partnerData->employee_name ?? $scaleSession->partner_nik)
            : $scaleSession->partner_nik;

        // Total tanpa sample = gabungan seluruh hitungan (round 1 + round 2 + ...).
        $totalWithout = $scaleSession->total_without_sample !== null
            ? (float) $scaleSession->total_without_sample
            : (float) SoScaleRound::where('session_id', $scaleSession->id)
                ->where('stage', 'SAMPLE')
                ->sum('weight_no_sample');

        $totalWith = $scaleSession->total_with_sample !== null
            ? (float) $scaleSession->total_with_sample
            : null;

        if ($totalWith === null) {
            $lastFinal = SoScaleRound::where('session_id', $scaleSession->id)
                ->where('stage', 'FINAL')
                ->where('status', 'OK')
                ->orderByDesc('round_no')
                ->first();
            $totalWith = $lastFinal ? (float) $lastFinal->weight_with_sample : null;
        }

        // Per-round detail for print label
        $sampleRounds = SoScaleRound::where('session_id', $scaleSession->id)
            ->where('stage', 'SAMPLE')
            ->orderBy('round_no')
            ->get(['round_no', 'weight_no_sample']);

        $roundDetails = $sampleRounds->map(function ($r) {
            return [
                'round_no' => (int) $r->round_no,
                'weight'   => round((float) $r->weight_no_sample, 4),
            ];
        })->values()->toArray();

        // Ambil waktu dari ronde hitungan OK terakhir
        $lastOkFinal = SoScaleRound::where('session_id', $scaleSession->id)
            ->where('stage', 'FINAL')
            ->where('status', 'OK')
            ->orderByDesc('id')
            ->first();

        $completedTime = null;
        if ($lastOkFinal && $lastOkFinal->created_at) {
            $completedTime = $lastOkFinal->created_at->format('d/m/Y H:i:s');
        } elseif ($item->count_completed_at) {
            $completedTime = $item->count_completed_at->format('d/m/Y H:i:s');
        } elseif ($scaleSession->updated_at) {
            $completedTime = $scaleSession->updated_at->format('d/m/Y H:i:s');
        } else {
            $completedTime = now()->format('d/m/Y H:i:s');
        }

        return [
            'tracking_code'        => $item->tracking_code,
            'rack_no'              => $item->rack_no,
            'code_part'            => $item->code_part,
            'name_part'            => $item->name_part,
            'loc'                  => ($item->area || $item->cell) ? (($item->area ?? '-') . '-' . ($item->cell ?? '-')) : '',
            'final_count'          => $finalCount,
            'total_without_sample' => round($totalWithout, 4),
            'total_with_sample'    => $totalWith !== null ? round($totalWith, 4) : null,
            'sample_qty'           => (int) ($scaleSession->sample_qty ?: 0),
            'round_count'          => (int) ($scaleSession->round_count ?: 0),
            'round_details'        => $roundDetails,
            'scale_number'         => $scale ? $scale->scale_number : '-',
            'user_nik'             => $member->nik,
            'user_name'            => $member->nama ?? $member->nik,
            'partner_nik'          => $scaleSession->partner_nik,
            'partner_name'         => $partnerName,
            'completed_at'         => $completedTime,
        ];
    }

    private function formatScaleRound(SoScaleRound $round): array
    {
        return [
            'id'                 => $round->id,
            'round_no'           => (int) $round->round_no,
            'stage'              => $round->stage,
            'weight_no_sample'   => (float) $round->weight_no_sample,
            'weight_with_sample' => $round->weight_with_sample !== null ? (float) $round->weight_with_sample : null,
            'sample_qty'         => $round->sample_qty !== null ? (int) $round->sample_qty : null,
            'error_margin'       => $round->error_margin_percent !== null ? (float) $round->error_margin_percent : null,
            'result_count'       => $round->result_count !== null ? (int) $round->result_count : null,
            'status'             => $round->status,
            'photo_path'         => $round->photo_path,
            'photo_url'          => $round->photo_path ? asset($round->photo_path) : null,
            'notes'              => $round->notes,
            'created_at'         => $round->created_at ? $round->created_at->format('d/m/Y H:i:s') : '-',
        ];
    }

    private function formatScaleSession(SoScaleSession $session): array
    {
        $sampleRounds = $session->rounds->where('stage', 'SAMPLE');
        $totalWithout = (float) $sampleRounds->sum('weight_no_sample');

        return [
            'id'                   => $session->id,
            'so_item_id'           => $session->so_item_id,
            'status'               => $session->status,
            'round_count'          => (int) $session->round_count,
            'sample_qty'           => $session->sample_qty !== null ? (int) $session->sample_qty : null,
            'total_without_sample' => $session->total_without_sample !== null
                ? (float) $session->total_without_sample
                : round($totalWithout, 4),
            'total_with_sample'    => $session->total_with_sample !== null ? (float) $session->total_with_sample : null,
            'final_count'          => $session->final_count !== null ? (int) $session->final_count : null,
            'final_margin'         => $session->final_margin !== null ? (float) $session->final_margin : null,
            'partner_nik'          => $session->partner_nik,
            'scale_id'             => $session->scale_id,
            'label_photo_path'     => $session->label_photo_path,
            'rounds'               => $session->rounds->map(fn($r) => $this->formatScaleRound($r))->values(),
            'updated_at'           => $session->updated_at ? $session->updated_at->format('d/m/Y H:i:s') : null,
        ];
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
