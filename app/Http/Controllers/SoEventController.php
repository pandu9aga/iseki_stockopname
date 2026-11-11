<?php

namespace App\Http\Controllers;

use App\Models\SoEvent;
use App\Models\SoEventPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
     * Download Template Excel Target Rak & Area untuk Event SO
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Target Rak');

        // Headers
        $sheet->setCellValue('A1', 'rack_no');
        $sheet->setCellValue('B1', 'area_letter');
        $sheet->setCellValue('C1', 'area_no');

        // Sample Rows
        $sheet->setCellValue('A2', 'LO-C003');
        $sheet->setCellValue('B2', 'W');
        $sheet->setCellValue('C2', '01');

        $sheet->setCellValue('A3', 'LO-C004');
        $sheet->setCellValue('B3', 'W');
        $sheet->setCellValue('C3', '01');

        $sheet->setCellValue('A4', 'LO-E001');
        $sheet->setCellValue('B4', 'E');
        $sheet->setCellValue('C4', '02');

        // Styling
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);

        $fileName = 'template_target_rak_so.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Admin: Simpan Jadwal Baru & Snapshot Part berdasarkan Pilihan Sumber (Copy Master iseki_label atau Upload Excel)
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_code'  => 'required|string|max:50|unique:so_events,event_code',
            'event_name'  => 'required|string|max:150',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'status'      => 'required|in:DRAFT,ACTIVE',
            'source_type' => 'required|in:ALL_LABEL,EXCEL_TARGET',
            'excel_file'  => 'required_if:source_type,EXCEL_TARGET|nullable|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $admin = Auth::guard('admin')->user();
        $sourceType = $request->source_type;

        // 1. Jika mode Excel Target, validasi dan baca file Excel target rak
        $rackTargetMap = [];
        if ($sourceType === 'EXCEL_TARGET') {
            $excelFile = $request->file('excel_file');
            if (!$excelFile) {
                return response()->json([
                    'success' => false,
                    'message' => 'File Excel wajib diunggah untuk opsi Target Excel.',
                ], 422);
            }
            $rackTargetMap = $this->parseRackExcel($excelFile->getPathname());

            if (empty($rackTargetMap)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File Excel tidak berisi data rak yang valid atau kolom header tidak sesuai (rack_no, area_letter, area_no).',
                ], 422);
            }
        }

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

            // 2. Salin snapshot part dari iseki_label
            if ($sourceType === 'EXCEL_TARGET') {
                $insertedCount = $this->processSnapshotFromLabel($event, $rackTargetMap);
            } else {
                $insertedCount = $this->processSnapshotAllFromLabel($event);
            }

            $event->update([
                'total_parts_snapshot' => $insertedCount,
            ]);

            DB::commit();

            $modeText = ($sourceType === 'EXCEL_TARGET') ? 'Target Excel' : 'Seluruh Master iseki_label';
            return response()->json([
                'success' => true,
                'message' => "Jadwal Stock Opname \"{$event->event_name}\" ({$modeText}) berhasil dibuat dan disalin sebanyak {$insertedCount} part snapshot!",
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
     * Helper: Salin snapshot SELURUH master dari iseki_label (tanpa excel/target area_no)
     * Mengelompokkan cell ganda dengan area letter yang sama menjadi 1 baris
     */
    private function processSnapshotAllFromLabel(SoEvent $event): int
    {
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
            $pairsByAreaLetter = [];

            for ($i = 0; $i < $maxPairs; $i++) {
                $currentLoc  = $rackArr[$i] ?? ($rackArr[0] ?? '-');
                $currentCell = $cellArr[$i] ?? ($cellArr[0] ?? '-');

                // Tentukan huruf abjad area (misal W3-5D -> W, E3-6D -> E)
                $combinedLocString = trim($currentLoc . ' ' . $currentCell);
                preg_match('/[A-Za-z]/', $combinedLocString, $matches);
                $detectedAreaLetter = !empty($matches[0]) ? strtoupper($matches[0]) : 'OTHER';

                $pairsByAreaLetter[$detectedAreaLetter][] = [
                    'location' => $currentLoc,
                    'cell'     => $currentCell,
                ];
            }

            // Simpan per kelompok area letter (jika W3-5D dan W3-6D berabjad sama W -> gabung jadi 1 baris)
            foreach ($pairsByAreaLetter as $areaLetter => $groupPairs) {
                $locDisplays = [];
                $locationsList = [];
                $cellsList = [];

                foreach ($groupPairs as $lp) {
                    $pairStr = $lp['location'] . '-' . $lp['cell'];
                    if (!in_array($pairStr, $locDisplays, true)) $locDisplays[] = $pairStr;
                    if (!in_array($lp['location'], $locationsList, true)) $locationsList[] = $lp['location'];
                    if (!in_array($lp['cell'], $cellsList, true)) $cellsList[] = $lp['cell'];
                }

                $mergedLocDisplay = implode(', ', $locDisplays);
                $mergedLocation   = implode(', ', $locationsList);
                $mergedCell       = implode(', ', $cellsList);

                $trackingCode = "{$item->item_code}|{$item->part_name}|{$item->rack_no}|{$mergedLocDisplay}";

                $partsToInsert[] = [
                    'so_event_id'      => $event->id,
                    'label_part_id'    => $item->id,
                    'rack_no'          => $item->rack_no,
                    'area_letter'      => $areaLetter,
                    'area_no'          => null, // Tanpa area_no (berbasis letter area saja)
                    'location'         => $mergedLocation ?: '-',
                    'cell'             => $mergedCell ?: '-',
                    'loc_cell_display' => $mergedLocDisplay ?: '-',
                    'item_code'        => $item->item_code,
                    'part_name'        => $item->part_name,
                    'supplier'         => $item->supplier ?? '-',
                    'satuan'           => $item->satuan ?? 'PCS',
                    'tracking_code'    => $trackingCode,
                    'pair_index'       => 1,
                    'total_pairs'      => count($locDisplays),
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }
        }

        // Insert batch ke so_event_parts
        foreach (array_chunk($partsToInsert, 500) as $chunk) {
            SoEventPart::insert($chunk);
        }

        return count($partsToInsert);
    }

    /**
     * Admin: Import / Ganti Excel Target Rak untuk Event SO yang sudah ada
     */
    public function importExcel(Request $request, $id)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $event = SoEvent::findOrFail($id);
        $excelFile = $request->file('excel_file');
        $rackTargetMap = $this->parseRackExcel($excelFile->getPathname());

        if (empty($rackTargetMap)) {
            return response()->json([
                'success' => false,
                'message' => 'File Excel tidak berisi data rak yang valid (rack_no, area_letter, area_no).',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Hapus snapshot part lama jika belum ada transaksi di so_items
            $hasItems = \App\Models\SoItem::where('so_event_id', $event->id)->exists();
            if ($hasItems) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jadwal ini sudah memiliki transaksi perhitungan part SO dan snapshot tidak dapat di-overwrite.',
                ], 422);
            }

            SoEventPart::where('so_event_id', $event->id)->delete();

            $insertedCount = $this->processSnapshotFromLabel($event, $rackTargetMap);

            $event->update([
                'total_parts_snapshot' => $insertedCount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Target rak berhasil diperbarui dan disalin sebanyak ' . $insertedCount . ' part snapshot!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimpor target rak: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Parse File Excel (rack_no, area_letter, area_no)
     * Mengembalikan associative array [ rack_no_upper => [ ['rack_no' => ..., 'area_letter' => ..., 'area_no' => ...], ... ] ]
     */
    private function parseRackExcel($filePath)
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (empty($rows)) {
            return [];
        }

        $header = array_map(function ($h) {
            return strtolower(trim((string)$h));
        }, $rows[0]);

        $rackCol = array_search('rack_no', $header);
        if ($rackCol === false) $rackCol = array_search('rack', $header);
        if ($rackCol === false) $rackCol = 0; // fallback column A

        $letterCol = array_search('area_letter', $header);
        if ($letterCol === false) $letterCol = array_search('letter', $header);
        if ($letterCol === false) $letterCol = 1; // fallback column B

        $areaNoCol = array_search('area_no', $header);
        if ($areaNoCol === false) $areaNoCol = array_search('no_area', $header);
        if ($areaNoCol === false) $areaNoCol = array_search('area', $header);
        if ($areaNoCol === false) $areaNoCol = 2; // fallback column C

        $rackTargetMap = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // skip header

            $rackNo = trim((string)($row[$rackCol] ?? ''));
            $areaLetter = strtoupper(trim((string)($row[$letterCol] ?? '')));
            $areaNo = trim((string)($row[$areaNoCol] ?? ''));

            if ($rackNo === '') continue;

            $key = strtoupper($rackNo);
            // Simpan unik per pasangan rack_no + area_letter + area_no
            $targetSignature = $key . '###' . $areaLetter . '###' . $areaNo;
            $rackTargetMap[$key][$targetSignature] = [
                'rack_no'     => $rackNo,
                'area_letter' => $areaLetter,
                'area_no'     => $areaNo,
            ];
        }

        // Ubah list target tiap rack menjadi indexed array unik
        foreach ($rackTargetMap as $k => $targets) {
            $rackTargetMap[$k] = array_values($targets);
        }

        return $rackTargetMap;
    }

    /**
     * Helper: Salin snapshot dari iseki_label.rack_part_lists sesuai rackTargetMap
     */
    private function processSnapshotFromLabel(SoEvent $event, array $rackTargetMap): int
    {
        $targetRacks = array_keys($rackTargetMap);
        $labelItems = DB::connection('label')->table('rack_part_lists')
            ->whereIn(DB::raw('UPPER(TRIM(rack_no))'), $targetRacks)
            ->get();

        $partsToInsert = [];
        $now = now();

        foreach ($labelItems as $item) {
            $rackKey = strtoupper(trim($item->rack_no ?? ''));
            $targets = $rackTargetMap[$rackKey] ?? [
                ['rack_no' => $item->rack_no, 'area_letter' => '', 'area_no' => '']
            ];

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
            $pairsByAreaLetter = [];

            for ($i = 0; $i < $maxPairs; $i++) {
                $currentLoc  = $rackArr[$i] ?? ($rackArr[0] ?? '-');
                $currentCell = $cellArr[$i] ?? ($cellArr[0] ?? '-');

                // Tentukan huruf abjad area (misal W3-5D -> W, E3-6D -> E)
                $combinedLocString = trim($currentLoc . ' ' . $currentCell);
                preg_match('/[A-Za-z]/', $combinedLocString, $matches);
                $detectedAreaLetter = !empty($matches[0]) ? strtoupper($matches[0]) : 'OTHER';

                $pairsByAreaLetter[$detectedAreaLetter][] = [
                    'location' => $currentLoc,
                    'cell'     => $currentCell,
                ];
            }

            // Kelompokkan per area target dari Excel
            foreach ($targets as $targetInfo) {
                $targetLetter = $targetInfo['area_letter'];
                $targetAreaNo = $targetInfo['area_no'];

                // Jika target letter ditentukan di Excel dan ada pasangan lokasi dengan letter tersebut
                if (!empty($targetLetter) && isset($pairsByAreaLetter[$targetLetter])) {
                    $groupPairs = $pairsByAreaLetter[$targetLetter];
                    $areaKey = $targetLetter;
                } else {
                    // Jika tidak cocok atau tidak ditentukan, proses seluruh area letters
                    $groupPairs = [];
                    foreach ($pairsByAreaLetter as $lKey => $lpList) {
                        $groupPairs = array_merge($groupPairs, $lpList);
                    }
                    $areaKey = !empty($targetLetter) ? $targetLetter : array_key_first($pairsByAreaLetter);
                }

                // Gabungkan lokasi & cell jika huruf area sama
                $locDisplays = [];
                $locationsList = [];
                $cellsList = [];

                foreach ($groupPairs as $lp) {
                    $pairStr = $lp['location'] . '-' . $lp['cell'];
                    if (!in_array($pairStr, $locDisplays, true)) $locDisplays[] = $pairStr;
                    if (!in_array($lp['location'], $locationsList, true)) $locationsList[] = $lp['location'];
                    if (!in_array($lp['cell'], $cellsList, true)) $cellsList[] = $lp['cell'];
                }

                $mergedLocDisplay = implode(', ', $locDisplays);
                $mergedLocation   = implode(', ', $locationsList);
                $mergedCell       = implode(', ', $cellsList);

                $trackingCode = "{$item->item_code}|{$item->part_name}|{$item->rack_no}|{$mergedLocDisplay}";

                $partsToInsert[] = [
                    'so_event_id'      => $event->id,
                    'label_part_id'    => $item->id,
                    'rack_no'          => $item->rack_no,
                    'area_letter'      => $areaKey ?: null,
                    'area_no'          => $targetAreaNo ?: null,
                    'location'         => $mergedLocation ?: '-',
                    'cell'             => $mergedCell ?: '-',
                    'loc_cell_display' => $mergedLocDisplay ?: '-',
                    'item_code'        => $item->item_code,
                    'part_name'        => $item->part_name,
                    'supplier'         => $item->supplier ?? '-',
                    'satuan'           => $item->satuan ?? 'PCS',
                    'tracking_code'    => $trackingCode,
                    'pair_index'       => 1,
                    'total_pairs'      => count($locDisplays),
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }
        }

        // Insert batch ke so_event_parts
        foreach (array_chunk($partsToInsert, 500) as $chunk) {
            SoEventPart::insert($chunk);
        }

        return count($partsToInsert);
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

        // Hitung statistik per area_no (Card Grid Area)
        $partMonitoringController = app(\App\Http\Controllers\PartMonitoringController::class);
        $areaCards = $partMonitoringController->calculateAreaStatistics($event->id);

        return view('admin.so_events.parts', compact(
            'event',
            'events',
            'totalStockopname',
            'totalReadyToCount',
            'totalCounted',
            'totalAudited',
            'areaCards'
        ));
    }
}

