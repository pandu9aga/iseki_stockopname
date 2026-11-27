<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\SoEvent;
use App\Models\SoEventPart;
use App\Models\SoItem;
use App\Models\SoCountHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SoTimerController extends Controller
{
    /**
     * Tampilan 1: List Event Stock Opname untuk Modul Timer
     */
    public function index()
    {
        $events = SoEvent::withCount(['parts', 'soItems'])
            ->orderBy('id', 'desc')
            ->get();

        $activeEvent = SoEvent::getActiveEvent();

        return view('admin.timers.index', compact('events', 'activeEvent'));
    }

    /**
     * Tampilan 2: Detail Analisis Timer per Event SO
     */
    public function showEventTimer(Request $request, $id)
    {
        $event = SoEvent::withCount(['parts', 'soItems'])->findOrFail($id);
        $events = SoEvent::orderBy('id', 'desc')->get();

        // Ambil rekap KPI Durasi Singkat (Cache 15 detik agar ringan)
        $summaryStats = Cache::remember("so_timer_summary_{$event->id}", 15, function () use ($event) {
            return $this->calculateEventTimerSummary($event->id);
        });

        // Ambil data Card Area (Cache 15 detik)
        $partMonitoringController = app(\App\Http\Controllers\PartMonitoringController::class);
        $areaCards = Cache::remember("so_area_cards_{$event->id}", 15, function () use ($partMonitoringController, $event) {
            return $partMonitoringController->calculateAreaStatistics($event->id);
        });

        return view('admin.timers.detail', compact('event', 'events', 'summaryStats', 'areaCards'));
    }

    /**
     * Server-side DataTables AJAX Endpoint untuk Tabel Timer Part
     */
    public function timerData(Request $request, $id)
    {
        $eventId = $id;

        // Ambil data snapshot parts
        $partsQuery = SoEventPart::where('so_event_id', $eventId);
        $areaFilter = $request->input('area_no');
        if ($areaFilter !== null && $areaFilter !== '') {
            $filterClean = strtoupper(trim($areaFilter));
            if ($filterClean !== 'TAMBAHAN') {
                $partsQuery->where(function ($q) use ($filterClean) {
                    $q->where('area_no', $filterClean)
                      ->orWhere('area_letter', $filterClean)
                      ->orWhere('location', 'LIKE', $filterClean . '%')
                      ->orWhere('cell', 'LIKE', $filterClean . '%')
                      ->orWhere('loc_cell_display', 'LIKE', $filterClean . '%');
                });
            }
        }
        $snapshotParts = $partsQuery->orderBy('rack_no', 'asc')->get();

        // Ambil seluruh SoItem pada event ini dengan histories-nya
        $soItems = SoItem::where('so_event_id', $eventId)
            ->with(['histories' => function ($q) {
                $q->select('id', 'so_item_id', 'count_type', 'member_nik', 'created_at', 'status', 'result_count')
                  ->orderBy('created_at', 'asc');
            }])
            ->get();

        // Ambil mapping nama karyawan dari DB rifa
        $allNiks = [];
        foreach ($soItems as $so) {
            if ($so->courier_nik) $allNiks[] = $so->courier_nik;
            if ($so->return_nik) $allNiks[] = $so->return_nik;
            foreach ($so->histories as $h) {
                if ($h->member_nik) $allNiks[] = $h->member_nik;
            }
        }
        $allNiks = array_values(array_unique(array_filter($allNiks)));
        $employeeNames = [];
        if (!empty($allNiks)) {
            try {
                $employeeNames = DB::connection('rifa')->table('employees')
                    ->whereIn('nik', $allNiks)
                    ->pluck('nama', 'nik')
                    ->toArray();
            } catch (\Exception $e) {
                $employeeNames = [];
            }
        }
        $getEmpName = function ($nik) use ($employeeNames) {
            if (!$nik || $nik === '-') return '-';
            return $employeeNames[$nik] ?? $nik;
        };

        // =========================================================================
        // ALGORITMA SUPER CEPAT (O(N)): Perhitungan Delta Time per User Sebelumnya
        // =========================================================================

        // 1. Hitung Delta Time Kurir: urutkan semua soItem yang punya courier_at berdasarkan courier_nik, courier_at asc
        $courierSorted = $soItems->filter(fn($i) => !empty($i->courier_nik) && !empty($i->courier_at))
            ->sortBy([
                ['courier_nik', 'asc'],
                ['courier_at', 'asc'],
            ]);

        $courierDeltaMap = []; // [so_item_id => ['delta_seconds' => int, 'prev_time' => Carbon, 'curr_time' => Carbon]]
        $lastCourierTime = []; // [nik => Carbon]

        foreach ($courierSorted as $cItem) {
            $nik = $cItem->courier_nik;
            $currTime = $cItem->courier_at;

            if (isset($lastCourierTime[$nik])) {
                $prevTime = $lastCourierTime[$nik];
                $diffSec = abs($currTime->diffInSeconds($prevTime));
                $courierDeltaMap[$cItem->id] = [
                    'delta_seconds' => $diffSec,
                    'prev_time'     => $prevTime,
                    'curr_time'     => $currTime,
                    'is_first'      => false,
                ];
            } else {
                $courierDeltaMap[$cItem->id] = [
                    'delta_seconds' => null,
                    'prev_time'     => null,
                    'curr_time'     => $currTime,
                    'is_first'      => true,
                ];
            }
            $lastCourierTime[$nik] = $currTime;
        }

        // 2. Hitung Delta Time Return: urutkan semua soItem yang punya returned_at berdasarkan return_nik, returned_at asc
        $returnSorted = $soItems->filter(fn($i) => !empty($i->return_nik) && !empty($i->returned_at))
            ->sortBy([
                ['return_nik', 'asc'],
                ['returned_at', 'asc'],
            ]);

        $returnDeltaMap = [];
        $lastReturnTime = [];

        foreach ($returnSorted as $rItem) {
            $nik = $rItem->return_nik;
            $currTime = $rItem->returned_at;

            if (isset($lastReturnTime[$nik])) {
                $prevTime = $lastReturnTime[$nik];
                $diffSec = abs($currTime->diffInSeconds($prevTime));
                $returnDeltaMap[$rItem->id] = [
                    'delta_seconds' => $diffSec,
                    'prev_time'     => $prevTime,
                    'curr_time'     => $currTime,
                    'is_first'      => false,
                ];
            } else {
                $returnDeltaMap[$rItem->id] = [
                    'delta_seconds' => null,
                    'prev_time'     => null,
                    'curr_time'     => $currTime,
                    'is_first'      => true,
                ];
            }
            $lastReturnTime[$nik] = $currTime;
        }

        // 3. Hitung Delta Time Penghitungan (Pos 1 / Pos 2 / Timbangan):
        // Kumpulkan semua riwayat per-hitung, urutkan per member_nik, created_at asc
        $allHistories = [];
        foreach ($soItems as $so) {
            foreach ($so->histories as $h) {
                if ($h->member_nik && $h->created_at) {
                    $allHistories[] = [
                        'history_id' => $h->id,
                        'so_item_id' => $so->id,
                        'member_nik' => $h->member_nik,
                        'count_type' => $h->count_type,
                        'time'       => $h->created_at,
                    ];
                }
            }
        }
        $historiesSorted = collect($allHistories)->sortBy([
            ['member_nik', 'asc'],
            ['time', 'asc'],
        ]);

        $countDeltaMap = []; // [so_item_id => array of delta histories]
        $lastCountTime = []; // [member_nik => Carbon]

        foreach ($historiesSorted as $hData) {
            $nik = $hData['member_nik'];
            $currTime = $hData['time'];
            $soId = $hData['so_item_id'];

            if (isset($lastCountTime[$nik])) {
                $prevTime = $lastCountTime[$nik];
                $diffSec = abs($currTime->diffInSeconds($prevTime));
                $countDeltaMap[$soId][] = [
                    'delta_seconds' => $diffSec,
                    'count_type'    => $hData['count_type'],
                    'member_nik'    => $nik,
                    'prev_time'     => $prevTime,
                    'curr_time'     => $currTime,
                    'is_first'      => false,
                ];
            } else {
                $countDeltaMap[$soId][] = [
                    'delta_seconds' => null,
                    'count_type'    => $hData['count_type'],
                    'member_nik'    => $nik,
                    'prev_time'     => null,
                    'curr_time'     => $currTime,
                    'is_first'      => true,
                ];
            }
            $lastCountTime[$nik] = $currTime;
        }

        // Index SoItems by tracking code and combo key
        $soMapByTracking = [];
        $soMapByCombo = [];
        $soMapByPartRack = [];
        foreach ($soItems as $so) {
            if ($so->tracking_code) {
                $soMapByTracking[$so->tracking_code] = $so;
            }
            $loc = Area::formatLocDisplay($so->area ?? null, $so->cell ?? null);
            $key = strtoupper(trim($so->code_part)) . '###' . strtoupper(trim($so->rack_no)) . '###' . strtoupper(trim($loc));
            $soMapByCombo[$key] = $so;
            if ($so->cell) {
                $cellKey = strtoupper(trim($so->code_part)) . '###' . strtoupper(trim($so->rack_no)) . '###' . strtoupper(trim($so->cell));
                $soMapByCombo[$cellKey] = $so;
            }
            $partRackKey = strtoupper(trim($so->code_part)) . '###' . strtoupper(trim($so->rack_no));
            $soMapByPartRack[$partRackKey][] = $so;
        }

        // Kelompokkan Snapshot Part jika lokasi sama
        $groupedSnapshot = [];
        foreach ($snapshotParts as $item) {
            $loc = $item->location ?: '-';
            $cell = $item->cell ?: '-';
            $locDisplay = $item->loc_cell_display ?: Area::formatLocDisplay($loc, $cell);
            $detectedAreaLetter = !empty($item->area_letter) ? $item->area_letter : Area::detectAreaLetter($loc, $cell);
            $groupKey = strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no)) . '###' . $detectedAreaLetter;

            if (!isset($groupedSnapshot[$groupKey])) {
                $groupedSnapshot[$groupKey] = [
                    'primary_item' => $item,
                    'items'        => [$item],
                    'locations'    => [$loc],
                    'cells'        => [$cell],
                    'loc_displays' => [$locDisplay],
                    'area_letter'  => $detectedAreaLetter,
                ];
            } else {
                $groupedSnapshot[$groupKey]['items'][] = $item;
                if (!in_array($loc, $groupedSnapshot[$groupKey]['locations'], true)) {
                    $groupedSnapshot[$groupKey]['locations'][] = $loc;
                }
                if (!in_array($cell, $groupedSnapshot[$groupKey]['cells'], true)) {
                    $groupedSnapshot[$groupKey]['cells'][] = $cell;
                }
                if (!in_array($locDisplay, $groupedSnapshot[$groupKey]['loc_displays'], true)) {
                    $groupedSnapshot[$groupKey]['loc_displays'][] = $locDisplay;
                }
            }
        }

        $rows = [];
        foreach ($groupedSnapshot as $group) {
            $item = $group['primary_item'];
            $loc = implode(', ', $group['locations']);
            $cell = implode(', ', $group['cells']);
            $locDisplay = implode(', ', $group['loc_displays']);

            $trackingCode = $item->tracking_code ?: "{$item->item_code}|{$item->part_name}|{$item->rack_no}|{$locDisplay}";
            $comboKey = strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no)) . '###' . strtoupper(trim($locDisplay));

            $soItem = $soMapByTracking[$trackingCode] ?? ($soMapByCombo[$comboKey] ?? null);
            if (!$soItem) {
                $partRackKey = strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no));
                if (isset($soMapByPartRack[$partRackKey]) && count($soMapByPartRack[$partRackKey]) === 1) {
                    $soItem = $soMapByPartRack[$partRackKey][0];
                }
            }

            // Susun info timer
            $rowCourierHtml = '<span class="text-muted">-</span>';
            $rowCourierSec = null;
            $rowCourierNik = null;

            $rowPos1Html = '<span class="text-muted">-</span>';
            $rowPos1Sec  = null;

            $rowPos2Html = '<span class="text-muted">-</span>';
            $rowPos2Sec  = null;

            $rowScaleHtml = '<span class="text-muted">-</span>';
            $rowScaleSec  = null;

            $rowReturnHtml = '<span class="text-muted">-</span>';
            $rowReturnSec = null;
            $rowReturnNik = null;

            $rowCycleHtml = '<span class="text-muted">-</span>';
            $rowCycleSec = null;

            if ($soItem) {
                // 1. Courier Timer
                if ($soItem->courier_nik && $soItem->courier_at) {
                    $cNik = $soItem->courier_nik;
                    $rowCourierNik = $cNik;
                    $cName = $getEmpName($cNik);
                    $cTimeStr = $soItem->courier_at->format('H:i');
                    $cDelta = $courierDeltaMap[$soItem->id] ?? null;

                    if ($cDelta && $cDelta['delta_seconds'] !== null) {
                        $rowCourierSec = $cDelta['delta_seconds'];
                        $durText = $this->formatDuration($rowCourierSec);
                        $prevStr = $cDelta['prev_time']->format('H:i');
                        $badgeBg = $rowCourierSec > 600 ? 'bg-danger-subtle text-danger border border-danger' : ($rowCourierSec > 300 ? 'bg-warning-subtle text-warning border border-warning' : 'bg-success-subtle text-success border border-success');
                        $rowCourierHtml = "<span class='badge {$badgeBg} font-monospace timer-badge'><i class='fas fa-stopwatch me-1'></i>{$durText}</span>
                                           <div class='timer-subtext text-truncate mt-1' title='" . e($cName) . "'><strong>" . e($cName) . "</strong> ({$cTimeStr})</div>
                                           <div class='timer-subtext' style='font-size:0.58rem;'>Sblm: {$prevStr}</div>";
                    } elseif ($cDelta && $cDelta['is_first']) {
                        $rowCourierSec = 0;
                        $rowCourierHtml = "<span class='badge bg-info-subtle text-info border border-info font-monospace timer-badge'>Scan 1st</span>
                                           <div class='timer-subtext text-truncate mt-1' title='" . e($cName) . "'><strong>" . e($cName) . "</strong> ({$cTimeStr})</div>";
                    } else {
                        $rowCourierHtml = "<div class='timer-subtext text-truncate'><strong>" . e($cName) . "</strong> ({$cTimeStr})</div>";
                    }
                }

                // 2. Count Timer dipisah: Pos 1, Pos 2, dan Timbangan (Scale)
                if (isset($countDeltaMap[$soItem->id])) {
                    $hDeltas = $countDeltaMap[$soItem->id];
                    foreach ($hDeltas as $hD) {
                        $mName = $getEmpName($hD['member_nik']);
                        $cType = $hD['count_type'];
                        $hTimeStr = $hD['curr_time']->format('H:i');

                        $durBadge = '';
                        $prevInfo = '';

                        if ($hD['delta_seconds'] !== null) {
                            $durText = $this->formatDuration($hD['delta_seconds']);
                            $prevStr = $hD['prev_time']->format('H:i');
                            $badgeBg = $hD['delta_seconds'] > 300 ? 'bg-warning-subtle text-warning border border-warning' : 'bg-primary-subtle text-primary border border-primary';
                            $durBadge = "<span class='badge {$badgeBg} font-monospace timer-badge'><i class='fas fa-stopwatch me-1'></i>{$durText}</span>";
                            $prevInfo = "<div class='timer-subtext' style='font-size:0.58rem;'>Sblm: {$prevStr}</div>";
                        } else {
                            $durBadge = "<span class='badge bg-info-subtle text-info border border-info font-monospace timer-badge'>Hitung 1st</span>";
                        }

                        $cellContent = "{$durBadge}
                                        <div class='timer-subtext text-truncate mt-1' title='" . e($mName) . "'><strong>" . e($mName) . "</strong> ({$hTimeStr})</div>
                                        {$prevInfo}";

                        if ($cType === 'POS1') {
                            $rowPos1Html = $cellContent;
                            $rowPos1Sec  = $hD['delta_seconds'] ?? 0;
                        } elseif ($cType === 'POS2') {
                            $rowPos2Html = $cellContent;
                            $rowPos2Sec  = $hD['delta_seconds'] ?? 0;
                        } elseif ($cType === 'SCALE') {
                            $rowScaleHtml = $cellContent;
                            $rowScaleSec  = $hD['delta_seconds'] ?? 0;
                        }
                    }
                }

                // 3. Return Timer
                if ($soItem->return_nik && $soItem->returned_at) {
                    $rNik = $soItem->return_nik;
                    $rowReturnNik = $rNik;
                    $rName = $getEmpName($rNik);
                    $rTimeStr = $soItem->returned_at->format('H:i');
                    $rDelta = $returnDeltaMap[$soItem->id] ?? null;

                    if ($rDelta && $rDelta['delta_seconds'] !== null) {
                        $rowReturnSec = $rDelta['delta_seconds'];
                        $durText = $this->formatDuration($rowReturnSec);
                        $prevStr = $rDelta['prev_time']->format('H:i');
                        $badgeBg = $rowReturnSec > 600 ? 'bg-danger-subtle text-danger border border-danger' : 'bg-success-subtle text-success border border-success';
                        $rowReturnHtml = "<span class='badge {$badgeBg} font-monospace timer-badge'><i class='fas fa-undo-alt me-1'></i>{$durText}</span>
                                          <div class='timer-subtext text-truncate mt-1' title='" . e($rName) . "'><strong>" . e($rName) . "</strong> ({$rTimeStr})</div>
                                          <div class='timer-subtext' style='font-size:0.58rem;'>Sblm: {$prevStr}</div>";
                    } elseif ($rDelta && $rDelta['is_first']) {
                        $rowReturnSec = 0;
                        $rowReturnHtml = "<span class='badge bg-info-subtle text-info border border-info font-monospace timer-badge'>Return 1st</span>
                                          <div class='timer-subtext text-truncate mt-1' title='" . e($rName) . "'><strong>" . e($rName) . "</strong> ({$rTimeStr})</div>";
                    } else {
                        $rowReturnHtml = "<div class='timer-subtext text-truncate'><strong>" . e($rName) . "</strong> ({$rTimeStr})</div>";
                    }
                }

                // 4. Total Siklus Part (Dari Scan Kurir sampai Return Rak)
                if ($soItem->courier_at && $soItem->returned_at) {
                    $rowCycleSec = abs($soItem->returned_at->diffInSeconds($soItem->courier_at));
                    $durText = $this->formatDuration($rowCycleSec);
                    $rowCycleHtml = "<span class='badge bg-success-subtle text-success border border-success font-monospace timer-badge px-1 py-1'><i class='fas fa-check-double me-1'></i>{$durText}</span>";
                } elseif ($soItem->courier_at) {
                    $rowCycleSec = abs(now()->diffInSeconds($soItem->courier_at));
                    $durText = $this->formatDuration($rowCycleSec);
                    $rowCycleHtml = "<span class='badge bg-warning-subtle text-warning border border-warning font-monospace timer-badge px-1 py-1'><i class='fas fa-spinner fa-spin me-1'></i>{$durText}</span>";
                }
            }

            $rows[] = [
                'id'               => $item->id,
                'rack_no'          => $item->rack_no,
                'area_no'          => $item->area_no ?: '-',
                'loc_display'      => $locDisplay,
                'item_code'        => $item->item_code,
                'part_name'        => $item->part_name,
                'courier_timer'    => $rowCourierHtml,
                'courier_seconds'  => $rowCourierSec ?? 999999,
                'courier_nik'      => $rowCourierNik,
                'pos1_timer'       => $rowPos1Html,
                'pos1_seconds'     => $rowPos1Sec ?? 999999,
                'pos2_timer'       => $rowPos2Html,
                'pos2_seconds'     => $rowPos2Sec ?? 999999,
                'scale_timer'      => $rowScaleHtml,
                'scale_seconds'    => $rowScaleSec ?? 999999,
                'return_timer'     => $rowReturnHtml,
                'return_seconds'   => $rowReturnSec ?? 999999,
                'return_nik'       => $rowReturnNik,
                'cycle_timer'      => $rowCycleHtml,
                'cycle_seconds'    => $rowCycleSec ?? 999999,
                'so_item_id'       => $soItem ? $soItem->id : null,
                'has_courier'      => $soItem && $soItem->courier_nik ? true : false,
                'has_pos1'         => $rowPos1Sec !== null,
                'has_pos2'         => $rowPos2Sec !== null,
                'has_scale'        => $rowScaleSec !== null,
                'has_count'        => ($rowPos1Sec !== null || $rowPos2Sec !== null || $rowScaleSec !== null || ($soItem && $soItem->final_count !== null)),
                'has_return'       => $soItem && $soItem->returned_at ? true : false,
            ];
        }

        // Terapkan filter proses
        $filterCourier = $request->input('filter_courier');
        $filterCount   = $request->input('filter_count');
        $filterMethod  = $request->input('filter_method');
        $filterReturn  = $request->input('filter_return');
        $filterCourierNik = $request->input('filter_courier_nik');
        $filterReturnNik  = $request->input('filter_return_nik');

        $collection = collect($rows);

        if ($filterCourier) {
            if ($filterCourier === 'done') {
                $collection = $collection->filter(fn($r) => !empty($r['has_courier']));
            } elseif ($filterCourier === 'undone') {
                $collection = $collection->filter(fn($r) => empty($r['has_courier']));
            }
        }

        if ($filterCount) {
            if ($filterCount === 'done') {
                $collection = $collection->filter(fn($r) => !empty($r['has_count']));
            } elseif ($filterCount === 'undone') {
                $collection = $collection->filter(fn($r) => empty($r['has_count']));
            }
        }

        if ($filterMethod) {
            if ($filterMethod === 'manual') {
                $collection = $collection->filter(fn($r) => !empty($r['has_pos1']) || !empty($r['has_pos2']));
            } elseif ($filterMethod === 'scale') {
                $collection = $collection->filter(fn($r) => !empty($r['has_scale']));
            }
        }

        if ($filterReturn) {
            if ($filterReturn === 'done') {
                $collection = $collection->filter(fn($r) => !empty($r['has_return']));
            } elseif ($filterReturn === 'undone') {
                $collection = $collection->filter(fn($r) => empty($r['has_return']));
            }
        }

        if ($filterCourierNik) {
            $collection = $collection->filter(fn($r) => $r['courier_nik'] === $filterCourierNik);
        }
        if ($filterReturnNik) {
            $collection = $collection->filter(fn($r) => $r['return_nik'] === $filterReturnNik);
        }

        return DataTables::of($collection)
            ->addIndexColumn()
            ->rawColumns(['courier_timer', 'pos1_timer', 'pos2_timer', 'scale_timer', 'return_timer', 'cycle_timer', 'loc_display', 'part_name', 'area_no', 'rack_no'])
            ->make(true);
    }

    /**
     * Hitung Rekap KPI Rata-rata Durasi Step untuk Header Event (Kurir, Pos 1, Pos 2, Scale, Return, Siklus)
     */
    protected function calculateEventTimerSummary($eventId)
    {
        $soItems = SoItem::where('so_event_id', $eventId)
            ->select('id', 'courier_nik', 'courier_at', 'return_nik', 'returned_at')
            ->get();

        // 1. Rata-rata Kurir
        $courierSorted = $soItems->filter(fn($i) => !empty($i->courier_nik) && !empty($i->courier_at))
            ->sortBy([
                ['courier_nik', 'asc'],
                ['courier_at', 'asc'],
            ]);

        $courierDeltas = [];
        $lastCourierTime = [];
        foreach ($courierSorted as $item) {
            $nik = $item->courier_nik;
            $curr = $item->courier_at;
            if (isset($lastCourierTime[$nik])) {
                $courierDeltas[] = abs($curr->diffInSeconds($lastCourierTime[$nik]));
            }
            $lastCourierTime[$nik] = $curr;
        }
        $avgCourierSec = !empty($courierDeltas) ? round(array_sum($courierDeltas) / count($courierDeltas)) : null;

        // 2. Rata-rata Pos 1, Pos 2, Timbangan (Scale)
        $soItemIds = $soItems->pluck('id')->toArray();
        $histories = SoCountHistory::whereIn('so_item_id', $soItemIds)
            ->select('id', 'so_item_id', 'count_type', 'member_nik', 'created_at')
            ->orderBy('created_at', 'asc')
            ->get();

        $calcTypeAvg = function ($type) use ($histories) {
            $filtered = $histories->where('count_type', $type)->sortBy([
                ['member_nik', 'asc'],
                ['created_at', 'asc'],
            ]);
            $deltas = [];
            $lastTimes = [];
            foreach ($filtered as $h) {
                $nik = $h->member_nik;
                $curr = $h->created_at;
                if (isset($lastTimes[$nik])) {
                    $deltas[] = abs($curr->diffInSeconds($lastTimes[$nik]));
                }
                $lastTimes[$nik] = $curr;
            }
            return !empty($deltas) ? round(array_sum($deltas) / count($deltas)) : null;
        };

        $avgPos1Sec  = $calcTypeAvg('POS1');
        $avgPos2Sec  = $calcTypeAvg('POS2');
        $avgScaleSec = $calcTypeAvg('SCALE');

        // 3. Rata-rata Return
        $returnSorted = $soItems->filter(fn($i) => !empty($i->return_nik) && !empty($i->returned_at))
            ->sortBy([
                ['return_nik', 'asc'],
                ['returned_at', 'asc'],
            ]);

        $returnDeltas = [];
        $lastReturnTime = [];
        foreach ($returnSorted as $item) {
            $nik = $item->return_nik;
            $curr = $item->returned_at;
            if (isset($lastReturnTime[$nik])) {
                $returnDeltas[] = abs($curr->diffInSeconds($lastReturnTime[$nik]));
            }
            $lastReturnTime[$nik] = $curr;
        }
        $avgReturnSec = !empty($returnDeltas) ? round(array_sum($returnDeltas) / count($returnDeltas)) : null;

        // 4. Rata-rata Siklus Part (Courier -> Return)
        $completedCycles = $soItems->filter(fn($i) => !empty($i->courier_at) && !empty($i->returned_at))
            ->map(fn($i) => abs($i->returned_at->diffInSeconds($i->courier_at)))
            ->toArray();
        $avgCycleSec = !empty($completedCycles) ? round(array_sum($completedCycles) / count($completedCycles)) : null;

        return [
            'total_scanned_courier' => $soItems->whereNotNull('courier_at')->count(),
            'total_returned'        => $soItems->whereNotNull('returned_at')->count(),
            'avg_courier_formatted' => $avgCourierSec !== null ? $this->formatDuration($avgCourierSec) : '-',
            'avg_pos1_formatted'    => $avgPos1Sec !== null ? $this->formatDuration($avgPos1Sec) : '-',
            'avg_pos2_formatted'    => $avgPos2Sec !== null ? $this->formatDuration($avgPos2Sec) : '-',
            'avg_scale_formatted'   => $avgScaleSec !== null ? $this->formatDuration($avgScaleSec) : '-',
            'avg_return_formatted'  => $avgReturnSec !== null ? $this->formatDuration($avgReturnSec) : '-',
            'avg_cycle_formatted'   => $avgCycleSec !== null ? $this->formatDuration($avgCycleSec) : '-',
        ];
    }

    /**
     * Format durasi detik ke teks rapi (contoh: 2 j 15 mnt, 4 mnt 12 dtk, 45 dtk)
     */
    protected function formatDuration($seconds)
    {
        if ($seconds === null) return '-';
        if ($seconds < 60) return $seconds . ' dtk';
        $minutes = floor($seconds / 60);
        $remSeconds = $seconds % 60;
        if ($minutes < 60) {
            return $remSeconds > 0 ? "{$minutes} mnt {$remSeconds} dtk" : "{$minutes} mnt";
        }
        $hours = floor($minutes / 60);
        $remMinutes = $minutes % 60;
        return $remMinutes > 0 ? "{$hours} j {$remMinutes} mnt" : "{$hours} j";
    }
}
