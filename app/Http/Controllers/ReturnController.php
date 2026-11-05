<?php

namespace App\Http\Controllers;

use App\Models\SoItem;
use App\Models\SoPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ReturnController extends Controller
{
    /**
     * Tampilan Utama Modul Pengembalian (Return)
     */
    public function index()
    {
        $member = Auth::guard('member')->user();
        if (!$member) {
            return redirect()->route('page.login');
        }

        // Ambil 5 aktivitas pengembalian terakhir oleh user ini
        $recentReturns = SoItem::where('return_nik', $member->nik)
            ->where('current_step', 'RETURNED')
            ->orderBy('returned_at', 'desc')
            ->limit(5)
            ->get();

        return view('return.index', compact('member', 'recentReturns'));
    }

    /**
     * Step 1: Lookup & Validasi Label Final (QR box part)
     * Format payload QR: part|name|rak|loc|qty
     */
    public function lookupFinalLabel(Request $request)
    {
        $raw = trim((string) $request->input('qr_code', ''));
        if ($raw === '') {
            return response()->json([
                'found'   => false,
                'message' => 'QR Label Final tidak boleh kosong.',
            ], 422);
        }

        // Cek langsung by tracking_code
        $item = SoItem::where('tracking_code', $raw)->first();

        // Jika tidak ditemukan dan input dipisahkan pipe (|), parse QR final: part|name|rak|loc|qty
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
                'message' => 'Data part / QR label final tidak ditemukan dalam sistem stockopname.',
            ]);
        }

        // Sequential validation: hanya part dengan status READY_TO_RETURN yang boleh dikembalikan
        if ($item->current_step !== 'READY_TO_RETURN') {
            $stepLabels = [
                'COURIER'         => 'Masih di tahap Kurir (belum selesai proses kurir)',
                'READY_TO_COUNT'  => 'Part masih harus dihitung (Timbangan / Pos 1)',
                'POS1_DONE'       => 'Baru selesai Pos 1 (wajib verifikasi Pos 2 terlebih dahulu)',
                'MISMATCH_NG'     => 'Status Mismatch NG (perlu koreksi hitung terlebih dahulu)',
                'RETURNED'        => 'Part ini SUDAH PERNAH dikembalikan ke rak fisik',
            ];
            $msg = $stepLabels[$item->current_step] ?? 'Status tidak valid untuk proses pengembalian.';

            return response()->json([
                'found'   => true,
                'allowed' => false,
                'message' => 'Tidak dapat diproses: ' . $msg,
                'item'    => $this->formatItem($item),
            ]);
        }

        return response()->json([
            'found'   => true,
            'allowed' => true,
            'item'    => $this->formatItem($item),
        ]);
    }

    /**
     * Step 2: Verifikasi kecocokan nomor rak fisik dengan rak asal
     */
    public function verifyRack(Request $request)
    {
        $soItemId = $request->input('so_item_id');
        $scannedRack = trim((string) $request->input('scanned_rack', ''));

        if (!$soItemId || $scannedRack === '') {
            return response()->json([
                'match'   => false,
                'message' => 'Data item atau nomor rak tidak valid.',
            ], 422);
        }

        $item = SoItem::findOrFail($soItemId);
        $expectedRack = strtoupper(trim($item->rack_no));
        $actualRack = strtoupper($scannedRack);

        // Jika scan QR rak menghasilkan string berformat atau code_rack
        // cocokkan dengan rack_no atau code_rack
        $expectedCodeRack = strtoupper(trim($item->code_rack ?? ''));

        $isMatch = ($actualRack === $expectedRack) || ($expectedCodeRack !== '' && $actualRack === $expectedCodeRack);

        // Cek juga jika query mencari di iseki_label
        if (!$isMatch) {
            $matchedLabel = DB::connection('label')->table('rack_part_lists')
                ->where('rack_no', $item->rack_no)
                ->where(function ($q) use ($scannedRack) {
                    $q->where('rack_no', $scannedRack)
                      ->orWhere('rack', 'LIKE', '%' . $scannedRack . '%');
                })
                ->exists();
            if ($matchedLabel) {
                $isMatch = true;
            }
        }

        if (!$isMatch) {
            return response()->json([
                'match'         => false,
                'expected_rack' => $item->rack_no,
                'scanned_rack'  => $scannedRack,
                'message'       => "Nomor Rak Fisik SALAH! Part ini seharusnya dikembalikan ke rak \"{$item->rack_no}\", tetapi Anda men-scan rak \"{$scannedRack}\".",
            ]);
        }

        return response()->json([
            'match'         => true,
            'expected_rack' => $item->rack_no,
            'scanned_rack'  => $scannedRack,
            'message'       => "Rak Fisik COCOK! Silakan tata part ke rak {$item->rack_no} dan ambil foto bukti penataan rak.",
        ]);
    }

    /**
     * Step 3: Submit Pengembalian + Upload Foto Bukti Penataan Rak (➔ RETURNED)
     */
    public function submitReturn(Request $request)
    {
        $request->validate([
            'so_item_id'          => 'required|exists:so_items,id',
            'return_rack_scanned' => 'required|string',
            'photos.*'            => 'nullable|image|max:15360',
            'photo'               => 'nullable|image|max:15360',
        ]);

        $item = SoItem::findOrFail($request->so_item_id);

        if ($item->current_step !== 'READY_TO_RETURN') {
            return response()->json([
                'success' => false,
                'message' => 'Status item tidak valid untuk pengembalian.',
            ], 422);
        }

        $member = Auth::guard('member')->user();

        DB::beginTransaction();
        try {
            $savedPhotoPaths = [];

            // Multi-photo atau single photo upload
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $f) {
                    if ($f && $f->isValid()) {
                        $p = $this->saveCompressedPhoto($f, $item->tracking_code);
                        $savedPhotoPaths[] = $p;
                        SoPhoto::create([
                            'so_item_id' => $item->id,
                            'pos_type'   => 'RETURN',
                            'photo_path' => $p,
                        ]);
                    }
                }
            } elseif ($request->hasFile('photo')) {
                $p = $this->saveCompressedPhoto($request->file('photo'), $item->tracking_code);
                $savedPhotoPaths[] = $p;
                SoPhoto::create([
                    'so_item_id' => $item->id,
                    'pos_type'   => 'RETURN',
                    'photo_path' => $p,
                ]);
            }

            // Update item siklus ke RETURNED
            $item->update([
                'current_step'        => 'RETURNED',
                'return_nik'          => $member->nik,
                'return_rack_scanned' => $request->return_rack_scanned,
                'return_photo'        => $savedPhotoPaths[0] ?? null,
                'returned_at'         => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Part {$item->code_part} berhasil dikembalikan ke rak {$item->rack_no}. Siklus stockopname part ini telah lengkap!",
                'item'    => $this->formatItem($item),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan pengembalian: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function saveCompressedPhoto($file, $trackingCode)
    {
        $safeTrackingCode = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $trackingCode);
        $destinationDir = public_path('uploads/' . $safeTrackingCode . '/return');

        if (!File::isDirectory($destinationDir)) {
            File::makeDirectory($destinationDir, 0755, true, true);
        }

        $filename = 'return_' . time() . '_' . Str::random(6) . '.jpg';
        $fullPath = $destinationDir . '/' . $filename;

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

                return 'uploads/' . $safeTrackingCode . '/return/' . $filename;
            }
        }

        $file->move($destinationDir, $filename);
        return 'uploads/' . $safeTrackingCode . '/return/' . $filename;
    }

    private function formatItem(SoItem $item): array
    {
        return [
            'id'            => $item->id,
            'tracking_code' => $item->tracking_code,
            'rack_no'       => $item->rack_no,
            'code_rack'     => $item->code_rack,
            'loc_cell'      => ($item->area ?: '-') . '-' . ($item->cell ?: '-'),
            'code_part'     => $item->code_part,
            'name_part'     => $item->name_part,
            'final_count'   => $item->final_count,
            'current_step'  => $item->current_step,
            'courier_at'    => $item->courier_at ? $item->courier_at->format('d/m/Y H:i') : '-',
            'completed_at'  => $item->count_completed_at ? $item->count_completed_at->format('d/m/Y H:i:s') : '-',
            'returned_at'   => $item->returned_at ? $item->returned_at->format('d/m/Y H:i:s') : '-',
        ];
    }
}

