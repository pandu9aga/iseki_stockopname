@extends('layouts.main')

@section('style')
<style>
    .history-header-card {
        background: linear-gradient(135deg, #1f2421 0%, #212529 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
    }
    .filter-card {
        border-radius: 12px;
        background: #fdfdfd;
        border: 1px solid #e9ecef;
    }
    .badge-round-count {
        background: #e7f1ff;
        color: #0d6efd;
        border: 1px solid #b6d4fe;
        font-weight: 700;
        font-size: 0.72rem;
        padding: 3px 8px;
        border-radius: 20px;
    }
    .table-audit-history td, .table-audit-history th {
        vertical-align: middle;
        font-size: 0.84rem;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">

        <!-- Top Header Card -->
        <div class="history-header-card shadow-sm mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge {{ $selectedEvent->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' }} px-3 py-1 rounded-pill">
                            {{ $selectedEvent->status }}
                        </span>
                        <span class="text-white-50 font-monospace small">#{{ $selectedEvent->event_code }}</span>
                    </div>
                    <h3 class="fw-bold mb-1">
                        <i class="fas fa-clipboard-list text-warning me-2"></i> Riwayat Audit Part SO
                    </h3>
                    <p class="mb-0 text-white-50 small">
                        Rekapitulasi seluruh part pada event <strong>{{ $selectedEvent->event_name }}</strong> &bull; 1 Baris per part dengan riwayat audit multi-ronde
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="input-group input-group-sm" style="width: auto;">
                        <span class="input-group-text bg-white border-0 fw-bold text-dark">
                            <i class="fas fa-calendar-alt text-primary me-1"></i> Pilih Event SO:
                        </span>
                        <select class="form-select form-select-sm fw-bold border-0 text-primary shadow-sm" id="selectSwitchEvent" style="min-width: 220px;">
                            @foreach($events as $ev)
                                <option value="{{ $ev->id }}" {{ $selectedEvent->id == $ev->id ? 'selected' : '' }}>
                                    {{ $ev->event_name }} ({{ $ev->status }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card filter-card shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row g-2 align-items-end">
                    <!-- Filter Status Audit -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold mb-1"><i class="fas fa-filter text-primary me-1"></i>Status Audit:</label>
                        <select class="form-select form-select-sm" id="filterAuditStatus">
                            <option value="">Semua (Sudah / Belum)</option>
                            <option value="SUDAH">Sudah Diaudit</option>
                            <option value="BELUM">Belum Diaudit</option>
                        </select>
                    </div>

                    <!-- Filter Auditor -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold mb-1"><i class="fas fa-user-check text-primary me-1"></i>Nama Auditor:</label>
                        <select class="form-select form-select-sm" id="filterAuditorName">
                            <option value="">Semua Auditor</option>
                            @foreach($auditorNames as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Kesesuaian (OK / NG) -->
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label small fw-bold mb-1"><i class="fas fa-check-circle text-primary me-1"></i>Hasil Toleransi:</label>
                        <select class="form-select form-select-sm" id="filterResultStatus">
                            <option value="">Semua (OK / NG)</option>
                            <option value="OK">OK (&lt; 2%)</option>
                            <option value="NG">NG (&ge; 2%)</option>
                        </select>
                    </div>

                    <!-- Filter Metode Audit -->
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label small fw-bold mb-1"><i class="fas fa-balance-scale text-primary me-1"></i>Metode Audit:</label>
                        <select class="form-select form-select-sm" id="filterMethod">
                            <option value="">Semua Metode</option>
                            <option value="MANUAL">Manual (2x)</option>
                            <option value="SCALE">Timbangan</option>
                        </select>
                    </div>

                    <!-- Reset Filter Button -->
                    <div class="col-md-2 col-sm-12">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btnResetFilter">
                            <i class="fas fa-undo me-1"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main DataTable Card -->
        <div class="card shadow-sm border-0" style="border-radius:14px; overflow:hidden;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fas fa-table me-2 text-primary"></i>Daftar Part & Rekapitulasi Audit
                </h6>
                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i> Part yang diaudit lebih dari 1 kali ditampilkan dalam <strong>1 baris</strong> dengan rincian tombol histori
                </div>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-audit-history w-100" id="tableAuditHistory">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">No</th>
                                <th>Rak</th>
                                <th>Part Info</th>
                                <th>Lokasi / Cell</th>
                                <th class="text-center">Hasil SO</th>
                                <th class="text-center">Hasil Audit Akhir</th>
                                <th class="text-center">Selisih</th>
                                <th class="text-center">Margin Error</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Total Audit</th>
                                <th class="text-center" style="width: 100px;">Aksi</th>
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

<!-- Modal Detail Riwayat Multi-Audit Part -->
<div class="modal fade" id="modalPartAuditHistory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-history me-2"></i>Rincian Riwayat Seluruh Audit Part
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Part Header Info in Modal -->
                <div class="card bg-light border-0 p-3 mb-3 small">
                    <div class="row g-2">
                        <div class="col-sm-4">
                            <span class="text-muted d-block">Nomor Rak:</span>
                            <strong class="font-monospace fs-6 text-primary" id="modalDetailRack">-</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted d-block">Kode & Nama Part:</span>
                            <strong class="text-dark" id="modalDetailPart">-</strong>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-muted d-block">Lokasi / Cell:</span>
                            <span class="fw-semibold" id="modalDetailLoc">-</span>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-muted d-block">Hasil Hitung Member SO:</span>
                            <span class="fw-bold text-secondary" id="modalDetailSo">-</span>
                        </div>
                        <div class="col-sm-4">
                            <span class="text-muted d-block">Total Ronde Audit:</span>
                            <span class="badge bg-primary" id="modalDetailTotalRound">0 Ronde</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive border rounded" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-sm table-striped align-middle mb-0" style="font-size:0.83rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Ronde</th>
                                <th>Waktu</th>
                                <th>Auditor</th>
                                <th>Metode</th>
                                <th class="text-center">Qty Audit</th>
                                <th class="text-center">Margin Alat</th>
                                <th class="text-center">Margin vs Member</th>
                                <th class="text-center">Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody id="modalDetailAuditTableBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    const CURRENT_EVENT_ID = "{{ $selectedEvent->id }}";
    const URL_DATA = "{{ route('auditor.history-data', $selectedEvent->id) }}";

    // Switch Event Handler
    $('#selectSwitchEvent').on('change', function() {
        const evId = $(this).val();
        window.location.href = "{{ url('/auditor/history') }}/" + evId;
    });

    // Inisialisasi DataTable
    const table = $('#tableAuditHistory').DataTable({
        processing: true,
        serverSide: false, // Client-side search agar filter custom interaktif & cepat
        ajax: {
            url: URL_DATA,
            dataSrc: 'data'
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
                data: 'item_code', 
                name: 'item_code',
                render: function(data, type, row) {
                    return `
                        <div class="fw-semibold text-dark">${data || '-'}</div>
                        <small class="text-muted text-truncate d-block" style="max-width: 200px;">${row.part_name || '-'}</small>
                    `;
                }
            },
            { 
                data: 'loc_display', 
                name: 'loc_display',
                render: function(data) {
                    return `<span class="badge bg-light text-dark border">${data || '-'}</span>`;
                }
            },
            { 
                data: 'so_final_count', 
                name: 'so_final_count',
                className: 'text-center fw-bold text-secondary'
            },
            { 
                data: 'latest_qty', 
                name: 'latest_qty',
                className: 'text-center',
                render: function(data, type, row) {
                    if (!row.is_audited) {
                        return '<span class="text-muted">—</span>';
                    }
                    let methodBadge = row.latest_method === 'SCALE'
                        ? '<span class="badge bg-info-subtle text-info border border-info" style="font-size:0.68rem;"><i class="fas fa-balance-scale me-1"></i>Timbangan</span>'
                        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary" style="font-size:0.68rem;"><i class="fas fa-hand-paper me-1"></i>Manual</span>';
                    return `
                        <div class="fw-bold text-dark fs-6">${data}</div>
                        <div>${methodBadge}</div>
                    `;
                }
            },
            { 
                data: 'diff_so', 
                name: 'diff_so',
                className: 'text-center',
                render: function(data, type, row) {
                    if (!row.is_audited || data === null) {
                        return '<span class="text-muted">—</span>';
                    }
                    if (data === 0) {
                        return '<span class="badge bg-success-subtle text-success border border-success">0 pcs</span>';
                    } else if (data > 0) {
                        return `<span class="badge bg-danger-subtle text-danger border border-danger"><i class="fas fa-arrow-up me-1"></i>+${data.toLocaleString('id-ID')} (Over)</span>`;
                    } else {
                        return `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning"><i class="fas fa-arrow-down me-1"></i>${data.toLocaleString('id-ID')} (Under)</span>`;
                    }
                }
            },
            { 
                data: 'latest_member_moe', 
                name: 'latest_member_moe',
                className: 'text-center',
                render: function(data, type, row) {
                    if (!row.is_audited || data === '-') {
                        return '<span class="text-muted">—</span>';
                    }
                    const num = parseFloat(data);
                    const isOk = !isNaN(num) && num < 2.0;
                    return `
                        <span class="badge ${isOk ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger'}">
                            ${data}
                        </span>
                    `;
                }
            },
            { 
                data: 'latest_status', 
                name: 'latest_status',
                className: 'text-center',
                render: function(data, type, row) {
                    if (!row.is_audited) {
                        return '<span class="badge bg-light text-muted border">Belum Diaudit</span>';
                    }
                    if (data === 'OK') {
                        return '<span class="badge bg-success px-3 py-1 fw-bold fs-6"><i class="fas fa-check-circle me-1"></i>OK</span>';
                    } else if (data === 'NG') {
                        return '<span class="badge bg-danger px-3 py-1 fw-bold fs-6"><i class="fas fa-times-circle me-1"></i>NG</span>';
                    }
                    return '<span class="badge bg-secondary">Unknown</span>';
                }
            },
            { 
                data: 'audit_count', 
                name: 'audit_count',
                className: 'text-center',
                render: function(data, type, row) {
                    if (!row.is_audited || data === 0) {
                        return '<span class="text-muted">0x</span>';
                    }
                    let badgeColor = data > 1 ? 'badge-round-count' : 'badge bg-light text-primary border border-primary';
                    let auditorName = row.latest_auditor ? `<div class="text-muted mt-1" style="font-size:0.7rem;">Oleh: ${row.latest_auditor}</div>` : '';
                    return `
                        <span class="${badgeColor}">${data}x Audit</span>
                        ${auditorName}
                    `;
                }
            },
            { 
                data: null, 
                orderable: false, 
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    if (!row.is_audited) {
                        return '<span class="text-muted small">—</span>';
                    }
                    return `
                        <button type="button" class="btn btn-sm btn-outline-primary btn-view-history" title="Lihat Rincian Seluruh Ronde Audit">
                            <i class="fas fa-eye me-1"></i> Detail (${row.audit_count})
                        </button>
                    `;
                }
            }
        ],
        order: [[1, 'asc']],
        language: {
            search: "Cari Part / Rak:",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Tidak ada data part yang cocok",
            info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ part",
            infoEmpty: "Menampilkan 0 data",
            infoFiltered: "(disaring dari _MAX_ total part)",
            paginate: {
                first: "Awal",
                last: "Akhir",
                next: "&rarr;",
                previous: "&larr;"
            }
        }
    });

    // Custom filtering function
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.sTableId !== 'tableAuditHistory') {
            return true;
        }

        const rowData = table.row(dataIndex).data();
        if (!rowData) return true;

        const fStatus = $('#filterAuditStatus').val();
        const fAuditor = $('#filterAuditorName').val();
        const fResult = $('#filterResultStatus').val();
        const fMethod = $('#filterMethod').val();

        // 1. Status Audit (SUDAH / BELUM)
        if (fStatus === 'SUDAH' && !rowData.is_audited) return false;
        if (fStatus === 'BELUM' && rowData.is_audited) return false;

        // 2. Nama Auditor
        if (fAuditor !== '') {
            if (!rowData.is_audited) return false;
            if (!rowData.auditor_names.includes(fAuditor)) return false;
        }

        // 3. Hasil Toleransi (OK / NG)
        if (fResult !== '') {
            if (!rowData.is_audited) return false;
            if (rowData.latest_status !== fResult) return false;
        }

        // 4. Metode Audit (MANUAL / SCALE)
        if (fMethod !== '') {
            if (!rowData.is_audited) return false;
            if (rowData.latest_method !== fMethod) return false;
        }

        return true;
    });

    // Filter trigger events
    $('#filterAuditStatus, #filterAuditorName, #filterResultStatus, #filterMethod').on('change', function() {
        table.draw();
    });

    $('#btnResetFilter').on('click', function() {
        $('#filterAuditStatus').val('');
        $('#filterAuditorName').val('');
        $('#filterResultStatus').val('');
        $('#filterMethod').val('');
        table.draw();
    });

    // Open Modal Detail History Ronde Audit
    $('#tableAuditHistory tbody').on('click', '.btn-view-history', function() {
        const rowData = table.row($(this).closest('tr')).data();
        if (!rowData) return;

        $('#modalDetailRack').text(rowData.rack_no);
        $('#modalDetailPart').text((rowData.item_code || '-') + ' — ' + (rowData.part_name || '-'));
        $('#modalDetailLoc').text(rowData.loc_display);
        $('#modalDetailSo').text(rowData.so_final_count);
        $('#modalDetailTotalRound').text(rowData.audit_count + ' Ronde');

        const tbody = $('#modalDetailAuditTableBody');
        tbody.empty();

        if (rowData.history_details && rowData.history_details.length > 0) {
            rowData.history_details.forEach(item => {
                let badgeStatus = '<span class="badge bg-light text-muted border">-</span>';
                if (item.status === 'OK') {
                    badgeStatus = '<span class="badge bg-success">OK</span>';
                } else if (item.status === 'NG') {
                    badgeStatus = '<span class="badge bg-danger">NG</span>';
                }

                tbody.append(`
                    <tr>
                        <td class="fw-bold">Ronde ${item.round}</td>
                        <td class="font-monospace text-muted small">${item.time}</td>
                        <td class="fw-bold text-dark">${item.auditor_name}</td>
                        <td><span class="badge bg-light text-dark border">${item.method}</span></td>
                        <td class="text-center fw-bold text-dark">${item.qty.toLocaleString('id-ID')} pcs</td>
                        <td class="text-center text-muted">${item.scale_margin}</td>
                        <td class="text-center fw-semibold">${item.member_margin}</td>
                        <td class="text-center">${badgeStatus}</td>
                        <td class="text-muted small">${item.notes}</td>
                    </tr>
                `);
            });
        } else {
            tbody.html('<tr><td colspan="9" class="text-center text-muted py-3">Belum ada riwayat audit.</td></tr>');
        }

        $('#modalPartAuditHistory').modal('show');
    });
});
</script>
@endsection

