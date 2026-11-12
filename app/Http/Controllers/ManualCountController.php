<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberSession;
use App\Models\SoCountHistory;
use App\Models\SoItem;
use App\Models\SoPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ManualCountController extends Controller
{
    /**
     * Tampilan Pos 1 Manual
     */
    public function pos1Index()
    {
        $member = Auth::guard('member')->user();
        if (!$member) {
            return redirect()->route('page.login');
        }

        $activeEvent = \App\Models\SoEvent::getActiveEvent();

        // Ambil riwayat input Pos 1 terbaru oleh user ini pada SO aktif (Maksimal 5)
        $query = SoCountHistory::with('soItem')
            ->where('member_nik', $member->nik)
            ->where('count_type', 'POS1');

        if ($activeEvent) {
            $query->whereHas('soItem', function ($q) use ($activeEvent) {
                $q->where('so_event_id', $activeEvent->id);
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        $recentHistories = $query->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('manual.pos1', compact('member', 'recentHistories'));
    }

    /**
     * Tampilan Pos 2 Manual
     */
    public function pos2Index()
    {
        $member = Auth::guard('member')->user();
        if (!$member) {
            return redirect()->route('page.login');
        }

        $activeEvent = \App\Models\SoEvent::getActiveEvent();

        // Ambil riwayat input Pos 2 terbaru oleh user ini pada SO aktif (Maksimal 5)
        $query = SoCountHistory::with('soItem')
            ->where('member_nik', $member->nik)
            ->where('count_type', 'POS2');

        if ($activeEvent) {
            $query->whereHas('soItem', function ($q) use ($activeEvent) {
                $q->where('so_event_id', $activeEvent->id);
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        $recentHistories = $query->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('manual.pos2', compact('member', 'recentHistories'));
    }

    /**
     * Lookup item berdasarkan QR code atau tracking_code
     * Support format QR kurir: part|name|rak|loc
     */
    public function lookupItem(Request $request)
    {
        $raw = trim((string) $request->input('tracking_code', ''));
        $pos = $request->input('pos', 'POS1'); // POS1 or POS2

        if ($raw === '') {
            return response()->json([
                'found'   => false,
                'message' => 'Kode tracking tidak boleh kosong.',
            ], 422);
        }

        $activeEvent = \App\Models\SoEvent::getActiveEvent();
        if (!$activeEvent) {
            return response()->json([
                'found'   => false,
                'message' => 'Belum ada jadwal Stock Opname yang aktif saat ini.',
            ], 422);
        }

        // 1. Cek langsung by tracking_code di event yang aktif
        $item = SoItem::where('so_event_id', $activeEvent->id)
            ->where('tracking_code', $raw)
            ->first();

        // 2. Jika tidak ditemukan dan input mengandung pipe (|), parse format QR kurir
        if (!$item && str_contains($raw, '|')) {
            $parts = explode('|', $raw);
            $codePart = trim($parts[0] ?? '');
            $namePart = trim($parts[1] ?? '');
            $rackNo   = trim($parts[2] ?? '');
            $locCell  = trim($parts[3] ?? '');

            // Cari berdasarkan kombinasi code_part dan rack_no pada EVENT AKTIF
            $query = SoItem::where('so_event_id', $activeEvent->id);
            if ($codePart !== '') $query->where('code_part', $codePart);
            if ($rackNo !== '')   $query->where('rack_no', $rackNo);

            if ($locCell !== '') {
                $query->where(function ($sub) use ($locCell) {
                    $sub->where('tracking_code', 'like', '%' . $locCell . '%')
                        ->orWhere('cell', 'like', '%' . $locCell . '%');

                    // Jika ada pemisah dash '-'
                    if (str_contains($locCell, '-')) {
                        $locParts = explode('-', $locCell);
                        $loc  = trim($locParts[0] ?? '');
                        $cell = trim($locParts[1] ?? '');
                        if ($loc !== '' && $cell !== '') {
                            $sub->orWhere(function ($q2) use ($loc, $cell) {
                                $q2->where('area', $loc)->where('cell', $cell);
                            });
                        }
                    }
                });
            }

            $item = $query->orderBy('created_at', 'desc')->first();

            // Fallback: cari by code_part dan rack_no paling baru di event aktif
            if (!$item && $codePart !== '' && $rackNo !== '') {
                $item = SoItem::where('so_event_id', $activeEvent->id)
                    ->where('code_part', $codePart)
                    ->where('rack_no', $rackNo)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
        }

        if (!$item) {
            return response()->json([
                'found'   => false,
                'message' => 'Part belum terdaftar di antrean Stock Opname! Harap pastikan Kurir telah menempel label dan menekan "Simpan & Kirim ke Meja Hitung" terlebih dahulu.',
            ]);
        }

        $member = Auth::guard('member')->user();
        $targetRole = ($pos === 'POS2') ? 'pos2' : 'pos1';
        $roleCheck = $item->canMemberAct($member->nik, $targetRole);
        if (!$roleCheck['allowed']) {
            return response()->json([
                'found'   => true,
                'allowed' => false,
                'message' => $roleCheck['reason'],
                'item'    => $this->formatItemResponse($item),
            ]);
        }

        // Validasi urutan status siklus (Sequential check)
        if ($item->current_step === 'RETURNED') {
            // Pos 2: Izinkan reprint label QR final meskipun sudah RETURNED (tanpa opsi hitung ulang / submit foto)
            if ($pos === 'POS2' && ($item->final_count !== null || $item->hasCompletedManualCount())) {
                $pos1Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'desc')->first();
                $pos2Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'desc')->first();
                $member = Auth::guard('member')->user();
                $pic1Name = $pos1Record ? ($pos1Record->member ? $pos1Record->member->nama : $pos1Record->member_nik) : '-';
                $pic2Name = $pos2Record ? ($pos2Record->member ? $pos2Record->member->nama : $pos2Record->member_nik) : ($member->nama ?: $member->nik);

                $locDisplay = Area::formatLocDisplay($item->area, $item->cell);
                if (str_contains($item->tracking_code, '|')) {
                    $tParts = explode('|', $item->tracking_code);
                    if (!empty($tParts[3])) {
                        $locDisplay = trim($tParts[3]);
                    }
                } elseif (!empty($item->cell) && str_contains($item->cell, ',')) {
                    $locDisplay = $item->cell;
                }

                // Ambil riwayat Pos 1 dan Pos 2
                $pos1HistoryRecords = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'asc')->get();
                $pos2HistoryRecords = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'asc')->get();

                $latestPos1 = $pos1HistoryRecords->last();
                $latestPos2 = $pos2HistoryRecords->last();

                $pos1Count = $latestPos1 ? $latestPos1->result_count : ($item->pos1_count ?? '-');
                $pos1Time  = $latestPos1 ? $latestPos1->created_at->format('d/m/Y H:i') : '';

                $pos2Count = $latestPos2 ? $latestPos2->result_count : ($item->final_count ?? $item->pos2_count ?? '-');
                $pos2Time  = $latestPos2 ? $latestPos2->created_at->format('d/m/Y H:i') : '';

                // Kumpulkan riwayat update (selain yang terbaru)
                $updateHistory = [];
                if ($pos1HistoryRecords->count() > 1) {
                    for ($i = 0; $i < $pos1HistoryRecords->count() - 1; $i++) {
                        $rec = $pos1HistoryRecords[$i];
                        $updateHistory[] = 'P1:' . $rec->result_count . ' (' . $rec->created_at->format('d/m/Y H:i') . ')';
                    }
                }
                if ($pos2HistoryRecords->count() > 1) {
                    for ($i = 0; $i < $pos2HistoryRecords->count() - 1; $i++) {
                        $rec = $pos2HistoryRecords[$i];
                        $updateHistory[] = 'P2:' . $rec->result_count . ' (' . $rec->created_at->format('d/m/Y H:i') . ')';
                    }
                }

                $lastCompletedAt = $item->count_completed_at ? $item->count_completed_at->format('d/m/Y, H:i:s') : now()->format('d/m/Y, H:i:s');

                $printData = [
                    'tracking_code'   => $item->tracking_code,
                    'code_part'       => $item->code_part,
                    'name_part'       => $item->name_part,
                    'rack_no'         => $item->rack_no,
                    'loc'             => $locDisplay,
                    'final_count'     => $item->final_count ?? $item->pos2_count,
                    'pic1'            => $pic1Name,
                    'pic2'            => $pic2Name,
                    'pos1_count'      => $pos1Count,
                    'pos1_time'       => $pos1Time,
                    'pos2_count'      => $pos2Count,
                    'pos2_time'       => $pos2Time,
                    'has_updates'     => !empty($updateHistory),
                    'update_history'  => !empty($updateHistory) ? implode(', ', $updateHistory) : null,
                    'pos1_history'    => !empty($pos1HistoryCounts) ? implode(', ', $pos1HistoryCounts) : '-',
                    'pos2_history'    => !empty($pos2HistoryCounts) ? implode(', ', $pos2HistoryCounts) : '-',
                    'completed_at'    => $lastCompletedAt,
                    'user_nik'        => $member->nik,
                    'user_name'       => $member->nama,
                    'method'          => 'MANUAL POS 1 & 2',
                    'isManual'        => true,
                ];

                return response()->json([
                    'found'            => true,
                    'allowed'          => true,
                    'needs_final_flow' => true,
                    'is_reprint'       => true,
                    'is_returned'      => true,
                    'already_photo'    => true,
                    'manual_completed' => true,
                    'scale_completed'  => $item->hasCompletedScaleCount(),
                    'printData'        => $printData,
                    'final_count'      => $item->final_count ?? $item->pos2_count,
                    'message'          => 'Item sudah berstatus RETURNED. Hanya cetak ulang label QR yang diizinkan.',
                    'item'             => $this->formatItemResponse($item),
                ]);
            }

            return response()->json([
                'found'   => true,
                'allowed' => false,
                'message' => 'Item sudah berstatus RETURNED (sudah dikembalikan ke rak). Tidak dapat dihitung ulang.',
                'item'    => $this->formatItemResponse($item),
            ]);
        }

        // Status lintas pos: item yang sudah selesai di pos lain tetap boleh
        // dihitung di pos ini. Hasil tiap pos tetap tercatat di riwayat.
        $manualCompleted = $item->hasCompletedManualCount();
        $scaleCompleted  = $item->hasCompletedScaleCount();
        $scaleWarning    = $scaleCompleted
            ? 'Item ini sudah selesai dihitung di Pos Timbangan. Anda tetap bisa menghitung di pos manual; hasil masing-masing pos akan tersimpan.'
            : null;

        if ($pos === 'POS1') {
            // Validasi apakah kurir sudah selesai submit serah terima
            if ($item->current_step === 'COURIER' || empty($item->courier_at)) {
                return response()->json([
                    'found'   => true,
                    'allowed' => false,
                    'message' => 'Part ini belum selesai disubmit oleh Kurir! Kurir baru mencetak label tetapi belum menekan "Simpan & Kirim ke Meja Hitung".',
                    'item'    => $this->formatItemResponse($item),
                ]);
            }
            // Pos 1 mengizinkan semua part sebelum RETURNED (READY_TO_COUNT, MISMATCH_NG, POS1_DONE, READY_TO_RETURN dari timbangan/pos2)
            // Lanjut ke submit Pos 1
        } elseif ($pos === 'POS2') {
            // Validasi apakah kurir sudah selesai submit
            if ($item->current_step === 'COURIER' || empty($item->courier_at)) {
                return response()->json([
                    'found'   => true,
                    'allowed' => false,
                    'message' => 'Part ini belum selesai disubmit oleh Kurir! Kurir belum menyelesaikan proses serah terima.',
                    'item'    => $this->formatItemResponse($item),
                ]);
            }

            // Validasi apakah Pos 1 sudah pernah dihitung
            if ($item->current_step === 'READY_TO_COUNT' && empty($item->pos1_count)) {
                return response()->json([
                    'found'   => true,
                    'allowed' => false,
                    'message' => 'Part ini belum pernah dihitung di Pos 1! Wajib melalui Pos 1 terlebih dahulu sebelum Pos 2.',
                    'item'    => $this->formatItemResponse($item),
                ]);
            }

            // Pos 2 mengizinkan item POS1_DONE, MISMATCH_NG, dan READY_TO_RETURN yang belum upload foto final
            $hasFinalPhoto = SoPhoto::where('so_item_id', $item->id)
                ->where('photo_path', 'like', '%pos2_final%')
                ->exists();

            if ($item->current_step === 'READY_TO_RETURN') {
                // Hasil manual Pos 1 & Pos 2 sudah lengkap → panel cetak ulang QR.
                if ($manualCompleted) {
                // Baik sudah upload foto maupun belum, izinkan reprint QR final jika discan ulang di Pos 2
                $pos1Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'desc')->first();
                $pos2Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'desc')->first();
                $member = Auth::guard('member')->user();
                $pic1Name = $pos1Record ? ($pos1Record->member ? $pos1Record->member->nama : $pos1Record->member_nik) : '-';
                $pic2Name = $pos2Record ? ($pos2Record->member ? $pos2Record->member->nama : $pos2Record->member_nik) : ($member->nama ?: $member->nik);

                // Ambil loc asli dari item (area-cell) atau dari QR
                $locDisplay = Area::formatLocDisplay($item->area, $item->cell);
                if (str_contains($item->tracking_code, '|')) {
                    $tParts = explode('|', $item->tracking_code);
                    if (!empty($tParts[3])) {
                        $locDisplay = trim($tParts[3]);
                    }
                } elseif (!empty($item->cell) && str_contains($item->cell, ',')) {
                    $locDisplay = $item->cell;
                }

                // Ambil riwayat Pos 1 dan Pos 2
                $pos1HistoryRecords = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'asc')->get();
                $pos2HistoryRecords = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'asc')->get();

                $latestPos1 = $pos1HistoryRecords->last();
                $latestPos2 = $pos2HistoryRecords->last();

                $pos1Count = $latestPos1 ? $latestPos1->result_count : ($item->pos1_count ?? '-');
                $pos1Time  = $latestPos1 ? $latestPos1->created_at->format('d/m/Y H:i') : '';

                $pos2Count = $latestPos2 ? $latestPos2->result_count : ($item->final_count ?? $item->pos2_count ?? '-');
                $pos2Time  = $latestPos2 ? $latestPos2->created_at->format('d/m/Y H:i') : '';

                // Kumpulkan riwayat update (selain yang terbaru)
                $updateHistory = [];
                if ($pos1HistoryRecords->count() > 1) {
                    for ($i = 0; $i < $pos1HistoryRecords->count() - 1; $i++) {
                        $rec = $pos1HistoryRecords[$i];
                        $updateHistory[] = 'P1:' . $rec->result_count . ' (' . $rec->created_at->format('d/m/Y H:i') . ')';
                    }
                }
                if ($pos2HistoryRecords->count() > 1) {
                    for ($i = 0; $i < $pos2HistoryRecords->count() - 1; $i++) {
                        $rec = $pos2HistoryRecords[$i];
                        $updateHistory[] = 'P2:' . $rec->result_count . ' (' . $rec->created_at->format('d/m/Y H:i') . ')';
                    }
                }

                // Waktu perhitungan OK terakhir
                $lastCompletedAt = $item->count_completed_at ? $item->count_completed_at->format('d/m/Y, H:i:s') : now()->format('d/m/Y, H:i:s');

                $printData = [
                    'tracking_code'   => $item->tracking_code,
                    'code_part'       => $item->code_part,
                    'name_part'       => $item->name_part,
                    'rack_no'         => $item->rack_no,
                    'loc'             => $locDisplay,
                    'final_count'     => $item->final_count ?? $item->pos2_count,
                    'pic1'            => $pic1Name,
                    'pic2'            => $pic2Name,
                    'pos1_count'      => $pos1Count,
                    'pos1_time'       => $pos1Time,
                    'pos2_count'      => $pos2Count,
                    'pos2_time'       => $pos2Time,
                    'has_updates'     => !empty($updateHistory),
                    'update_history'  => !empty($updateHistory) ? implode(', ', $updateHistory) : null,
                    'pos1_history'    => $pos1HistoryRecords->pluck('result_count')->implode(', ') ?: '-',
                    'pos2_history'    => $pos2HistoryRecords->pluck('result_count')->implode(', ') ?: '-',
                    'completed_at'    => $lastCompletedAt,
                    'user_nik'        => $member->nik,
                    'user_name'       => $member->nama,
                    'method'          => 'MANUAL POS 1 & 2',
                    'isManual'        => true,
                ];

                return response()->json([
                    'found'            => true,
                    'allowed'          => true,
                    'needs_final_flow' => true, // Masuk ke panel cetak QR & upload foto
                    'already_photo'    => $hasFinalPhoto, // apakah foto sudah ada sebelumnya
                    'manual_completed' => true,
                    'scale_completed'  => $scaleCompleted,
                    'printData'        => $printData,
                    'final_count'      => $item->final_count ?? $item->pos2_count,
                    'item'             => $this->formatItemResponse($item),
                ]);
                }

                // READY_TO_RETURN tetapi bukan hasil manual (mis. selesai di timbangan):
                // Pos 2 butuh angka Pos 1 dulu, jadi arahkan ke Pos 1 tanpa menutup proses.
                return response()->json([
                    'found'            => true,
                    'allowed'          => false,
                    'manual_completed' => false,
                    'scale_completed'  => $scaleCompleted,
                    'warning'          => $scaleWarning,
                    'message'          => $scaleCompleted
                        ? 'Item sudah selesai dihitung di Pos Timbangan. Untuk hitung manual, mulai dari Pos 1 terlebih dahulu.'
                        : 'Item sudah selesai dihitung. Untuk hitung manual, mulai dari Pos 1 terlebih dahulu.',
                    'item'             => $this->formatItemResponse($item),
                ]);
            }

            if ($item->current_step !== 'POS1_DONE' && $item->current_step !== 'MISMATCH_NG') {
                $stepLabels = [
                    'COURIER'         => 'Masih di tahap Kurir',
                    'READY_TO_COUNT'  => 'Belum dihitung di Pos 1 (wajib Pos 1 terlebih dahulu sebelum Pos 2)',
                    'RETURNED'        => 'Sudah dikembalikan ke rak',
                ];
                $msg = $stepLabels[$item->current_step] ?? 'Status tidak valid untuk Pos 2';

                return response()->json([
                    'found'   => true,
                    'allowed' => false,
                    'message' => 'Tidak dapat diproses di Pos 2: ' . $msg,
                    'item'    => $this->formatItemResponse($item),
                ]);
            }
        }

        return response()->json([
            'found'            => true,
            'allowed'          => true,
            'manual_completed' => $manualCompleted,
            'scale_completed'  => $scaleCompleted,
            'warning'          => $scaleWarning,
            'item'             => $this->formatItemResponse($item),
        ]);
    }

    /**
     * Submit Perhitungan Pos 1 (Double input verification + Foto)
     */
    public function submitPos1(Request $request)
    {
        $request->validate([
            'so_item_id' => 'required|exists:so_items,id',
            'input_1'    => 'required|integer|min:0',
            'input_2'    => 'required|integer|min:0',
            'photos.*'   => 'nullable|image|max:15360',
        ]);

        $item = SoItem::findOrFail($request->so_item_id);
        $member = Auth::guard('member')->user();

        // Validasi multi-peran
        $roleCheck = $item->canMemberAct($member->nik, 'pos1');
        if (!$roleCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $roleCheck['reason'],
            ], 422);
        }

        // Pos 1 mengizinkan semua tahap sebelum RETURNED:
        // READY_TO_COUNT, POS1_DONE (bisa koreksi/hitung ulang mandiri), MISMATCH_NG, dan READY_TO_RETURN
        $allowedSteps = ['READY_TO_COUNT', 'POS1_DONE', 'MISMATCH_NG', 'READY_TO_RETURN'];

        if (!in_array($item->current_step, $allowedSteps, true)) {
            $stepLabels = [
                'COURIER'  => 'Part masih dalam perjalanan / penanganan kurir (belum selesai serah terima).',
                'RETURNED' => 'Part sudah berstatus RETURNED (sudah dikembalikan ke rak oleh tim Returner). Tidak dapat dihitung ulang.',
            ];
            $detailMsg = $stepLabels[$item->current_step] ?? ('Status part saat ini (' . $item->current_step . ') belum siap untuk dihitung di Pos 1.');

            return response()->json([
                'success' => false,
                'message' => 'Gagal submit Pos 1: ' . $detailMsg,
            ], 422);
        }

        $input1 = (int) $request->input_1;
        $input2 = (int) $request->input_2;

        if ($input1 !== $input2) {
            return response()->json([
                'success' => false,
                'message' => 'Input 1 (' . $input1 . ') dan Input 2 (' . $input2 . ') tidak sama! Harap hitung ulang dengan teliti.',
            ], 422);
        }

        $member = Auth::guard('member')->user();

        DB::beginTransaction();
        try {
            // Simpan riwayat hitungan Pos 1
            $history = SoCountHistory::create([
                'so_item_id'   => $item->id,
                'count_type'   => 'POS1',
                'member_nik'   => $member->nik,
                'input_1'      => $input1,
                'input_2'      => $input2,
                'result_count' => $input1,
                'status'       => 'OK',
                'notes'        => $request->notes,
            ]);

            // Handle Multiple Photos upload jika ada
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photoFile) {
                    if ($photoFile && $photoFile->isValid()) {
                        $photoPath = $this->saveCompressedPhoto($photoFile, $item, 'pos1');
                        SoPhoto::create([
                            'so_item_id' => $item->id,
                            'history_id' => $history->id,
                            'pos_type'   => 'POS1',
                            'photo_path' => $photoPath,
                        ]);
                    }
                }
            } elseif ($request->hasFile('photo')) { // fallback single
                $photoPath = $this->saveCompressedPhoto($request->file('photo'), $item, 'pos1');
                SoPhoto::create([
                    'so_item_id' => $item->id,
                    'history_id' => $history->id,
                    'pos_type'   => 'POS1',
                    'photo_path' => $photoPath,
                ]);
            }

            // Cek apakah hasil baru ini MATCH dengan Pos 2 yang sudah ada
            $isResolvedMatch = false;
            $newFinalCount = null;
            $pos2ExistingCount = $item->pos2_count;

            if (in_array($item->current_step, ['MISMATCH_NG', 'READY_TO_RETURN', 'POS1_DONE'], true) 
                && $pos2ExistingCount !== null 
                && (int)$pos2ExistingCount === $input1) {
                // Pos 1 diinput/dikoreksi dan sekarang sudah sama persis dengan Pos 2
                $isResolvedMatch = true;
                $newFinalCount = $input1;
                $item->update([
                    'process_type'       => 'MANUAL',
                    'pos1_count'         => $input1,
                    'pos1_at'            => now(),
                    'final_count'        => $input1,
                    'current_step'       => 'READY_TO_RETURN',
                    'count_completed_at' => now(),
                ]);
            } else {
                // Update status item ke POS1_DONE (agar Pos 2 memverifikasi ulang sesuai SOP)
                $item->update([
                    'process_type' => 'MANUAL',
                    'pos1_count'   => $input1,
                    'pos1_at'      => now(),
                    'final_count'  => null,
                    'current_step' => 'POS1_DONE',
                ]);
            }

            DB::commit();

            if ($isResolvedMatch) {
                $pos1Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'desc')->first();
                $pos2Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'desc')->first();
                $pic1Name = $member->nama ?: $member->nik; // sedang dikoreksi oleh user ini di pos 1
                $pic2Name = $pos2Record ? ($pos2Record->member ? $pos2Record->member->nama : $pos2Record->member_nik) : '-';

                $locDisplay = Area::formatLocDisplay($item->area, $item->cell);
                if (str_contains($item->tracking_code, '|')) {
                    $tParts = explode('|', $item->tracking_code);
                    if (!empty($tParts[3])) {
                        $locDisplay = trim($tParts[3]);
                    }
                } elseif (!empty($item->cell) && str_contains($item->cell, ',')) {
                    $locDisplay = $item->cell;
                }

                // Ambil seluruh histori hitungan pos 1 dan pos 2 dari yang terawal hingga terbaru
                $pos1HistoryCounts = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'asc')->pluck('result_count')->toArray();
                $pos2HistoryCounts = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'asc')->pluck('result_count')->toArray();

                $pos1HistoryRecords = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'asc')->get();
                $pos2HistoryRecords = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'asc')->get();

                $latestPos1 = $pos1HistoryRecords->last();
                $latestPos2 = $pos2HistoryRecords->last();

                $pos1CountVal = $latestPos1 ? $latestPos1->result_count : $newFinalCount;
                $pos1Time     = $latestPos1 ? $latestPos1->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i');

                $pos2CountVal = $latestPos2 ? $latestPos2->result_count : $newFinalCount;
                $pos2Time     = $latestPos2 ? $latestPos2->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i');

                $updateHistory = [];
                if ($pos1HistoryRecords->count() > 1) {
                    for ($i = 0; $i < $pos1HistoryRecords->count() - 1; $i++) {
                        $rec = $pos1HistoryRecords[$i];
                        $updateHistory[] = 'P1:' . $rec->result_count . ' (' . $rec->created_at->format('d/m/Y H:i') . ')';
                    }
                }
                if ($pos2HistoryRecords->count() > 1) {
                    for ($i = 0; $i < $pos2HistoryRecords->count() - 1; $i++) {
                        $rec = $pos2HistoryRecords[$i];
                        $updateHistory[] = 'P2:' . $rec->result_count . ' (' . $rec->created_at->format('d/m/Y H:i') . ')';
                    }
                }

                $printData = [
                    'tracking_code'   => $item->tracking_code,
                    'code_part'       => $item->code_part,
                    'name_part'       => $item->name_part,
                    'rack_no'         => $item->rack_no,
                    'loc'             => $locDisplay,
                    'final_count'     => $newFinalCount,
                    'pic1'            => $pic1Name,
                    'pic2'            => $pic2Name,
                    'pos1_count'      => $pos1CountVal,
                    'pos1_time'       => $pos1Time,
                    'pos2_count'      => $pos2CountVal,
                    'pos2_time'       => $pos2Time,
                    'has_updates'     => !empty($updateHistory),
                    'update_history'  => !empty($updateHistory) ? implode(', ', $updateHistory) : null,
                    'pos1_history'    => $pos1HistoryRecords->pluck('result_count')->implode(', ') ?: '-',
                    'pos2_history'    => $pos2HistoryRecords->pluck('result_count')->implode(', ') ?: '-',
                    'completed_at'    => now()->format('d/m/Y, H:i:s'),
                    'user_nik'        => $member->nik,
                    'user_name'       => $member->nama,
                    'method'          => 'MANUAL POS 1 & 2',
                    'isManual'        => true,
                ];

                return response()->json([
                    'success'       => true,
                    'resolved_match'=> true,
                    'message'       => 'Koreksi Pos 1 berhasil! Hasil hitung (' . $input1 . ') kini telah COCOK dengan Pos 2. Status diubah ke READY_TO_RETURN dan label siap dicetak.',
                    'count'         => $input1,
                    'printData'     => $printData,
                    'history_id'    => $history->id,
                ]);
            }

            return response()->json([
                'success'     => true,
                'resolved_match' => false,
                'message'     => 'Perhitungan Pos 1 berhasil disimpan. Part siap diverifikasi di Pos 2!',
                'count'       => $input1,
                'history_id'  => $history->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit Perhitungan Pos 2 (Double input verification + Foto + Compare dengan Pos 1)
     */
    public function submitPos2(Request $request)
    {
        $request->validate([
            'so_item_id' => 'required|exists:so_items,id',
            'input_1'    => 'required|integer|min:0',
            'input_2'    => 'required|integer|min:0',
            'photos.*'   => 'nullable|image|max:15360',
        ]);

        $item = SoItem::findOrFail($request->so_item_id);
        $member = Auth::guard('member')->user();

        // Validasi multi-peran
        $roleCheck = $item->canMemberAct($member->nik, 'pos2');
        if (!$roleCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $roleCheck['reason'],
            ], 422);
        }

        // Pos 2 mengizinkan item yang sudah melewati Pos 1:
        // POS1_DONE, MISMATCH_NG, dan READY_TO_RETURN (jika ada part tercecer / hitung ulang sesuai SOP)
        $allowedStepsPos2 = ['POS1_DONE', 'MISMATCH_NG', 'READY_TO_RETURN'];

        if (!in_array($item->current_step, $allowedStepsPos2, true)) {
            $stepLabels = [
                'COURIER'        => 'Part masih dalam penanganan kurir (belum selesai serah terima).',
                'READY_TO_COUNT' => 'Part belum pernah dihitung di Pos 1. Wajib melalui Pos 1 terlebih dahulu sebelum Pos 2.',
                'RETURNED'       => 'Part sudah berstatus RETURNED (sudah dikembalikan ke rak oleh tim Returner). Tidak dapat dihitung ulang.',
            ];
            $detailMsg = $stepLabels[$item->current_step] ?? ('Status part saat ini (' . $item->current_step . ') belum siap untuk Pos 2.');

            return response()->json([
                'success' => false,
                'message' => 'Gagal submit Pos 2: ' . $detailMsg,
            ], 422);
        }

        $input1 = (int) $request->input_1;
        $input2 = (int) $request->input_2;

        if ($input1 !== $input2) {
            return response()->json([
                'success' => false,
                'message' => 'Input 1 (' . $input1 . ') dan Input 2 (' . $input2 . ') tidak sama! Harap hitung ulang dengan teliti.',
            ], 422);
        }

        $member = Auth::guard('member')->user();
        $pos1Count = (int) $item->pos1_count;
        $pos2Count = $input1;

        // Bandingkan Pos 1 dan Pos 2
        $isMatch = ($pos1Count === $pos2Count);

        DB::beginTransaction();
        try {
            // Simpan riwayat hitungan Pos 2
            $history = SoCountHistory::create([
                'so_item_id'   => $item->id,
                'count_type'   => 'POS2',
                'member_nik'   => $member->nik,
                'input_1'      => $input1,
                'input_2'      => $input2,
                'result_count' => $pos2Count,
                'status'       => $isMatch ? 'OK' : 'NG',
                'notes'        => $isMatch ? $request->notes : 'MISMATCH Pos 1 (' . $pos1Count . ') vs Pos 2 (' . $pos2Count . ')',
            ]);

            // Handle Multiple Photos upload jika ada
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photoFile) {
                    if ($photoFile && $photoFile->isValid()) {
                        $photoPath = $this->saveCompressedPhoto($photoFile, $item, 'pos2');
                        SoPhoto::create([
                            'so_item_id' => $item->id,
                            'history_id' => $history->id,
                            'pos_type'   => 'POS2',
                            'photo_path' => $photoPath,
                        ]);
                    }
                }
            } elseif ($request->hasFile('photo')) { // fallback single
                $photoPath = $this->saveCompressedPhoto($request->file('photo'), $item, 'pos2');
                SoPhoto::create([
                    'so_item_id' => $item->id,
                    'history_id' => $history->id,
                    'pos_type'   => 'POS2',
                    'photo_path' => $photoPath,
                ]);
            }

            if (!$isMatch) {
                // MISMATCH NG: Update status item ke MISMATCH_NG dan kosongkan final_count
                $item->update([
                    'pos2_count'   => $pos2Count,
                    'pos2_at'      => now(),
                    'final_count'  => null,
                    'current_step' => 'MISMATCH_NG',
                ]);

                DB::commit();

                return response()->json([
                    'success'     => false,
                    'status'      => 'MISMATCH_NG',
                    'message'     => 'Hasil Pos 2 (' . $pos2Count . ') TIDAK SAMA dengan Pos 1 (' . $pos1Count . ')! Status item berubah menjadi MISMATCH NG. Perlu verifikasi & hitung ulang.',
                    'pos1_count'  => $pos1Count,
                    'pos2_count'  => $pos2Count,
                    'history_id'  => $history->id,
                ]);
            }

            // MATCH OK: Update status item ke READY_TO_RETURN & simpan final count
            $item->update([
                'process_type'       => 'MANUAL',
                'pos2_count'         => $pos2Count,
                'pos2_at'            => now(),
                'final_count'        => $pos2Count,
                'current_step'       => 'READY_TO_RETURN',
                'count_completed_at' => now(),
            ]);

            DB::commit();

            // Sediakan payload untuk langsung cetak label final via Bluetooth S-Logic
            $pos1Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'desc')->first();
            $pic1Name = $pos1Record ? ($pos1Record->member ? $pos1Record->member->nama : $pos1Record->member_nik) : '-';
            $pic2Name = $member->nama ?: $member->nik;

            $locDisplay = ($item->area && $item->cell) ? ($item->area . '-' . $item->cell) : (($item->area ?: '-') . '-' . ($item->cell ?: '-'));
            if (str_contains($item->tracking_code, '|')) {
                $tParts = explode('|', $item->tracking_code);
                if (!empty($tParts[3])) {
                    $locDisplay = trim($tParts[3]);
                }
            } elseif (!empty($item->cell) && str_contains($item->cell, ',')) {
                $locDisplay = $item->cell;
            }

            // Ambil riwayat Pos 1 dan Pos 2
            $pos1HistoryRecords = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'asc')->get();
            $pos2HistoryRecords = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'asc')->get();

            $latestPos1 = $pos1HistoryRecords->last();
            $latestPos2 = $pos2HistoryRecords->last();

            $pos1CountVal = $latestPos1 ? $latestPos1->result_count : $pos1Count;
            $pos1Time     = $latestPos1 ? $latestPos1->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i');

            $pos2CountVal = $latestPos2 ? $latestPos2->result_count : $pos2Count;
            $pos2Time     = $latestPos2 ? $latestPos2->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i');

            // Kumpulkan riwayat update (selain yang terbaru)
            $updateHistory = [];
            if ($pos1HistoryRecords->count() > 1) {
                for ($i = 0; $i < $pos1HistoryRecords->count() - 1; $i++) {
                    $rec = $pos1HistoryRecords[$i];
                    $updateHistory[] = 'P1:' . $rec->result_count . ' (' . $rec->created_at->format('d/m/Y H:i') . ')';
                }
            }
            if ($pos2HistoryRecords->count() > 1) {
                for ($i = 0; $i < $pos2HistoryRecords->count() - 1; $i++) {
                    $rec = $pos2HistoryRecords[$i];
                    $updateHistory[] = 'P2:' . $rec->result_count . ' (' . $rec->created_at->format('d/m/Y H:i') . ')';
                }
            }

            $printData = [
                'tracking_code'   => $item->tracking_code,
                'code_part'       => $item->code_part,
                'name_part'       => $item->name_part,
                'rack_no'         => $item->rack_no,
                'loc'             => $locDisplay,
                'final_count'     => $pos2Count,
                'pic1'            => $pic1Name,
                'pic2'            => $pic2Name,
                'pos1_count'      => $pos1CountVal,
                'pos1_time'       => $pos1Time,
                'pos2_count'      => $pos2CountVal,
                'pos2_time'       => $pos2Time,
                'has_updates'     => !empty($updateHistory),
                'update_history'  => !empty($updateHistory) ? implode(', ', $updateHistory) : null,
                'pos1_history'    => $pos1HistoryRecords->pluck('result_count')->implode(', ') ?: '-',
                'pos2_history'    => $pos2HistoryRecords->pluck('result_count')->implode(', ') ?: '-',
                'completed_at'    => now()->format('d/m/Y, H:i:s'),
                'user_nik'        => $member->nik,
                'user_name'       => $member->nama,
                'method'          => 'MANUAL POS 1 & 2',
                'isManual'        => true,
            ];

            return response()->json([
                'success'    => true,
                'status'     => 'OK',
                'message'    => 'Hasil Cocok! Part berstatus READY TO RETURN. Silakan cetak label QR Final.',
                'final_count'=> $pos2Count,
                'printData'  => $printData,
                'history_id' => $history->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses Pos 2: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check Match status realtime Pos 1 vs Pos 2 saat operator Pos 2 klik "Cetak Label QR Final"
     * Jika Pos 1 sebelumnya salah dan sekarang sudah mengupdate perhitungannya, Pos 2 langsung cocok tanpa reload.
     */
    public function checkMatchAndPrintPos2(Request $request)
    {
        $request->validate([
            'so_item_id' => 'required|exists:so_items,id',
        ]);

        $item = SoItem::findOrFail($request->so_item_id);
        $member = Auth::guard('member')->user();

        // Validasi multi-peran
        $roleCheck = $item->canMemberAct($member->nik, 'pos2');
        if (!$roleCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $roleCheck['reason'],
            ], 422);
        }
        $member = Auth::guard('member')->user();

        // Ambil input Pos 2 terakhir dari database atau item
        $latestPos2Record = SoCountHistory::where('so_item_id', $item->id)
            ->where('count_type', 'POS2')
            ->orderBy('id', 'desc')
            ->first();

        $pos2Count = $latestPos2Record ? (int)$latestPos2Record->result_count : (int)$item->pos2_count;

        // Ambil input Pos 1 terbaru
        $latestPos1Record = SoCountHistory::where('so_item_id', $item->id)
            ->where('count_type', 'POS1')
            ->orderBy('id', 'desc')
            ->first();

        $pos1Count = $latestPos1Record ? (int)$latestPos1Record->result_count : (int)$item->pos1_count;

        // Cek apakah match
        $isMatch = ($pos1Count === $pos2Count);

        if (!$isMatch) {
            return response()->json([
                'match'      => false,
                'status'     => 'MISMATCH_NG',
                'message'    => 'Hasil perhitungan belum cocok! Pos 1: ' . number_format($pos1Count) . ' Pcs, sedangkan Pos 2: ' . number_format($pos2Count) . ' Pcs. Harap koordinasi atau koreksi sebelum cetak label.',
                'pos1_count' => $pos1Count,
                'pos2_count' => $pos2Count,
            ]);
        }

        // Jika cocok tapi status item belum READY_TO_RETURN (misal tadi Pos 1 sempat mismatch, lalu Pos 1 selesai update)
        if ($item->current_step !== 'READY_TO_RETURN' && $item->current_step !== 'RETURNED') {
            $item->update([
                'process_type'       => 'MANUAL',
                'pos1_count'         => $pos1Count,
                'pos2_count'         => $pos2Count,
                'final_count'        => $pos2Count,
                'current_step'       => 'READY_TO_RETURN',
                'count_completed_at' => now(),
            ]);
        }

        // Siapkan printData
        $pos1Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'desc')->first();
        $pic1Name = $pos1Record ? ($pos1Record->member ? $pos1Record->member->nama : $pos1Record->member_nik) : '-';
        $pic2Name = $latestPos2Record ? ($latestPos2Record->member ? $latestPos2Record->member->nama : $latestPos2Record->member_nik) : ($member->nama ?: $member->nik);

        $locDisplay = ($item->area && $item->cell) ? ($item->area . '-' . $item->cell) : (($item->area ?: '-') . '-' . ($item->cell ?: '-'));
        if (str_contains($item->tracking_code, '|')) {
            $tParts = explode('|', $item->tracking_code);
            if (!empty($tParts[3])) {
                $locDisplay = trim($tParts[3]);
            }
        }

        $pos1HistoryRecords = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'asc')->get();
        $pos2HistoryRecords = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'asc')->get();

        $latestPos1 = $pos1HistoryRecords->last();
        $latestPos2 = $pos2HistoryRecords->last();

        $pos1CountVal = $latestPos1 ? $latestPos1->result_count : $pos1Count;
        $pos1Time     = $latestPos1 ? $latestPos1->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i');

        $pos2CountVal = $latestPos2 ? $latestPos2->result_count : $pos2Count;
        $pos2Time     = $latestPos2 ? $latestPos2->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i');

        $updateHistory = [];
        if ($pos1HistoryRecords->count() > 1) {
            for ($i = 0; $i < $pos1HistoryRecords->count() - 1; $i++) {
                $rec = $pos1HistoryRecords[$i];
                $updateHistory[] = 'P1:' . $rec->result_count . ' (' . $rec->created_at->format('d/m/Y H:i') . ')';
            }
        }
        if ($pos2HistoryRecords->count() > 1) {
            for ($i = 0; $i < $pos2HistoryRecords->count() - 1; $i++) {
                $rec = $pos2HistoryRecords[$i];
                $updateHistory[] = 'P2:' . $rec->result_count . ' (' . $rec->created_at->format('d/m/Y H:i') . ')';
            }
        }

        $printData = [
            'tracking_code'   => $item->tracking_code,
            'code_part'       => $item->code_part,
            'name_part'       => $item->name_part,
            'rack_no'         => $item->rack_no,
            'loc'             => $locDisplay,
            'final_count'     => $pos2Count,
            'pic1'            => $pic1Name,
            'pic2'            => $pic2Name,
            'pos1_count'      => $pos1CountVal,
            'pos1_time'       => $pos1Time,
            'pos2_count'      => $pos2CountVal,
            'pos2_time'       => $pos2Time,
            'has_updates'     => !empty($updateHistory),
            'update_history'  => !empty($updateHistory) ? implode(', ', $updateHistory) : null,
            'pos1_history'    => $pos1HistoryRecords->pluck('result_count')->implode(', ') ?: '-',
            'pos2_history'    => $pos2HistoryRecords->pluck('result_count')->implode(', ') ?: '-',
            'completed_at'    => now()->format('d/m/Y, H:i:s'),
            'user_nik'        => $member->nik,
            'user_name'       => $member->nama,
            'method'          => 'MANUAL POS 1 & 2',
            'isManual'        => true,
        ];

        return response()->json([
            'match'       => true,
            'status'      => 'OK',
            'message'     => 'Hasil Pos 1 dan Pos 2 Cocok!',
            'final_count' => $pos2Count,
            'printData'   => $printData,
        ]);
    }

    /**
     * Upload foto part ber-QR label final oleh Pos 2 sebelum lanjut scan item berikutnya
     */
    public function uploadFinalPhoto(Request $request)
    {
        $request->validate([
            'so_item_id' => 'required|exists:so_items,id',
            'photo'      => 'required|image|max:15360',
        ]);

        $item = SoItem::findOrFail($request->so_item_id);
        $member = Auth::guard('member')->user();

        // Validasi multi-peran
        $roleCheck = $item->canMemberAct($member->nik, 'pos2');
        if (!$roleCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $roleCheck['reason'],
            ], 422);
        }
        $photoPath = $this->saveCompressedPhoto($request->file('photo'), $item, 'pos2_final');

        $photo = SoPhoto::create([
            'so_item_id' => $item->id,
            'pos_type'   => 'POS2',
            'photo_path' => $photoPath,
        ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Foto part dengan QR final berhasil disimpan.',
            'photo_path' => asset($photoPath),
        ]);
    }

    /**
     * Ambil riwayat penimbangan / perhitungan item
     */
    public function itemHistory(Request $request)
    {
        $soItemId = $request->input('so_item_id');
        $pos = strtoupper((string) $request->input('pos', ''));
        if (!$soItemId) {
            return response()->json(['histories' => [], 'photos' => []]);
        }

        $histories = SoCountHistory::where('so_item_id', $soItemId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($h) use ($pos) {
                // Di Pos 2, jaga agar hasil perhitungan Pos 1 tidak bisa diintip (blind count)
                $resultCount = $h->result_count;
                if ($pos === 'POS2' && $h->count_type === 'POS1') {
                    $resultCount = '***';
                }

                return [
                    'id'           => $h->id,
                    'count_type'   => $h->count_type,
                    'member_nik'   => $h->member_nik,
                    'result_count' => $resultCount,
                    'status'       => $h->status,
                    'notes'        => $h->notes,
                    'created_at'   => $h->created_at->format('d/m/Y H:i:s'),
                ];
            });

        // Ambil akumulasi seluruh foto part ini (Pos 1 & Pos 2)
        $photos = SoPhoto::where('so_item_id', $soItemId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($p) {
                return [
                    'id'         => $p->id,
                    'pos_type'   => $p->pos_type,
                    'url'        => asset($p->photo_path),
                    'created_at' => $p->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'histories' => $histories,
            'photos'    => $photos,
        ]);
    }

    /**
     * Helper save compressed photo
     */
    private function saveCompressedPhoto($file, $itemOrTrackingCode, $subfolder)
    {
        $trackingCode = is_object($itemOrTrackingCode) ? $itemOrTrackingCode->tracking_code : (string) $itemOrTrackingCode;
        $eventFolder  = is_object($itemOrTrackingCode) && method_exists($itemOrTrackingCode, 'getEventUploadFolder')
            ? $itemOrTrackingCode->getEventUploadFolder()
            : \App\Models\SoEvent::getActiveUploadFolder();

        $safeTrackingCode = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $trackingCode);
        $safeTrackingCode = trim($safeTrackingCode, ' ._') ?: 'item';
        $destinationDir   = public_path('uploads/' . $eventFolder . '/' . $safeTrackingCode . '/' . $subfolder);

        if (!File::isDirectory($destinationDir)) {
            File::makeDirectory($destinationDir, 0755, true, true);
        }

        $filename = time() . '_' . uniqid() . '.jpg';
        $fullPath = $destinationDir . '/' . $filename;

        // Resize with GD to max 1280px & 75% quality
        $sourcePath = $file->getRealPath();
        $imageInfo = @getimagesize($sourcePath);

        if ($imageInfo) {
            $origWidth  = $imageInfo[0];
            $origHeight = $imageInfo[1];
            $mime       = $imageInfo['mime'];

            switch ($mime) {
                case 'image/jpeg':
                    $src = @imagecreatefromjpeg($sourcePath);
                    break;
                case 'image/png':
                    $src = @imagecreatefrompng($sourcePath);
                    break;
                case 'image/webp':
                    $src = @imagecreatefromwebp($sourcePath);
                    break;
                default:
                    $src = false;
            }

            if ($src) {
                $maxDim = 1280;
                $scaleRatio = 1.0;
                if ($origWidth > $maxDim || $origHeight > $maxDim) {
                    $scaleRatio = min($maxDim / $origWidth, $maxDim / $origHeight);
                }
                $newWidth  = (int) round($origWidth * $scaleRatio);
                $newHeight = (int) round($origHeight * $scaleRatio);

                $dst = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                imagejpeg($dst, $fullPath, 75);

                imagedestroy($src);
                imagedestroy($dst);

                return 'uploads/' . $eventFolder . '/' . $safeTrackingCode . '/' . $subfolder . '/' . $filename;
            }
        }

        // Fallback jika GD gagal
        $file->move($destinationDir, $filename);
        return 'uploads/' . $eventFolder . '/' . $safeTrackingCode . '/' . $subfolder . '/' . $filename;
    }

    private function formatItemResponse(SoItem $item): array
    {
        // Cari nama kurir
        $courierName = null;
        if ($item->courier) {
            $courierName = $item->courier->nama ?: $item->courier->nik;
        } elseif ($item->courier_nik) {
            $c = Member::where('nik', $item->courier_nik)->first();
            $courierName = $c ? ($c->nama ?: $c->nik) : $item->courier_nik;
        }

        // Cari PIC Pos 1
        $pos1Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'desc')->first();
        $picPos1 = $pos1Record ? ($pos1Record->member ? ($pos1Record->member->nama ?: $pos1Record->member->nik) : $pos1Record->member_nik) : null;

        // Cari PIC Pos 2
        $pos2Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'desc')->first();
        $picPos2 = $pos2Record ? ($pos2Record->member ? ($pos2Record->member->nama ?: $pos2Record->member->nik) : $pos2Record->member_nik) : null;

        return [
            'id'            => $item->id,
            'tracking_code' => $item->tracking_code,
            'rack_no'       => $item->rack_no,
            'code_rack'     => $item->code_rack,
            'area'          => $item->area,
            'cell'          => $item->cell,
            'loc_cell'      => Area::formatLocDisplay($item->area, $item->cell),
            'code_part'     => $item->code_part,
            'name_part'     => $item->name_part,
            'process_type'  => $item->process_type,
            'current_step'  => $item->current_step,
            'courier_nik'   => $item->courier_nik,
            'courier_name'  => $courierName,
            'pic_courier'   => $courierName ? "{$courierName} ({$item->courier_nik})" : ($item->courier_nik ?: '-'),
            'pic_pos1'      => $picPos1 ? ($pos1Record && $pos1Record->member_nik ? "{$picPos1} ({$pos1Record->member_nik})" : $picPos1) : null,
            'pic_pos2'      => $picPos2 ? ($pos2Record && $pos2Record->member_nik ? "{$picPos2} ({$pos2Record->member_nik})" : $picPos2) : null,
            'pos1_count'    => $item->pos1_count,
            'pos2_count'    => $item->pos2_count,
            'final_count'   => $item->final_count,
            'courier_at'    => $item->courier_at ? $item->courier_at->format('d/m/Y H:i') : '-',
        ];
    }
}

