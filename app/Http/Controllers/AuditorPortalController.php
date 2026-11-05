<?php

namespace App\Http\Controllers;

use App\Models\AuditorRecord;
use App\Models\SoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditorPortalController extends Controller
{
    /**
     * Dashboard Auditor: Scan QR Rak & Audit Submission
     */
    public function index()
    {
        $auditor = Auth::guard('auditor')->user();
        if (!$auditor) {
            return redirect()->route('page.login');
        }

        // Hitung statistik ringkasan audit
        $totalAudited = AuditorRecord::count();
        $myAudited = AuditorRecord::where('auditor_name', $auditor->name)->count();

        // Riwayat audit terakhir oleh auditor ini beserta data perbandingan SO
        $recentAudits = $this->getRecentAuditsWithComparison($auditor->name, 10);

        return view('auditor.portal', compact('auditor', 'totalAudited', 'myAudited', 'recentAudits'));
    }

    /**
     * Helper untuk mengambil riwayat audit dilengkapi data perbandingan SO
     */
    private function getRecentAuditsWithComparison($auditorName, $limit = 10)
    {
        $records = AuditorRecord::where('auditor_name', $auditorName)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $records->map(function ($rec) {
            $query = SoItem::where('rack_no', $rec->rack_no);
            if (!empty($rec->code_part)) {
                $query->where('code_part', $rec->code_part);
            }
            $soItem = $query->orderBy('id', 'desc')->first();

            $soCount = $soItem ? $soItem->final_count : null;
            $processType = $soItem ? $soItem->process_type : null; // SCALE atau MANUAL
            $pos1Count = $soItem ? $soItem->pos1_count : null;
            $pos2Count = $soItem ? $soItem->pos2_count : null;

            $diff = null;
            $statusDiff = 'UNKNOWN';
            if ($soCount !== null) {
                $diff = (int)$rec->audited_count - (int)$soCount;
                if ($diff === 0) {
                    $statusDiff = 'MATCH';
                } elseif ($diff > 0) {
                    $statusDiff = 'OVER';
                } else {
                    $statusDiff = 'UNDER';
                }
            }

            return [
                'id'            => $rec->id,
                'rack_no'       => $rec->rack_no,
                'code_part'     => $rec->code_part ?? ($soItem ? $soItem->code_part : '-'),
                'name_part'     => $soItem ? $soItem->name_part : '-',
                'audited_count' => (int)$rec->audited_count,
                'so_count'      => $soCount,
                'process_type'  => $processType,
                'pos1_count'    => $pos1Count,
                'pos2_count'    => $pos2Count,
                'diff'          => $diff,
                'status_diff'   => $statusDiff,
                'notes'         => $rec->notes,
                'created_at'    => $rec->created_at->format('H:i:s d/m/Y'),
            ];
        });
    }

    /**
     * Tampilan Daftar Part Yang Telah Dikembalikan & Belum Diaudit (Unaudited List)
     * Sumber data: SoItem dengan current_step = 'RETURNED' yang belum diaudit.
     * Dikelompokkan berdasarkan huruf awalan rak (A-Z)
     */
    public function unauditedList(Request $request)
    {
        $auditor = Auth::guard('auditor')->user();
        if (!$auditor) {
            return redirect()->route('page.login');
        }

        $activeLetter = strtoupper(trim((string) $request->input('letter', 'ALL')));

        // Ambil semua kombinasi rak dan code_part yang sudah pernah diaudit dari auditor_records
        $auditedRecords = AuditorRecord::select('rack_no', 'code_part')->get();
        $auditedSet = [];
        foreach ($auditedRecords as $rec) {
            $key = strtoupper(trim($rec->rack_no)) . '###' . strtoupper(trim($rec->code_part ?? ''));
            $auditedSet[$key] = true;
            // juga simpan key rak saja sebagai fallback
            $auditedSet[strtoupper(trim($rec->rack_no))] = true;
        }

        // Ambil semua item yang sudah berstatus RETURNED dari SoItem
        $returnedItems = SoItem::where('current_step', 'RETURNED')
            ->orderBy('returned_at', 'desc')
            ->get();

        // Koleksi semua huruf awalan yang unik dari rak item returned
        $availableLetters = $returnedItems->map(function ($item) {
            $cleanRack = trim($item->rack_no ?: ($item->code_rack ?: ''));
            $firstChar = strtoupper(substr($cleanRack, 0, 1));
            return ctype_alpha($firstChar) ? $firstChar : '#';
        })->unique()->sort()->values()->toArray();

        // Filter item returned yang belum pernah diaudit
        $unauditedItems = [];
        $totalAllReturned = $returnedItems->count();
        $totalAuditedReturned = 0;

        foreach ($returnedItems as $item) {
            $cleanRack = trim($item->rack_no ?: ($item->code_rack ?: ''));
            $cleanCode = trim($item->code_part ?? '');
            
            $keyCombined = strtoupper($cleanRack) . '###' . strtoupper($cleanCode);
            $keyRackOnly = strtoupper($cleanRack);

            $isAudited = isset($auditedSet[$keyCombined]) || isset($auditedSet[$keyRackOnly]);

            if ($isAudited) {
                $totalAuditedReturned++;
                continue;
            }

            $firstChar = strtoupper(substr($cleanRack, 0, 1));
            $groupKey = ctype_alpha($firstChar) ? $firstChar : '#';

            if ($activeLetter !== 'ALL' && $groupKey !== $activeLetter) {
                continue;
            }

            // Location display
            $loc = trim($item->code_rack ?? '-');
            $cell = trim($item->cell ?? '-');

            $unauditedItems[] = [
                'id'            => $item->id,
                'tracking_code' => $item->tracking_code,
                'rack_no'       => $cleanRack,
                'code_part'     => $item->code_part,
                'name_part'     => $item->name_part,
                'loc'           => $loc,
                'cell'          => $cell,
                'letter'        => $groupKey,
                'final_count'   => $item->final_count,
                'returned_at'   => $item->returned_at ? $item->returned_at->format('d/m/Y H:i') : '-',
                'return_nik'    => $item->return_nik ?? '-',
                'process_type'  => $item->process_type ?? 'MANUAL',
            ];
        }

        $totalAllMaster     = $totalAllReturned;
        $totalAuditedMaster = $totalAuditedReturned;
        $totalUnaudited     = $totalAllReturned - $totalAuditedReturned;

        return view('auditor.unaudited_list', compact(
            'auditor',
            'unauditedItems',
            'availableLetters',
            'activeLetter',
            'totalAllMaster',
            'totalAuditedMaster',
            'totalUnaudited'
        ));
    }

    /**
     * Lookup Item / Rak untuk Form Audit
     * Input dapat berupa:
     * 1. Scan QR Label Pengembalian / Final: part|name|rak|loc|qty
     * 2. Tracking code: part|name|rak|loc
     * 3. QR Nomor Rak Fisik
     * Validasi: Part harus sudah berstatus 'RETURNED' (sudah dikembalikan oleh member return)
     */
    public function lookupRack(Request $request)
    {
        $raw = trim((string) $request->input('rack_no', ''));
        if ($raw === '') {
            return response()->json([
                'found'   => false,
                'message' => 'Label pengembalian / QR tidak boleh kosong.',
            ], 422);
        }

        $targetCodePart = null;
        $targetRackNo   = null;
        $targetLoc      = null;
        $targetQty      = null;

        // Cek langsung kecocokan tracking_code di SoItem
        $soItem = SoItem::where('tracking_code', $raw)->orderBy('id', 'desc')->first();

        // Jika input berformat pipa: part|name|rak|loc|qty atau part|name|rak|loc
        if (!$soItem && str_contains($raw, '|')) {
            $parts = explode('|', $raw);
            $targetCodePart = trim($parts[0] ?? '');
            $targetNamePart = trim($parts[1] ?? '');
            $targetRackNo   = trim($parts[2] ?? '');
            $targetLoc      = trim($parts[3] ?? '');
            $targetQty      = isset($parts[4]) ? (int) trim($parts[4]) : null;

            // Cari SoItem berdasarkan kombinasi part dan rack
            $query = SoItem::query();
            if ($targetCodePart !== '') {
                $query->where('code_part', $targetCodePart);
            }
            if ($targetRackNo !== '') {
                $query->where(function ($q) use ($targetRackNo) {
                    $q->where('rack_no', $targetRackNo)
                      ->orWhere('code_rack', $targetRackNo);
                });
            }
            $soItem = $query->orderBy('id', 'desc')->first();
        }

        // Jika masih belum ketemu, coba cari berdasarkan rack_no fisik
        if (!$soItem) {
            $soItem = SoItem::where(function ($q) use ($raw) {
                $q->where('rack_no', $raw)
                  ->orWhere('code_rack', $raw);
            })->orderBy('id', 'desc')->first();
        }

        // Jika sama sekali tidak ada di riwayat SoItem
        if (!$soItem) {
            // Cek apakah ada di master label untuk memberi pesan informatif
            $masterItem = DB::connection('label')->table('rack_part_lists')
                ->where('rack_no', $raw)
                ->orWhere('item_code', $raw)
                ->first();

            if ($masterItem) {
                return response()->json([
                    'found'   => false,
                    'message' => "Part/Rak \"{$raw}\" terdaftar di master data, namun belum pernah diproses Stock Opname atau belum dikembalikan oleh tim Return.",
                ]);
            }

            return response()->json([
                'found'   => false,
                'message' => "Label pengembalian / part \"{$raw}\" tidak ditemukan dalam sistem.",
            ]);
        }

        // VALIDASI STATUS: Hanya part yang berstatus RETURNED yang diizinkan untuk diaudit
        if ($soItem->current_step !== 'RETURNED') {
            $stepLabels = [
                'COURIER_PICKED'  => 'Sedang diambil kurir',
                'READY_TO_COUNT'  => 'Di area hitung / Belum dihitung',
                'POS1_DONE'       => 'Selesai Pos 1 (Menunggu Pos 2)',
                'COUNTED'         => 'Sudah dihitung',
                'READY_TO_RETURN' => 'Siap dikembalikan (Belum dikembalikan ke rak oleh tim Return)',
            ];
            $friendlyStep = $stepLabels[$soItem->current_step] ?? $soItem->current_step;

            return response()->json([
                'found'          => false,
                'not_returned'   => true,
                'current_step'   => $soItem->current_step,
                'friendly_step'  => $friendlyStep,
                'message'        => "Part \"{$soItem->name_part}\" ({$soItem->code_part}) belum dapat diaudit karena belum selesai dikembalikan ke rak fisik oleh tim Return.\nStatus saat ini: {$friendlyStep}.",
            ]);
        }

        // Cek riwayat audit untuk rak & part ini
        $previousAudits = AuditorRecord::where('rack_no', $soItem->rack_no)
            ->where(function ($q) use ($soItem) {
                $q->where('code_part', $soItem->code_part)
                  ->orWhereNull('code_part');
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($a) {
                return [
                    'id'            => $a->id,
                    'auditor_name'  => $a->auditor_name,
                    'audited_count' => $a->audited_count,
                    'notes'         => $a->notes,
                    'created_at'    => $a->created_at->format('d/m/Y H:i:s'),
                ];
            });

        return response()->json([
            'found'           => true,
            'so_item_id'      => $soItem->id,
            'tracking_code'   => $soItem->tracking_code,
            'rack_no'         => $soItem->rack_no,
            'code_part'       => $soItem->code_part,
            'name_part'       => $soItem->name_part,
            'loc'             => $soItem->code_rack ?? '-',
            'cell'            => $soItem->cell ?? '-',
            'so_step'         => $soItem->current_step,
            'so_final_count'  => $soItem->final_count,
            'process_type'    => $soItem->process_type ?? 'MANUAL',
            'pos1_count'      => $soItem->pos1_count,
            'pos2_count'      => $soItem->pos2_count,
            'scale_number'    => $soItem->scale_number,
            'returned_at'     => $soItem->returned_at ? $soItem->returned_at->format('d/m/Y H:i:s') : '-',
            'return_nik'      => $soItem->return_nik ?? '-',
            'already_audited' => $previousAudits->isNotEmpty(),
            'previous_audits' => $previousAudits,
            'total_history'   => $previousAudits->count(),
        ]);
    }

    /**
     * Submit Hasil Audit oleh Auditor
     */
    public function submitAudit(Request $request)
    {
        $request->validate([
            'rack_no'       => 'required|string|max:100',
            'audited_count' => 'required|integer|min:0',
            'notes'         => 'nullable|string',
        ]);

        $auditor = Auth::guard('auditor')->user();
        if (!$auditor) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi login auditor tidak valid.',
            ], 401);
        }

        try {
            $activeEvent = \App\Models\SoEvent::getActiveEvent();

            $record = AuditorRecord::create([
                'so_event_id'   => $activeEvent ? $activeEvent->id : null,
                'rack_no'       => $request->rack_no,
                'code_part'     => $request->code_part,
                'auditor_name'  => $auditor->name,
                'audited_count' => (int) $request->audited_count,
                'notes'         => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hasil audit rak ' . $request->rack_no . ' berhasil disimpan!',
                'record'  => $record,
                'recent_audits' => $this->getRecentAuditsWithComparison($auditor->name, 10),
                'total_audited' => AuditorRecord::count(),
                'my_audited'    => AuditorRecord::where('auditor_name', $auditor->name)->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan audit: ' . $e->getMessage(),
            ], 500);
        }
    }
}

