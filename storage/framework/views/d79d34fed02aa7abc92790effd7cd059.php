<?php $__env->startSection('style'); ?>
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
    /* Area Cards */
    .area-card-box {
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        border: 2px solid transparent;
        border-radius: 12px;
    }
    .area-card-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.1) !important;
        border-color: #0d6efd !important;
    }
    .area-card-box.active-area {
        border-color: #0d6efd !important;
        background: #f0f7ff !important;
        box-shadow: 0 4px 12px rgba(13,110,253,0.2) !important;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="page-inner">

        <!-- Top Header Card -->
        <div class="parts-header-card shadow-sm mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge <?php echo e($event->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary'); ?> px-3 py-1 rounded-pill">
                            <?php echo e($event->status); ?>

                        </span>
                        <span class="text-white-50 font-monospace small">#<?php echo e($event->event_code); ?></span>
                    </div>
                    <h3 class="fw-bold mb-1">
                        <i class="fas fa-boxes text-warning me-2"></i> <?php echo e($event->event_name); ?>

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
                            <?php $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($ev->id); ?>" <?php echo e($event->id == $ev->id ? 'selected' : ''); ?>>
                                    <?php echo e($ev->event_name); ?> (<?php echo e($ev->status); ?>)
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <a href="<?php echo e(route('member.parts.index')); ?>" class="btn btn-outline-light btn-sm fw-bold">
                        <i class="fas fa-th-large me-1"></i> Daftar Jadwal
                    </a>
                    <a href="<?php echo e(route('member.role.redirect')); ?>" class="btn btn-primary btn-sm fw-bold shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> Ruang Kerja
                    </a>
                </div>
            </div>

            <!-- Metric Summary Badges -->
            <div class="row g-2 mt-3">
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Total Item Masuk SO</small>
                        <span class="fs-4 fw-bold text-white"><?php echo e(number_format($totalStockopname)); ?></span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Siap Dihitung</small>
                        <span class="fs-4 fw-bold text-warning"><?php echo e(number_format($totalReadyToCount)); ?></span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Selesai Dihitung</small>
                        <span class="fs-4 fw-bold text-success"><?php echo e(number_format($totalCounted)); ?></span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Telah Diaudit</small>
                        <span class="fs-4 fw-bold text-info"><?php echo e(number_format($totalAudited)); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if(!empty($areaCards) && count($areaCards) > 0): ?>
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
            <div class="row g-3" id="areaCardsContainer">
                <?php $__currentLoopData = $areaCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card shadow-sm border h-100 area-card-box p-3 bg-white <?php echo e(!empty($card['is_extra']) ? 'border-warning-subtle' : ''); ?>" data-area-no="<?php echo e($card['area_no']); ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <?php if(!empty($card['is_extra'])): ?>
                                <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1 fw-bold">
                                    <i class="fas fa-plus-circle text-warning me-1"></i> Part Tambahan
                                </span>
                                <span class="badge bg-light text-muted border ms-1" style="font-size:0.7rem;">Non-Target</span>
                                <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1 fw-bold">
                                    <i class="fas fa-map-marker-alt me-1"></i> <?php echo e($card['card_title'] ?? ('Area ' . $card['area_no'])); ?>

                                </span>
                                <?php if(!empty($card['letters'])): ?>
                                <span class="badge bg-light text-secondary border ms-1" title="Area Letter"><?php echo e($card['letters']); ?></span>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <span class="badge <?php echo e($card['progress_pct'] == 100 ? 'bg-success' : 'bg-warning text-dark'); ?> rounded-pill font-monospace">
                                <?php echo e($card['progress_pct']); ?>%
                            </span>
                        </div>
                        
                        <div class="mt-2">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Total Part:</span>
                                <strong class="text-dark font-monospace"><?php echo e(number_format($card['total_part'])); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Selesai (Return):</span>
                                <strong class="text-success font-monospace"><?php echo e(number_format($card['total_returned'])); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mb-2">
                                <span>Teraudit Fisik:</span>
                                <strong class="text-info font-monospace"><?php echo e(number_format($card['total_audited'])); ?></strong>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo e($card['progress_pct']); ?>%" aria-valuenow="<?php echo e($card['progress_pct']); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(asset('assets/js/plugin/datatables/datatables.min.js')); ?>"></script>
<script>
$(document).ready(function() {
    const eventId = "<?php echo e($event->id); ?>";
    let selectedAreaNo = null;

    function buildDataUrl() {
        let url = "<?php echo e(route('parts.data', ['id' => $event->id])); ?>";
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
            window.location.href = "<?php echo e(url('/member/parts/event')); ?>/" + targetId;
        }
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\iseki_stockopname\resources\views/member/part_list.blade.php ENDPATH**/ ?>