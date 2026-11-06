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
        
        // Total part/rak-loc unik yang diaudit (bukan jumlah baris riwayat audit)
        $totalAudited = AuditorRecord::where('so_event_id', $event->id)
            ->selectRaw('COUNT(DISTINCT CONCAT(COALESCE(rack_no, ""), "###", COALESCE(location, ""), "###", COALESCE(cell, ""), "###", COALESCE(code_part, ""))) as total')
            ->value('total') ?? 0;

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

        // Ambil SoItem sesuai eventId beserta relasi histories, scaleRounds, dan scale
        $soItemQuery = SoItem::with([
            'histories' => function ($q) {
                $q->orderBy('id', 'asc');
            },
            'scaleRounds' => function ($q) {
                $q->orderBy('round_no', 'asc');
            },
            'scale'
        ]);
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
            // Indexing berbasis tracking code
            if ($ar->tracking_code) {
                $auditorMap['TRACKING###' . strtoupper(trim($ar->tracking_code))][] = $ar;
            }
            // Indexing berbasis cell
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

            // Cari apakah sudah pernah diproses SO
            $soItem = $soMapByTracking[$trackingCode] ?? ($soMapByCombo[$comboKey] ?? null);

            // Cari riwayat audit secara spesifik per cell / tracking code
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
                    // Fallback hanya jika bukan multi-cell
                    $audits = $auditorMap['COMBO###' . strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no))];
                }
            }

            // Susun ringkasan riwayat perhitungan tiap tahap secara LENGKAP (Full History)
            $stepSummary = [];
            $stepLabel = 'BELUM_SO';
            $badgeClass = 'bg-secondary';
            $allMethodsUsed = [];

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
                        $courierTime = $soItem->courier_at ? $soItem->courier_at->format('d/m H:i') : '-';
                        $stepSummary[] = '<span class="badge bg-secondary me-1">Kurir</span> NIK ' . $soItem->courier_nik . ' (' . $courierTime . ')';
                    }
                }

                // 2. Perhitungan Pos 1 / Pos 2 / Timbangan (Full History dari histories table)
                if (in_array($soItem->current_step, ['READY_TO_RETURN', 'RETURNED', 'COUNTED']) || $soItem->final_count !== null || $soItem->histories->isNotEmpty()) {
                    $hasCount = true;
                }

                if ($soItem->histories->isNotEmpty()) {
                    foreach ($soItem->histories as $idx => $h) {
                        $timeStr = $h->created_at ? $h->created_at->format('d/m H:i') : '';
                        $nikStr = $h->member_nik ?: '-';

                        if ($h->count_type === 'POS1') {
                            $allMethodsUsed['MANUAL'] = true;
                            $res = $h->result_count !== null ? $h->result_count . ' pcs' : '-';
                            $statusBadge = $h->status === 'OK' ? '<span class="badge bg-success-subtle text-success border border-success" style="font-size:0.7rem;">OK</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.7rem;">' . ($h->status ?: 'NG') . '</span>';
                            $stepSummary[] = '<span class="badge bg-info text-dark me-1">Pos 1</span> ' . $res . ' ' . $statusBadge . ' <small class="text-muted">(NIK: ' . $nikStr . ($timeStr ? ' &bull; ' . $timeStr : '') . ')</small>';
                        } elseif ($h->count_type === 'POS2') {
                            $allMethodsUsed['MANUAL'] = true;
                            $res = $h->result_count !== null ? $h->result_count . ' pcs' : '-';
                            $statusBadge = $h->status === 'OK' ? '<span class="badge bg-success-subtle text-success border border-success" style="font-size:0.7rem;">OK</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.7rem;">' . ($h->status ?: 'NG') . '</span>';
                            $stepSummary[] = '<span class="badge bg-primary text-white me-1">Pos 2</span> ' . $res . ' ' . $statusBadge . ' <small class="text-muted">(NIK: ' . $nikStr . ($timeStr ? ' &bull; ' . $timeStr : '') . ')</small>';
                        } elseif ($h->count_type === 'SCALE') {
                            $allMethodsUsed['SCALE'] = true;
                            $scaleName = $soItem->scale_number ?: ($h->scale ? $h->scale->scale_number : 'Scale');
                            $res = $h->result_count !== null ? $h->result_count . ' pcs' : ($h->weight_with_sample !== null ? (int)$h->weight_with_sample . ' pcs' : '-');
                            $moeStr = $h->error_margin_percent !== null ? 'MoE: ' . $h->error_margin_percent . '%' : '';
                            $statusBadge = $h->status === 'OK' ? '<span class="badge bg-success-subtle text-success border border-success" style="font-size:0.7rem;">OK</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.7rem;">' . ($h->status ?: 'NG') . '</span>';
                            $stepSummary[] = '<span class="badge bg-primary-subtle text-primary border border-primary me-1">Timbangan (' . $scaleName . ')</span> ' . $res . ' ' . $statusBadge . ($moeStr ? ' <small class="text-muted">[' . $moeStr . ']</small>' : '') . ' <small class="text-muted">(NIK: ' . $nikStr . ($timeStr ? ' &bull; ' . $timeStr : '') . ')</small>';
                        }
                    }
                } else {
                    // Fallback jika histories kosong tapi di soItem ada data
                    if ($soItem->process_type === 'SCALE') {
                        $allMethodsUsed['SCALE'] = true;
                        $finalCountText = $soItem->final_count !== null ? $soItem->final_count . ' pcs' : 'Proses';
                        $scaleNo = $soItem->scale_number ?: '-';
                        $stepSummary[] = '<span class="badge bg-primary-subtle text-primary border border-primary me-1">Timbangan (' . $scaleNo . ')</span> ' . $finalCountText;
                    } elseif ($soItem->process_type === 'MANUAL' || $soItem->pos1_count !== null || $soItem->pos2_count !== null) {
                        $allMethodsUsed['MANUAL'] = true;
                        $p1 = $soItem->pos1_count !== null ? $soItem->pos1_count . ' pcs' : '-';
                        $p2 = $soItem->pos2_count !== null ? $soItem->pos2_count . ' pcs' : '-';
                        $stepSummary[] = '<span class="badge bg-secondary me-1">Manual</span> Pos 1 = ' . $p1 . ' | Pos 2 = ' . $p2;
                    }
                }

                // 3. Pengembalian (Return)
                if ($soItem->current_step === 'RETURNED' || $soItem->returned_at) {
                    $hasReturn = true;
                    $retTime = $soItem->returned_at ? $soItem->returned_at->format('d/m H:i') : '-';
                    $stepSummary[] = '<span class="badge bg-success me-1">Return</span> Selesai NIK ' . ($soItem->return_nik ?? '-') . ' (' . $retTime . ')';
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

            // 4. Auditor jika ada (Full History seluruh ronde audit)
            if (!empty($audits)) {
                $hasAudit = true;
                foreach ($audits as $idx => $a) {
                    if ($a->method === 'SCALE') {
                        $allMethodsUsed['SCALE'] = true;
                    } elseif ($a->method === 'MANUAL') {
                        $allMethodsUsed['MANUAL'] = true;
                    }

                    $aMethod = $a->method === 'SCALE' ? 'Timbangan' . ($a->scale_number ? ' (' . $a->scale_number . ')' : '') : 'Manual';
                    $aTime = $a->created_at ? $a->created_at->format('d/m H:i') : '';
                    $aStatusBadge = '';
                    if ($a->audit_status === 'OK') {
                        $aStatusBadge = '<span class="badge bg-success-subtle text-success border border-success" style="font-size:0.7rem;">OK</span>';
                    } elseif ($a->audit_status === 'NG') {
                        $aStatusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.7rem;">NG</span>';
                    }
                    $roundLabel = count($audits) > 1 ? 'Audit #' . ($idx + 1) : 'Audit';
                    $stepSummary[] = '<span class="badge bg-dark text-warning border border-warning me-1">' . $roundLabel . '</span> ' . number_format($a->audited_count) . ' pcs ' . $aStatusBadge . ' <small class="text-muted">(' . $aMethod . ' &bull; ' . $a->auditor_name . ($aTime ? ' &bull; ' . $aTime : '') . ')</small>';
                }
            }

            // Tentukan Metode SO gabungan jika pernah lewat manual & timbangan
            if ($soItem && $soItem->process_type) {
                $allMethodsUsed[$soItem->process_type] = true;
            }
            if ($soItem && ($soItem->pos1_count !== null || $soItem->pos2_count !== null)) {
                $allMethodsUsed['MANUAL'] = true;
            }
            if ($soItem && ($soItem->scale_number || $soItem->scale_id)) {
                $allMethodsUsed['SCALE'] = true;
            }

            $methodList = array_keys($allMethodsUsed);
            $processTypeDisplay = '-';
            if (in_array('MANUAL', $methodList) && in_array('SCALE', $methodList)) {
                $processTypeDisplay = 'MANUAL & SCALE';
            } elseif (in_array('SCALE', $methodList)) {
                $processTypeDisplay = 'SCALE';
            } elseif (in_array('MANUAL', $methodList)) {
                $processTypeDisplay = 'MANUAL';
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
                'process_type'     => $processTypeDisplay,
                'step_summary'     => !empty($stepSummary) ? implode('<br>', $stepSummary) : '<span class="text-muted">Belum ada aktivitas</span>',
                'so_item_id'       => $soItem ? $soItem->id : null,
            ];
        }

        return DataTables::of(collect($flattenedRows))
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-cell" 
                            data-id="' . $row['id'] . '" 
                            data-rack="' . e($row['rack_no']) . '" 
                            data-code="' . e($row['item_code']) . '" 
                            data-name="' . e($row['part_name']) . '" 
                            data-location="' . e($row['location'] === '-' ? '' : $row['location']) . '" 
                            data-cell="' . e($row['cell'] === '-' ? '' : $row['cell']) . '" 
                            title="Edit Cell & Lokasi">
                            <i class="fas fa-edit me-1"></i> Edit Cell
                        </button>';
            })
            ->rawColumns(['step_summary', 'current_step', 'loc_display', 'part_name', 'action'])
            ->make(true);
    }

    /**
     * Update Lokasi & Cell Part Snapshot di SO dan Master iseki_label
     */
    public function updatePartLocation(Request $request, $id)
    {
        $request->validate([
            'location' => 'required|string|max:50',
            'cell'     => 'required|string|max:50',
        ]);

        $part = SoEventPart::findOrFail($id);

        $newLoc = strtoupper(trim($request->location));
        $newCell = strtoupper(trim($request->cell));
        $newLocDisplay = $newLoc . '-' . $newCell;
        $newTrackingCode = "{$part->item_code}|{$part->part_name}|{$part->rack_no}|{$newLocDisplay}";

        DB::beginTransaction();
        try {
            // 1. Update pada snapshot SO (so_event_parts)
            $part->location = $newLoc;
            $part->cell = $newCell;
            $part->loc_cell_display = $newLocDisplay;
            $part->tracking_code = $newTrackingCode;
            $part->save();

            // 2. Update pada master iseki_label.rack_part_lists
            // Bentuk format JSON array seperti ["M85"] dan ["D"]
            $labelRackJson = json_encode([$newLoc]);
            $labelCellJson = json_encode([$newCell]);

            if ($part->label_part_id) {
                DB::connection('label')->table('rack_part_lists')
                    ->where('id', $part->label_part_id)
                    ->update([
                        'rack'       => $labelRackJson,
                        'cell'       => $labelCellJson,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::connection('label')->table('rack_part_lists')
                    ->where('rack_no', $part->rack_no)
                    ->where('item_code', $part->item_code)
                    ->update([
                        'rack'       => $labelRackJson,
                        'cell'       => $labelCellJson,
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Lokasi & Cell part {$part->item_code} berhasil diperbarui di snapshot SO dan master iseki_label ({$newLocDisplay})!",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui cell: ' . $e->getMessage(),
            ], 500);
        }
    }
}
