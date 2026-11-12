<?php $__env->startSection('style'); ?>
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
                        Master part (salinan <code>iseki_label</code>) &bull; Status tahapan kerja &bull; Riwayat perhitungan
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
                    <a href="<?php echo e(route('admin.so-events.index')); ?>" class="btn btn-outline-light btn-sm fw-bold">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Jadwal
                    </a>
                </div>
            </div>

            <!-- Metric Summary Badges -->
            <div class="row g-2 mt-3 row-cols-2 row-cols-md-5">
                <div class="col">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Total Part</small>
                        <span class="fs-4 fw-bold text-white"><?php echo e(number_format($event->parts_count)); ?></span>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Item Masuk SO</small>
                        <span class="fs-4 fw-bold text-warning"><?php echo e(number_format($totalStockopname)); ?></span>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Selesai Dihitung</small>
                        <span class="fs-4 fw-bold text-success"><?php echo e(number_format($totalCounted)); ?></span>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-badge text-center">
                        <small class="text-white-50 d-block" style="font-size:0.75rem;">Hitungan NG</small>
                        <span class="fs-4 fw-bold text-danger"><?php echo e(number_format($totalNg ?? 0)); ?></span>
                    </div>
                </div>
                <div class="col">
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
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?php echo e(route('parts.export-excel', $event->id)); ?>" class="btn btn-sm btn-success shadow-sm fw-bold" id="btnExportExcel" title="Download Excel Lengkap dengan Auto Filter">
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
                                <th style="width: 95px;">Lokasi / Cell</th>
                                <th style="min-width: 170px;">Part Code &amp; Name</th>
                                <th class="text-center" style="width: 135px;">Tahap Terkini</th>
                                <th class="text-center" style="width: 95px;">Waktu Siklus</th>
                                <th class="text-center" style="width: 95px;">Metode SO</th>
                                <th class="text-center" style="width: 90px;">Hasil Final</th>
                                <th>Riwayat Perhitungan Tiap Tahap</th>
                                <th class="text-center" style="width: 125px;">Aksi</th>
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
                <?php echo csrf_field(); ?>
                <input type="hidden" id="editPartId" name="part_id">
                <div class="modal-body p-4">
                    <div class="alert alert-info small mb-3">
                        <i class="fas fa-info-circle me-1"></i> Data yang diubah di sini akan <strong>otomatis mengupdate jadwal SO</strong> dan <strong>master data <code>iseki_label</code></strong> sekaligus.
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
<!-- Modal Detail Part & Riwayat Step by Step -->
<div class="modal fade" id="modalPartDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px; border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
            <div class="modal-header bg-dark text-white py-3 px-4" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary px-2 py-1"><i class="fas fa-boxes me-1"></i> Detail Part SO</span>
                    <h5 class="modal-title fw-bold mb-0" id="detailItemCode">Memuat Data...</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Part Main Info Header -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px;">
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <small class="text-muted d-block" style="font-size:0.75rem;">Nama Part</small>
                                <strong class="fs-6 text-dark" id="detailPartName">-</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block" style="font-size:0.75rem;">Nomor Rak</small>
                                <strong class="font-monospace text-primary fs-6" id="detailRackNo">-</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block" style="font-size:0.75rem;">Lokasi / Cell</small>
                                <strong class="font-monospace text-dark fs-6" id="detailLoc">-</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block" style="font-size:0.75rem;">Tahap Terkini</small>
                                <span class="badge bg-secondary" id="detailStep">-</span>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block" style="font-size:0.75rem;">Metode SO</small>
                                <strong class="text-dark" id="detailMethod">-</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block" style="font-size:0.75rem;">Hasil Final SO</small>
                                <strong class="text-success fs-6" id="detailFinalCount">-</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block" style="font-size:0.75rem;">Tracking Code</small>
                                <span class="font-monospace small text-muted text-truncate d-block" id="detailTrackingCode" style="max-width: 150px;">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs: Riwayat Tahap & Galeri Foto -->
                <ul class="nav nav-pills nav-fill mb-3" id="pills-tab-detail" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold py-2" id="pills-timeline-tab" data-bs-toggle="pill" data-bs-target="#pills-timeline" type="button" role="tab">
                            <i class="fas fa-stream me-2"></i>Riwayat Step by Step
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold py-2" id="pills-gallery-tab" data-bs-toggle="pill" data-bs-target="#pills-gallery" type="button" role="tab">
                            <i class="fas fa-images me-2"></i>Semua Foto Dokumentasi (<span id="detailPhotoCount">0</span>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContentDetail">
                    <!-- Tab Timeline -->
                    <div class="tab-pane fade show active" id="pills-timeline" role="tabpanel">
                        <div id="detailTimelineContainer" class="py-2">
                            <!-- Populated via AJAX -->
                        </div>
                    </div>

                    <!-- Tab Galeri Foto -->
                    <div class="tab-pane fade" id="pills-gallery" role="tabpanel">
                        <div class="row g-3" id="detailGalleryContainer">
                            <!-- Populated via AJAX -->
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

<!-- Modal Foto Preview (Zoom) -->
<div class="modal fade" id="modalPhotoPreview" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark text-white border-0">
            <div class="modal-header border-0 py-2">
                <h6 class="modal-title fw-bold" id="photoPreviewTitle">Foto Dokumentasi</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img src="" id="photoPreviewImg" class="img-fluid rounded" style="max-height: 80vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(asset('assets/js/plugin/datatables/datatables.min.js')); ?>"></script>
<script>
$(document).ready(function() {
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
                        <div class="part-row-clickable" style="cursor: pointer;" title="Klik untuk melihat detail riwayat & foto">
                            <div class="fw-bold text-primary font-monospace text-decoration-underline">${row.item_code}</div>
                            <div class="text-dark small text-truncate" style="max-width:240px;" title="${data}">${data}</div>
                        </div>
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

        // 5. Filter NG (Beda Hitungan) vs OK (Sudah Dihitung & Sesuai)
        const filterNg = $('#filterStatusNg').val();
        if (filterNg === 'ng' && !rowData.is_ng) return false;
        if (filterNg === 'ok' && (!rowData.has_count || rowData.is_ng)) return false;

        return true;
    });

    $('#filterCourier, #filterCount, #filterReturn, #filterAudit, #filterStatusNg').on('change', function() {
        table.draw();
    });

    $('#btnResetFilter').on('click', function() {
        $('#filterCourier').val('');
        $('#filterCount').val('');
        $('#filterReturn').val('');
        $('#filterAudit').val('');
        $('#filterStatusNg').val('');
        table.draw();
    });

    $('#selectSwitchEvent').on('change', function() {
        const targetId = $(this).val();
        if (targetId) {
            window.location.href = "<?php echo e(url('/admin/so-events')); ?>/" + targetId + "/parts";
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
            url: `<?php echo e(url('/admin/so-events/parts')); ?>/${partId}/update-location`,
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

    // Handle Klik Lihat Detail Part (baik dari tombol 'Detail' maupun klik Nama/Kode Part)
    $(document).on('click', '.btn-view-detail, .part-row-clickable', function() {
        let partId = $(this).data('id');
        if (!partId) {
            // Jika diklik dari .part-row-clickable di dalam row
            const tr = $(this).closest('tr');
            const rowData = table.row(tr).data();
            if (rowData) {
                partId = rowData.id;
            }
        }

        if (!partId) return;

        // Reset & Tampilkan Modal
        $('#detailItemCode').text('Memuat...');
        $('#detailPartName').text('-');
        $('#detailRackNo').text('-');
        $('#detailLoc').text('-');
        $('#detailStep').text('-').attr('class', 'badge bg-secondary');
        $('#detailMethod').text('-');
        $('#detailFinalCount').text('-');
        $('#detailTrackingCode').text('-');
        $('#detailPhotoCount').text('0');
        $('#detailTimelineContainer').html(`
            <div class="text-center py-5">
                <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2"></i>
                <div class="small text-muted">Sedang memuat riwayat tahapan & foto dokumentasi...</div>
            </div>
        `);
        $('#detailGalleryContainer').html(`
            <div class="col-12 text-center py-5 text-muted small">
                <i class="fas fa-spinner fa-spin me-1"></i> Memuat galeri foto...
            </div>
        `);

        // Aktifkan tab pertama
        $('#pills-timeline-tab').tab('show');
        $('#modalPartDetail').modal('show');

        $.ajax({
            url: `<?php echo e(url('/parts/detail')); ?>/${partId}`,
            type: 'GET',
            success: function(res) {
                if (!res.success) {
                    swal('Gagal', res.message || 'Gagal memuat detail part.', 'error');
                    return;
                }

                const item = res.item;
                const timeline = res.timeline || [];
                const gallery = res.gallery || [];

                // Header Info
                $('#detailItemCode').text(item.item_code);
                $('#detailPartName').text(item.part_name);
                $('#detailRackNo').text(item.rack_no);
                $('#detailLoc').text(item.loc_display);
                $('#detailStep').text(item.current_step);
                $('#detailMethod').text(item.process_type || '-');
                $('#detailFinalCount').text(item.final_count !== '-' ? item.final_count + ' pcs' : '-');
                $('#detailTrackingCode').text(item.tracking_code).attr('title', item.tracking_code);
                $('#detailPhotoCount').text(gallery.length);

                // Step Badge color
                switch (item.current_step) {
                    case 'READY_TO_COUNT': $('#detailStep').attr('class', 'badge bg-warning text-dark'); break;
                    case 'POS1_DONE': $('#detailStep').attr('class', 'badge bg-info text-dark'); break;
                    case 'MISMATCH_NG': $('#detailStep').attr('class', 'badge bg-danger'); break;
                    case 'READY_TO_RETURN': $('#detailStep').attr('class', 'badge bg-primary'); break;
                    case 'RETURNED': $('#detailStep').attr('class', 'badge bg-success'); break;
                    default: $('#detailStep').attr('class', 'badge bg-secondary');
                }

                // Render Timeline Step-by-Step
                if (timeline.length === 0) {
                    $('#detailTimelineContainer').html('<div class="text-center py-4 text-muted small">Belum ada riwayat proses untuk part ini.</div>');
                } else {
                    let timelineHtml = '<div class="timeline-step-list d-flex flex-column gap-3">';
                    timeline.forEach((step, idx) => {
                        let isDone = step.status === 'COMPLETED' || step.status === 'OK';
                        let isNg = step.status === 'NG';
                        let iconBg = isNg ? 'bg-danger text-white' : (isDone ? 'bg-success text-white' : 'bg-light text-muted border');
                        let borderClass = isNg ? 'border-danger' : (isDone ? 'border-success' : 'border-light');

                        let photoBadges = '';
                        if (step.photos && step.photos.length > 0) {
                            photoBadges = '<div class="d-flex gap-2 flex-wrap mt-2">';
                            step.photos.forEach(p => {
                                photoBadges += `
                                    <div class="position-relative">
                                        <img src="${p.url}" class="img-thumbnail btn-zoom-photo" data-url="${p.url}" data-title="${p.title}" style="width: 65px; height: 65px; object-fit: cover; cursor: pointer; border-radius: 6px;" title="Klik untuk memperbesar">
                                        <span class="badge bg-dark bg-opacity-75 position-absolute bottom-0 start-0 m-1" style="font-size:0.6rem;"><i class="fas fa-search-plus"></i></span>
                                    </div>
                                `;
                            });
                            photoBadges += '</div>';
                        }

                        timelineHtml += `
                            <div class="card border ${borderClass} shadow-sm" style="border-radius: 10px;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center ${iconBg}" style="width: 32px; height: 32px; flex-shrink: 0;">
                                                <i class="fas ${step.icon}"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark">${step.step}</h6>
                                                <small class="text-muted"><i class="fas fa-user me-1"></i>${step.pic} &bull; <i class="far fa-clock ms-1 me-1"></i>${step.time}</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="${step.badge} fw-bold">${step.status}</span>
                                            ${step.qty ? `<div class="fw-bold text-success font-monospace mt-1" style="font-size: 0.9rem;">${step.qty}</div>` : ''}
                                        </div>
                                    </div>
                                    <div class="text-muted small mt-2 pt-2 border-top">
                                        ${step.details}
                                    </div>
                                    ${photoBadges}
                                </div>
                            </div>
                        `;
                    });
                    timelineHtml += '</div>';
                    $('#detailTimelineContainer').html(timelineHtml);
                }

                // Render Galeri Semua Foto Dokumentasi
                if (gallery.length === 0) {
                    $('#detailGalleryContainer').html(`
                        <div class="col-12 text-center py-5 text-muted">
                            <i class="fas fa-camera fa-2x mb-2 text-muted opacity-50"></i>
                            <div class="small">Belum ada foto dokumentasi yang diunggah untuk part ini.</div>
                        </div>
                    `);
                } else {
                    let galleryHtml = '';
                    gallery.forEach((g, gIdx) => {
                        galleryHtml += `
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="card h-100 border shadow-sm" style="border-radius: 10px; overflow: hidden;">
                                    <div class="position-relative">
                                        <img src="${g.url}" class="card-img-top btn-zoom-photo" data-url="${g.url}" data-title="${g.title}" style="height: 140px; object-fit: cover; cursor: pointer;" title="Klik untuk memperbesar">
                                        <span class="badge bg-dark bg-opacity-75 position-absolute top-0 end-0 m-2" style="font-size: 0.65rem;">
                                            <i class="fas fa-search-plus me-1"></i>Zoom
                                        </span>
                                    </div>
                                    <div class="card-body p-2 text-center">
                                        <small class="fw-semibold text-dark text-truncate d-block" style="font-size: 0.72rem;" title="${g.title}">
                                            ${g.title}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#detailGalleryContainer').html(galleryHtml);
                }
            },
            error: function(xhr) {
                $('#detailTimelineContainer').html('<div class="alert alert-danger py-2 small">Gagal mengambil data dari server.</div>');
                $('#detailGalleryContainer').html('<div class="alert alert-danger py-2 small">Gagal mengambil foto.</div>');
            }
        });
    });

    // Zoom Foto Preview
    $(document).on('click', '.btn-zoom-photo', function() {
        const url = $(this).data('url');
        const title = $(this).data('title') || 'Foto Dokumentasi';
        $('#photoPreviewTitle').text(title);
        $('#photoPreviewImg').attr('src', url);
        $('#modalPhotoPreview').modal('show');
    });
});
</script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\iseki_stockopname\resources\views/admin/so_events/parts.blade.php ENDPATH**/ ?>