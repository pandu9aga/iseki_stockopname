<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AuditorRecord;
use App\Models\SoCountHistory;
use App\Models\SoEvent;
use App\Models\SoEventPart;
use App\Models\SoItem;
use App\Models\SoScaleSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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

        // Total item yang dihitung NG (mismatch atau riwayat NG)
        $totalNg = SoItem::where('so_event_id', $event->id)
            ->where(function ($q) {
                $q->where('current_step', 'MISMATCH_NG')
                  ->orWhereHas('histories', function ($sub) {
                      $sub->where('status', 'NG');
                  });
            })->count();

        // Hitung statistik per area_no (Card Grid Area)
        $areaCards = $this->calculateAreaStatistics($event->id);

        return view('member.part_list', compact(
            'member',
            'event',
            'events',
            'totalStockopname',
            'totalReadyToCount',
            'totalCounted',
            'totalAudited',
            'totalNg',
            'areaCards'
        ));
    }

    /**
     * DataTables AJAX Endpoint untuk List Part & Riwayat Tahap SO per Event
     */
    public function data(Request $request, $id = null)
    {
        $isMember = Auth::guard('member')->check();
        $isAdminOrAuditor = Auth::guard('admin')->check() || Auth::guard('auditor')->check();
        $hideQty = $isMember && !$isAdminOrAuditor;

        $eventId = $id ?: $request->input('event_id');
        $areaNoFilter = $request->input('area_no'); // Opsional: filter per nomor area card

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

        // Terapkan filter area jika diklik dari card (bisa berupa area_no atau area_letter)
        if ($areaNoFilter !== null && $areaNoFilter !== '') {
            if ($areaNoFilter !== 'TAMBAHAN') {
                $partsQuery->where(function ($q) use ($areaNoFilter) {
                    $q->where('area_no', $areaNoFilter)
                      ->orWhere(function ($sub) use ($areaNoFilter) {
                          $sub->whereNull('area_no')
                              ->where('area_letter', $areaNoFilter);
                      });
                });
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
        $soMapByPartRack = [];
        foreach ($soItems as $so) {
            if ($so->tracking_code) {
                $soMapByTracking[$so->tracking_code] = $so;
            }
            $loc = Area::formatLocDisplay($so->area ?? null, $so->cell ?? null);
            $key = strtoupper(trim($so->code_part)) . '###' . strtoupper(trim($so->rack_no)) . '###' . strtoupper(trim($loc));
            $soMapByCombo[$key] = $so;

            // Simpan juga berdasarkan cell langsung jika cell gabungan (cth: W3-5D, W3-6D)
            if ($so->cell) {
                $cellKey = strtoupper(trim($so->code_part)) . '###' . strtoupper(trim($so->rack_no)) . '###' . strtoupper(trim($so->cell));
                $soMapByCombo[$cellKey] = $so;
            }

            // Simpan mapping berdasarkan code_part + rack_no untuk pencocokan multi-cell
            $partRackKey = strtoupper(trim($so->code_part)) . '###' . strtoupper(trim($so->rack_no));
            $soMapByPartRack[$partRackKey][] = $so;
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

        // Kelompokkan snapshot parts jika berada pada rak & part yang sama dan memiliki abjad area yang sama
        // Contoh: W3-5D dan W3-6D digabung menjadi 1 baris 'W3-5D, W3-6D'
        // Jika abjad area berbeda (misal W3-5D dan E3-6D), tetap terpisah barisnya
        $groupedSnapshot = [];
        foreach ($snapshotParts as $item) {
            $loc = $item->location ?: '-';
            $cell = $item->cell ?: '-';
            $locDisplay = $item->loc_cell_display ?: Area::formatLocDisplay($loc, $cell);

            // Deteksi huruf abjad area (Urutan standar: E, ET, M, W, P, GR, baru OTHER)
            $detectedAreaLetter = !empty($item->area_letter) ? $item->area_letter : Area::detectAreaLetter($loc, $cell);

            // Grouping key: event + part_code + rack_no + area_letter
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

        $flattenedRows = [];

        foreach ($groupedSnapshot as $group) {
            $item = $group['primary_item'];
            $itemsInGroup = $group['items'];
            $isMultiLocation = count($itemsInGroup) > 1 || count($group['loc_displays']) > 1;

            $loc = implode(', ', $group['locations']);
            $cell = implode(', ', $group['cells']);
            $locDisplay = implode(', ', $group['loc_displays']);

            $trackingCode = $item->tracking_code ?: "{$item->item_code}|{$item->part_name}|{$item->rack_no}|{$locDisplay}";
            $comboKey = strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no)) . '###' . strtoupper(trim($locDisplay));

            // Cari apakah sudah pernah diproses SO
            $soItem = $soMapByTracking[$trackingCode] ?? ($soMapByCombo[$comboKey] ?? null);

            // Jika belum ketemu by exact tracking/combo, cari lewat kombinasi multi-cell & fallback
            if (!$soItem) {
                $partRackKey = strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no));
                if (isset($soMapByPartRack[$partRackKey])) {
                    $candidates = $soMapByPartRack[$partRackKey];
                    foreach ($candidates as $cand) {
                        $candCell = strtoupper((string)$cand->cell);
                        $candTracking = strtoupper((string)$cand->tracking_code);
                        
                        // Periksa apakah ada locDisplay atau cell dalam grup ini yang terkandung di kandidat
                        foreach ($group['loc_displays'] as $ld) {
                            if (str_contains($candCell, strtoupper($ld)) || str_contains($candTracking, strtoupper($ld))) {
                                $soItem = $cand;
                                break 2;
                            }
                        }
                        foreach ($group['cells'] as $c) {
                            if ($c !== '-' && str_contains($candCell, strtoupper($c))) {
                                $soItem = $cand;
                                break 2;
                            }
                        }
                    }

                    // Jika masih belum dan kandidat hanya 1 untuk part & rak ini
                    if (!$soItem && count($candidates) === 1) {
                        $soItem = $candidates[0];
                    }
                }
            }

            // Cari riwayat audit secara spesifik per cell / tracking code
            $audits = [];
            if ($soItem && isset($auditorMap['SOITEM###' . $soItem->id])) {
                $audits = $auditorMap['SOITEM###' . $soItem->id];
            } elseif (isset($auditorMap['TRACKING###' . strtoupper(trim($trackingCode))])) {
                $audits = $auditorMap['TRACKING###' . strtoupper(trim($trackingCode))];
            } else {
                foreach ($group['cells'] as $c) {
                    $cKey = strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no)) . '###' . strtoupper(trim($c));
                    if (isset($auditorMap['CELL###' . $cKey])) {
                        $audits = array_merge($audits, $auditorMap['CELL###' . $cKey]);
                    }
                }
                if (empty($audits) && isset($auditorMap['COMBO###' . strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no))])) {
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
            $isNg = false;

            if ($soItem) {
                $stepLabel = $soItem->current_step;
                if ($soItem->current_step === 'MISMATCH_NG') {
                    $isNg = true;
                }
                
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
                            if ($h->status === 'NG') {
                                $isNg = true;
                            }
                            $res = $hideQty ? 'Selesai dihitung' : ($h->result_count !== null ? $h->result_count . ' pcs' : '-');
                            $statusBadge = $h->status === 'OK' ? '<span class="badge bg-success-subtle text-success border border-success" style="font-size:0.7rem;">OK</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.7rem;">' . ($h->status ?: 'NG') . '</span>';
                            $stepSummary[] = '<span class="badge bg-info text-dark me-1">Pos 1</span> ' . $res . ' ' . $statusBadge . ' <small class="text-muted">(NIK: ' . $nikStr . ($timeStr ? ' &bull; ' . $timeStr : '') . ')</small>';
                        } elseif ($h->count_type === 'POS2') {
                            $allMethodsUsed['MANUAL'] = true;
                            if ($h->status === 'NG') {
                                $isNg = true;
                            }
                            $res = $hideQty ? 'Selesai dihitung' : ($h->result_count !== null ? $h->result_count . ' pcs' : '-');
                            $statusBadge = $h->status === 'OK' ? '<span class="badge bg-success-subtle text-success border border-success" style="font-size:0.7rem;">OK</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.7rem;">' . ($h->status ?: 'NG') . '</span>';
                            $stepSummary[] = '<span class="badge bg-primary text-white me-1">Pos 2</span> ' . $res . ' ' . $statusBadge . ' <small class="text-muted">(NIK: ' . $nikStr . ($timeStr ? ' &bull; ' . $timeStr : '') . ')</small>';
                        } elseif ($h->count_type === 'SCALE') {
                            $allMethodsUsed['SCALE'] = true;
                            if ($h->status === 'NG') {
                                $isNg = true;
                            }
                            $scaleName = $soItem->scale_number ?: ($h->scale ? $h->scale->scale_number : 'Scale');
                            $res = $hideQty ? 'Selesai ditimbang' : ($h->result_count !== null ? $h->result_count . ' pcs' : ($h->weight_with_sample !== null ? (int)$h->weight_with_sample . ' pcs' : '-'));
                            $moeStr = (!$hideQty && $h->error_margin_percent !== null) ? 'MoE: ' . $h->error_margin_percent . '%' : '';
                            $statusBadge = $h->status === 'OK' ? '<span class="badge bg-success-subtle text-success border border-success" style="font-size:0.7rem;">OK</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.7rem;">' . ($h->status ?: 'NG') . '</span>';
                            $stepSummary[] = '<span class="badge bg-primary-subtle text-primary border border-primary me-1">Timbangan (' . $scaleName . ')</span> ' . $res . ' ' . $statusBadge . ($moeStr ? ' <small class="text-muted">[' . $moeStr . ']</small>' : '') . ' <small class="text-muted">(NIK: ' . $nikStr . ($timeStr ? ' &bull; ' . $timeStr : '') . ')</small>';
                        }
                    }
                } else {
                    // Fallback jika histories kosong tapi di soItem ada data
                    if ($soItem->process_type === 'SCALE') {
                        $allMethodsUsed['SCALE'] = true;
                        $finalCountText = $hideQty ? 'Selesai ditimbang' : ($soItem->final_count !== null ? $soItem->final_count . ' pcs' : 'Proses');
                        $scaleNo = $soItem->scale_number ?: '-';
                        $stepSummary[] = '<span class="badge bg-primary-subtle text-primary border border-primary me-1">Timbangan (' . $scaleNo . ')</span> ' . $finalCountText;
                    } elseif ($soItem->process_type === 'MANUAL' || $soItem->pos1_count !== null || $soItem->pos2_count !== null) {
                        $allMethodsUsed['MANUAL'] = true;
                        if ($hideQty) {
                            $stepSummary[] = '<span class="badge bg-secondary me-1">Manual</span> Selesai dihitung';
                        } else {
                            $p1 = $soItem->pos1_count !== null ? $soItem->pos1_count . ' pcs' : '-';
                            $p2 = $soItem->pos2_count !== null ? $soItem->pos2_count . ' pcs' : '-';
                            $stepSummary[] = '<span class="badge bg-secondary me-1">Manual</span> Pos 1 = ' . $p1 . ' | Pos 2 = ' . $p2;
                        }
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
                    $auditQtyText = $hideQty ? 'Selesai diaudit' : (number_format($a->audited_count) . ' pcs');
                    $stepSummary[] = '<span class="badge bg-dark text-warning border border-warning me-1">' . $roundLabel . '</span> ' . $auditQtyText . ' ' . $aStatusBadge . ' <small class="text-muted">(' . $aMethod . ' &bull; ' . $a->auditor_name . ($aTime ? ' &bull; ' . $aTime : '') . ')</small>';
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

            // Hitung durasi waktu dari scan kurir sampai dikembalikan ke rak
            $durationFormatted = '-';
            $durationSeconds = null;
            if ($soItem) {
                $start = $soItem->courier_at ?: $soItem->created_at;
                $end = $soItem->returned_at;
                if ($start && $end) {
                    $durationSeconds = abs($start->diffInSeconds($end, false));
                    $durationFormatted = $this->formatDuration($durationSeconds);
                } elseif ($start) {
                    $durationSeconds = abs($start->diffInSeconds(now(), false));
                    $durationFormatted = '<span class="text-warning small"><i class="fas fa-spinner fa-spin me-1"></i>' . $this->formatDuration($durationSeconds) . '</span>';
                }
            }

            $flattenedRows[] = [
                'id'               => $item->id,
                'rack_no'          => $item->rack_no,
                'item_code'        => $item->item_code,
                'part_name'        => $item->part_name,
                'location'         => $loc,
                'cell'             => $cell,
                'loc_display'      => $locDisplay,
                'area_letter'      => $item->area_letter ?: $group['area_letter'],
                'area_no'          => $item->area_no ?: '-',
                'pair_info'        => $isMultiLocation ? ('Lokasi: ' . count($group['loc_displays']) . ' cell') : null,
                'has_double_cell'  => $isMultiLocation,
                'current_step'     => $stepLabel,
                'badge_class'      => $badgeClass,
                'has_courier'      => $hasCourier,
                'has_count'        => $hasCount,
                'has_return'       => $hasReturn,
                'has_audit'        => $hasAudit,
                'is_ng'            => $isNg,
                'duration'         => $durationFormatted,
                'duration_seconds' => $durationSeconds,
                'final_count'      => $hideQty ? ($soItem && $soItem->final_count !== null ? '<span class="badge bg-success-subtle text-success border border-success">Sudah Dihitung</span>' : '-') : ($soItem ? ($soItem->final_count !== null ? number_format($soItem->final_count) . ' pcs' : '-') : '-'),
                'process_type'     => $processTypeDisplay,
                'step_summary'     => !empty($stepSummary) ? implode('<br>', $stepSummary) : '<span class="text-muted">Belum ada aktivitas</span>',
                'so_item_id'       => $soItem ? $soItem->id : null,
            ];
        }

        // TAMPILKAN JUGA PART TAMBAHAN (SO ITEM YANG DISCAN TAPI TIDAK ADA DI LIST EXCEL TARGET)
        // Jika filter area_no adalah 'TAMBAHAN' atau filter kosong (semua), sertakan part tambahan ini
        if ($areaNoFilter === null || $areaNoFilter === '' || $areaNoFilter === 'TAMBAHAN') {
            foreach ($soItems as $extraSo) {
                // Cek apakah soItem ini sudah terpetakan di snapshot target
                $isMapped = false;
                foreach ($flattenedRows as $fr) {
                    if ($fr['so_item_id'] === $extraSo->id) {
                        $isMapped = true;
                        break;
                    }
                }

                if (!$isMapped) {
                    // Cari relasi audit untuk part tambahan ini
                    $extraAudits = [];
                    if (isset($auditorMap['SOITEM###' . $extraSo->id])) {
                        $extraAudits = $auditorMap['SOITEM###' . $extraSo->id];
                    } elseif ($extraSo->tracking_code && isset($auditorMap['TRACKING###' . strtoupper(trim($extraSo->tracking_code))])) {
                        $extraAudits = $auditorMap['TRACKING###' . strtoupper(trim($extraSo->tracking_code))];
                    }

                    // Susun riwayat untuk part tambahan
                    $extraSummary = [];
                    $extraMethods = [];
                    $extraHasCourier = false;
                    $extraHasCount = false;
                    $extraHasReturn = false;
                    $extraHasAudit = false;
                    $extraIsNg = ($extraSo->current_step === 'MISMATCH_NG');

                    if ($extraSo->courier_nik) {
                        $extraHasCourier = true;
                        $cTime = $extraSo->courier_at ? $extraSo->courier_at->format('d/m H:i') : '-';
                        $extraSummary[] = '<span class="badge bg-secondary me-1">Kurir</span> NIK ' . $extraSo->courier_nik . ' (' . $cTime . ')';
                    }

                    if (in_array($extraSo->current_step, ['READY_TO_RETURN', 'RETURNED', 'COUNTED']) || $extraSo->final_count !== null || $extraSo->histories->isNotEmpty()) {
                        $extraHasCount = true;
                    }

                    if ($extraSo->histories->isNotEmpty()) {
                        foreach ($extraSo->histories as $h) {
                            $timeStr = $h->created_at ? $h->created_at->format('d/m H:i') : '';
                            $nikStr = $h->member_nik ?: '-';
                            if ($h->count_type === 'POS1') {
                                $extraMethods['MANUAL'] = true;
                                if ($h->status === 'NG') $extraIsNg = true;
                                $res = $hideQty ? 'Selesai dihitung' : ($h->result_count !== null ? $h->result_count . ' pcs' : '-');
                                $statusBadge = $h->status === 'OK' ? '<span class="badge bg-success-subtle text-success border border-success" style="font-size:0.7rem;">OK</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.7rem;">' . ($h->status ?: 'NG') . '</span>';
                                $extraSummary[] = '<span class="badge bg-info text-dark me-1">Pos 1</span> ' . $res . ' ' . $statusBadge . ' <small class="text-muted">(NIK: ' . $nikStr . ($timeStr ? ' &bull; ' . $timeStr : '') . ')</small>';
                            } elseif ($h->count_type === 'POS2') {
                                $extraMethods['MANUAL'] = true;
                                if ($h->status === 'NG') $extraIsNg = true;
                                $res = $hideQty ? 'Selesai dihitung' : ($h->result_count !== null ? $h->result_count . ' pcs' : '-');
                                $statusBadge = $h->status === 'OK' ? '<span class="badge bg-success-subtle text-success border border-success" style="font-size:0.7rem;">OK</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.7rem;">' . ($h->status ?: 'NG') . '</span>';
                                $extraSummary[] = '<span class="badge bg-primary text-white me-1">Pos 2</span> ' . $res . ' ' . $statusBadge . ' <small class="text-muted">(NIK: ' . $nikStr . ($timeStr ? ' &bull; ' . $timeStr : '') . ')</small>';
                            } elseif ($h->count_type === 'SCALE') {
                                $extraMethods['SCALE'] = true;
                                if ($h->status === 'NG') $extraIsNg = true;
                                $scaleName = $extraSo->scale_number ?: ($h->scale ? $h->scale->scale_number : 'Scale');
                                $res = $hideQty ? 'Selesai ditimbang' : ($h->result_count !== null ? $h->result_count . ' pcs' : ($h->weight_with_sample !== null ? (int)$h->weight_with_sample . ' pcs' : '-'));
                                $moeStr = (!$hideQty && $h->error_margin_percent !== null) ? 'MoE: ' . $h->error_margin_percent . '%' : '';
                                $statusBadge = $h->status === 'OK' ? '<span class="badge bg-success-subtle text-success border border-success" style="font-size:0.7rem;">OK</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.7rem;">' . ($h->status ?: 'NG') . '</span>';
                                $extraSummary[] = '<span class="badge bg-primary-subtle text-primary border border-primary me-1">Timbangan (' . $scaleName . ')</span> ' . $res . ' ' . $statusBadge . ($moeStr ? ' <small class="text-muted">[' . $moeStr . ']</small>' : '') . ' <small class="text-muted">(NIK: ' . $nikStr . ($timeStr ? ' &bull; ' . $timeStr : '') . ')</small>';
                            }
                        }
                    } else {
                        if ($extraSo->process_type === 'SCALE') {
                            $extraMethods['SCALE'] = true;
                            $finalCountText = $hideQty ? 'Selesai ditimbang' : ($extraSo->final_count !== null ? $extraSo->final_count . ' pcs' : 'Proses');
                            $scaleNo = $extraSo->scale_number ?: '-';
                            $extraSummary[] = '<span class="badge bg-primary-subtle text-primary border border-primary me-1">Timbangan (' . $scaleNo . ')</span> ' . $finalCountText;
                        } elseif ($extraSo->process_type === 'MANUAL' || $extraSo->pos1_count !== null || $extraSo->pos2_count !== null) {
                            $extraMethods['MANUAL'] = true;
                            if ($hideQty) {
                                $extraSummary[] = '<span class="badge bg-secondary me-1">Manual</span> Selesai dihitung';
                            } else {
                                $p1 = $extraSo->pos1_count !== null ? $extraSo->pos1_count . ' pcs' : '-';
                                $p2 = $extraSo->pos2_count !== null ? $extraSo->pos2_count . ' pcs' : '-';
                                $extraSummary[] = '<span class="badge bg-secondary me-1">Manual</span> Pos 1 = ' . $p1 . ' | Pos 2 = ' . $p2;
                            }
                        }
                    }

                    if ($extraSo->current_step === 'RETURNED' || $extraSo->returned_at) {
                        $extraHasReturn = true;
                        $retTime = $extraSo->returned_at ? $extraSo->returned_at->format('d/m H:i') : '-';
                        $extraSummary[] = '<span class="badge bg-success me-1">Return</span> Selesai NIK ' . ($extraSo->return_nik ?? '-') . ' (' . $retTime . ')';
                    }

                    if (!empty($extraAudits)) {
                        $extraHasAudit = true;
                        foreach ($extraAudits as $idx => $a) {
                            if ($a->method === 'SCALE') $extraMethods['SCALE'] = true;
                            elseif ($a->method === 'MANUAL') $extraMethods['MANUAL'] = true;
                            $aMethod = $a->method === 'SCALE' ? 'Timbangan' . ($a->scale_number ? ' (' . $a->scale_number . ')' : '') : 'Manual';
                            $aTime = $a->created_at ? $a->created_at->format('d/m H:i') : '';
                            $aStatusBadge = $a->audit_status === 'OK' ? '<span class="badge bg-success-subtle text-success border border-success" style="font-size:0.7rem;">OK</span>' : '<span class="badge bg-danger-subtle text-danger border border-danger" style="font-size:0.7rem;">' . ($a->audit_status ?: 'NG') . '</span>';
                            $roundLabel = count($extraAudits) > 1 ? 'Audit #' . ($idx + 1) : 'Audit';
                            $auditQtyText = $hideQty ? 'Selesai diaudit' : (number_format($a->audited_count) . ' pcs');
                            $extraSummary[] = '<span class="badge bg-dark text-warning border border-warning me-1">' . $roundLabel . '</span> ' . $auditQtyText . ' ' . $aStatusBadge . ' <small class="text-muted">(' . $aMethod . ' &bull; ' . $a->auditor_name . ($aTime ? ' &bull; ' . $aTime : '') . ')</small>';
                        }
                    }

                    if ($extraSo->process_type) $extraMethods[$extraSo->process_type] = true;
                    if ($extraSo->pos1_count !== null || $extraSo->pos2_count !== null) $extraMethods['MANUAL'] = true;
                    if ($extraSo->scale_number || $extraSo->scale_id) $extraMethods['SCALE'] = true;
                    $mList = array_keys($extraMethods);
                    $mDisplay = '-';
                    if (in_array('MANUAL', $mList) && in_array('SCALE', $mList)) $mDisplay = 'MANUAL & SCALE';
                    elseif (in_array('SCALE', $mList)) $mDisplay = 'SCALE';
                    elseif (in_array('MANUAL', $mList)) $mDisplay = 'MANUAL';

                    $extraBadgeClass = match($extraSo->current_step) {
                        'READY_TO_COUNT' => 'bg-warning text-dark',
                        'POS1_DONE'      => 'bg-info text-dark',
                        'MISMATCH_NG'    => 'bg-danger',
                        'READY_TO_RETURN'=> 'bg-primary',
                        'RETURNED'       => 'bg-success',
                        default          => 'bg-secondary'
                    };

                    $extraLocDisplay = Area::formatLocDisplay($extraSo->area, $extraSo->cell);
                    $extraLetter = Area::detectAreaLetter($extraSo->area, $extraSo->cell);

                    $extraDurationFormatted = '-';
                    $extraDurationSeconds = null;
                    $extraStart = $extraSo->courier_at ?: $extraSo->created_at;
                    $extraEnd = $extraSo->returned_at;
                    if ($extraStart && $extraEnd) {
                        $extraDurationSeconds = abs($extraStart->diffInSeconds($extraEnd, false));
                        $extraDurationFormatted = $this->formatDuration($extraDurationSeconds);
                    } elseif ($extraStart) {
                        $extraDurationSeconds = abs($extraStart->diffInSeconds(now(), false));
                        $extraDurationFormatted = '<span class="text-warning small"><i class="fas fa-spinner fa-spin me-1"></i>' . $this->formatDuration($extraDurationSeconds) . '</span>';
                    }

                    $flattenedRows[] = [
                        'id'               => 'extra_' . $extraSo->id,
                        'rack_no'          => $extraSo->rack_no,
                        'item_code'        => $extraSo->code_part,
                        'part_name'        => $extraSo->name_part,
                        'location'         => $extraSo->area ?: '-',
                        'cell'             => $extraSo->cell ?: '-',
                        'loc_display'      => $extraLocDisplay . ' <span class="badge bg-secondary-subtle text-secondary border ms-1" style="font-size:0.68rem;"><i class="fas fa-plus-circle me-1"></i>Part Tambahan</span>',
                        'area_letter'      => $extraLetter,
                        'area_no'          => 'TAMBAHAN',
                        'pair_info'        => 'Part Tambahan',
                        'has_double_cell'  => false,
                        'current_step'     => $extraSo->current_step,
                        'badge_class'      => $extraBadgeClass,
                        'has_courier'      => $extraHasCourier,
                        'has_count'        => $extraHasCount,
                        'has_return'       => $extraHasReturn,
                        'has_audit'        => $extraHasAudit,
                        'is_ng'            => $extraIsNg,
                        'duration'         => $extraDurationFormatted,
                        'duration_seconds' => $extraDurationSeconds,
                        'final_count'      => $hideQty ? ($extraSo->final_count !== null ? '<span class="badge bg-success-subtle text-success border border-success">Sudah Dihitung</span>' : '-') : ($extraSo->final_count !== null ? number_format($extraSo->final_count) . ' pcs' : '-'),
                        'process_type'     => $mDisplay,
                        'step_summary'     => !empty($extraSummary) ? implode('<br>', $extraSummary) : '<span class="text-muted">Belum ada aktivitas</span>',
                        'so_item_id'       => $extraSo->id,
                    ];
                }
            }
        }

        return DataTables::of(collect($flattenedRows))
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $detailBtn = '<button type="button" class="btn btn-sm btn-info btn-view-detail me-1" 
                                data-id="' . $row['id'] . '" 
                                data-so-id="' . ($row['so_item_id'] ?? '') . '" 
                                data-rack="' . e($row['rack_no']) . '" 
                                data-code="' . e($row['item_code']) . '" 
                                data-name="' . e($row['part_name']) . '" 
                                data-loc="' . e($row['loc_display']) . '" 
                                title="Lihat Detail Riwayat &amp; Foto">
                                <i class="fas fa-eye me-1"></i> Detail
                            </button>';

                $editBtn = '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-cell" 
                            data-id="' . $row['id'] . '" 
                            data-rack="' . e($row['rack_no']) . '" 
                            data-code="' . e($row['item_code']) . '" 
                            data-name="' . e($row['part_name']) . '" 
                            data-location="' . e($row['location'] === '-' ? '' : $row['location']) . '" 
                            data-cell="' . e($row['cell'] === '-' ? '' : $row['cell']) . '" 
                            title="Edit Cell & Lokasi">
                            <i class="fas fa-edit"></i>
                        </button>';

                return '<div class="d-inline-flex">' . $detailBtn . $editBtn . '</div>';
            })
            ->rawColumns(['step_summary', 'current_step', 'loc_display', 'part_name', 'final_count', 'duration', 'action'])
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
        $newLocDisplay = Area::formatLocDisplay($newLoc, $newCell);
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

    /**
     * Detail Riwayat Step-by-Step, Jumlah, dan Foto-foto Part SO
     */
    public function getPartDetail(Request $request, $id)
    {
        $soItem = null;
        $partSnapshot = null;

        if (str_starts_with($id, 'extra_')) {
            $extraId = substr($id, 6);
            $soItem = SoItem::with(['histories.member', 'histories.partner', 'histories.scale', 'scaleRounds', 'scaleSession.rounds', 'photos', 'event'])->find($extraId);
        } else {
            $partSnapshot = SoEventPart::with('event')->find($id);
            if ($partSnapshot) {
                // Cari SoItem yang berelasi
                $locDisplay = $partSnapshot->loc_cell_display ?: Area::formatLocDisplay($partSnapshot->location, $partSnapshot->cell);
                $trackingCode = $partSnapshot->tracking_code ?: "{$partSnapshot->item_code}|{$partSnapshot->part_name}|{$partSnapshot->rack_no}|{$locDisplay}";
                
                $soItem = SoItem::with(['histories.member', 'histories.partner', 'histories.scale', 'scaleRounds', 'scaleSession.rounds', 'photos', 'event'])
                    ->where('so_event_id', $partSnapshot->so_event_id)
                    ->where(function ($q) use ($partSnapshot, $trackingCode, $locDisplay) {
                        $q->where('tracking_code', $trackingCode)
                          ->orWhere(function ($sub) use ($partSnapshot, $locDisplay) {
                              $sub->where('code_part', $partSnapshot->item_code)
                                  ->where('rack_no', $partSnapshot->rack_no);
                          });
                    })->first();
            }
        }

        // Jika id yang dioper adalah so_item_id langsung
        if (!$soItem && is_numeric($id)) {
            $soItem = SoItem::with(['histories.member', 'histories.partner', 'histories.scale', 'scaleRounds', 'scaleSession.rounds', 'photos', 'event'])->find($id);
        }

        // Ambil Data Audit terkait
        $audits = collect();
        if ($soItem) {
            $audits = AuditorRecord::where('so_item_id', $soItem->id)
                ->orWhere(function ($q) use ($soItem) {
                    if ($soItem->tracking_code) {
                        $q->where('tracking_code', $soItem->tracking_code);
                    }
                })->orderBy('id', 'asc')->get();
        } elseif ($partSnapshot) {
            $audits = AuditorRecord::where('so_event_id', $partSnapshot->so_event_id)
                ->where('rack_no', $partSnapshot->rack_no)
                ->where('code_part', $partSnapshot->item_code)
                ->orderBy('id', 'asc')->get();
        }

        // Rak, part code, part name, cell
        $rackNo = $soItem ? $soItem->rack_no : ($partSnapshot ? $partSnapshot->rack_no : '-');
        $itemCode = $soItem ? $soItem->code_part : ($partSnapshot ? $partSnapshot->item_code : '-');
        $partName = $soItem ? $soItem->name_part : ($partSnapshot ? $partSnapshot->part_name : '-');
        $locDisplay = $soItem ? Area::formatLocDisplay($soItem->area, $soItem->cell) : ($partSnapshot ? ($partSnapshot->loc_cell_display ?: Area::formatLocDisplay($partSnapshot->location, $partSnapshot->cell)) : '-');
        $areaLetter = $partSnapshot ? $partSnapshot->area_letter : ($soItem ? Area::detectAreaLetter($soItem->area, $soItem->cell) : '-');

        // Susun riwayat tahapan step-by-step
        $timeline = [];

        // 1. Kurir
        $courierPhotos = [];
        if ($soItem && $soItem->courier_photo_part) {
            $courierPhotos[] = [
                'title' => 'Foto Part (Kurir)',
                'url'   => asset($soItem->courier_photo_part),
                'role'  => 'courier_part'
            ];
        }
        if ($soItem && $soItem->courier_photo_rack) {
            $courierPhotos[] = [
                'title' => 'Foto Rak (Kurir)',
                'url'   => asset($soItem->courier_photo_rack),
                'role'  => 'courier_rack'
            ];
        }

        $timeline[] = [
            'step'      => 'Kurir (Pengambilan)',
            'icon'      => 'fa-truck',
            'status'    => ($soItem && ($soItem->courier_nik || $soItem->courier_at)) ? 'COMPLETED' : 'PENDING',
            'badge'     => ($soItem && ($soItem->courier_nik || $soItem->courier_at)) ? 'badge bg-success' : 'badge bg-secondary',
            'pic'       => $soItem ? ($soItem->courier_nik ? 'NIK ' . $soItem->courier_nik : '-') : '-',
            'time'      => ($soItem && $soItem->courier_at) ? $soItem->courier_at->format('d/m/Y H:i:s') : '-',
            'qty'       => null,
            'details'   => ($soItem && $soItem->courier_nik) ? 'Part diambil oleh kurir dari rak untuk proses perhitungan.' : 'Part belum diambil kurir.',
            'photos'    => $courierPhotos,
        ];

        // 2. Perhitungan Pos 1 / Pos 2 / Timbangan
        $countDetails = [];
        $countPhotos = [];

        if ($soItem && $soItem->histories->isNotEmpty()) {
            foreach ($soItem->histories as $idx => $h) {
                $hPhotos = [];
                // Cari foto dari so_photos untuk history ini
                if ($soItem->photos) {
                    $matchedPhotos = $soItem->photos->where('history_id', $h->id);
                    foreach ($matchedPhotos as $mp) {
                        $hPhotos[] = [
                            'title' => 'Foto ' . $h->count_type . ($mp->photo_role ? ' (' . $mp->photo_role . ')' : ''),
                            'url'   => asset($mp->photo_path),
                            'role'  => $mp->photo_role
                        ];
                    }
                }

                $timeline[] = [
                    'step'      => 'Perhitungan ' . $h->count_type . ($h->scale_id ? ' (' . ($h->scale ? $h->scale->scale_number : 'Scale') . ')' : ''),
                    'icon'      => $h->count_type === 'SCALE' ? 'fa-balance-scale' : 'fa-calculator',
                    'status'    => $h->status ?: 'OK',
                    'badge'     => $h->status === 'NG' ? 'badge bg-danger' : 'badge bg-success',
                    'pic'       => 'NIK ' . ($h->member_nik ?: '-') . ($h->partner_nik ? ' &amp; NIK ' . $h->partner_nik : ''),
                    'time'      => $h->created_at ? $h->created_at->format('d/m/Y H:i:s') : '-',
                    'qty'       => $h->result_count !== null ? number_format($h->result_count) . ' pcs' : ($h->weight_with_sample !== null ? number_format($h->weight_with_sample) . ' pcs' : '-'),
                    'details'   => ($h->error_margin_percent !== null ? 'Margin Error: ' . $h->error_margin_percent . '% | ' : '') . ($h->notes ?: 'Hasil hitungan: ' . ($h->status ?: 'OK')),
                    'photos'    => $hPhotos,
                ];
            }
        } elseif ($soItem && ($soItem->pos1_count !== null || $soItem->pos2_count !== null || $soItem->final_count !== null)) {
            // Fallback manual count tanpa history
            if ($soItem->pos1_count !== null) {
                $timeline[] = [
                    'step'      => 'Perhitungan Pos 1',
                    'icon'      => 'fa-calculator',
                    'status'    => 'COMPLETED',
                    'badge'     => 'badge bg-success',
                    'pic'       => '-',
                    'time'      => $soItem->pos1_at ? $soItem->pos1_at->format('d/m/Y H:i:s') : '-',
                    'qty'       => number_format($soItem->pos1_count) . ' pcs',
                    'details'   => 'Hitungan Pos 1 selesai.',
                    'photos'    => [],
                ];
            }
            if ($soItem->pos2_count !== null) {
                $timeline[] = [
                    'step'      => 'Perhitungan Pos 2',
                    'icon'      => 'fa-calculator',
                    'status'    => 'COMPLETED',
                    'badge'     => 'badge bg-success',
                    'pic'       => '-',
                    'time'      => $soItem->pos2_at ? $soItem->pos2_at->format('d/m/Y H:i:s') : '-',
                    'qty'       => number_format($soItem->pos2_count) . ' pcs',
                    'details'   => 'Hitungan Pos 2 selesai.',
                    'photos'    => [],
                ];
            }
        } else {
            $timeline[] = [
                'step'      => 'Perhitungan SO (Pos / Timbangan)',
                'icon'      => 'fa-calculator',
                'status'    => 'PENDING',
                'badge'     => 'badge bg-secondary',
                'pic'       => '-',
                'time'      => '-',
                'qty'       => '-',
                'details'   => 'Belum dihitung oleh tim penghitung.',
                'photos'    => [],
            ];
        }

        // Foto-foto tambahan dari scale session / label proof jika ada
        if ($soItem && $soItem->scaleSession && $soItem->scaleSession->label_photo_path) {
            $timeline[] = [
                'step'      => 'Bukti Cetak Label Timbangan',
                'icon'      => 'fa-receipt',
                'status'    => 'COMPLETED',
                'badge'     => 'badge bg-info',
                'pic'       => 'NIK ' . ($soItem->scaleSession->member_nik ?: '-'),
                'time'      => $soItem->scaleSession->updated_at ? $soItem->scaleSession->updated_at->format('d/m/Y H:i:s') : '-',
                'qty'       => $soItem->final_count !== null ? number_format($soItem->final_count) . ' pcs' : '-',
                'details'   => 'Foto bukti label fisik QR yang telah dicetak & ditempel.',
                'photos'    => [[
                    'title' => 'Foto Bukti Label QR',
                    'url'   => asset($soItem->scaleSession->label_photo_path),
                    'role'  => 'LABEL_PROOF'
                ]],
            ];
        }

        // 3. Pengembalian (Return)
        $returnPhotos = [];
        if ($soItem && $soItem->return_photo) {
            $returnPhotos[] = [
                'title' => 'Foto Pengembalian ke Rak',
                'url'   => asset($soItem->return_photo),
                'role'  => 'return'
            ];
        }
        $timeline[] = [
            'step'      => 'Pengembalian ke Rak (Return)',
            'icon'      => 'fa-undo-alt',
            'status'    => ($soItem && ($soItem->current_step === 'RETURNED' || $soItem->returned_at)) ? 'COMPLETED' : 'PENDING',
            'badge'     => ($soItem && ($soItem->current_step === 'RETURNED' || $soItem->returned_at)) ? 'badge bg-success' : 'badge bg-secondary',
            'pic'       => $soItem ? ($soItem->return_nik ? 'NIK ' . $soItem->return_nik : '-') : '-',
            'time'      => ($soItem && $soItem->returned_at) ? $soItem->returned_at->format('d/m/Y H:i:s') : '-',
            'qty'       => ($soItem && $soItem->final_count !== null) ? number_format($soItem->final_count) . ' pcs' : '-',
            'details'   => ($soItem && $soItem->returned_at) ? 'Part telah diletakkan kembali ke rak fisik: ' . ($soItem->return_rack_scanned ?: $rackNo) : 'Part belum dikembalikan ke rak.',
            'photos'    => $returnPhotos,
        ];

        // 4. Audit Fisik
        if ($audits->isNotEmpty()) {
            foreach ($audits as $idx => $aud) {
                $auditPhotos = [];
                if (!empty($aud->photo_paths) && is_array($aud->photo_paths)) {
                    foreach ($aud->photo_paths as $pIndex => $pPath) {
                        $auditPhotos[] = [
                            'title' => 'Foto Audit #' . ($idx + 1) . ' (' . ($pIndex + 1) . ')',
                            'url'   => asset($pPath),
                            'role'  => 'audit'
                        ];
                    }
                }

                $timeline[] = [
                    'step'      => 'Audit Fisik #' . ($idx + 1) . ' (' . ($aud->method ?: 'MANUAL') . ')',
                    'icon'      => 'fa-clipboard-check',
                    'status'    => $aud->audit_status ?: 'OK',
                    'badge'     => $aud->audit_status === 'NG' ? 'badge bg-danger' : 'badge bg-success',
                    'pic'       => $aud->auditor_name ?: 'Auditor',
                    'time'      => $aud->created_at ? $aud->created_at->format('d/m/Y H:i:s') : '-',
                    'qty'       => number_format($aud->audited_count) . ' pcs',
                    'details'   => 'Hasil Audit: ' . ($aud->audit_status ?: 'OK') . ($aud->notes ? ' | Catatan: ' . $aud->notes : ''),
                    'photos'    => $auditPhotos,
                ];
            }
        }

        // Kumpulkan SEMUA foto dalam satu galeri
        $allGalleryPhotos = [];
        foreach ($timeline as $t) {
            if (!empty($t['photos'])) {
                foreach ($t['photos'] as $p) {
                    $allGalleryPhotos[] = $p;
                }
            }
        }

        // Tambahkan foto dari so_photos yang mungkin belum masuk di timeline
        if ($soItem && $soItem->photos) {
            foreach ($soItem->photos as $sp) {
                $pUrl = asset($sp->photo_path);
                $exists = false;
                foreach ($allGalleryPhotos as $agp) {
                    if ($agp['url'] === $pUrl) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $allGalleryPhotos[] = [
                        'title' => 'Dokumentasi ' . ($sp->pos_type ?: $sp->photo_role),
                        'url'   => $pUrl,
                        'role'  => $sp->photo_role ?: $sp->pos_type,
                    ];
                }
            }
        }

        return response()->json([
            'success'   => true,
            'item'      => [
                'rack_no'       => $rackNo,
                'item_code'     => $itemCode,
                'part_name'     => $partName,
                'loc_display'   => $locDisplay,
                'area_letter'   => $areaLetter,
                'current_step'  => $soItem ? $soItem->current_step : 'BELUM_SO',
                'final_count'   => ($soItem && $soItem->final_count !== null) ? number_format($soItem->final_count) : '-',
                'process_type'  => $soItem ? $soItem->process_type : '-',
                'tracking_code' => $soItem ? $soItem->tracking_code : ($partSnapshot ? $partSnapshot->tracking_code : '-'),
            ],
            'timeline'  => $timeline,
            'gallery'   => $allGalleryPhotos,
        ]);
    }

    /**
     * Hitung ringkasan statistik per area_no untuk Card Grid
     */
    public function calculateAreaStatistics($eventId): array
    {
        $parts = SoEventPart::where('so_event_id', $eventId)->get();
        if ($parts->isEmpty()) {
            return [];
        }

        // Ambil SO items yang sudah sampai RETURNED
        $returnedSoItems = SoItem::where('so_event_id', $eventId)
            ->where('current_step', 'RETURNED')
            ->get();

        // Index returned items by tracking & combo part+rack
        $returnedByTracking = [];
        $returnedByCombo = [];
        $returnedByPartRack = [];
        foreach ($returnedSoItems as $rsi) {
            if ($rsi->tracking_code) {
                $returnedByTracking[$rsi->tracking_code] = true;
            }
            $loc = ($rsi->area ?? '-') . '-' . ($rsi->cell ?? '-');
            $k = strtoupper(trim($rsi->code_part)) . '###' . strtoupper(trim($rsi->rack_no)) . '###' . strtoupper(trim($loc));
            $returnedByCombo[$k] = true;

            $prKey = strtoupper(trim($rsi->code_part)) . '###' . strtoupper(trim($rsi->rack_no));
            $returnedByPartRack[$prKey][] = $rsi;
        }

        // Ambil data audit fisik
        $audits = AuditorRecord::where('so_event_id', $eventId)->get();
        $auditedTracking = [];
        $auditedCombo = [];
        $auditedBySoItemId = [];
        foreach ($audits as $ar) {
            if ($ar->tracking_code) {
                $auditedTracking[strtoupper(trim($ar->tracking_code))] = true;
            }
            if ($ar->so_item_id) {
                $auditedBySoItemId[$ar->so_item_id] = true;
            }
            $ck = strtoupper(trim($ar->code_part ?? '')) . '###' . strtoupper(trim($ar->rack_no ?? ''));
            $auditedCombo[$ck] = true;
        }

        // Kelompokkan part snapshot per area_no (atau per area_letter jika event tidak memiliki area_no)
        $hasAnyAreaNo = $parts->contains(function ($p) {
            return !empty($p->area_no);
        });

        $groups = [];
        $mappedSoItemIds = [];

        foreach ($parts as $p) {
            $loc = $p->location ?: '-';
            $cell = $p->cell ?: '-';
            $locDisplay = $p->loc_cell_display ?: Area::formatLocDisplay($loc, $cell);

            // Deteksi area letter (Urutan standar: E, ET, M, W, P, GR, baru OTHER)
            $areaLetter = !empty($p->area_letter) ? $p->area_letter : Area::detectAreaLetter($loc, $cell);

            // Jika ada area_no (mode Excel), kelompokkan per area_no.
            // Jika mode copy langsung iseki_label (tanpa area_no), kelompokkan berdasarkan area_letter saja.
            if ($hasAnyAreaNo) {
                $groupKeyArea = !empty($p->area_no) ? (string)$p->area_no : 'Tanpa Area';
            } else {
                $groupKeyArea = $areaLetter;
            }

            $rowKey = strtoupper(trim($p->item_code)) . '###' . strtoupper(trim($p->rack_no)) . '###' . $areaLetter;

            if (!isset($groups[$groupKeyArea][$rowKey])) {
                $groups[$groupKeyArea][$rowKey] = [
                    'primary'      => $p,
                    'area_letter'  => $areaLetter,
                    'loc_displays' => [$locDisplay],
                ];
            } else {
                if (!in_array($locDisplay, $groups[$groupKeyArea][$rowKey]['loc_displays'], true)) {
                    $groups[$groupKeyArea][$rowKey]['loc_displays'][] = $locDisplay;
                }
            }
        }

        $cards = [];
        // Sort area: Jika mode area_letter (tanpa area_no), urutkan sesuai urutan standar: E, ET, M, W, P, GR, OTHER
        if (!$hasAnyAreaNo) {
            uksort($groups, function ($a, $b) {
                $wA = Area::sortWeight($a);
                $wB = Area::sortWeight($b);
                if ($wA === $wB) {
                    return strnatcasecmp($a, $b);
                }
                return $wA <=> $wB;
            });
        } else {
            // Sort area natural ascending untuk nomor area (01, 02, ...)
            ksort($groups, SORT_NATURAL);
        }

        foreach ($groups as $groupKeyArea => $items) {
            $totalPart = count($items);
            $totalReturned = 0;
            $totalAudited = 0;
            $letters = [];

            foreach ($items as $rowKey => $row) {
                $p = $row['primary'];
                $letters[$row['area_letter']] = true;
                $mergedLoc = implode(', ', $row['loc_displays']);
                $trackingCode = $p->tracking_code ?: "{$p->item_code}|{$p->part_name}|{$p->rack_no}|{$mergedLoc}";
                $comboKey = strtoupper(trim($p->item_code)) . '###' . strtoupper(trim($p->rack_no)) . '###' . strtoupper(trim($mergedLoc));
                $partRackKey = strtoupper(trim($p->item_code)) . '###' . strtoupper(trim($p->rack_no));

                // Cek returned & tandai so item yang sudah masuk area target
                $isReturned = false;
                if (isset($returnedByTracking[$trackingCode])) {
                    $isReturned = true;
                } elseif (isset($returnedByCombo[$comboKey])) {
                    $isReturned = true;
                } elseif (isset($returnedByPartRack[$partRackKey])) {
                    $isReturned = true;
                    foreach ($returnedByPartRack[$partRackKey] as $matchedItem) {
                        $mappedSoItemIds[$matchedItem->id] = true;
                    }
                }

                if ($isReturned) {
                    $totalReturned++;
                }

                // Cek audited
                $isAudited = isset($auditedTracking[strtoupper(trim($trackingCode))]) || isset($auditedCombo[$partRackKey]);
                if ($isAudited) {
                    $totalAudited++;
                }
            }

            $cardTitle = $hasAnyAreaNo ? "Area {$groupKeyArea}" : "Area {$groupKeyArea}";
            $cardBadgeLetter = $hasAnyAreaNo ? implode(', ', array_keys($letters)) : '';

            $cards[] = [
                'area_no'        => $groupKeyArea,
                'card_title'     => $cardTitle,
                'letters'        => $cardBadgeLetter,
                'total_part'     => $totalPart,
                'total_returned' => $totalReturned,
                'total_audited'  => $totalAudited,
                'progress_pct'   => $totalPart > 0 ? round(($totalReturned / $totalPart) * 100) : 0,
                'is_extra'       => false,
                'is_letter_mode' => !$hasAnyAreaNo,
            ];
        }

        // Hitung Card Tambahan: Seluruh SoItem pada event ini yang tidak ada di snapshot target excel
        $allEventSoItems = SoItem::where('so_event_id', $eventId)->get();
        $extraItems = [];
        foreach ($allEventSoItems as $so) {
            // Cek apakah soItem cocok dengan salah satu snapshot target
            $loc = Area::formatLocDisplay($so->area ?? null, $so->cell ?? null);
            $tc = $so->tracking_code;
            $ck = strtoupper(trim($so->code_part)) . '###' . strtoupper(trim($so->rack_no)) . '###' . strtoupper(trim($loc));
            $prk = strtoupper(trim($so->code_part)) . '###' . strtoupper(trim($so->rack_no));

            $matched = false;
            foreach ($groups as $aNo => $grpItems) {
                foreach ($grpItems as $rKey => $g) {
                    $gp = $g['primary'];
                    $gMerged = implode(', ', $g['loc_displays']);
                    $gTc = $gp->tracking_code ?: "{$gp->item_code}|{$gp->part_name}|{$gp->rack_no}|{$gMerged}";
                    $gCk = strtoupper(trim($gp->item_code)) . '###' . strtoupper(trim($gp->rack_no)) . '###' . strtoupper(trim($gMerged));
                    $gPrk = strtoupper(trim($gp->item_code)) . '###' . strtoupper(trim($gp->rack_no));

                    if ($tc === $gTc || $ck === $gCk || ($prk === $gPrk && str_contains(strtoupper((string)$so->cell), strtoupper((string)$gp->cell)))) {
                        $matched = true;
                        break 2;
                    }
                }
            }

            if (!$matched && !isset($mappedSoItemIds[$so->id])) {
                $extraItems[] = $so;
            }
        }

        if (!empty($extraItems)) {
            $extraTotal = count($extraItems);
            $extraReturned = 0;
            $extraAudited = 0;

            foreach ($extraItems as $ei) {
                if ($ei->current_step === 'RETURNED' || $ei->returned_at) {
                    $extraReturned++;
                }
                if (isset($auditedBySoItemId[$ei->id]) || ($ei->tracking_code && isset($auditedTracking[strtoupper(trim($ei->tracking_code))]))) {
                    $extraAudited++;
                }
            }

            $cards[] = [
                'area_no'        => 'TAMBAHAN',
                'letters'        => 'Di Luar List',
                'total_part'     => $extraTotal,
                'total_returned' => $extraReturned,
                'total_audited'  => $extraAudited,
                'progress_pct'   => $extraTotal > 0 ? round(($extraReturned / $extraTotal) * 100) : 0,
                'is_extra'       => true,
            ];
        }

        return $cards;
    }

    /**
     * Format durasi detik menjadi string terbaca: ".. menit .. detik" (atau ".. jam .. menit .. detik")
     */
    private function formatDuration(int $totalSeconds): string
    {
        $totalSeconds = abs($totalSeconds);

        if ($totalSeconds < 60) {
            return $totalSeconds . ' detik';
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        if ($hours > 0) {
            $parts = [$hours . ' jam', $minutes . ' menit'];
            if ($seconds > 0) {
                $parts[] = $seconds . ' detik';
            }
            return implode(' ', $parts);
        }

        if ($seconds > 0) {
            return $minutes . ' menit ' . $seconds . ' detik';
        }

        return $minutes . ' menit';
    }

    /**
     * Export Excel Hasil Monitoring List Part SO (Lengkap, Auto Filter, Auto Column Size)
     */
    public function exportPartsExcel(Request $request, $id)
    {
        $event = SoEvent::findOrFail($id);

        // Ambil data snapshot parts dari tabel so_event_parts berdasarkan eventId
        $snapshotParts = SoEventPart::where('so_event_id', $event->id)
            ->orderBy('rack_no', 'asc')
            ->get();

        // Ambil SoItem beserta relasi
        $soItems = SoItem::with([
            'histories' => function ($q) {
                $q->orderBy('id', 'asc');
            },
            'scaleRounds' => function ($q) {
                $q->orderBy('round_no', 'asc');
            },
            'scale'
        ])->where('so_event_id', $event->id)->get();

        // Index soItems
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

        // Ambil auditor records
        $auditorRecords = AuditorRecord::where('so_event_id', $event->id)->get();
        $auditorMap = [];
        foreach ($auditorRecords as $ar) {
            if ($ar->tracking_code) {
                $auditorMap['TRACKING###' . strtoupper(trim($ar->tracking_code))][] = $ar;
            }
            if ($ar->cell) {
                $cellKey = strtoupper(trim($ar->code_part ?? '')) . '###' . strtoupper(trim($ar->rack_no)) . '###' . strtoupper(trim($ar->cell));
                $auditorMap['CELL###' . $cellKey][] = $ar;
            }
            if ($ar->so_item_id) {
                $auditorMap['SOITEM###' . $ar->so_item_id][] = $ar;
            }
            $comboKey = strtoupper(trim($ar->code_part ?? '')) . '###' . strtoupper(trim($ar->rack_no));
            $auditorMap['COMBO###' . $comboKey][] = $ar;
        }

        // Kelompokkan snapshot parts
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

        $exportRows = [];
        $mappedSoItemIds = [];

        foreach ($groupedSnapshot as $group) {
            $item = $group['primary_item'];
            $itemsInGroup = $group['items'];

            $loc = implode(', ', $group['locations']);
            $cell = implode(', ', $group['cells']);
            $locDisplay = implode(', ', $group['loc_displays']);

            $trackingCode = $item->tracking_code ?: "{$item->item_code}|{$item->part_name}|{$item->rack_no}|{$locDisplay}";
            $comboKey = strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no)) . '###' . strtoupper(trim($locDisplay));

            $soItem = $soMapByTracking[$trackingCode] ?? ($soMapByCombo[$comboKey] ?? null);

            if (!$soItem) {
                $partRackKey = strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no));
                if (isset($soMapByPartRack[$partRackKey])) {
                    $candidates = $soMapByPartRack[$partRackKey];
                    foreach ($candidates as $cand) {
                        $candCell = strtoupper((string)$cand->cell);
                        $candTracking = strtoupper((string)$cand->tracking_code);
                        foreach ($group['loc_displays'] as $ld) {
                            if (str_contains($candCell, strtoupper($ld)) || str_contains($candTracking, strtoupper($ld))) {
                                $soItem = $cand;
                                break 2;
                            }
                        }
                        foreach ($group['cells'] as $c) {
                            if ($c !== '-' && str_contains($candCell, strtoupper($c))) {
                                $soItem = $cand;
                                break 2;
                            }
                        }
                    }
                    if (!$soItem && count($candidates) === 1) {
                        $soItem = $candidates[0];
                    }
                }
            }

            if ($soItem) {
                $mappedSoItemIds[$soItem->id] = true;
            }

            $audits = [];
            if ($soItem && isset($auditorMap['SOITEM###' . $soItem->id])) {
                $audits = $auditorMap['SOITEM###' . $soItem->id];
            } elseif (isset($auditorMap['TRACKING###' . strtoupper(trim($trackingCode))])) {
                $audits = $auditorMap['TRACKING###' . strtoupper(trim($trackingCode))];
            } else {
                foreach ($group['cells'] as $c) {
                    $cKey = strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no)) . '###' . strtoupper(trim($c));
                    if (isset($auditorMap['CELL###' . $cKey])) {
                        $audits = array_merge($audits, $auditorMap['CELL###' . $cKey]);
                    }
                }
                if (empty($audits) && isset($auditorMap['COMBO###' . strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no))])) {
                    $audits = $auditorMap['COMBO###' . strtoupper(trim($item->item_code)) . '###' . strtoupper(trim($item->rack_no))];
                }
            }

            $exportRows[] = $this->formatRowForExport(
                rackNo: $item->rack_no,
                areaLetter: $item->area_letter ?: $group['area_letter'],
                areaNo: $item->area_no ?: '-',
                cellDisplay: $locDisplay,
                itemCode: $item->item_code,
                partName: $item->part_name,
                isExtra: false,
                soItem: $soItem,
                audits: $audits
            );
        }

        // Part Tambahan
        foreach ($soItems as $extraSo) {
            if (!isset($mappedSoItemIds[$extraSo->id])) {
                $extraAudits = [];
                if (isset($auditorMap['SOITEM###' . $extraSo->id])) {
                    $extraAudits = $auditorMap['SOITEM###' . $extraSo->id];
                } elseif ($extraSo->tracking_code && isset($auditorMap['TRACKING###' . strtoupper(trim($extraSo->tracking_code))])) {
                    $extraAudits = $auditorMap['TRACKING###' . strtoupper(trim($extraSo->tracking_code))];
                }

                $extraLocDisplay = Area::formatLocDisplay($extraSo->area, $extraSo->cell);
                $extraLetter = Area::detectAreaLetter($extraSo->area, $extraSo->cell);

                $exportRows[] = $this->formatRowForExport(
                    rackNo: $extraSo->rack_no,
                    areaLetter: $extraLetter,
                    areaNo: 'TAMBAHAN',
                    cellDisplay: $extraLocDisplay,
                    itemCode: $extraSo->code_part,
                    partName: $extraSo->name_part,
                    isExtra: true,
                    soItem: $extraSo,
                    audits: $extraAudits
                );
            }
        }

        // Bangun Spreadsheet Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('List Part SO');

        // Nonaktifkan gridlines default? Biarkan aktif agar mudah dibaca
        $sheet->setShowGridLines(true);

        // Judul & Info Event
        $sheet->setCellValue('A1', 'LAPORAN HASIL MONITORING STOCK OPNAME');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1B2A47'));

        $sheet->setCellValue('A2', 'Nama Agenda / Event: ' . $event->event_name . ' (' . $event->event_code . ')');
        $sheet->setCellValue('A3', 'Periode: ' . ($event->start_date ? $event->start_date->format('d/m/Y') : '-') . ' s/d ' . ($event->end_date ? $event->end_date->format('d/m/Y') : '-') . ' | Status: ' . $event->status);
        $sheet->setCellValue('A4', 'Waktu Download: ' . now()->format('d/m/Y H:i:s') . ' | Total Baris Part: ' . count($exportRows));
        $sheet->getStyle('A2:A4')->getFont()->setSize(10)->setItalic(true);

        // Header Kolom Tabel (Mulai Baris 6)
        $headers = [
            'A' => 'No',
            'B' => 'Nomor Rak',
            'C' => 'Abjad Area',
            'D' => 'No Area',
            'E' => 'Lokasi / Cell',
            'F' => 'Kode Part',
            'G' => 'Nama Part',
            'H' => 'Status Tahap Terkini',
            'I' => 'Status Hitungan',
            'J' => 'Metode SO',
            'K' => 'Hasil Final SO',
            'L' => 'Pos 1 (Qty)',
            'M' => 'Pos 1 (Waktu)',
            'N' => 'Pos 2 (Qty)',
            'O' => 'Pos 2 (Waktu)',
            'P' => 'Durasi Siklus SO',
            'Q' => 'Kurir (NIK & Waktu)',
            'R' => 'Pengembalian (NIK & Waktu)',
            'S' => 'Status Audit',
            'T' => 'Audit Qty',
            'U' => 'Auditor (Nama & Waktu)',
            'V' => 'Ringkasan Riwayat Lengkap',
        ];

        $headerRow = 6;
        foreach ($headers as $col => $title) {
            $sheet->setCellValue($col . $headerRow, $title);
        }

        // Styling Baris Header
        $headerRange = 'A6:V6';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1B2A47');
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($headerRow)->setRowHeight(26);

        // Tulis Data Baris
        $currentRow = 7;
        foreach ($exportRows as $index => $row) {
            $sheet->setCellValue('A' . $currentRow, $index + 1);
            $sheet->setCellValue('B' . $currentRow, $row['rack_no']);
            $sheet->setCellValue('C' . $currentRow, $row['area_letter']);
            $sheet->setCellValue('D' . $currentRow, $row['area_no']);
            $sheet->setCellValue('E' . $currentRow, $row['cell_display']);
            $sheet->setCellValue('F' . $currentRow, $row['item_code']);
            $sheet->setCellValue('G' . $currentRow, $row['part_name']);
            $sheet->setCellValue('H' . $currentRow, $row['current_step']);
            $sheet->setCellValue('I' . $currentRow, $row['calc_status']);
            $sheet->setCellValue('J' . $currentRow, $row['method']);
            
            // Angka final count jika numerik
            if (is_numeric($row['final_count'])) {
                $sheet->setCellValueExplicit('K' . $currentRow, $row['final_count'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('K' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->setCellValue('K' . $currentRow, $row['final_count']);
            }

            // Pos 1
            if (is_numeric($row['pos1_count'])) {
                $sheet->setCellValueExplicit('L' . $currentRow, $row['pos1_count'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('L' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->setCellValue('L' . $currentRow, $row['pos1_count']);
            }
            $sheet->setCellValue('M' . $currentRow, $row['pos1_time']);

            // Pos 2
            if (is_numeric($row['pos2_count'])) {
                $sheet->setCellValueExplicit('N' . $currentRow, $row['pos2_count'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('N' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->setCellValue('N' . $currentRow, $row['pos2_count']);
            }
            $sheet->setCellValue('O' . $currentRow, $row['pos2_time']);

            $sheet->setCellValue('P' . $currentRow, $row['duration']);
            $sheet->setCellValue('Q' . $currentRow, $row['courier_info']);
            $sheet->setCellValue('R' . $currentRow, $row['return_info']);
            $sheet->setCellValue('S' . $currentRow, $row['audit_status']);

            if (is_numeric($row['audit_qty'])) {
                $sheet->setCellValueExplicit('T' . $currentRow, $row['audit_qty'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('T' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->setCellValue('T' . $currentRow, $row['audit_qty']);
            }

            $sheet->setCellValue('U' . $currentRow, $row['audit_info']);
            $sheet->setCellValue('V' . $currentRow, $row['history_text']);

            // Pewarnaan khusus jika NG / OK agar sangat informatif saat filter & sorting
            if ($row['calc_status'] === 'NG') {
                $sheet->getStyle('I' . $currentRow)->getFont()->setBold(true)->getColor()->setARGB('FFB00020');
                $sheet->getStyle('I' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFCE8E6');
            } elseif ($row['calc_status'] === 'OK') {
                $sheet->getStyle('I' . $currentRow)->getFont()->setBold(true)->getColor()->setARGB('FF137333');
                $sheet->getStyle('I' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6F4EA');
            }

            $currentRow++;
        }

        $lastDataRow = max($currentRow - 1, $headerRow);

        // Border tipis untuk seluruh sel tabel
        $dataTableRange = 'A6:V' . $lastDataRow;
        $sheet->getStyle($dataTableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD3D3D3'));

        // Alignment kolom
        $sheet->getStyle('A7:A' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B7:D' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E7:E' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F7:F' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H7:J' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('M7:M' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('O7:O' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('P7:P' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('S7:S' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Aktifkan Auto Filter bawaan Excel pada baris header (Memudahkan Sort & Filter Mana yang NG, Kurir, Pos 1, dll)
        $sheet->setAutoFilter('A6:V' . $lastDataRow);

        // Auto Size Kolom (Kolom A / No diberi lebar pas agar tidak melebar karena judul A1-A4)
        $sheet->getColumnDimension('A')->setAutoSize(false);
        $sheet->getColumnDimension('A')->setWidth(8);

        foreach (range('B', 'V') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Stream Download File Excel
        $cleanEventCode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $event->event_code);
        $fileName = 'List_Part_SO_' . $cleanEventCode . '_' . date('Ymd_His') . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Helper pemformat data baris untuk Export Excel
     */
    private function formatRowForExport(
        string $rackNo,
        string $areaLetter,
        string $areaNo,
        string $cellDisplay,
        string $itemCode,
        string $partName,
        bool $isExtra,
        ?SoItem $soItem,
        array $audits
    ): array {
        $stepLabel = 'BELUM_SO';
        $calcStatus = 'BELUM DIHITUNG';
        $methods = [];
        $isNg = false;

        $pos1Count = '-';
        $pos1Time = '-';
        $pos2Count = '-';
        $pos2Time = '-';

        $courierInfo = '-';
        $returnInfo = '-';
        $durationFormatted = '-';
        $historyList = [];

        if ($soItem) {
            $stepLabel = $soItem->current_step;
            if ($soItem->current_step === 'MISMATCH_NG') {
                $isNg = true;
            }

            // Kurir
            if ($soItem->courier_nik) {
                $cTime = $soItem->courier_at ? $soItem->courier_at->format('d/m/Y H:i') : '-';
                $courierInfo = 'NIK ' . $soItem->courier_nik . ' (' . $cTime . ')';
                $historyList[] = '[Kurir] NIK ' . $soItem->courier_nik . ' (' . $cTime . ')';
            }

            // Hitung durasi siklus SO (kurir -> return)
            $start = $soItem->courier_at ?: $soItem->created_at;
            $end = $soItem->returned_at;
            if ($start && $end) {
                $sec = abs($start->diffInSeconds($end, false));
                $durationFormatted = $this->formatDuration($sec);
            } elseif ($start) {
                $sec = abs($start->diffInSeconds(now(), false));
                $durationFormatted = $this->formatDuration($sec) . ' (Dalam Proses)';
            }

            // Riwayat Perhitungan
            if ($soItem->histories->isNotEmpty()) {
                foreach ($soItem->histories as $h) {
                    $timeStr = $h->created_at ? $h->created_at->format('d/m/Y H:i') : '';
                    $nikStr = $h->member_nik ?: '-';

                    if ($h->count_type === 'POS1') {
                        $methods['MANUAL'] = true;
                        $pos1Count = $h->result_count;
                        $pos1Time = $timeStr ?: ($soItem->pos1_at ? $soItem->pos1_at->format('d/m/Y H:i') : '-');
                        if ($h->status === 'NG') $isNg = true;
                        $historyList[] = '[Pos 1] Qty: ' . ($h->result_count ?? '-') . ' (' . ($h->status ?: 'OK') . ') NIK ' . $nikStr . ($timeStr ? ' ' . $timeStr : '');
                    } elseif ($h->count_type === 'POS2') {
                        $methods['MANUAL'] = true;
                        $pos2Count = $h->result_count;
                        $pos2Time = $timeStr ?: ($soItem->pos2_at ? $soItem->pos2_at->format('d/m/Y H:i') : '-');
                        if ($h->status === 'NG') $isNg = true;
                        $historyList[] = '[Pos 2] Qty: ' . ($h->result_count ?? '-') . ' (' . ($h->status ?: 'OK') . ') NIK ' . $nikStr . ($timeStr ? ' ' . $timeStr : '');
                    } elseif ($h->count_type === 'SCALE') {
                        $methods['SCALE'] = true;
                        if ($h->status === 'NG') $isNg = true;
                        $scaleName = $soItem->scale_number ?: ($h->scale ? $h->scale->scale_number : 'Scale');
                        $res = $h->result_count ?? ($h->weight_with_sample !== null ? (int)$h->weight_with_sample : '-');
                        $moe = $h->error_margin_percent !== null ? ' MoE:' . $h->error_margin_percent . '%' : '';
                        $historyList[] = '[Scale ' . $scaleName . '] Qty: ' . $res . $moe . ' (' . ($h->status ?: 'OK') . ') NIK ' . $nikStr . ($timeStr ? ' ' . $timeStr : '');
                    }
                }
            } else {
                if ($soItem->pos1_count !== null) {
                    $methods['MANUAL'] = true;
                    $pos1Count = $soItem->pos1_count;
                    $pos1Time = $soItem->pos1_at ? $soItem->pos1_at->format('d/m/Y H:i') : '-';
                }
                if ($soItem->pos2_count !== null) {
                    $methods['MANUAL'] = true;
                    $pos2Count = $soItem->pos2_count;
                    $pos2Time = $soItem->pos2_at ? $soItem->pos2_at->format('d/m/Y H:i') : '-';
                }
                if ($soItem->process_type === 'SCALE') {
                    $methods['SCALE'] = true;
                    $scaleNo = $soItem->scale_number ?: '-';
                    $historyList[] = '[Scale ' . $scaleNo . '] Qty: ' . ($soItem->final_count ?? '-');
                } elseif ($soItem->process_type === 'MANUAL') {
                    $methods['MANUAL'] = true;
                    $historyList[] = '[Manual] Pos 1: ' . ($pos1Count) . ' | Pos 2: ' . ($pos2Count);
                }
            }

            // Pengembalian (Return)
            if ($soItem->current_step === 'RETURNED' || $soItem->returned_at) {
                $retTime = $soItem->returned_at ? $soItem->returned_at->format('d/m/Y H:i') : '-';
                $returnInfo = 'NIK ' . ($soItem->return_nik ?? '-') . ' (' . $retTime . ')';
                $historyList[] = '[Return] NIK ' . ($soItem->return_nik ?? '-') . ' (' . $retTime . ')';
            }

            // Status Hitungan
            if ($isNg) {
                $calcStatus = 'NG';
            } elseif ($soItem->final_count !== null || in_array($soItem->current_step, ['READY_TO_RETURN', 'RETURNED', 'COUNTED'])) {
                $calcStatus = 'OK';
            }
        }

        // Audit
        $auditStatus = 'BELUM DIAUDIT';
        $auditQty = '-';
        $auditInfo = '-';
        if (!empty($audits)) {
            $auditStatus = 'TERAUDIT';
            $auditQtys = [];
            $auditInfos = [];
            foreach ($audits as $idx => $a) {
                if ($a->method === 'SCALE') $methods['SCALE'] = true;
                elseif ($a->method === 'MANUAL') $methods['MANUAL'] = true;

                $auditQtys[] = $a->audited_count;
                $aTime = $a->created_at ? $a->created_at->format('d/m/Y H:i') : '-';
                $auditInfos[] = ($a->auditor_name ?: 'Auditor') . ' (' . ($a->audit_status ?: 'OK') . ' &bull; ' . $aTime . ')';
                $historyList[] = '[Audit #' . ($idx + 1) . '] ' . $a->audited_count . ' pcs (' . ($a->audit_status ?: 'OK') . ') ' . ($a->auditor_name ?? '-') . ' ' . $aTime;
            }
            $auditQty = implode(', ', $auditQtys);
            $auditInfo = implode(' | ', $auditInfos);
        }

        // Metode
        if ($soItem && $soItem->process_type) {
            $methods[$soItem->process_type] = true;
        }
        $mKeys = array_keys($methods);
        $methodDisplay = '-';
        if (in_array('MANUAL', $mKeys) && in_array('SCALE', $mKeys)) {
            $methodDisplay = 'MANUAL & SCALE';
        } elseif (in_array('SCALE', $mKeys)) {
            $methodDisplay = 'SCALE';
        } elseif (in_array('MANUAL', $mKeys)) {
            $methodDisplay = 'MANUAL';
        }

        return [
            'rack_no'       => $rackNo,
            'area_letter'   => $areaLetter,
            'area_no'       => $areaNo,
            'cell_display'  => $cellDisplay,
            'part_type'     => $isExtra ? 'Part Tambahan' : 'Snapshot Target',
            'item_code'     => $itemCode,
            'part_name'     => $partName,
            'current_step'  => $stepLabel,
            'calc_status'   => $calcStatus,
            'method'        => $methodDisplay,
            'final_count'   => ($soItem && $soItem->final_count !== null) ? $soItem->final_count : '-',
            'pos1_count'    => $pos1Count,
            'pos1_time'     => $pos1Time,
            'pos2_count'    => $pos2Count,
            'pos2_time'     => $pos2Time,
            'duration'      => $durationFormatted,
            'courier_info'  => $courierInfo,
            'return_info'   => $returnInfo,
            'audit_status'  => $auditStatus,
            'audit_qty'     => $auditQty,
            'audit_info'    => $auditInfo,
            'history_text'  => !empty($historyList) ? implode(' -> ', $historyList) : '-',
        ];
    }
}
