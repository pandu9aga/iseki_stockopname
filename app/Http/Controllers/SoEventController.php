<?php

namespace App\Http\Controllers;

use App\Models\SoEvent;
use App\Models\SoEventPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SoEventController extends Controller
{
    /**
     * Admin: Tampilan List & Manajemen Jadwal Stock Opname
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $events = SoEvent::withCount(['parts', 'soItems'])->orderByDesc('id');
            return DataTables::of($events)
                ->addIndexColumn()
                ->addColumn('period', function ($row) {
                    return $row->start_date->format('d/m/Y') . ' - ' . $row->end_date->format('d/m/Y');
                })
                ->addColumn('status_badge', function ($row) {
                    if ($row->status === 'ACTIVE') {
                        return '<span class="badge bg-success px-3 py-2 fw-bold"><i class="fas fa-play me-1"></i>AKTIF</span>';
                    } elseif ($row->status === 'CLOSED') {
                        return '<span class="badge bg-secondary px-3 py-2"><i class="fas fa-lock me-1"></i>SELESAI (CLOSED)</span>';
                    }
                    return '<span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-file-alt me-1"></i>DRAFT</span>';
                })
                ->addColumn('action', function ($row) {
                    $buttons = '';
                    $buttons .= '<a href="' . route('admin.so-events.parts', $row->id) . '" class="btn btn-sm btn-info me-1" title="Lihat Snapshot Part & Riwayat">
                                    <i class="fas fa-boxes"></i> List Part
                                 </a>';
                    if ($row->status !== 'ACTIVE') {
                        $buttons .= '<button type="button" class="btn btn-sm btn-success btn-activate me-1" data-id="' . $row->id . '" title="Aktifkan Jadwal Ini">
                                        <i class="fas fa-check"></i> Aktifkan
                                     </button>';
                    }
                    if ($row->status === 'ACTIVE') {
                        $buttons .= '<button type="button" class="btn btn-sm btn-secondary btn-close-event me-1" data-id="' . $row->id . '" title="Tutup / Selesaikan Jadwal">
                                        <i class="fas fa-lock"></i> Tutup
                                     </button>';
                    }
                    $buttons .= '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $row->id . '" data-name="' . e($row->event_name) . '" title="Hapus Jadwal">
                                    <i class="fas fa-trash"></i>
                                 </button>';
                    return $buttons;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        $activeEvent = SoEvent::getActiveEvent();
        return view('admin.so_events.index', compact('activeEvent'));
    }

    /**
     * Admin: Simpan Jadwal Baru & Snapshot/Salin Semua Part dari iseki_label
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_code' => 'required|string|max:50|unique:so_events,event_code',
            'event_name' => 'required|string|max:150',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'status'     => 'required|in:DRAFT,ACTIVE',
        ]);

        $admin = Auth::guard('admin')->user();

        DB::beginTransaction();
        try {
            // Jika statusnya ACTIVE, set event yang lain menjadi CLOSED
            if ($request->status === 'ACTIVE') {
                SoEvent::where('status', 'ACTIVE')->update(['status' => 'CLOSED']);
            }

            $event = SoEvent::create([
                'event_code'  => strtoupper(trim($request->event_code)),
                'event_name'  => trim($request->event_name),
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
                'status'      => $request->status,
                'description' => $request->description,
                'created_by'  => $admin ? ($admin->nama ?? $admin->name ?? 'Admin') : 'Admin',
            ]);

            // Salin snapshot seluruh part dari iseki_label.rack_part_lists ke so_event_parts
            $labelItems = DB::connection('label')->table('rack_part_lists')
                ->whereNotNull('rack_no')
                ->where('rack_no', '!=', '')
                ->get();

            $partsToInsert = [];
            $now = now();

            foreach ($labelItems as $item) {
                $rackVal = trim($item->rack ?? '');
                $cellVal = trim($item->cell ?? '');

                // Parse location (rack)
                $rackArr = [];
                if (str_starts_with($rackVal, '[') && str_ends_with($rackVal, ']')) {
                    $decoded = json_decode($rackVal, true);
                    if (is_array($decoded)) {
                        $rackArr = array_values(array_filter(array_map('trim', $decoded)));
                    }
                } elseif (!empty($rackVal)) {
                    $rackArr = array_values(array_filter(array_map('trim', explode(',', $rackVal))));
                }

                // Parse cell
                $cellArr = [];
                if (str_starts_with($cellVal, '[') && str_ends_with($cellVal, ']')) {
                    $decodedCell = json_decode($cellVal, true);
                    if (is_array($decodedCell)) {
                        $cellArr = array_values(array_filter(array_map('trim', $decodedCell)));
                    }
                } elseif (!empty($cellVal)) {
                    $cellArr = array_values(array_filter(array_map('trim', explode(',', $cellVal))));
                }

                $maxPairs = max(count($rackArr), count($cellArr), 1);

                for ($i = 0; $i < $maxPairs; $i++) {
                    $loc = $rackArr[$i] ?? ($rackArr[0] ?? '-');
                    $cell = $cellArr[$i] ?? ($cellArr[0] ?? '-');
                    $locDisplay = $loc . '-' . $cell;
                    $trackingCode = "{$item->item_code}|{$item->part_name}|{$item->rack_no}|{$locDisplay}";

                    $partsToInsert[] = [
                        'so_event_id'      => $event->id,
                        'label_part_id'    => $item->id,
                        'rack_no'          => $item->rack_no,
                        'location'         => $loc,
                        'cell'             => $cell,
                        'loc_cell_display' => $locDisplay,
                        'item_code'        => $item->item_code,
                        'part_name'        => $item->part_name,
                        'supplier'         => $item->supplier ?? '-',
                        'satuan'           => $item->satuan ?? 'PCS',
                        'tracking_code'    => $trackingCode,
                        'pair_index'       => $i + 1,
                        'total_pairs'      => $maxPairs,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }
            }

            // Insert secara batch (misal 500 rows per chunk)
            foreach (array_chunk($partsToInsert, 500) as $chunk) {
                SoEventPart::insert($chunk);
            }

            $event->update([
                'total_parts_snapshot' => count($partsToInsert),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal Stock Opname "' . $event->event_name . '" berhasil dibuat dan disalin sebanyak ' . count($partsToInsert) . ' part master!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat jadwal SO: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Aktifkan Jadwal SO tertentu
     */
    public function activate($id)
    {
        DB::beginTransaction();
        try {
            $event = SoEvent::findOrFail($id);
            SoEvent::where('status', 'ACTIVE')->update(['status' => 'CLOSED']);
            $event->update(['status' => 'ACTIVE']);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal SO "' . $event->event_name . '" sekarang berstatus AKTIF!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengaktifkan jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Tutup / Selesaikan Jadwal SO
     */
    public function close($id)
    {
        try {
            $event = SoEvent::findOrFail($id);
            $event->update(['status' => 'CLOSED']);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal SO "' . $event->event_name . '" berhasil ditutup (CLOSED).',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menutup jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Hapus Jadwal SO
     */
    public function destroy($id)
    {
        try {
            $event = SoEvent::findOrFail($id);
            $name = $event->event_name;
            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal SO "' . $name . '" beserta seluruh snapshot part berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Tampilan List Part Snapshot & Riwayat Tahap SO Berdasarkan Event
     */
    public function showEventParts($id)
    {
        $event = SoEvent::withCount(['parts', 'soItems'])->findOrFail($id);
        $events = SoEvent::orderBy('id', 'desc')->get();

        $totalStockopname = \App\Models\SoItem::where('so_event_id', $event->id)->count();
        $totalReadyToCount = \App\Models\SoItem::where('so_event_id', $event->id)->where('current_step', 'READY_TO_COUNT')->count();
        $totalCounted = \App\Models\SoItem::where('so_event_id', $event->id)->whereIn('current_step', ['READY_TO_RETURN', 'RETURNED'])->count();
        
        // Total part/rak-loc unik yang diaudit (bukan jumlah baris riwayat audit)
        $totalAudited = \App\Models\AuditorRecord::where('so_event_id', $event->id)
            ->selectRaw('COUNT(DISTINCT CONCAT(COALESCE(rack_no, ""), "###", COALESCE(location, ""), "###", COALESCE(cell, ""), "###", COALESCE(code_part, ""))) as total')
            ->value('total') ?? 0;

        return view('admin.so_events.parts', compact(
            'event',
            'events',
            'totalStockopname',
            'totalReadyToCount',
            'totalCounted',
            'totalAudited'
        ));
    }
}

