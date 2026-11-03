<?php

namespace App\Http\Controllers;

use App\Models\SoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourierController extends Controller
{
    public function index()
    {
        $member = Auth::guard('member')->user();
        if (!$member) {
            return redirect()->route('page.login');
        }

        // Recent items created by this courier
        $recentItems = SoItem::where('courier_nik', $member->nik)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('courier.index', compact('member', 'recentItems'));
    }

    /**
     * Look up rack in iseki_label.rack_part_lists
     */
    public function lookupRack(Request $request)
    {
        $rackQuery = trim($request->query('rack', ''));

        if (empty($rackQuery)) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter QR / Kode Rak wajib diisi.'
            ], 422);
        }

        // Search both by rack_no or matching rack column
        // e.g. rack_no = 'BP-A010' or rack LIKE '%"M85"%' or rack = 'M85'
        $items = DB::connection('label')->table('rack_part_lists')
            ->where(function ($q) use ($rackQuery) {
                $q->where('rack_no', $rackQuery)
                  ->orWhere('rack', 'LIKE', '%' . $rackQuery . '%');
            })
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'status' => 'NOT_FOUND',
                'message' => "Kode rak \"{$rackQuery}\" tidak ditemukan di master data iseki_label."
            ]);
        }

        // Check if all rows have empty rack
        $validItems = [];
        $unregisteredCount = 0;

        foreach ($items as $item) {
            $rackVal = trim($item->rack ?? '');
            if (empty($rackVal) || $rackVal === '[]') {
                $unregisteredCount++;
                continue;
            }

            // Decode rack array (location) and cell array
            $rackArr = [];
            if (str_starts_with($rackVal, '[') && str_ends_with($rackVal, ']')) {
                $decoded = json_decode($rackVal, true);
                if (is_array($decoded)) {
                    $rackArr = array_values(array_filter(array_map('trim', $decoded)));
                }
            } else {
                $rackArr = array_values(array_filter(array_map('trim', explode(',', $rackVal))));
            }

            $cellVal = trim($item->cell ?? '');
            $cellArr = [];
            if (str_starts_with($cellVal, '[') && str_ends_with($cellVal, ']')) {
                $decodedCell = json_decode($cellVal, true);
                if (is_array($decodedCell)) {
                    $cellArr = array_values(array_filter(array_map('trim', $decodedCell)));
                }
            } else {
                $cellArr = array_values(array_filter(array_map('trim', explode(',', $cellVal))));
            }

            if (empty($rackArr)) {
                $unregisteredCount++;
                continue;
            }

            // Pair rack (location) with cell
            // If there are multiple locations or cells, expand into options for courier to choose
            $maxPairs = max(count($rackArr), count($cellArr));

            for ($i = 0; $i < $maxPairs; $i++) {
                $currentLoc = $rackArr[$i] ?? ($rackArr[0] ?? '-');
                $currentCell = $cellArr[$i] ?? ($cellArr[0] ?? '-');

                $validItems[] = [
                    'id' => $item->id,
                    'rack_no' => $item->rack_no,
                    'location' => $currentLoc,
                    'cell' => $currentCell,
                    'loc_cell_display' => $currentLoc . '-' . $currentCell,
                    'item_code' => $item->item_code,
                    'part_name' => $item->part_name,
                    'supplier' => $item->supplier ?? '-',
                    'satuan' => $item->satuan ?? 'PCS',
                    'pair_index' => $i + 1,
                    'total_pairs' => $maxPairs,
                ];
            }
        }

        if (empty($validItems)) {
            return response()->json([
                'success' => false,
                'status' => 'UNREGISTERED',
                'message' => "Rak \"{$rackQuery}\" belum didaftarkan di master label (kolom rack kosong). Tidak bisa diprint. Harap tunggu hingga terisi oleh admin."
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => 'OK',
            'count' => count($validItems),
            'items' => $validItems,
        ]);
    }

    /**
     * Store item and set ready to count
     */
    public function store(Request $request)
    {
        $request->validate([
            'rack_label_id' => 'required',
            'rack_no' => 'required',
            'code_part' => 'required',
            'name_part' => 'required',
            'photo_part' => 'required|image|max:15360',
            'photo_rack' => 'required|image|max:15360',
        ]);

        $member = Auth::guard('member')->user();

        // Tracking code using the same format as QR: part|name|rak|loc
        $locDisplay = ($request->area ?? '-') . '-' . ($request->cell ?? '-');
        $trackingCode = "{$request->code_part}|{$request->name_part}|{$request->rack_no}|{$locDisplay}";

        // Sanitized folder name for Windows/filesystem (replace | and special chars with _)
        $safeFolderName = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $trackingCode);
        $safeFolderName = trim($safeFolderName, ' ._');

        // Destination directory: public/uploads/{safeFolderName}/kurir
        $targetDir = public_path("uploads/{$safeFolderName}/kurir");
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Process & resize photos
        $photoPartPath = $this->resizeAndSaveImage($request->file('photo_part'), $targetDir, 'part');
        $photoRackPath = $this->resizeAndSaveImage($request->file('photo_rack'), $targetDir, 'rack');

        // Relative path for database
        $dbPartPath = "uploads/{$safeFolderName}/kurir/{$photoPartPath}";
        $dbRackPath = "uploads/{$safeFolderName}/kurir/{$photoRackPath}";

        $soItem = SoItem::create([
            'tracking_code' => $trackingCode,
            'rack_label_id' => $request->rack_label_id,
            'rack_no' => $request->rack_no,
            'code_rack' => $request->code_rack,
            'area' => $request->area,
            'cell' => $request->cell,
            'code_part' => $request->code_part,
            'name_part' => $request->name_part,
            'current_step' => 'READY_TO_COUNT',
            'courier_nik' => $member->nik,
            'courier_photo_part' => $dbPartPath,
            'courier_photo_rack' => $dbRackPath,
            'courier_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data kurir berhasil disimpan.',
            'item' => $soItem,
            'tracking_code' => $trackingCode,
        ]);
    }

    /**
     * Resize uploaded image to max width/height 1280px with 75% JPEG quality
     */
    private function resizeAndSaveImage($uploadedFile, $targetDir, $prefix)
    {
        $filename = "{$prefix}_" . time() . '_' . Str::random(6) . '.jpg';
        $fullPath = "{$targetDir}/{$filename}";

        $imagePath = $uploadedFile->getRealPath();
        $imageInfo = @getimagesize($imagePath);

        if (!$imageInfo) {
            $uploadedFile->move($targetDir, $filename);
            return $filename;
        }

        $origWidth  = $imageInfo[0];
        $origHeight = $imageInfo[1];
        $mime       = $imageInfo['mime'];

        // Max dimension
        $maxDim = 1280;

        if ($origWidth > $maxDim || $origHeight > $maxDim) {
            $ratio = min($maxDim / $origWidth, $maxDim / $origHeight);
            $newWidth  = (int) round($origWidth * $ratio);
            $newHeight = (int) round($origHeight * $ratio);
        } else {
            $newWidth  = $origWidth;
            $newHeight = $origHeight;
        }

        // Create image source
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

        // Handle smartphone EXIF orientation if JPEG
        if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/jpg')) {
            $exif = @exif_read_data($imagePath);
            if (!empty($exif['Orientation'])) {
                $srcImage = match ($exif['Orientation']) {
                    3 => imagerotate($srcImage, 180, 0),
                    6 => imagerotate($srcImage, -90, 0),
                    8 => imagerotate($srcImage, 90, 0),
                    default => $srcImage,
                };
                $newWidth  = imagesx($srcImage);
                $newHeight = imagesy($srcImage);
                if ($newWidth > $maxDim || $newHeight > $maxDim) {
                    $ratio = min($maxDim / $newWidth, $maxDim / $newHeight);
                    $newWidth  = (int) round($newWidth * $ratio);
                    $newHeight = (int) round($newHeight * $ratio);
                }
            }
        }

        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($srcImage), imagesy($srcImage));

        // Save as optimized JPEG
        imagejpeg($dstImage, $fullPath, 75);

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $filename;
    }
}

