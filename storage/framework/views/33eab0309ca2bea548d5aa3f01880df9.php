<?php $__env->startSection('style'); ?>
<style>
    .event-header-card {
        background: linear-gradient(135deg, #0d3b66 0%, #001e3d 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.5rem;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="page-inner">

        <!-- Top Header Card -->
        <div class="event-header-card shadow-sm mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h3 class="fw-bold mb-1">
                        <i class="fas fa-calendar-alt text-warning me-2"></i> Master Data Jadwal Stock Opname
                    </h3>
                    <p class="mb-0 text-white-50 small">
                        Kelola agenda SO berkala &bull; Salin snapshot part otomatis dari <code>iseki_label</code> saat jadwal dibuat
                    </p>
                </div>
                <div>
                    <button type="button" class="btn btn-warning fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCreateEvent">
                        <i class="fas fa-plus me-1"></i> Buat Jadwal SO Baru
                    </button>
                </div>
            </div>

            <?php if($activeEvent): ?>
            <div class="alert alert-success bg-success bg-opacity-25 border-0 text-white mt-3 mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-broadcast-tower fa-lg text-warning"></i>
                <div>
                    Jadwal SO Aktif Saat Ini: <strong><?php echo e($activeEvent->event_name); ?> (<?php echo e($activeEvent->event_code); ?>)</strong> &bull;
                    Periode: <?php echo e($activeEvent->start_date->format('d/m/Y')); ?> s/d <?php echo e($activeEvent->end_date->format('d/m/Y')); ?> &bull;
                    Part Snapshot: <strong><?php echo e(number_format($activeEvent->total_parts_snapshot)); ?> part</strong>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning bg-warning bg-opacity-25 border-0 text-white mt-3 mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-exclamation-triangle fa-lg text-warning"></i>
                <div>
                    <strong>Perhatian:</strong> Belum ada jadwal SO yang berstatus <strong>AKTIF</strong>. Buat jadwal baru atau aktifkan jadwal di bawah.
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- DataTables Card -->
        <div class="card shadow-sm border-0" style="border-radius:14px;">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fas fa-history me-2 text-primary"></i>Daftar Jadwal Stock Opname
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="eventsTable" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Kode Event</th>
                                <th>Nama Agenda SO</th>
                                <th>Periode Pelaksanaan</th>
                                <th class="text-center">Total Part Snapshot</th>
                                <th class="text-center">Item Diproses</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 160px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Create Event -->
<div class="modal fade" id="modalCreateEvent" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-calendar-plus me-2"></i>Buat Jadwal Stock Opname Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCreateEvent" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sumber Snapshot Part <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="card p-3 border h-100 cursor-pointer source-card active-source" style="cursor: pointer; border-radius: 10px;">
                                    <div class="form-check p-0 m-0 d-flex align-items-start gap-2">
                                        <input class="form-check-input mt-1" type="radio" name="source_type" id="sourceAllLabel" value="ALL_LABEL" checked>
                                        <div>
                                            <strong class="d-block text-dark mb-1"><i class="fas fa-database text-primary me-1"></i> Copy Semua dari iseki_label</strong>
                                            <small class="text-muted" style="font-size:0.75rem;">
                                                Salin seluruh part master secara otomatis. Card area akan dikelompokkan <strong>berdasarkan Area Letter</strong> (W, E, dll).
                                            </small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="card p-3 border h-100 cursor-pointer source-card" style="cursor: pointer; border-radius: 10px;">
                                    <div class="form-check p-0 m-0 d-flex align-items-start gap-2">
                                        <input class="form-check-input mt-1" type="radio" name="source_type" id="sourceExcelTarget" value="EXCEL_TARGET">
                                        <div>
                                            <strong class="d-block text-dark mb-1"><i class="fas fa-file-excel text-success me-1"></i> Upload Target Excel</strong>
                                            <small class="text-muted" style="font-size:0.75rem;">
                                                Hanya rak target dari Excel. Card area dikelompokkan <strong>berdasarkan Nomor Area</strong> (01, 02, dll).
                                            </small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Aturan Peran Kurir & Pengembali -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Aturan Peran Kurir & Pengembali <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="card p-3 border h-100 cursor-pointer role-merged-card active-role-mode" style="cursor: pointer; border-radius: 10px; border-color: #FF95A5 !important; background: #fff8f9;">
                                    <div class="form-check p-0 m-0 d-flex align-items-start gap-2">
                                        <input class="form-check-input mt-1" type="radio" name="is_courier_return_merged" id="roleSeparate" value="0" checked>
                                        <div>
                                            <strong class="d-block text-dark mb-1"><i class="fas fa-user-friends text-secondary me-1"></i> Terpisah (Beda Orang)</strong>
                                            <small class="text-muted" style="font-size:0.75rem;">
                                                Kurir &amp; Pengembali adalah peran terpisah. Member yang mengambil part <strong>dilarang</strong> mengembalikannya ke rak.
                                            </small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="card p-3 border h-100 cursor-pointer role-merged-card" style="cursor: pointer; border-radius: 10px;">
                                    <div class="form-check p-0 m-0 d-flex align-items-start gap-2">
                                        <input class="form-check-input mt-1" type="radio" name="is_courier_return_merged" id="roleMerged" value="1">
                                        <div>
                                            <strong class="d-block text-dark mb-1"><i class="fas fa-sync-alt text-primary me-1"></i> Digabung / Sama (Boleh Merangkap)</strong>
                                            <small class="text-muted" style="font-size:0.75rem;">
                                                Kurir &amp; Pengembali dijadikan 1 menu. Kurir boleh mengembalikan part yang sama, namun <strong>tetap dilarang menghitung</strong> di Pos 1 / Pos 2 / Timbangan.
                                            </small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Box Upload Excel (Hanya tampil jika opsi EXCEL_TARGET dipilih) -->
                    <div class="alert alert-info small mb-3 d-none" id="boxExcelUpload">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold"><i class="fas fa-file-excel text-success me-1"></i> File Target Rak (.xlsx / .csv)</span>
                            <a href="<?php echo e(route('admin.so-events.template')); ?>" class="btn btn-xs btn-outline-primary fw-bold bg-white">
                                <i class="fas fa-download me-1"></i> Template Excel
                            </a>
                        </div>
                        <input type="file" class="form-control form-control-sm" name="excel_file" id="inputExcelFile" accept=".xlsx,.xls,.csv">
                        <div class="form-text" style="font-size:0.72rem;">Kolom header wajib: <code>rack_no</code>, <code>area_letter</code>, <code>area_no</code>. Rak multi-cell se-abjad otomatis disatukan.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Kode Jadwal / Event <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" name="event_code" placeholder="Contoh: SO-2026-Q1, SO-2026-NOV" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Agenda Stock Opname <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="event_name" placeholder="Contoh: Stock Opname Semester 2 2026" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo e(date('Y-m-d')); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo e(date('Y-m-d', strtotime('+7 days'))); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Status Awal <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" required>
                            <option value="ACTIVE" selected>AKTIF (Langsung Digunakan Sekarang)</option>
                            <option value="DRAFT">DRAFT (Disiapkan Dulu)</option>
                        </select>
                        <div class="form-text">Jika dipilih AKTIF, jadwal aktif sebelumnya akan otomatis ditutup.</div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Catatan / Keterangan (Opsional)</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Keterangan agenda SO..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitCreate">
                        <i class="fas fa-save me-1"></i> Simpan &amp; Buat Jadwal SO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(asset('assets/js/plugin/datatables/datatables.min.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/plugin/sweetalert/sweetalert.min.js')); ?>"></script>
<script>
$(document).ready(function() {
    const table = $('#eventsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "<?php echo e(route('admin.so-events.index')); ?>",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { 
                data: 'event_code', 
                name: 'event_code',
                render: function(data) {
                    return `<span class="fw-bold font-monospace text-primary">${data}</span>`;
                }
            },
            { data: 'event_name', name: 'event_name', className: 'fw-semibold text-dark' },
            { data: 'period', name: 'start_date' },
            { 
                data: 'parts_count', 
                name: 'parts_count', 
                className: 'text-center fw-bold',
                render: function(data, type, row) {
                    const count = data || row.total_parts_snapshot || 0;
                    return `<span class="badge bg-light text-dark border">${parseInt(count).toLocaleString('id-ID')} part</span>`;
                }
            },
            { 
                data: 'so_items_count', 
                name: 'so_items_count', 
                className: 'text-center fw-bold',
                render: function(data) {
                    return `<span class="text-primary">${parseInt(data || 0).toLocaleString('id-ID')} item</span>`;
                }
            },
            { data: 'status_badge', name: 'status', className: 'text-center' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: "Cari Jadwal:",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Belum ada jadwal Stock Opname",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ jadwal",
            paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" }
        }
    });

    // Toggle Tampilan Input Excel berdasarkan Pilihan Sumber
    $('input[name="source_type"]').on('change', function() {
        const val = $(this).val();
        $('.source-card').removeClass('active-source border-primary bg-light');
        $(this).closest('.source-card').addClass('active-source border-primary bg-light');

        if (val === 'EXCEL_TARGET') {
            $('#boxExcelUpload').removeClass('d-none');
            $('#inputExcelFile').prop('required', true);
        } else {
            $('#boxExcelUpload').addClass('d-none');
            $('#inputExcelFile').prop('required', false).val('');
        }
    });

    // Toggle styling radio peran kurir & pengembali
    $('input[name="is_courier_return_merged"]').on('change', function() {
        $('.role-merged-card').removeClass('active-role-mode').css({'border-color': '', 'background': ''});
        $(this).closest('.role-merged-card').addClass('active-role-mode').css({'border-color': '#FF95A5', 'background': '#fff8f9'});
    });

    // Submit Create Event
    $('#formCreateEvent').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSubmitCreate');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyalin &amp; Memproses Snapshot...');

        $.ajax({
            url: "<?php echo e(route('admin.so-events.store')); ?>",
            type: "POST",
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(resp) {
                if (resp.success) {
                    $('#modalCreateEvent').modal('hide');
                    $('#formCreateEvent')[0].reset();
                    swal({ title: 'Berhasil!', text: resp.message, type: 'success' }).then(() => {
                        location.reload();
                    });
                } else {
                    swal('Gagal', resp.message || 'Terjadi kesalahan.', 'error');
                }
            },
            error: function(xhr) {
                swal('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan sistem.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan &amp; Buat Jadwal SO');
            }
        });
    });

    // Activate Event
    $(document).on('click', '.btn-activate', function() {
        const id = $(this).data('id');
        swal({
            title: 'Aktifkan Jadwal SO ini?',
            text: 'Jadwal yang sedang aktif saat ini akan otomatis ditutup, dan seluruh aktivitas SO akan masuk ke jadwal ini.',
            type: 'warning',
            buttons: {
                cancel: { visible: true, text: 'Batal', className: 'btn btn-secondary' },
                confirm: { text: 'Ya, Aktifkan', className: 'btn btn-success' }
            }
        }).then((willActivate) => {
            if (willActivate) {
                $.ajax({
                    url: `<?php echo e(url('/admin/so-events')); ?>/${id}/activate`,
                    type: 'POST',
                    data: { _token: "<?php echo e(csrf_token()); ?>" },
                    success: function(resp) {
                        swal('Berhasil!', resp.message, 'success').then(() => { location.reload(); });
                    },
                    error: function(xhr) {
                        swal('Gagal', xhr.responseJSON?.message || 'Gagal mengaktifkan jadwal.', 'error');
                    }
                });
            }
        });
    });

    // Close Event
    $(document).on('click', '.btn-close-event', function() {
        const id = $(this).data('id');
        swal({
            title: 'Tutup Jadwal SO ini?',
            text: 'Jadwal akan diubah statusnya menjadi CLOSED (Selesai).',
            type: 'warning',
            buttons: {
                cancel: { visible: true, text: 'Batal', className: 'btn btn-secondary' },
                confirm: { text: 'Ya, Tutup', className: 'btn btn-warning' }
            }
        }).then((willClose) => {
            if (willClose) {
                $.ajax({
                    url: `<?php echo e(url('/admin/so-events')); ?>/${id}/close`,
                    type: 'POST',
                    data: { _token: "<?php echo e(csrf_token()); ?>" },
                    success: function(resp) {
                        swal('Berhasil!', resp.message, 'success').then(() => { location.reload(); });
                    },
                    error: function(xhr) {
                        swal('Gagal', xhr.responseJSON?.message || 'Gagal menutup jadwal.', 'error');
                    }
                });
            }
        });
    });

    // Delete Event
    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        swal({
            title: 'Hapus Jadwal SO ini?',
            text: `Seluruh data snapshot part pada jadwal "${name}" akan dihapus permanen.`,
            type: 'error',
            buttons: {
                cancel: { visible: true, text: 'Batal', className: 'btn btn-secondary' },
                confirm: { text: 'Ya, Hapus', className: 'btn btn-danger' }
            }
        }).then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    url: `<?php echo e(url('/admin/so-events')); ?>/${id}`,
                    type: 'DELETE',
                    data: { _token: "<?php echo e(csrf_token()); ?>" },
                    success: function(resp) {
                        swal('Terhapus!', resp.message, 'success');
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        swal('Gagal', xhr.responseJSON?.message || 'Gagal menghapus jadwal.', 'error');
                    }
                });
            }
        });
    });
});
</script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\iseki_stockopname\resources\views/admin/so_events/index.blade.php ENDPATH**/ ?>