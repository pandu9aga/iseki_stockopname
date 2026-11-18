@extends('layouts.main')

@section('style')
<style>
    .member-history-header-card {
        background: linear-gradient(135deg, #1b2a47 0%, #101827 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
    }
    .stat-badge {
        background: rgba(255, 255, 255, 0.10);
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 10px;
        padding: 0.6rem 1rem;
    }
    .activity-timeline {
        position: relative;
        padding-left: 20px;
    }
    .activity-timeline::before {
        content: '';
        position: absolute;
        left: 6px;
        top: 6px;
        bottom: 6px;
        width: 2px;
        background: #dee2e6;
    }
    .activity-item {
        position: relative;
        margin-bottom: 14px;
    }
    .activity-dot {
        position: absolute;
        left: -20px;
        top: 4px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #0d6efd;
        background: #0d6efd;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner px-1 pt-2">

        <!-- Top Header Card -->
        <div class="member-history-header-card shadow-sm mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge {{ $event->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' }} px-3 py-1 rounded-pill">
                            {{ $event->status }}
                        </span>
                        <span class="text-white-50 font-monospace small">#{{ $event->event_code }}</span>
                    </div>
                    <h3 class="fw-bold mb-1">
                        <i class="fas fa-users-cog text-warning me-2"></i> Riwayat &amp; Produktivitas Member SO
                    </h3>
                    <p class="mb-0 text-white-50 small">
                        Event: <strong>{{ $event->event_name }}</strong> &bull; Rekap seluruh tugas yang dikerjakan oleh setiap NIK karyawan selama event SO
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
                    <a href="{{ route('admin.so-events.parts', $event->id) }}" class="btn btn-outline-info btn-sm fw-bold">
                        <i class="fas fa-boxes me-1"></i> List Part SO
                    </a>
                    <a href="{{ route('admin.so-events.index') }}" class="btn btn-outline-light btn-sm fw-bold">
                        <i class="fas fa-arrow-left me-1"></i> Daftar Jadwal
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Card DataTables -->
        <div class="card shadow-sm border-0" style="border-radius:14px;">
            <div class="card-header bg-white py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <div>
                        <h6 class="card-title mb-0 fw-bold text-dark">
                            <i class="fas fa-user-check me-2 text-primary"></i>Daftar Member &amp; Riwayat Aktivitas
                        </h6>
                        <small class="text-muted">Klik tombol "Rincian Aktivitas" pada tiap baris member untuk melihat riwayat part dan tindakan apa saja yang telah dikerjakan.</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefreshTable">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="memberTable" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">No</th>
                                <th>Karyawan (NIK &amp; Nama)</th>
                                <th>Peran Dikerjakan</th>
                                <th class="text-center">Ambil Kurir</th>
                                <th class="text-center">Pos 1</th>
                                <th class="text-center">Pos 2</th>
                                <th class="text-center">Timbangan</th>
                                <th class="text-center">Pengembalian</th>
                                <th class="text-center fw-bold">Total Aksi</th>
                                <th class="text-center">Aktivitas Terakhir</th>
                                <th class="text-center" style="width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Rincian Aktivitas Member -->
<div class="modal fade" id="modalMemberDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
            <div class="modal-header bg-dark text-white py-3 px-4" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary px-2 py-1"><i class="fas fa-user-clock me-1"></i> Log Aktivitas</span>
                    <h5 class="modal-title fw-bold mb-0" id="detailModalTitle">Riwayat Aktivitas Member</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Header Info Karyawan -->
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 10px;">
                    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <span class="text-muted small d-block">Nama Karyawan:</span>
                            <h5 class="fw-bold text-dark mb-0" id="detailMemberName">-</h5>
                            <span class="font-monospace text-primary fw-bold small" id="detailMemberNik">-</span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small d-block">Total Aktivitas Tercatat:</span>
                            <span class="badge bg-success fs-6" id="detailMemberTotal">0 Aksi</span>
                        </div>
                    </div>
                </div>

                <!-- Tabel Riwayat Semua Aktivitas Member Ini -->
                <div class="card border-0 shadow-sm" style="border-radius: 10px;">
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0 w-100" id="detailActivityTable" style="font-size: 0.83rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 140px;">Waktu</th>
                                        <th style="width: 150px;">Tahapan / Peran</th>
                                        <th>Part / Tracking Code</th>
                                        <th>Rak &amp; Lokasi</th>
                                        <th>Hasil / Keterangan</th>
                                        <th class="text-center" style="width: 80px;">Status</th>
                                        <th class="text-center" style="width: 70px;">Foto</th>
                                    </tr>
                                </thead>
                                <tbody id="detailActivityBody">
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-spinner fa-spin me-2"></i> Memuat data aktivitas...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Foto Preview -->
<div class="modal fade" id="modalPhotoPreview" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-0">
            <div class="modal-header border-0 py-2">
                <h6 class="modal-title fw-bold">Foto Bukti Aktivitas</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img id="imgPhotoPreview" src="" alt="Bukti Foto" class="img-fluid rounded" style="max-height: 70vh;">
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('assets/js/plugin/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/sweetalert/sweetalert.min.js') }}"></script>
<script>
$(document).ready(function() {
    const table = $('#memberTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.so-events.member-history-data', $event->id) }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'member_info', name: 'name', className: 'fw-semibold' },
            { data: 'roles_badge', name: 'roles', orderable: false, searchable: false },
            { 
                data: 'courier_count', 
                name: 'courier_count', 
                className: 'text-center fw-bold',
                render: data => data > 0 ? `<span class="badge bg-primary bg-opacity-75">${data}</span>` : '<span class="text-muted">-</span>'
            },
            { 
                data: 'pos1_count', 
                name: 'pos1_count', 
                className: 'text-center fw-bold',
                render: data => data > 0 ? `<span class="badge bg-warning text-dark">${data}</span>` : '<span class="text-muted">-</span>'
            },
            { 
                data: 'pos2_count', 
                name: 'pos2_count', 
                className: 'text-center fw-bold',
                render: data => data > 0 ? `<span class="badge bg-success">${data}</span>` : '<span class="text-muted">-</span>'
            },
            { 
                data: 'scale_count', 
                name: 'scale_count', 
                className: 'text-center fw-bold',
                render: data => data > 0 ? `<span class="badge bg-secondary">${data}</span>` : '<span class="text-muted">-</span>'
            },
            { 
                data: 'return_count', 
                name: 'return_count', 
                className: 'text-center fw-bold',
                render: data => data > 0 ? `<span class="badge bg-dark">${data}</span>` : '<span class="text-muted">-</span>'
            },
            { 
                data: 'total_activity', 
                name: 'total_activity', 
                className: 'text-center fw-bold fs-6 text-primary',
                render: data => `<span class="badge bg-light text-primary border border-primary px-2 py-1">${data} Aksi</span>`
            },
            { data: 'last_active_at', name: 'last_active_at', className: 'text-center text-muted small' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: "Cari Karyawan / NIK:",
            lengthMenu: "Tampilkan _MENU_ member",
            zeroRecords: "Belum ada riwayat aktivitas member pada event SO ini",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ member",
            paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" }
        }
    });

    $('#btnRefreshTable').on('click', function() {
        table.ajax.reload(null, false);
    });

    $('#selectSwitchEvent').on('change', function() {
        const targetId = $(this).val();
        if (targetId) {
            window.location.href = "{{ url('/admin/so-events') }}/" + targetId + "/member-history";
        }
    });

    // Detail Aktivitas Member Modal
    $(document).on('click', '.btn-view-member-detail', function() {
        const nik = $(this).data('nik');
        const name = $(this).data('name');

        $('#detailMemberName').text(name);
        $('#detailMemberNik').text('NIK: ' + nik);
        $('#detailMemberTotal').text('Memuat...');
        $('#detailActivityBody').html(`
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fas fa-spinner fa-spin me-2"></i> Memuat log aktivitas member...
                </td>
            </tr>
        `);

        $('#modalMemberDetail').modal('show');

        $.ajax({
            url: "{{ url('/admin/so-events') }}/{{ $event->id }}/member-detail-data/" + encodeURIComponent(nik),
            type: "GET",
            success: function(res) {
                if (!res.success || !res.activities || res.activities.length === 0) {
                    $('#detailMemberTotal').text('0 Aksi');
                    $('#detailActivityBody').html(`
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Tidak ada aktivitas yang ditemukan untuk karyawan ini.
                            </td>
                        </tr>
                    `);
                    return;
                }

                $('#detailMemberTotal').text(res.total + ' Aksi');
                let rowsHtml = '';
                res.activities.forEach((act, index) => {
                    const photoBtn = act.photo 
                        ? `<button type="button" class="btn btn-xs btn-outline-info btn-photo-preview" data-src="${act.photo}" title="Lihat Foto"><i class="fas fa-image"></i></button>`
                        : '<span class="text-muted small">-</span>';

                    const statusBadge = act.status === 'OK' 
                        ? '<span class="badge bg-success">OK</span>' 
                        : (act.status === 'NG' ? '<span class="badge bg-danger">NG</span>' : '<span class="badge bg-secondary">-</span>');

                    rowsHtml += `
                        <tr>
                            <td class="font-monospace small text-nowrap">${act.time_display}</td>
                            <td>${act.action_badge}</td>
                            <td>
                                <strong class="text-dark">${act.code_part || '-'}</strong><br>
                                <small class="text-muted d-block text-truncate" style="max-width: 220px;" title="${act.name_part || ''}">${act.name_part || '-'}</small>
                                <span class="font-monospace text-primary" style="font-size: 0.72rem;">${act.tracking_code || '-'}</span>
                            </td>
                            <td>
                                <span class="fw-bold">${act.rack_no || '-'}</span><br>
                                <small class="text-muted">${act.location || '-'}</small>
                            </td>
                            <td>
                                <div>${act.details}</div>
                                ${act.result_qty !== '-' ? `<small class="text-dark fw-bold">Qty: ${act.result_qty}</small>` : ''}
                            </td>
                            <td class="text-center">${statusBadge}</td>
                            <td class="text-center">${photoBtn}</td>
                        </tr>
                    `;
                });

                $('#detailActivityBody').html(rowsHtml);
            },
            error: function(xhr) {
                $('#detailMemberTotal').text('Error');
                $('#detailActivityBody').html(`
                    <tr>
                        <td colspan="7" class="text-center py-4 text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i> Gagal memuat data aktivitas.
                        </td>
                    </tr>
                `);
            }
        });
    });

    // Preview Foto
    $(document).on('click', '.btn-photo-preview', function() {
        const src = $(this).data('src');
        if (src) {
            $('#imgPhotoPreview').attr('src', src);
            $('#modalPhotoPreview').modal('show');
        }
    });
});
</script>
@endsection
