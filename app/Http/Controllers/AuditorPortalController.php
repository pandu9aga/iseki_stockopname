<?php

namespace App\Http\Controllers;

use App\Models\AuditorRecord;
use App\Models\Scale;
use App\Models\SoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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

        // Riwayat audit terakhir oleh auditor ini beserta data perbandingan SO (Maksimal 5 riwayat terbaru)
        $recentAudits = $this->getRecentAuditsWithComparison($auditor->name, 5);
        $scales = Scale::where('is_active', true)->orderBy('scale_number', 'asc')->get();

        return view('auditor.portal', compact('auditor', 'totalAudited', 'myAudited', 'recentAudits', 'scales'));
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
            $soItem = null;
            if ($rec->so_item_id) {
                $soItem = SoItem::find($rec->so_item_id);
            }
            if (!$soItem) {
                $query = SoItem::where('rack_no', $rec->rack_no);
                if (!empty($rec->code_part)) {
                    $query->where('code_part', $rec->code_part);
                }
                if (!empty($rec->cell)) {
                    $query->where('cell', $rec->cell);
                }
                $soItem = $query->orderBy('id', 'desc')->first();
            }

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

            // Margin info
            $scaleErrorMargin = $rec->error_margin_percent !== null ? (float)$rec->error_margin_percent : null;
            $memberErrorMargin = $rec->member_error_margin_percent !== null ? (float)$rec->member_error_margin_percent : null;
            $scaleStatus = $rec->scale_status ?? null;
            $auditStatus = $rec->audit_status ?? ($soCount !== null ? ($memberErrorMargin !== null && $memberErrorMargin <= 2.0 ? 'OK' : 'NG') : null);

            return [
                'id'            => $rec->id,
                'rack_no'       => $rec->rack_no,
                'code_part'     => $rec->code_part ?? ($soItem ? $soItem->code_part : '-'),
                'name_part'     => $soItem ? $soItem->name_part : '-',
                'audited_count' => (int)$rec->audited_count,
                'method'        => $rec->method ?? 'MANUAL',
                'input_1'       => $rec->input_1,
                'input_2'       => $rec->input_2,
                'scale_number'  => $rec->scale_number,
                'sample_qty'    => $rec->sample_qty,
                'weight_without_sample' => $rec->weight_without_sample,
                'weight_with_sample'    => $rec->weight_with_sample,
                'weight_calculated'     => $rec->weight_calculated,
                'weight_diff'           => $rec->weight_diff,
                'error_margin_percent'  => $scaleErrorMargin,
                'member_diff'           => $rec->member_diff ?? $diff,
                'member_error_margin_percent' => $memberErrorMargin,
                'scale_status'          => $scaleStatus,
                'audit_status'          => $auditStatus,
                'photo_paths'   => $rec->photo_paths,
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

        // Helper untuk mendapatkan location (area atau code_rack) dan huruf awalannya
        $getLocationAndLetter = function ($item) {
            $loc = trim($item->area ?: ($item->code_rack ?: ''));
            if ($loc === '' && str_contains($item->tracking_code, '|')) {
                $parts = explode('|', $item->tracking_code);
                $locDisplay = trim($parts[3] ?? '');
                $loc = explode('-', $locDisplay)[0] ?? '';
            }
            $cleanLoc = strtoupper(trim($loc));
            $firstChar = substr($cleanLoc, 0, 1);
            $groupKey = ctype_alpha($firstChar) ? $firstChar : ($firstChar !== '' ? '#' : '-');
            return [$cleanLoc, $groupKey];
        };

        // Koleksi semua huruf awalan yang unik dari LOCATION item returned
        $availableLetters = $returnedItems->map(function ($item) use ($getLocationAndLetter) {
            [$cleanLoc, $groupKey] = $getLocationAndLetter($item);
            return $groupKey;
        })->filter(fn($l) => $l !== '-')->unique()->sort()->values()->toArray();

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

            [$cleanLoc, $groupKey] = $getLocationAndLetter($item);

            if ($activeLetter !== 'ALL' && $groupKey !== $activeLetter) {
                continue;
            }

            // Location display
            $loc = $cleanLoc ?: '-';
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

        // Cek riwayat audit untuk rak, part, dan spesifik cell/lokasi ini
        $auditQuery = AuditorRecord::where('rack_no', $soItem->rack_no)
            ->where(function ($q) use ($soItem) {
                $q->where('code_part', $soItem->code_part)
                  ->orWhereNull('code_part');
            });

        // Filter lebih spesifik ke cell / tracking_code jika ada
        if (!empty($soItem->cell)) {
            $auditQuery->where(function ($q) use ($soItem) {
                $q->where('cell', $soItem->cell)
                  ->orWhere('so_item_id', $soItem->id)
                  ->orWhereNull('cell');
            });
        } elseif ($soItem->id) {
            $auditQuery->where(function ($q) use ($soItem) {
                $q->where('so_item_id', $soItem->id)
                  ->orWhereNull('so_item_id');
            });
        }

        $previousAudits = $auditQuery->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($a) {
                return [
                    'id'                          => $a->id,
                    'auditor_name'                => $a->auditor_name,
                    'method'                      => $a->method ?? 'MANUAL',
                    'audited_count'               => $a->audited_count,
                    'scale_number'                => $a->scale_number,
                    'error_margin_percent'        => $a->error_margin_percent,
                    'member_error_margin_percent' => $a->member_error_margin_percent,
                    'audit_status'                => $a->audit_status,
                    'notes'                       => $a->notes,
                    'created_at'                  => $a->created_at->format('d/m/Y H:i:s'),
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
     * Submit Hasil Audit oleh Auditor (Mendukung Metode Manual & Timbangan)
     */
    public function submitAudit(Request $request)
    {
        $method = strtoupper(trim((string) $request->input('method', 'MANUAL')));

        if ($method === 'SCALE') {
            $request->validate([
                'rack_no'               => 'required|string|max:100',
                'scale_id'              => 'required|exists:scales,id',
                'sample_qty'            => 'required|integer|min:1',
                'weight_without_sample' => 'required|integer|min:0',
                'weight_with_sample'    => 'required|integer|min:1',
                'photos.*'              => 'nullable|image|max:15360',
                'photo'                 => 'nullable|image|max:15360',
                'notes'                 => 'nullable|string',
            ]);
        } else {
            $request->validate([
                'rack_no'       => 'required|string|max:100',
                'input_1'       => 'required|integer|min:0',
                'input_2'       => 'required|integer|min:0',
                'photos.*'      => 'nullable|image|max:15360',
                'photo'         => 'nullable|image|max:15360',
                'notes'         => 'nullable|string',
            ]);

            if ((int)$request->input_1 !== (int)$request->input_2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Input 1 dan Input 2 tidak sama! Pastikan kedua input verifikasi bernilai sama persis.',
                ], 422);
            }
        }

        $auditor = Auth::guard('auditor')->user();
        if (!$auditor) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi login auditor tidak valid.',
            ], 401);
        }

        try {
            $activeEvent = \App\Models\SoEvent::getActiveEvent();
            $auditedCount = 0;
            $scaleNumber = null;
            $sampleQty = null;
            $weightWithout = null;
            $weightWith = null;
            $weightCalc = null;
            $weightDiff = null;
            $scaleErrorMargin = null;
            $scaleStatus = null;

            if ($method === 'SCALE') {
                $scale = Scale::find($request->scale_id);
                $scaleNumber = $scale ? $scale->scale_number : null;
                $sampleQty = (int) $request->sample_qty;
                $weightWithout = (int) $request->weight_without_sample;
                $weightWith = (int) $request->weight_with_sample;
                $weightCalc = $weightWithout + $sampleQty;
                $weightDiff = abs($weightWith - $weightCalc);

                // Margin of error timbangan: (|weight_with_sample - weight_calculated| / weight_with_sample) * 100%
                $scaleErrorMargin = $weightWith > 0 ? round(($weightDiff / $weightWith) * 100, 2) : 0;
                $scaleStatus = $scaleErrorMargin <= 2.0 ? 'OK' : 'NG';
                $auditedCount = $weightWith; // Hasil akhir timbangan adalah total dengan sample
            } else {
                $auditedCount = (int) $request->input_1;
            }

            // Ambil data SoItem terkait untuk menghitung margin of error terhadap hasil hitung member
            $soItem = null;
            if ($request->filled('so_item_id')) {
                $soItem = SoItem::find($request->so_item_id);
            }
            if (!$soItem) {
                $soItemQuery = SoItem::where('rack_no', $request->rack_no);
                if (!empty($request->code_part)) {
                    $soItemQuery->where('code_part', $request->code_part);
                }
                if ($request->filled('cell')) {
                    $soItemQuery->where('cell', $request->cell);
                }
                $soItem = $soItemQuery->orderBy('id', 'desc')->first();
            }

            $memberDiff = null;
            $memberErrorMargin = null;
            $auditStatus = 'OK';

            if ($soItem && $soItem->final_count !== null) {
                $soFinalCount = (int) $soItem->final_count;
                $memberDiff = abs($auditedCount - $soFinalCount);

                // Margin of error terhadap hasil member: (|audited_count - member_count| / member_count) * 100%
                if ($soFinalCount > 0) {
                    $memberErrorMargin = round(($memberDiff / $soFinalCount) * 100, 2);
                } else {
                    $memberErrorMargin = $auditedCount === 0 ? 0 : 100.0;
                }

                // Audit Status OK jika toleransi margin terhadap member <= 2% (dan jika timbangan, margin timbangan juga harus OK)
                if ($memberErrorMargin > 2.0 || ($method === 'SCALE' && $scaleStatus === 'NG')) {
                    $auditStatus = 'NG';
                } else {
                    $auditStatus = 'OK';
                }
            } elseif ($method === 'SCALE') {
                $auditStatus = $scaleStatus;
            }

            // Simpan foto bukti jika diupload
            $uploadedPhotos = [];
            $safeTracking = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $request->rack_no . '_' . ($request->code_part ?? 'part'));
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photoFile) {
                    if ($photoFile && $photoFile->isValid()) {
                        $uploadedPhotos[] = $this->saveCompressedPhoto($photoFile, $safeTracking, 'audit');
                    }
                }
            } elseif ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $uploadedPhotos[] = $this->saveCompressedPhoto($request->file('photo'), $safeTracking, 'audit');
            }

            $record = AuditorRecord::create([
                'so_event_id'                 => $activeEvent ? $activeEvent->id : null,
                'so_item_id'                  => $soItem ? $soItem->id : ($request->so_item_id ?: null),
                'rack_no'                     => $request->rack_no,
                'code_part'                   => $request->code_part,
                'location'                    => $soItem ? ($soItem->code_rack ?: $soItem->area) : $request->location,
                'cell'                        => $soItem ? $soItem->cell : $request->cell,
                'tracking_code'               => $soItem ? $soItem->tracking_code : $request->tracking_code,
                'auditor_name'                => $auditor->name,
                'audited_count'               => $auditedCount,
                'method'                      => $method,
                'input_1'                     => $method === 'MANUAL' ? (int)$request->input_1 : null,
                'input_2'                     => $method === 'MANUAL' ? (int)$request->input_2 : null,
                'scale_id'                    => $method === 'SCALE' ? $request->scale_id : null,
                'scale_number'                => $scaleNumber,
                'sample_qty'                  => $sampleQty,
                'weight_without_sample'       => $weightWithout,
                'weight_with_sample'          => $weightWith,
                'weight_calculated'           => $weightCalc,
                'weight_diff'                 => $weightDiff,
                'error_margin_percent'        => $scaleErrorMargin,
                'member_diff'                 => $memberDiff,
                'member_error_margin_percent' => $memberErrorMargin,
                'scale_status'                => $scaleStatus,
                'audit_status'                => $auditStatus,
                'photo_paths'                 => !empty($uploadedPhotos) ? $uploadedPhotos : null,
                'notes'                       => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hasil audit rak ' . $request->rack_no . ' (' . ($method === 'SCALE' ? 'Timbangan' : 'Manual') . ') berhasil disimpan!',
                'record'  => $record,
                'recent_audits' => $this->getRecentAuditsWithComparison($auditor->name, 5),
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

    /**
     * Helper save compressed photo audit
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

    /**
     * Tampilan Riwayat Audit Seluruh Part pada Suatu Event SO
     */
    public function auditHistory(Request $request, $id = null)
    {
        $user = Auth::guard('auditor')->user() ?: Auth::guard('admin')->user();
        if (!$user) {
            return redirect()->route('page.login');
        }

        $events = \App\Models\SoEvent::orderBy('id', 'desc')->get();
        $selectedEvent = null;

        if ($id) {
            $selectedEvent = \App\Models\SoEvent::find($id);
        }
        if (!$selectedEvent) {
            $selectedEvent = \App\Models\SoEvent::getActiveEvent() ?: $events->first();
        }

        // Ambil daftar nama auditor unik untuk dropdown filter
        $auditorNames = AuditorRecord::whereNotNull('auditor_name')
            ->distinct()
            ->orderBy('auditor_name', 'asc')
            ->pluck('auditor_name');

        return view('auditor.history', compact('user', 'events', 'selectedEvent', 'auditorNames'));
    }

    /**
     * DataTables AJAX Endpoint Riwayat Audit per Part (1 Baris per Part)
     */
    public function auditHistoryData(Request $request, $id = null)
    {
        $eventId = $id ?: $request->input('event_id');
        if (!$eventId) {
            $active = \App\Models\SoEvent::getActiveEvent();
            $eventId = $active ? $active->id : null;
        }

        // Ambil data snapshot parts dari tabel so_event_parts berdasarkan eventId
        $partsQuery = \App\Models\SoEventPart::query();
        if ($eventId) {
            $partsQuery->where('so_event_id', $eventId);
        } else {
            $firstEv = \App\Models\SoEvent::orderBy('id', 'desc')->first();
            if ($firstEv) {
                $partsQuery->where('so_event_id', $firstEv->id);
                $eventId = $firstEv->id;
            }
        }
        $snapshotParts = $partsQuery->orderBy('rack_no', 'asc')->get();

        // Ambil SoItem sesuai eventId
        $soItemQuery = \App\Models\SoItem::query();
        if ($eventId) {
            $soItemQuery->where('so_event_id', $eventId);
        }
        $soItems = $soItemQuery->get();

        $soMapByTracking = [];
        $soMapByCombo = [];
        foreach ($soItems as $so) {
            if ($so->tracking_code) {
                $soMapByTracking[$so->tracking_code] = $so;
            }
            $loc = ($so->area ?? '-') . '-' . ($so->cell ?? '-');
            $key = strtoupper(trim($so->code_part)) . '###' . strtoupper(trim($so->rack_no)) . '###' . strtoupper(trim($loc));
            $soMapByCombo[$key] = $so;
        }

        // Ambil semua auditor records sesuai eventId (urut dari terlama ke terbaru agar bisa dirangkai)
        $auditorQuery = AuditorRecord::query();
        if ($eventId) {
            $auditorQuery->where('so_event_id', $eventId);
        }
        $auditorRecords = $auditorQuery->orderBy('id', 'asc')->get();

        $auditorMap = [];
        foreach ($auditorRecords as $ar) {
            // Indexing berbasis tracking code (paling spesifik)
            if ($ar->tracking_code) {
                $auditorMap['TRACKING###' . strtoupper(trim($ar->tracking_code))][] = $ar;
            }
            // Indexing berbasis kombinasi code_part + rack_no + cell
            if ($ar->cell) {
                $cellKey = strtoupper(trim($ar->code_part ?? '')) . '###' . strtoupper(trim($ar->rack_no)) . '###' . strtoupper(trim($ar->cell));
                $auditorMap['CELL###' . $cellKey][] = $ar;
            }
            // Indexing berbasis so_item_id
            if ($ar->so_item_id) {
                $auditorMap['SOITEM###' . $ar->so_item_id][] = $ar;
            }
            // Fallback combo code_part + rack_no
            $comboKey = strtoupper(trim($ar->code_part ?? '')) . '###' . strtoupper(trim($ar->rack_no));
            $auditorMap['COMBO###' . $comboKey][] = $ar;
        }

        $flattenedRows = [];

        foreach ($snapshotParts as $item) {
            $loc = $item->location ?: '-';
            $cell = $item->cell ?: '-';
            $locDisplay = $item->loc_cell_display ?: ($loc . '-' . $cell);

            $trackingCode = $item->tracking_code ?: "{$item->item_code}|{$item->part_name}|{$item->rack_no}|{$locDisplay}";
            $comboKey = strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no)) . '###' . strtoupper(trim($locDisplay));

            $soItem = $soMapByTracking[$trackingCode] ?? ($soMapByCombo[$comboKey] ?? null);

            // Pencarian audit yang presisi untuk part & cell ini
            $audits = [];
            if ($soItem && isset($auditorMap['SOITEM###' . $soItem->id])) {
                $audits = $auditorMap['SOITEM###' . $soItem->id];
            } elseif (isset($auditorMap['TRACKING###' . strtoupper(trim($trackingCode))])) {
                $audits = $auditorMap['TRACKING###' . strtoupper(trim($trackingCode))];
            } else {
                $cellKey = strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no)) . '###' . strtoupper(trim($cell));
                if (isset($auditorMap['CELL###' . $cellKey])) {
                    $audits = $auditorMap['CELL###' . $cellKey];
                } elseif (($item->total_pairs ?? 1) <= 1 && isset($auditorMap['COMBO###' . strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no))])) {
                    // Hanya gunakan fallback jika part ini bukan part multi-cell
                    $audits = $auditorMap['COMBO###' . strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no))];
                }
            }

            $auditCount = count($audits);
            $isAudited = $auditCount > 0;

            // Kumpulkan seluruh nama auditor yang pernah mengaudit part ini
            $auditorNamesList = [];
            $allAuditsDetail = [];
            $latestAudit = null;
            $latestAuditQty = null;
            $latestAuditStatus = null;
            $latestAuditMethod = null;
            $latestAuditMargin = null;
            $latestAuditMemberMargin = null;
            $latestAuditorName = null;
            $latestAuditAt = null;

            if ($isAudited) {
                foreach ($audits as $idx => $a) {
                    if (!in_array($a->auditor_name, $auditorNamesList)) {
                        $auditorNamesList[] = $a->auditor_name;
                    }

                    $methodText = $a->method === 'SCALE' ? 'Timbangan' . ($a->scale_number ? ' (' . $a->scale_number . ')' : '') : 'Manual';
                    $statusText = $a->audit_status ?: ($soItem && $soItem->final_count !== null ? ((int)$a->audited_count === (int)$soItem->final_count ? 'OK' : 'NG') : '-');

                    $allAuditsDetail[] = [
                        'round'         => $idx + 1,
                        'auditor_name'  => $a->auditor_name,
                        'method'        => $methodText,
                        'qty'           => (int)$a->audited_count,
                        'status'        => $statusText,
                        'scale_margin'  => $a->error_margin_percent !== null ? $a->error_margin_percent . '%' : '-',
                        'member_margin' => $a->member_error_margin_percent !== null ? $a->member_error_margin_percent . '%' : '-',
                        'notes'         => $a->notes ?: '-',
                        'time'          => $a->created_at ? $a->created_at->format('d/m/Y H:i') : '-',
                    ];
                }

                $latestAudit = end($audits);
                $latestAuditQty = (int)$latestAudit->audited_count;
                $latestAuditStatus = $latestAudit->audit_status ?: ($soItem && $soItem->final_count !== null ? ($latestAuditQty === (int)$soItem->final_count ? 'OK' : 'NG') : 'UNKNOWN');
                $latestAuditMethod = $latestAudit->method ?? 'MANUAL';
                $latestAuditMargin = $latestAudit->error_margin_percent !== null ? (float)$latestAudit->error_margin_percent : null;
                $latestAuditMemberMargin = $latestAudit->member_error_margin_percent !== null ? (float)$latestAudit->member_error_margin_percent : null;
                $latestAuditorName = $latestAudit->auditor_name;
                $latestAuditAt = $latestAudit->created_at ? $latestAudit->created_at->format('d/m/Y H:i') : '-';
            }

            $soFinalCount = $soItem ? ($soItem->final_count !== null ? (int)$soItem->final_count : null) : null;
            $diffSo = null;
            if ($isAudited && $soFinalCount !== null) {
                $diffSo = $latestAuditQty - $soFinalCount;
            }

            $flattenedRows[] = [
                'id'                      => $item->id,
                'rack_no'                 => $item->rack_no,
                'item_code'               => $item->item_code,
                'part_name'               => $item->part_name,
                'loc_display'             => $locDisplay,
                'so_status'               => $soItem ? $soItem->current_step : 'BELUM_SO',
                'so_process_type'         => $soItem ? ($soItem->process_type ?? '-') : '-',
                'so_final_count'          => $soFinalCount !== null ? number_format($soFinalCount) . ' pcs' : '-',
                'is_audited'              => $isAudited,
                'audit_count'             => $auditCount,
                'auditor_names'           => implode(', ', $auditorNamesList),
                'latest_auditor'          => $latestAuditorName ?: '-',
                'latest_qty'              => $latestAuditQty !== null ? number_format($latestAuditQty) . ' pcs' : '-',
                'latest_method'           => $latestAuditMethod ?: '-',
                'latest_scale_moe'        => $latestAuditMargin !== null ? $latestAuditMargin . '%' : '-',
                'latest_member_moe'       => $latestAuditMemberMargin !== null ? $latestAuditMemberMargin . '%' : '-',
                'latest_status'           => $latestAuditStatus ?: '-',
                'diff_so'                 => $diffSo,
                'latest_time'             => $latestAuditAt ?: '-',
                'history_details'         => $allAuditsDetail,
            ];
        }

        return \Yajra\DataTables\Facades\DataTables::of(collect($flattenedRows))
            ->addIndexColumn()
            ->make(true);
    }
}

