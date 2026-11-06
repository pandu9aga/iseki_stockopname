@extends('layouts.main')

@section('style')
<style>
    .parts-header-card {
        background: linear-gradient(135deg, #0d3b66 0%, #001e3d 100%);
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
                        Snapshot master part (salinan <code>iseki_label</code>) &bull; Status tahapan kerja &bull; Riwayat perhitungan
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
                    <a href="{{ route('admin.so-events.index') }}" class="btn btn-outline-light btn-sm fw-bold">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Jadwal
                    </a>
                </div>
            </div>

            <!-- Metric Summary Badges -->
            <div class="row g-2 mt-3">
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Total Part Snapshot</small>
                        <span class="fs-4 fw-bold text-white">{{ number_format($event->parts_count) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Item Masuk SO</small>
                        <span class="fs-4 fw-bold text-warning">{{ number_format($totalStockopname) }}</span>
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
            <div class="card-header bg-white py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="fas fa-table me-2 text-primary"></i>Katalog Master Part &amp; Status Tahapan
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefreshTable">
                        <i class="fas fa-sync-alt me-1"></i> Refresh Data
                    </button>
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
                        <select class="form-select form-select-sm" id="filterReturn">
                            <option value="">Return: Semua</option>
                            <option value="done">Return: Sudah (Hijau)</option>
                            <option value="undone">Return: Belum (Abu-abu)</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-auto">
                        <select class="form-select form-select-sm" id="filterAudit">
                            <option value="">Audit: Semua</option>
                            <option value="done">Audit: Sudah (Hijau)</option>
                            <option value="undone">Audit: Belum (Abu-abu)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" class="btn btn-sm btn-light border text-muted w-100" id="btnResetFilter">
                            <i class="fas fa-times me-1"></i> Reset Filter
                        </button>
                    </div>
                </div>
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
                                <th class="text-center" style="width: 110px;">Aksi</th>
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

<!-- Modal Edit Cell & Lokasi -->
<div class="modal fade" id="modalEditCell" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-edit me-2"></i>Edit Cell &amp; Lokasi Part
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditCell">
                @csrf
                <input type="hidden" id="editPartId" name="part_id">
                <div class="modal-body p-4">
                    <div class="alert alert-info small mb-3">
                        <i class="fas fa-info-circle me-1"></i> Data yang diubah di sini akan <strong>otomatis mengupdate snapshot jadwal SO</strong> dan <strong>master data <code>iseki_label</code></strong> sekaligus.
                    </div>

                    <div class="bg-light p-3 rounded mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Nomor Rak Fisik:</span>
                            <strong class="font-monospace text-primary fs-6" id="labelRackNo">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Part Code:</span>
                            <strong class="font-monospace text-dark" id="labelItemCode">-</strong>
                        </div>
                        <div>
                            <span class="text-muted d-block">Nama Part:</span>
                            <span class="fw-semibold text-dark" id="labelPartName">-</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Lokasi / Rak (Area) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="inputLocation" name="location" placeholder="Contoh: M85, R1, A01" required>
                        <div class="form-text">Nama lokasi fisik / area rak.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Cell <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="inputCell" name="cell" placeholder="Contoh: D, A, 1, 2" required>
                        <div class="form-text">Cell rak (misal: A, B, C, D). Jika sebelumnya kosong, masukkan cell di sini.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitEditCell">
                        <i class="fas fa-save me-1"></i> Simpan ke SO &amp; iseki_label
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('assets/js/plugin/datatables/datatables.min.js') }}"></script>
<script>
$(document).ready(function() {
    const dataUrl = "{{ route('parts.data', ['id' => $event->id]) }}";

    const table = $('#partsDataTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: dataUrl,
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
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-center'
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

    // Custom filtering DataTables berdasarkan status boolean tahapan
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
        if (!rowData) return true;

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

        return true;
    });

    $('#filterCourier, #filterCount, #filterReturn, #filterAudit').on('change', function() {
        table.draw();
    });

    $('#btnResetFilter').on('click', function() {
        $('#filterCourier').val('');
        $('#filterCount').val('');
        $('#filterReturn').val('');
        $('#filterAudit').val('');
        table.draw();
    });

    $('#selectSwitchEvent').on('change', function() {
        const targetId = $(this).val();
        if (targetId) {
            window.location.href = "{{ url('/admin/so-events') }}/" + targetId + "/parts";
        }
    });

    // Handle Click Edit Cell
    $(document).on('click', '.btn-edit-cell', function() {
        const id = $(this).data('id');
        const rack = $(this).data('rack');
        const code = $(this).data('code');
        const name = $(this).data('name');
        const loc = $(this).data('location');
        const cell = $(this).data('cell');

        $('#editPartId').val(id);
        $('#labelRackNo').text(rack);
        $('#labelItemCode').text(code);
        $('#labelPartName').text(name);
        $('#inputLocation').val(loc);
        $('#inputCell').val(cell);

        $('#modalEditCell').modal('show');
    });

    // Handle Form Submit Edit Cell
    $('#formEditCell').on('submit', function(e) {
        e.preventDefault();
        const partId = $('#editPartId').val();
        const btn = $('#btnSubmitEditCell');

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: `{{ url('/admin/so-events/parts') }}/${partId}/update-location`,
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    $('#modalEditCell').modal('hide');
                    swal({
                        title: 'Berhasil!',
                        text: res.message,
                        type: 'success',
                        timer: 2000,
                        buttons: false
                    });
                    table.ajax.reload(null, false); // Reload DataTables tanpa reset pagination
                } else {
                    swal('Gagal', res.message || 'Terjadi kesalahan.', 'error');
                }
            },
            error: function(xhr) {
                swal('Gagal', xhr.responseJSON?.message || 'Gagal menyimpan perubahan.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan ke SO &amp; iseki_label');
            }
        });
    });
});
</script>
@endsection

