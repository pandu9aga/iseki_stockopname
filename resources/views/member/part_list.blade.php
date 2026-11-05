@extends('layouts.main')

@section('style')
<link rel="stylesheet" href="{{ asset('assets/css/datatables.min.css') }}">
<style>
    .parts-header-card {
        background: linear-gradient(135deg, #1b2a47 0%, #283e68 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
    }
    .stat-badge {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        padding: 0.6rem 1rem;
    }
    .badge-double-cell {
        background: #f8f9fa;
        color: #d63384;
        border: 1px dashed #d63384;
        font-size: 0.72rem;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 600;
    }
    .summary-timeline {
        line-height: 1.45;
        font-size: 0.8rem;
    }
    .stage-step-group {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8f9fa;
        padding: 4px 8px;
        border-radius: 20px;
        border: 1px solid #e9ecef;
    }
    .stage-step-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        transition: all 0.2s ease;
    }
    .stage-step-icon.done {
        background-color: #28a745;
        color: #ffffff;
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.35);
    }
    .stage-step-icon.undone {
        background-color: #e9ecef;
        color: #adb5bd;
    }
    .stage-connector {
        width: 8px;
        height: 2px;
        background-color: #dee2e6;
    }
    .stage-connector.done {
        background-color: #28a745;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">

        <!-- Top Header Card -->
        <div class="parts-header-card shadow-sm mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h3 class="fw-bold mb-1">
                        <i class="fas fa-boxes text-warning me-2"></i> Monitoring &amp; List Semua Part
                    </h3>
                    <p class="mb-0 text-white-50 small">
                        Daftar seluruh part dari snapshot master stock opname (termasuk cell ganda) &bull; Status tahap &bull; Riwayat perhitungan lengkap
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="input-group input-group-sm" style="width: auto;">
                        <span class="input-group-text bg-white border-0 fw-bold text-dark">
                            <i class="fas fa-calendar-alt text-primary me-1"></i> Jadwal SO:
                        </span>
                        <select class="form-select form-select-sm fw-bold border-0 text-primary shadow-sm" id="filterSoEvent" style="min-width: 200px;">
                            @forelse($events as $ev)
                                <option value="{{ $ev->id }}" {{ $selectedEventId == $ev->id ? 'selected' : '' }}>
                                    {{ $ev->event_name }} ({{ $ev->status }})
                                </option>
                            @empty
                                <option value="">(Belum ada jadwal SO)</option>
                            @endforelse
                        </select>
                    </div>
                    <a href="{{ route('member.role.redirect') }}" class="btn btn-primary btn-sm fw-bold shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> Ruang Kerja
                    </a>
                </div>
            </div>

            <!-- Metric Summary Badges -->
            <div class="row g-2 mt-3">
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Total Item Masuk SO</small>
                        <span class="fs-4 fw-bold text-white" id="badgeTotalSo">{{ number_format($totalStockopname) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Siap Dihitung</small>
                        <span class="fs-4 fw-bold text-warning" id="badgeReadyCount">{{ number_format($totalReadyToCount) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Selesai Dihitung</small>
                        <span class="fs-4 fw-bold text-success" id="badgeCounted">{{ number_format($totalCounted) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Telah Diaudit</small>
                        <span class="fs-4 fw-bold text-info" id="badgeAudited">{{ number_format($totalAudited) }}</span>
                    </div>
                </div>
            </div>
        </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Siap Dihitung</small>
                        <span class="fs-4 fw-bold text-warning">{{ number_format($totalReadyToCount) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Selesai Dihitung</small>
                        <span class="fs-4 fw-bold text-success">{{ number_format($totalCounted) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Telah Diaudit</small>
                        <span class="fs-4 fw-bold text-info">{{ number_format($totalAudited) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card DataTables -->
        <div class="card shadow-sm border-0" style="border-radius:14px;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fas fa-table me-2 text-primary"></i>Katalog Master Part &amp; Status Tahapan
                </h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefreshTable">
                    <i class="fas fa-sync-alt me-1"></i> Refresh Data
                </button>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="partsDataTable" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Rak</th>
                                <th>Lokasi / Cell</th>
                                <th>Part Code &amp; Name</th>
                                <th class="text-center" style="min-width: 175px;">Tahap Terkini</th>
                                <th class="text-center">Metode SO</th>
                                <th class="text-center">Hasil Final SO</th>
                                <th>Riwayat Perhitungan Tiap Tahap</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loaded via DataTables AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('assets/js/plugin/datatables/datatables.min.js') }}"></script>
<script>
$(document).ready(function() {
    const table = $('#partsDataTable').DataTable({
        processing: true,
        serverSide: false, // Client-side pagination/filtering atas kumpulan row yang digenerate
        ajax: {
            url: "{{ route('member.parts.data') }}",
            type: "GET",
            data: function(d) {
                d.event_id = $('#filterSoEvent').val();
            },
            error: function(xhr, error, code) {
                console.error("DataTables load error:", error, code);
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { 
                data: 'rack_no', 
                name: 'rack_no', 
                render: function(data) {
                    return `<span class="fw-bold font-monospace text-primary">${data || '-'}</span>`;
                }
            },
            { 
                data: 'loc_display', 
                name: 'loc_display', 
                render: function(data, type, row) {
                    let badgeCell = '';
                    if (row.has_double_cell && row.pair_info) {
                        badgeCell = `<div class="mt-1"><span class="badge-double-cell"><i class="fas fa-layer-group me-1"></i>${row.pair_info}</span></div>`;
                    }
                    return `<div class="fw-semibold">${data}</div>${badgeCell}`;
                }
            },
            { 
                data: 'part_name', 
                name: 'part_name', 
                render: function(data, type, row) {
                    return `
                        <div class="fw-bold text-dark font-monospace">${row.item_code}</div>
                        <div class="text-muted small text-truncate" style="max-width:240px;" title="${data}">${data}</div>
                    `;
                }
            },
            { 
                data: 'current_step', 
                name: 'current_step', 
                className: 'text-center',
                render: function(data, type, row) {
                    const cDone = !!row.has_courier;
                    const kDone = !!row.has_count;
                    const rDone = !!row.has_return;
                    const aDone = !!row.has_audit;

                    const countIcon = (row.process_type === 'SCALE') ? 'fa-balance-scale' : 'fa-calculator';

                    return `
                        <div class="stage-step-group" title="Kurir &rarr; Hitung SO &rarr; Return &rarr; Audit">
                            <span class="stage-step-icon ${cDone ? 'done' : 'undone'}" title="Kurir: ${cDone ? 'Sudah Selesai' : 'Belum'}">
                                <i class="fas fa-truck"></i>
                            </span>
                            <span class="stage-connector ${kDone ? 'done' : ''}"></span>
                            <span class="stage-step-icon ${kDone ? 'done' : 'undone'}" title="Hitung (${row.process_type || 'SO'}): ${kDone ? 'Sudah Selesai' : 'Belum'}">
                                <i class="fas ${countIcon}"></i>
                            </span>
                            <span class="stage-connector ${rDone ? 'done' : ''}"></span>
                            <span class="stage-step-icon ${rDone ? 'done' : 'undone'}" title="Return ke Rak: ${rDone ? 'Sudah Selesai' : 'Belum'}">
                                <i class="fas fa-undo-alt"></i>
                            </span>
                            <span class="stage-connector ${aDone ? 'done' : ''}"></span>
                            <span class="stage-step-icon ${aDone ? 'done' : 'undone'}" title="Audit Fisik: ${aDone ? 'Sudah Selesai' : 'Belum'}">
                                <i class="fas fa-clipboard-check"></i>
                            </span>
                        </div>
                    `;
                }
            },
            { 
                data: 'process_type', 
                name: 'process_type', 
                className: 'text-center',
                render: function(data) {
                    if (data === 'SCALE') {
                        return '<span class="badge bg-primary-subtle text-primary border border-primary small"><i class="fas fa-balance-scale me-1"></i>Timbangan</span>';
                    } else if (data === 'MANUAL') {
                        return '<span class="badge bg-secondary-subtle text-secondary border border-secondary small"><i class="fas fa-hand-paper me-1"></i>Manual</span>';
                    }
                    return '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'final_count', 
                name: 'final_count', 
                className: 'text-center',
                render: function(data) {
                    if (data && data !== '-') {
                        return `<strong class="text-success fs-6">${data}</strong>`;
                    }
                    return `<span class="text-muted">-</span>`;
                }
            },
            { 
                data: 'step_summary', 
                name: 'step_summary',
                render: function(data) {
                    return `<div class="summary-timeline">${data}</div>`;
                }
            }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        language: {
            search: "Cari Part / Rak / Cell:",
            searchPlaceholder: "Ketik kode part, rak, loc...",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Tidak ada data yang sesuai",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data part",
            infoEmpty: "Menampilkan 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    $('#btnRefreshTable').on('click', function() {
        table.ajax.reload();
    });

    $('#filterSoEvent').on('change', function() {
        table.ajax.reload();
    });
});
</script>
@endsection
