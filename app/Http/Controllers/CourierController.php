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
            // Parse json if it's like ["M85"]
            $cleanRack = $rackVal;
            if (str_starts_with($rackVal, '[') && str_ends_with($rackVal, ']')) {
                $decoded = json_decode($rackVal, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $cleanRack = implode(', ', $decoded);
                }
            }

            $cleanCell = $item->cell ?? '';
            if (str_starts_with($cleanCell, '[') && str_ends_with($cleanCell, ']')) {
                $decodedCell = json_decode($cleanCell, true);
                if (is_array($decodedCell) && count($decodedCell) > 0) {
                    $cleanCell = implode(', ', $decodedCell);
                }
            }

            if (empty($rackVal) || $rackVal === '[]') {
                $unregisteredCount++;
                continue;
            }

            $validItems[] = [
                'id' => $item->id,
                'rack_no' => $item->rack_no,
                'rack_code' => $cleanRack,
                'cell' => $cleanCell,
                'item_code' => $item->item_code,
                'part_name' => $item->part_name,
                'supplier' => $item->supplier ?? '-',
                'satuan' => $item->satuan ?? 'PCS',
            ];
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
            'photo_part' => 'required|image|max:10240',
            'photo_rack' => 'required|image|max:10240',
        ]);

        $member = Auth::guard('member')->user();

        // Upload photos
        $photoPartPath = $request->file('photo_part')->store('so_photos/courier', 'public');
        $photoRackPath = $request->file('photo_rack')->store('so_photos/courier', 'public');

        // Generate unique tracking code e.g. TRK-261102-XXXX
        $trackingCode = 'TRK-' . date('ymd') . '-' . strtoupper(Str::random(5));

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
            'courier_photo_part' => $photoPartPath,
            'courier_photo_rack' => $photoRackPath,
            'courier_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data kurir berhasil disimpan.',
            'item' => $soItem,
            'tracking_code' => $trackingCode,
        ]);
    }
}

