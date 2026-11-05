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

        // Ambil riwayat input Pos 1 terbaru oleh user ini (Maksimal 5)
        $recentHistories = SoCountHistory::with('soItem')
            ->where('member_nik', $member->nik)
            ->where('count_type', 'POS1')
            ->orderBy('created_at', 'desc')
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

        // Ambil riwayat input Pos 2 terbaru oleh user ini (Maksimal 5)
        $recentHistories = SoCountHistory::with('soItem')
            ->where('member_nik', $member->nik)
            ->where('count_type', 'POS2')
            ->orderBy('created_at', 'desc')
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

        // Cek langsung by tracking_code
        $item = SoItem::where('tracking_code', $raw)->first();

        // Jika tidak ditemukan dan input mengandung pipe (|), parse format QR kurir
        if (!$item && str_contains($raw, '|')) {
            $parts = explode('|', $raw);
            $codePart = trim($parts[0] ?? '');
            $namePart = trim($parts[1] ?? '');
            $rackNo   = trim($parts[2] ?? '');
            $locCell  = trim($parts[3] ?? '');

            $query = SoItem::query();
            if ($codePart !== '') $query->where('code_part', $codePart);
            if ($rackNo !== '')   $query->where('rack_no', $rackNo);

            if ($locCell !== '') {
                $locParts = explode('-', $locCell);
                $loc  = trim($locParts[0] ?? '');
                $cell = trim($locParts[1] ?? '');
                if ($loc !== '')  $query->where('area', $loc);
                if ($cell !== '') $query->where('cell', $cell);
            }

            $item = $query->orderBy('created_at', 'desc')->first();
        }

        if (!$item) {
            return response()->json([
                'found'   => false,
                'message' => 'Data part / tracking code tidak ditemukan dalam antrean stockopname.',
            ]);
        }

        // Validasi urutan status siklus (Sequential check)
        if ($pos === 'POS1') {
            if ($item->current_step !== 'READY_TO_COUNT' && $item->current_step !== 'MISMATCH_NG') {
                $stepLabels = [
                    'COURIER'         => 'Masih di tahap Kurir (belum selesai proses kurir)',
                    'POS1_DONE'       => 'Sudah selesai Pos 1 (menunggu Pos 2)',
                    'READY_TO_RETURN' => 'Sudah selesai dihitung (menunggu pengembalian)',
                    'RETURNED'        => 'Sudah dikembalikan ke rak',
                ];
                $msg = $stepLabels[$item->current_step] ?? 'Status tidak valid untuk Pos 1';

                return response()->json([
                    'found'   => true,
                    'allowed' => false,
                    'message' => 'Tidak dapat diproses di Pos 1: ' . $msg,
                    'item'    => $this->formatItemResponse($item),
                ]);
            }
        } elseif ($pos === 'POS2') {
            // Pos 2 mengizinkan item POS1_DONE, MISMATCH_NG, dan READY_TO_RETURN yang belum upload foto final
            $hasFinalPhoto = SoPhoto::where('so_item_id', $item->id)
                ->where('photo_path', 'like', '%pos2_final%')
                ->exists();

            if ($item->current_step === 'READY_TO_RETURN') {
                // Baik sudah upload foto maupun belum, izinkan reprint QR final jika discan ulang di Pos 2
                $pos1Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'desc')->first();
                $pos2Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'desc')->first();
                $member = Auth::guard('member')->user();
                $pic1Name = $pos1Record ? ($pos1Record->member ? $pos1Record->member->nama : $pos1Record->member_nik) : '-';
                $pic2Name = $pos2Record ? ($pos2Record->member ? $pos2Record->member->nama : $pos2Record->member_nik) : ($member->nama ?: $member->nik);

                // Ambil loc asli dari item (area-cell) atau dari QR
                $locDisplay = ($item->area && $item->cell) ? ($item->area . '-' . $item->cell) : (($item->area ?: '-') . '-' . ($item->cell ?: '-'));
                if (str_contains($item->tracking_code, '|')) {
                    $tParts = explode('|', $item->tracking_code);
                    if (!empty($tParts[3])) {
                        $locDisplay = trim($tParts[3]);
                    }
                }

                // Ambil seluruh histori hitungan pos 1 dan pos 2 dari yang terawal hingga terbaru
                $pos1HistoryCounts = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'asc')->pluck('result_count')->toArray();
                $pos2HistoryCounts = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'asc')->pluck('result_count')->toArray();

                // Waktu perhitungan OK terakhir
                $lastCompletedAt = $item->count_completed_at ? $item->count_completed_at->format('d/m/Y, H:i:s') : now()->format('d/m/Y, H:i:s');

                $printData = [
                    'tracking_code' => $item->tracking_code,
                    'code_part'     => $item->code_part,
                    'name_part'     => $item->name_part,
                    'rack_no'       => $item->rack_no,
                    'loc'           => $locDisplay,
                    'final_count'   => $item->final_count ?? $item->pos2_count,
                    'pic1'          => $pic1Name,
                    'pic2'          => $pic2Name,
                    'pos1_history'  => !empty($pos1HistoryCounts) ? implode(', ', $pos1HistoryCounts) : '-',
                    'pos2_history'  => !empty($pos2HistoryCounts) ? implode(', ', $pos2HistoryCounts) : '-',
                    'completed_at'  => $lastCompletedAt,
                    'user_nik'      => $member->nik,
                    'user_name'     => $member->nama,
                    'method'        => 'MANUAL POS 1 & 2',
                    'isManual'      => true,
                ];

                return response()->json([
                    'found'            => true,
                    'allowed'          => true,
                    'needs_final_flow' => true, // Masuk ke panel cetak QR & upload foto
                    'already_photo'    => $hasFinalPhoto, // apakah foto sudah ada sebelumnya
                    'printData'        => $printData,
                    'final_count'      => $item->final_count ?? $item->pos2_count,
                    'item'             => $this->formatItemResponse($item),
                ]);
            }

            if ($item->current_step !== 'POS1_DONE' && $item->current_step !== 'MISMATCH_NG') {
                $stepLabels = [
                    'COURIER'         => 'Masih di tahap Kurir',
                    'READY_TO_COUNT'  => 'Belum dihitung di Pos 1 (wajib Pos 1 terlebih dahulu)',
                    'READY_TO_RETURN' => 'Sudah selesai Pos 2 & foto final telah lengkap (menunggu pengembalian)',
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
            'found'   => true,
            'allowed' => true,
            'item'    => $this->formatItemResponse($item),
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

        if ($item->current_step !== 'READY_TO_COUNT' && $item->current_step !== 'MISMATCH_NG') {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak dalam status siap dihitung di Pos 1.',
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
                        $photoPath = $this->saveCompressedPhoto($photoFile, $item->tracking_code, 'pos1');
                        SoPhoto::create([
                            'so_item_id' => $item->id,
                            'history_id' => $history->id,
                            'pos_type'   => 'POS1',
                            'photo_path' => $photoPath,
                        ]);
                    }
                }
            } elseif ($request->hasFile('photo')) { // fallback single
                $photoPath = $this->saveCompressedPhoto($request->file('photo'), $item->tracking_code, 'pos1');
                SoPhoto::create([
                    'so_item_id' => $item->id,
                    'history_id' => $history->id,
                    'pos_type'   => 'POS1',
                    'photo_path' => $photoPath,
                ]);
            }

            // Cek apakah item sedang berstatus MISMATCH_NG dan apakah hasil baru ini langsung MATCH dengan Pos 2
            $isResolvedMatch = false;
            $newFinalCount = null;
            $pos2ExistingCount = $item->pos2_count;

            if ($item->current_step === 'MISMATCH_NG' && $pos2ExistingCount !== null && (int)$pos2ExistingCount === $input1) {
                // Pos 1 dikoreksi dan sekarang sudah sama persis dengan Pos 2
                $isResolvedMatch = true;
                $newFinalCount = $input1;
                $item->update([
                    'process_type'       => 'MANUAL',
                    'pos1_count'         => $input1,
                    'final_count'        => $input1,
                    'current_step'       => 'READY_TO_RETURN',
                    'count_completed_at' => now(),
                ]);
            } else {
                // Update status item ke POS1_DONE (agar Pos 2 bisa memverifikasi ulang)
                $item->update([
                    'process_type' => 'MANUAL',
                    'pos1_count'   => $input1,
                    'current_step' => 'POS1_DONE',
                ]);
            }

            DB::commit();

            if ($isResolvedMatch) {
                $pos1Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'desc')->first();
                $pos2Record = SoCountHistory::with('member')->where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'desc')->first();
                $pic1Name = $member->nama ?: $member->nik; // sedang dikoreksi oleh user ini di pos 1
                $pic2Name = $pos2Record ? ($pos2Record->member ? $pos2Record->member->nama : $pos2Record->member_nik) : '-';

                $locDisplay = ($item->area && $item->cell) ? ($item->area . '-' . $item->cell) : (($item->area ?: '-') . '-' . ($item->cell ?: '-'));
                if (str_contains($item->tracking_code, '|')) {
                    $tParts = explode('|', $item->tracking_code);
                    if (!empty($tParts[3])) {
                        $locDisplay = trim($tParts[3]);
                    }
                }

                // Ambil seluruh histori hitungan pos 1 dan pos 2 dari yang terawal hingga terbaru
                $pos1HistoryCounts = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'asc')->pluck('result_count')->toArray();
                $pos2HistoryCounts = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'asc')->pluck('result_count')->toArray();

                $printData = [
                    'tracking_code' => $item->tracking_code,
                    'code_part'     => $item->code_part,
                    'name_part'     => $item->name_part,
                    'rack_no'       => $item->rack_no,
                    'loc'           => $locDisplay,
                    'final_count'   => $newFinalCount,
                    'pic1'          => $pic1Name,
                    'pic2'          => $pic2Name,
                    'pos1_history'  => !empty($pos1HistoryCounts) ? implode(', ', $pos1HistoryCounts) : '-',
                    'pos2_history'  => !empty($pos2HistoryCounts) ? implode(', ', $pos2HistoryCounts) : '-',
                    'completed_at'  => now()->format('d/m/Y, H:i:s'),
                    'user_nik'      => $member->nik,
                    'user_name'     => $member->nama,
                    'method'        => 'MANUAL POS 1 & 2',
                    'isManual'      => true,
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

        if ($item->current_step !== 'POS1_DONE' && $item->current_step !== 'MISMATCH_NG') {
            return response()->json([
                'success' => false,
                'message' => 'Item belum siap untuk Pos 2 (harus melewati Pos 1 terlebih dahulu).',
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
                        $photoPath = $this->saveCompressedPhoto($photoFile, $item->tracking_code, 'pos2');
                        SoPhoto::create([
                            'so_item_id' => $item->id,
                            'history_id' => $history->id,
                            'pos_type'   => 'POS2',
                            'photo_path' => $photoPath,
                        ]);
                    }
                }
            } elseif ($request->hasFile('photo')) { // fallback single
                $photoPath = $this->saveCompressedPhoto($request->file('photo'), $item->tracking_code, 'pos2');
                SoPhoto::create([
                    'so_item_id' => $item->id,
                    'history_id' => $history->id,
                    'pos_type'   => 'POS2',
                    'photo_path' => $photoPath,
                ]);
            }

            if (!$isMatch) {
                // MISMATCH NG: Update status item ke MISMATCH_NG
                $item->update([
                    'pos2_count'   => $pos2Count,
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
            }

            // Ambil seluruh histori hitungan pos 1 dan pos 2 dari yang terawal hingga terbaru
            $pos1HistoryCounts = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS1')->orderBy('id', 'asc')->pluck('result_count')->toArray();
            $pos2HistoryCounts = SoCountHistory::where('so_item_id', $item->id)->where('count_type', 'POS2')->orderBy('id', 'asc')->pluck('result_count')->toArray();

            $printData = [
                'tracking_code' => $item->tracking_code,
                'code_part'     => $item->code_part,
                'name_part'     => $item->name_part,
                'rack_no'       => $item->rack_no,
                'loc'           => $locDisplay,
                'final_count'   => $pos2Count,
                'pic1'          => $pic1Name,
                'pic2'          => $pic2Name,
                'pos1_history'  => !empty($pos1HistoryCounts) ? implode(', ', $pos1HistoryCounts) : '-',
                'pos2_history'  => !empty($pos2HistoryCounts) ? implode(', ', $pos2HistoryCounts) : '-',
                'completed_at'  => now()->format('d/m/Y, H:i:s'),
                'user_nik'      => $member->nik,
                'user_name'     => $member->nama,
                'method'        => 'MANUAL POS 1 & 2',
                'isManual'      => true,
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
     * Upload foto part ber-QR label final oleh Pos 2 sebelum lanjut scan item berikutnya
     */
    public function uploadFinalPhoto(Request $request)
    {
        $request->validate([
            'so_item_id' => 'required|exists:so_items,id',
            'photo'      => 'required|image|max:15360',
        ]);

        $item = SoItem::findOrFail($request->so_item_id);
        $photoPath = $this->saveCompressedPhoto($request->file('photo'), $item->tracking_code, 'pos2_final');

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
        if (!$soItemId) {
            return response()->json(['histories' => [], 'photos' => []]);
        }

        $histories = SoCountHistory::where('so_item_id', $soItemId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($h) {
                return [
                    'id'           => $h->id,
                    'count_type'   => $h->count_type,
                    'member_nik'   => $h->member_nik,
                    'input_1'      => $h->input_1,
                    'input_2'      => $h->input_2,
                    'result_count' => $h->result_count,
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
    private function saveCompressedPhoto($file, $trackingCode, $subfolder)
    {
        $safeTrackingCode = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $trackingCode);
        $destinationDir = public_path('uploads/' . $safeTrackingCode . '/' . $subfolder);

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

                return 'uploads/' . $safeTrackingCode . '/' . $subfolder . '/' . $filename;
            }
        }

        // Fallback jika GD gagal
        $file->move($destinationDir, $filename);
        return 'uploads/' . $safeTrackingCode . '/' . $subfolder . '/' . $filename;
    }

    private function formatItemResponse(SoItem $item): array
    {
        return [
            'id'            => $item->id,
            'tracking_code' => $item->tracking_code,
            'rack_no'       => $item->rack_no,
            'code_rack'     => $item->code_rack,
            'area'          => $item->area,
            'cell'          => $item->cell,
            'loc_cell'      => ($item->area ?: '-') . '-' . ($item->cell ?: '-'),
            'code_part'     => $item->code_part,
            'name_part'     => $item->name_part,
            'process_type'  => $item->process_type,
            'current_step'  => $item->current_step,
            'pos1_count'    => $item->pos1_count,
            'pos2_count'    => $item->pos2_count,
            'final_count'   => $item->final_count,
            'courier_at'    => $item->courier_at ? $item->courier_at->format('d/m/Y H:i') : '-',
        ];
    }
}

