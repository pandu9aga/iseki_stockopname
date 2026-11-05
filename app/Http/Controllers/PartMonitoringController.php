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
     * Tampilan 1: List Event / Jadwal Stock Opname untuk Member
     */
    public function index(Request $request)
    {
        $member = Auth::guard('member')->user();
        if (!$member) {
            return redirect()->route('page.login');
        }

        $events = SoEvent::withCount(['parts', 'soItems'])
            ->orderBy('id', 'desc')
            ->get();

        $activeEvent = SoEvent::getActiveEvent();

        return view('member.part_events', compact('member', 'events', 'activeEvent'));
    }

    /**
     * Tampilan 2: Detail List Part dari Event SO Tertentu
     */
    public function showEvent(Request $request, $id)
    {
        $member = Auth::guard('member')->user();
        if (!$member) {
            return redirect()->route('page.login');
        }

        $event = SoEvent::withCount(['parts', 'soItems'])->findOrFail($id);
        $events = SoEvent::orderBy('id', 'desc')->get();

        // Summary statistics untuk event ini
        $totalStockopname = SoItem::where('so_event_id', $event->id)->count();
        $totalReadyToCount = SoItem::where('so_event_id', $event->id)->where('current_step', 'READY_TO_COUNT')->count();
        $totalCounted = SoItem::where('so_event_id', $event->id)->whereIn('current_step', ['READY_TO_RETURN', 'RETURNED'])->count();
        $totalAudited = AuditorRecord::where('so_event_id', $event->id)->count();

        return view('member.part_list', compact(
            'member',
            'event',
            'events',
            'totalStockopname',
            'totalReadyToCount',
            'totalCounted',
            'totalAudited'
        ));
    }

    /**
     * DataTables AJAX Endpoint untuk List Part & Riwayat Tahap SO per Event
     */
    public function data(Request $request, $id = null)
    {
        $eventId = $id ?: $request->input('event_id');
        if (!$eventId) {
            $active = SoEvent::getActiveEvent();
            $eventId = $active ? $active->id : null;
        }

        // Ambil data snapshot parts dari tabel so_event_parts berdasarkan eventId
        $partsQuery = SoEventPart::query();
        if ($eventId) {
            $partsQuery->where('so_event_id', $eventId);
        } else {
            $firstEv = SoEvent::orderBy('id', 'desc')->first();
            if ($firstEv) {
                $partsQuery->where('so_event_id', $firstEv->id);
                $eventId = $firstEv->id;
            }
        }
        $snapshotParts = $partsQuery->orderBy('rack_no', 'asc')->get();

        // Ambil SoItem sesuai eventId beserta relasi histories
        $soItemQuery = SoItem::with(['histories' => function ($q) {
            $q->orderBy('id', 'asc');
        }]);
        if ($eventId) {
            $soItemQuery->where('so_event_id', $eventId);
        }
        $soItems = $soItemQuery->get();

        // Index soItems
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

        // Ambil auditor records sesuai eventId
        $auditorQuery = AuditorRecord::query();
        if ($eventId) {
            $auditorQuery->where('so_event_id', $eventId);
        }
        $auditorRecords = $auditorQuery->get();
        $auditorMap = [];
        foreach ($auditorRecords as $ar) {
            $key = strtoupper(trim($ar->rack_no)) . '###' . strtoupper(trim($ar->code_part ?? ''));
            $auditorMap[$key][] = $ar;
            $auditorMap[strtoupper(trim($ar->rack_no))][] = $ar;
        }

        $flattenedRows = [];

        foreach ($snapshotParts as $item) {
            $loc = $item->location ?: '-';
            $cell = $item->cell ?: '-';
            $locDisplay = $item->loc_cell_display ?: ($loc . '-' . $cell);

            $trackingCode = $item->tracking_code ?: "{$item->item_code}|{$item->part_name}|{$item->rack_no}|{$locDisplay}";
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

            $maxPairs = $item->total_pairs ?: 1;
            $pairIndex = $item->pair_index ?: 1;

            $flattenedRows[] = [
                'id'               => $item->id,
                'rack_no'          => $item->rack_no,
                'item_code'        => $item->item_code,
                'part_name'        => $item->part_name,
                'location'         => $loc,
                'cell'             => $cell,
                'loc_display'      => $locDisplay,
                'pair_info'        => $maxPairs > 1 ? ('Cell ' . $pairIndex . ' dari ' . $maxPairs) : null,
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

        return DataTables::of(collect($flattenedRows))
            ->addIndexColumn()
            ->rawColumns(['step_summary', 'current_step', 'loc_display', 'part_name'])
            ->make(true);
    }
}
