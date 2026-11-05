<?php

namespace App\Http\Controllers;

use App\Models\AuditorRecord;
use App\Models\SoCountHistory;
use App\Models\SoEvent;
use App\Models\SoEventPart;
use App\Models\SoItem;
use App\Models\SoScaleSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PartMonitoringController extends Controller
{
    /**
     * Tampilan Halaman Monitoring Seluruh Part dari iseki_label
     */
    public function index(Request $request)
    {
        $member = Auth::guard('member')->user();
        if (!$member) {
            return redirect()->route('page.login');
        }

        // Summary statistics
        $totalStockopname = SoItem::count();
        $totalReadyToCount = SoItem::where('current_step', 'READY_TO_COUNT')->count();
        $totalCounted = SoItem::whereIn('current_step', ['READY_TO_RETURN', 'RETURNED'])->count();
        $totalAudited = AuditorRecord::count();

        return view('member.part_list', compact('member', 'totalStockopname', 'totalReadyToCount', 'totalCounted', 'totalAudited'));
    }

    /**
     * DataTables AJAX Endpoint untuk List Part & Riwayat Tahap SO
     */
    public function data(Request $request)
    {
        // Ambil semua data dari iseki_label.rack_part_lists
        $labelItems = DB::connection('label')->table('rack_part_lists')
            ->select('id', 'rack_no', 'rack', 'cell', 'item_code', 'part_name', 'supplier', 'satuan')
            ->whereNotNull('rack_no')
            ->where('rack_no', '!=', '')
            ->orderBy('rack_no', 'asc')
            ->get();

        // Ambil semua SoItem yang ada beserta relasi histories
        $soItems = SoItem::with(['histories' => function ($q) {
            $q->orderBy('id', 'asc');
        }])->get();

        // Index soItems by:
        // 1. tracking_code: part|name|rak|loc
        // 2. combination: code_part + '###' + rack_no + '###' + loc_cell
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

        // Ambil semua auditor records
        $auditorRecords = AuditorRecord::all();
        $auditorMap = [];
        foreach ($auditorRecords as $ar) {
            $key = strtoupper(trim($ar->rack_no)) . '###' . strtoupper(trim($ar->code_part ?? ''));
            $auditorMap[$key][] = $ar;
            $auditorMap[strtoupper(trim($ar->rack_no))][] = $ar;
        }

        $flattenedRows = [];

        foreach ($labelItems as $item) {
            $rackVal = trim($item->rack ?? '');
            $cellVal = trim($item->cell ?? '');

            // Decode / parse rack (location)
            $rackArr = [];
            if (str_starts_with($rackVal, '[') && str_ends_with($rackVal, ']')) {
                $decoded = json_decode($rackVal, true);
                if (is_array($decoded)) {
                    $rackArr = array_values(array_filter(array_map('trim', $decoded)));
                }
            } elseif (!empty($rackVal)) {
                $rackArr = array_values(array_filter(array_map('trim', explode(',', $rackVal))));
            }

            // Decode / parse cell
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
                $comboKey = strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no)) . '###' . strtoupper(trim($locDisplay));

                // Cari apakah sudah pernah diproses SO
                $soItem = $soMapByTracking[$trackingCode] ?? ($soMapByCombo[$comboKey] ?? null);

                // Cari riwayat audit
                $auditKey = strtoupper(trim($item->rack_no)) . '###' . strtoupper(trim($item->item_code));
                $audits = $auditorMap[$auditKey] ?? ($auditorMap[strtoupper(trim($item->rack_no))] ?? []);

                // Susun ringkasan riwayat perhitungan tiap tahap
                $stepSummary = [];
                $stepLabel = 'BELUM_SO';
                $badgeClass = 'bg-secondary';

                // Status indikator per-tahapan (boolean)
                $hasCourier = false;
                $hasCount = false;
                $hasReturn = false;
                $hasAudit = false;

                if ($soItem) {
                    $stepLabel = $soItem->current_step;
                    
                    // 1. Kurir
                    if ($soItem->courier_nik || in_array($soItem->current_step, ['COURIER_PICKED', 'READY_TO_COUNT', 'POS1_DONE', 'MISMATCH_NG', 'COUNTED', 'READY_TO_RETURN', 'RETURNED'])) {
                        $hasCourier = true;
                        if ($soItem->courier_nik) {
                            $stepSummary[] = '<strong>Kurir:</strong> Diambil NIK ' . $soItem->courier_nik . ' (' . ($soItem->courier_at ? $soItem->courier_at->format('d/m H:i') : '-') . ')';
                        }
                    }

                    // 2. Perhitungan Pos 1 / Pos 2 / Timbangan
                    if (in_array($soItem->current_step, ['READY_TO_RETURN', 'RETURNED', 'COUNTED']) || $soItem->final_count !== null) {
                        $hasCount = true;
                    }

                    if ($soItem->process_type === 'SCALE') {
                        $finalCountText = $soItem->final_count !== null ? $soItem->final_count . ' pcs' : 'Proses';
                        $scaleNo = $soItem->scale_number ?: '-';
                        $stepSummary[] = '<strong>Timbangan (' . $scaleNo . '):</strong> ' . $finalCountText;
                    } elseif ($soItem->process_type === 'MANUAL' || $soItem->pos1_count !== null || $soItem->pos2_count !== null) {
                        $p1 = $soItem->pos1_count !== null ? $soItem->pos1_count . ' pcs' : '-';
                        $p2 = $soItem->pos2_count !== null ? $soItem->pos2_count . ' pcs' : '-';
                        $stepSummary[] = '<strong>Manual:</strong> Pos 1 = ' . $p1 . ' | Pos 2 = ' . $p2;
                    }

                    // 3. Pengembalian (Return)
                    if ($soItem->current_step === 'RETURNED' || $soItem->returned_at) {
                        $hasReturn = true;
                        $stepSummary[] = '<strong>Return:</strong> Selesai NIK ' . ($soItem->return_nik ?? '-') . ' (' . ($soItem->returned_at ? $soItem->returned_at->format('d/m H:i') : '-') . ')';
                    }

                    // Badge color mapping
                    switch ($soItem->current_step) {
                        case 'READY_TO_COUNT':
                            $badgeClass = 'bg-warning text-dark';
                            break;
                        case 'POS1_DONE':
                            $badgeClass = 'bg-info text-dark';
                            break;
                        case 'MISMATCH_NG':
                            $badgeClass = 'bg-danger';
                            break;
                        case 'READY_TO_RETURN':
                            $badgeClass = 'bg-primary';
                            break;
                        case 'RETURNED':
                            $badgeClass = 'bg-success';
                            break;
                        default:
                            $badgeClass = 'bg-secondary';
                    }
                }

                // 4. Auditor jika ada
                if (!empty($audits)) {
                    $hasAudit = true;
                    $latestAudit = end($audits);
                    $stepSummary[] = '<strong>Audit:</strong> ' . $latestAudit->audited_count . ' pcs (' . $latestAudit->auditor_name . ')';
                }

                $flattenedRows[] = [
                    'id'               => $item->id . '_' . $i,
                    'rack_no'          => $item->rack_no,
                    'item_code'        => $item->item_code,
                    'part_name'        => $item->part_name,
                    'location'         => $loc,
                    'cell'             => $cell,
                    'loc_display'      => $locDisplay,
                    'pair_info'        => $maxPairs > 1 ? ('Cell ' . ($i + 1) . ' dari ' . $maxPairs) : null,
                    'has_double_cell'  => $maxPairs > 1,
                    'current_step'     => $stepLabel,
                    'badge_class'      => $badgeClass,
                    'has_courier'      => $hasCourier,
                    'has_count'        => $hasCount,
                    'has_return'       => $hasReturn,
                    'has_audit'        => $hasAudit,
                    'final_count'      => $soItem ? ($soItem->final_count !== null ? number_format($soItem->final_count) . ' pcs' : '-') : '-',
                    'process_type'     => $soItem ? ($soItem->process_type ?? '-') : '-',
                    'step_summary'     => !empty($stepSummary) ? implode('<br>', $stepSummary) : '<span class="text-muted">Belum ada aktivitas</span>',
                    'so_item_id'       => $soItem ? $soItem->id : null,
                ];
            }
        }

        return DataTables::of(collect($flattenedRows))
            ->addIndexColumn()
            ->rawColumns(['step_summary', 'current_step', 'loc_display', 'part_name'])
            ->make(true);
    }
}
