@extends('layouts.main')

@section('style')
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
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        user-select: none;
    }
    .stat-badge:hover {
        transform: translateY(-2px);
        background: rgba(255, 255, 255, 0.22);
        border-color: rgba(255, 255, 255, 0.4);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    .stat-badge.active-filter {
        background: #ffffff !important;
        border-color: #ffc107 !important;
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.45) !important;
    }
    .stat-badge.active-filter small {
        color: #495057 !important;
        font-weight: 700 !important;
    }
    .stat-badge.active-filter span {
        filter: brightness(0.9);
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
        line-height: 1.25;
        font-size: 0.74rem;
    }
    .stage-step-group {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        background: #f8f9fa;
        padding: 2px 5px;
        border-radius: 14px;
        border: 1px solid #e9ecef;
    }
    .stage-step-icon {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.68rem;
        transition: all 0.2s ease;
    }
    .stage-step-icon.done {
        background-color: #28a745;
        color: #ffffff;
        box-shadow: 0 1px 3px rgba(40, 167, 69, 0.35);
    }
    .stage-step-icon.ng {
        background-color: #dc3545;
        color: #ffffff;
        box-shadow: 0 1px 3px rgba(220, 53, 69, 0.45);
    }
    .stage-step-icon.undone {
        background-color: #e9ecef;
        color: #adb5bd;
    }
    .stage-connector {
        width: 5px;
        height: 2px;
        background-color: #dee2e6;
    }
    /* Compact Table Styles */
    #partsDataTable {
        font-size: 0.77rem !important;
    }
    #partsDataTable th, #partsDataTable td {
        padding: 4px 6px !important;
        vertical-align: middle;
    }
    #partsDataTable .badge {
        font-size: 0.68rem !important;
        padding: 2px 4px !important;
    }
    #partsDataTable .badge-double-cell {
        font-size: 0.66rem !important;
        padding: 1px 4px !important;
    }
    .stage-connector.done {
        background-color: #28a745;
    }
    /* Area Cards - Compact & Balanced */
    .area-card-box {
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        border: 1.5px solid #dee2e6;
        border-radius: 8px;
    }
    .area-card-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.08) !important;
        border-color: #0d6efd !important;
    }
    .area-card-box.active-area {
        border-color: #0d6efd !important;
        background: #f0f7ff !important;
        box-shadow: 0 3px 8px rgba(13,110,253,0.2) !important;
    }
    .area-card-stat {
        font-size: 0.74rem;
        line-height: 1.3;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3" style="margin-top: 75px;">
    <div class="page-inner px-1 pt-2">

        <!-- Top Header Card -->
        <div class="parts-header-card shadow-sm mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge {{ $event->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' }} px-3 py-1 rounded-pill">
                            {{ $event->status }}
                        </span>
                        <span class="text-white-50 font-monospace small">#{{ $event->event_code }}</span>
                    </div>
                    <h3 class="fw-bold mb-1">
                        <i class="fas fa-boxes text-warning me-2"></i> {{ $event->event_name }}
                    </h3>
                    <p class="mb-0 text-white-50 small">
                        Daftar seluruh part dari snapshot master SO &bull; Status tahap &bull; Riwayat perhitungan lengkap
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="input-group input-group-sm" style="width: auto;">
                        <span class="input-group-text bg-white border-0 fw-bold text-dark">
                            <i class="fas fa-exchange-alt text-primary me-1"></i> Ganti Jadwal:
                        </span>
                        <select class="form-select form-select-sm fw-bold border-0 text-primary shadow-sm" id="selectSwitchEvent" style="min-width: 200px;">
                            @foreach($events as $ev)
                                <option value="{{ $ev->id }}" {{ $event->id == $ev->id ? 'selected' : '' }}>
                                    {{ $ev->event_name }} ({{ $ev->status }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('member.parts.index') }}" class="btn btn-outline-light btn-sm fw-bold">
                        <i class="fas fa-th-large me-1"></i> Daftar Jadwal
                    </a>
                    <a href="{{ route('member.role.redirect') }}" class="btn btn-primary btn-sm fw-bold shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> Ruang Kerja
                    </a>
                </div>
            </div>

            <!-- Metric Summary Badges -->
            <div class="row g-2 mt-3 row-cols-2 row-cols-md-4 row-cols-lg-7">
                <div class="col">
                    <div class="stat-badge text-center" data-filter-type="all" title="Klik untuk menampilkan semua data">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Total Part</small>
                        <span class="fs-4 fw-bold text-white">{{ number_format($event->parts_count) }}</span>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-badge text-center" data-filter-type="so_in" title="Klik untuk filter: Item Masuk SO">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Item Masuk SO</small>
                        <span class="fs-4 fw-bold text-warning">{{ number_format($totalStockopname) }}</span>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-badge text-center" data-filter-type="counted" title="Klik untuk filter: Selesai Dihitung">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Selesai Dihitung</small>
                        <span class="fs-4 fw-bold text-success">{{ number_format($totalCounted) }}</span>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-badge text-center" data-filter-type="uncounted" title="Klik untuk filter: Belum Dihitung">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Belum Dihitung</small>
                        <span class="fs-4 fw-bold text-warning-emphasis" style="color: #ffc107 !important;">{{ number_format($totalUncounted ?? 0) }}</span>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-badge text-center" data-filter-type="ng" title="Klik untuk filter: Hitungan NG">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Hitungan NG</small>
                        <span class="fs-4 fw-bold text-danger">{{ number_format($totalNg ?? 0) }}</span>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-badge text-center" data-filter-type="audited" title="Klik untuk filter: Telah Diaudit">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Telah Diaudit</small>
                        <span class="fs-4 fw-bold text-info">{{ number_format($totalAudited) }}</span>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-badge text-center" data-filter-type="audit_ng" title="Klik untuk filter: Audit NG (Beda Hitungan)">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Audit NG</small>
                        <span class="fs-4 fw-bold text-danger-emphasis" style="color: #ff6b6b !important;">{{ number_format($totalAuditNg ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($areaCards) && count($areaCards) > 0)
        <!-- Area Grid Cards -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-th-large text-primary me-2"></i>Pilih Area Kerja (Klik Card untuk Memfilter List Part)
                </h6>
                <button type="button" class="btn btn-xs btn-outline-secondary btn-reset-area d-none" id="btnResetAreaFilter">
                    <i class="fas fa-times me-1"></i> Tampilkan Semua Area
                </button>
            </div>
                        <div class="row g-1 mb-2" id="areaCardsContainer">
                @foreach($areaCards as $card)
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl" style="min-width: 140px;">
                    <div class="card shadow-sm border h-100 area-card-box p-2 bg-white {{ !empty($card['is_extra']) ? 'border-warning-subtle' : '' }}" data-area-no="{{ $card['area_no'] }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="text-truncate">
                                @if(!empty($card['is_extra']))
                                <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1 fw-bold" style="font-size:0.72rem;">
                                    <i class="fas fa-plus-circle text-warning me-1"></i>Part Tambahan
                                </span>
                                @else
                                <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1 fw-bold" style="font-size:0.75rem;">
                                    <i class="fas fa-map-marker-alt me-1"></i>{{ $card['card_title'] ?? ('Area ' . $card['area_no']) }}
                                </span>
                                @if(!empty($card['letters']))
                                <span class="badge bg-light text-secondary border px-1 py-0 ms-1" style="font-size:0.68rem;" title="Area Letter">{{ $card['letters'] }}</span>
                                @endif
                                @endif
                            </div>
                            <span class="badge {{ $card['progress_pct'] == 100 ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill font-monospace px-2 py-1" style="font-size:0.72rem;">
                                {{ $card['progress_pct'] }}%
                            </span>
                        </div>
                        
                        <div class="area-card-stat mt-1 pt-1 border-top">
                            <div class="d-flex justify-content-between text-muted" style="font-size:0.75rem;">
                                <span>Part: <strong class="text-dark font-monospace">{{ number_format($card['total_part']) }}</strong></span>
                                <span class="ms-1">Done: <strong class="text-success font-monospace">{{ number_format($card['total_returned']) }}</strong></span>
                                <span class="ms-1">Wait: <strong class="text-warning font-monospace">{{ number_format($card['total_waiting'] ?? 0) }}</strong></span>
                                <span class="ms-1">Aud: <strong class="text-info font-monospace">{{ number_format($card['total_audited']) }}</strong></span>
                            </div>
                            <div class="progress mt-2" style="height: 4px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $card['progress_pct'] }}%" aria-valuenow="{{ $card['progress_pct'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Main Card DataTables -->
        <div class="card shadow-sm border-0" style="border-radius:14px;">
            <div class="card-header bg-white py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <div>
                        <h6 class="card-title mb-0 fw-bold">
                            <i class="fas fa-table me-2 text-primary"></i>Katalog Master Part &amp; Status Tahapan
                        </h6>
                        <div id="activeAreaBadge" class="mt-1 d-none">
                            <span class="badge bg-primary px-2 py-1">
                                <i class="fas fa-filter me-1"></i> Memfilter: Area <span id="activeAreaText">-</span>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('parts.export-excel', $event->id) }}" class="btn btn-sm btn-success shadow-sm fw-bold" id="btnExportExcel" title="Download Excel Lengkap dengan Auto Filter">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" id="btnRefreshTable">
                            <i class="fas fa-sync-alt me-1"></i> Refresh Data
                        </button>
                    </div>
                </div>

                <!-- Filter Tahapan Proses SO -->
                <div class="row g-2 mt-3 pt-3 border-top">
                    <div class="col-12 col-md-auto d-flex align-items-center">
                        <span class="small fw-bold text-muted me-2"><i class="fas fa-filter text-primary me-1"></i> Filter Proses:</span>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select class="form-select form-select-sm" id="filterCourier">
                            <option value="">Kurir: Semua</option>
                            <option value="done">Kurir: Sudah (Hijau)</option>
                            <option value="undone">Kurir: Belum (Abu-abu)</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select class="form-select form-select-sm" id="filterCount">
                            <option value="">Hitung: Semua</option>
                            <option value="done">Hitung: Sudah (Hijau)</option>
                            <option value="undone">Hitung: Belum (Abu-abu)</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select class="form-select form-select-sm" id="filterMethod">
                            <option value="">Metode: Semua</option>
                            <option value="manual">Metode: Manual</option>
                            <option value="scale">Metode: Timbangan</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select class="form-select form-select-sm" id="filterReturn">
                            <option value="">Return: Semua</option>
                            <option value="done">Return: Sudah (Hijau)</option>
                            <option value="undone">Return: Belum (Abu-abu)</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select class="form-select form-select-sm" id="filterAudit">
                            <option value="">Audit: Semua</option>
                            <option value="done">Audit: Sudah Diaudit</option>
                            <option value="ng">Audit: NG (Beda Hitungan)</option>
                            <option value="ok">Audit: OK (Sesuai)</option>
                            <option value="undone">Audit: Belum Diaudit</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select class="form-select form-select-sm border-danger text-danger fw-bold" id="filterStatusNg">
                            <option value="">Status: Semua</option>
                            <option value="ng">Hanya Data NG (Beda Hitungan)</option>
                            <option value="ok">Hanya Data OK (Sudah Dihitung &amp; Sesuai)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" class="btn btn-sm btn-light border text-muted w-100" id="btnResetFilter">
                            <i class="fas fa-times me-1"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 w-100 table-bordered" id="partsDataTable">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th class="text-center" style="width: 35px;">No</th>
                                <th style="width: 75px;">Rak</th>
                                <th style="width: 85px;" class="text-center">Area</th>
                                <th style="width: 95px;">Lokasi / Cell</th>
                                <th style="min-width: 170px;">Part Code &amp; Name</th>
                                <th class="text-center" style="width: 135px;">Tahap Terkini</th>
                                <th class="text-center" style="width: 95px;">Waktu Siklus</th>
                                <th class="text-center" style="width: 95px;">Metode SO</th>
                                <th class="text-center" style="width: 90px;">Hasil Final</th>
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
    const eventId = "{{ $event->id }}";
    let selectedAreaNo = null;

    function buildDataUrl() {
        let url = "{{ route('parts.data', ['id' => $event->id]) }}";
        if (selectedAreaNo !== null && selectedAreaNo !== '') {
            url += "?area_no=" + encodeURIComponent(selectedAreaNo);
        }
        return url;
    }

    const table = $('#partsDataTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: buildDataUrl(),
            type: "GET",
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
                data: 'area_no',
                name: 'area_no',
                className: 'text-center',
                render: function(data, type, row) {
                    if (!data || data === '-') {
                        if (row.area_letter) {
                            return `<span class="badge bg-light text-secondary border px-2 py-1">${row.area_letter}</span>`;
                        }
                        return '<span class="text-muted">-</span>';
                    }
                    if (data === 'TAMBAHAN') {
                        return '<span class="badge bg-warning-subtle text-dark border border-warning px-1 py-1" style="font-size:0.65rem;">Tambahan</span>';
                    }
                    const letterBadge = row.area_letter ? `<span class="badge bg-light text-secondary border ms-1">${row.area_letter}</span>` : '';
                    return `<span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1 fw-bold">Area ${data}</span>${letterBadge}`;
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
                    const aIsNg = !!row.is_audit_ng;

                    const countIcon = (row.process_type === 'SCALE') ? 'fa-balance-scale' : 'fa-calculator';
                    const auditClass = aDone ? (aIsNg ? 'ng' : 'done') : 'undone';
                    const auditTitle = aDone ? (aIsNg ? 'Audit Fisik: Selesai (NG - Beda Hitungan)' : 'Audit Fisik: Selesai (OK - Sesuai)') : 'Audit Fisik: Belum';

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
                            <span class="stage-step-icon ${auditClass}" title="${auditTitle}">
                                <i class="fas fa-clipboard-check"></i>
                            </span>
                        </div>
                    `;
                }
            },
            {
                data: 'duration',
                name: 'duration_seconds',
                className: 'text-center font-monospace fw-semibold',
                render: function(data, type, row) {
                    if (data && data !== '-') {
                        if (row.has_return) {
                            return `<span class="badge bg-success-subtle text-success border border-success px-2 py-1"><i class="fas fa-check-circle me-1"></i>${data}</span>`;
                        }
                        return data;
                    }
                    return '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'process_type', 
                name: 'process_type', 
                className: 'text-center',
                render: function(data) {
                    if (!data || data === '-') {
                        return '<span class="badge bg-light text-muted border">Belum Dihitung</span>';
                    }
                    if (data.indexOf('MANUAL') !== -1 && data.indexOf('SCALE') !== -1) {
                        return `
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary small me-1"><i class="fas fa-hand-paper me-1"></i>Manual</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary small"><i class="fas fa-balance-scale me-1"></i>Timbangan</span>
                        `;
                    } else if (data.indexOf('SCALE') !== -1) {
                        return '<span class="badge bg-primary-subtle text-primary border border-primary small"><i class="fas fa-balance-scale me-1"></i>Timbangan</span>';
                    } else if (data.indexOf('MANUAL') !== -1) {
                        return '<span class="badge bg-secondary-subtle text-secondary border border-secondary small"><i class="fas fa-hand-paper me-1"></i>Manual</span>';
                    }
                    return `<span class="badge bg-info-subtle text-info border border-info small">${data}</span>`;
                }
            },
            { 
                data: 'final_count', 
                name: 'final_count', 
                className: 'text-center',
                render: function(data) {
                    if (data && data !== '-') {
                        return data;
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
        table.ajax.url(buildDataUrl()).load();
    });

    // Klik Card Area untuk filter list part
    $('.area-card-box').on('click', function() {
        const areaNo = $(this).data('area-no');

        if (selectedAreaNo === areaNo) {
            // Jika diklik lagi card yang sama, batalkan filter (tampilkan semua)
            selectedAreaNo = null;
            $('.area-card-box').removeClass('active-area');
            $('#activeAreaBadge').addClass('d-none');
            $('#btnResetAreaFilter').addClass('d-none');
        } else {
            selectedAreaNo = areaNo;
            $('.area-card-box').removeClass('active-area');
            $(this).addClass('active-area');
            $('#activeAreaText').text(areaNo);
            $('#activeAreaBadge').removeClass('d-none');
            $('#btnResetAreaFilter').removeClass('d-none');
        }

        table.ajax.url(buildDataUrl()).load();

        // Scroll smooth ke tabel
        $('html, body').animate({
            scrollTop: $('#partsDataTable').offset().top - 120
        }, 400);
    });

    // Reset Filter Area
    $('#btnResetAreaFilter').on('click', function() {
        selectedAreaNo = null;
        $('.area-card-box').removeClass('active-area');
        $('#activeAreaBadge').addClass('d-none');
        $(this).addClass('d-none');
        table.ajax.url(buildDataUrl()).load();
    });

    // Filter Metric Cards (Klik Stat Badge)
    let activeMetricFilter = null;
    $('.stat-badge[data-filter-type]').on('click', function() {
        const filterType = $(this).data('filter-type');
        if (activeMetricFilter === filterType || filterType === 'all') {
            activeMetricFilter = null;
            $('.stat-badge').removeClass('active-filter');
        } else {
            activeMetricFilter = filterType;
            $('.stat-badge').removeClass('active-filter');
            $(this).addClass('active-filter');
        }
        table.draw();
    });

    // Custom filtering DataTables berdasarkan status boolean tahapan
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
        if (!rowData) return true;

        // Metric Badge Card Filter
        if (activeMetricFilter) {
            if (activeMetricFilter === 'so_in' && !rowData.has_courier && !rowData.so_item_id) return false;
            if (activeMetricFilter === 'counted' && !rowData.has_count) return false;
            if (activeMetricFilter === 'uncounted' && rowData.has_count) return false;
            if (activeMetricFilter === 'ng' && !rowData.is_ng) return false;
            if (activeMetricFilter === 'audited' && !rowData.has_audit) return false;
            if (activeMetricFilter === 'audit_ng' && (!rowData.has_audit || !rowData.is_audit_ng)) return false;
        }

        const filterC = $('#filterCourier').val();
        const filterK = $('#filterCount').val();
        const filterR = $('#filterReturn').val();
        const filterA = $('#filterAudit').val();

        // 1. Kurir
        if (filterC === 'done' && !rowData.has_courier) return false;
        if (filterC === 'undone' && rowData.has_courier) return false;

        // 2. Hitung (Scale/Manual)
        if (filterK === 'done' && !rowData.has_count) return false;
        if (filterK === 'undone' && rowData.has_count) return false;

        // 3. Return
        if (filterR === 'done' && !rowData.has_return) return false;
        if (filterR === 'undone' && rowData.has_return) return false;

        // 4. Audit
        if (filterA === 'done' && !rowData.has_audit) return false;
        if (filterA === 'undone' && rowData.has_audit) return false;
        if (filterA === 'ng' && (!rowData.has_audit || !rowData.is_audit_ng)) return false;
        if (filterA === 'ok' && (!rowData.has_audit || rowData.is_audit_ng)) return false;

        // 5. Filter NG (Beda Hitungan) vs OK (Sudah Dihitung & Sesuai)
        const filterNg = $('#filterStatusNg').val();
        if (filterNg === 'ng' && !rowData.is_ng) return false;
        if (filterNg === 'ok' && (!rowData.has_count || rowData.is_ng)) return false;

        // 6. Filter Metode Hitung (Manual / Timbangan)
        const filterMethod = $('#filterMethod').val();
        if (filterMethod) {
            const proc = (rowData.process_type || '').toUpperCase();
            if (filterMethod === 'manual' && proc.indexOf('MANUAL') === -1) return false;
            if (filterMethod === 'scale' && proc.indexOf('SCALE') === -1) return false;
        }

        return true;
    });

    $('#filterCourier, #filterCount, #filterMethod, #filterReturn, #filterAudit, #filterStatusNg').on('change', function() {
        table.draw();
    });

    $('#btnResetFilter').on('click', function() {
        activeMetricFilter = null;
        $('.stat-badge').removeClass('active-filter');
        $('#filterCourier').val('');
        $('#filterCount').val('');
        $('#filterMethod').val('');
        $('#filterReturn').val('');
        $('#filterAudit').val('');
        $('#filterStatusNg').val('');
        table.draw();
    });

    $('#selectSwitchEvent').on('change', function() {
        const targetId = $(this).val();
        if (targetId) {
            window.location.href = "{{ url('/member/parts/event') }}/" + targetId;
        }
    });
});
</script>
@endsection
