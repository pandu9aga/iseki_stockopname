<?php $__env->startSection('style'); ?>
<style>
    .pos-card-header {
        background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
        color: #fff;
        border-radius: 12px 12px 0 0;
        padding: 1.25rem 1.5rem;
    }
    .preview-box {
        width: 100%;
        min-height: 140px;
        border: 2px dashed #ccc;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        cursor: pointer;
        padding: 1rem;
    }
    .preview-box:hover {
        background: #f1f3f5;
        border-color: #4361ee;
    }
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 10px;
        width: 100%;
        margin-top: 10px;
    }
    .photo-thumb-wrapper {
        position: relative;
        width: 100%;
        padding-top: 100%;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #dee2e6;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .photo-thumb-wrapper img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .photo-thumb-wrapper .btn-remove-photo {
        position: absolute;
        top: 4px;
        right: 4px;
        background: rgba(220, 53, 69, 0.85);
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
    }
    .photo-thumb-wrapper .btn-remove-photo:hover {
        background: #dc3545;
    }
    .match-indicator {
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="page-inner">

        <!-- Top Header & Role Badge -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
            <div>
                <h3 class="fw-bold mb-1">
                    <i class="fas fa-calculator text-primary me-2"></i> Pos 1: Perhitungan Manual
                </h3>
                <span class="text-muted small">Input ganda mandiri & upload bukti foto &rarr; Lanjutkan part ke Pos 2</span>
            </div>
            <div>
                <a href="<?php echo e(route('member.select-role')); ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                    <i class="fas fa-exchange-alt me-1"></i> Ganti Peran
                </a>
            </div>
        </div>

        <div class="row">
            <!-- LEFT: QR Scanner & Item Preview -->
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm mb-3" style="border-radius:14px;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-qrcode text-primary me-2"></i>Scan QR Kurir
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleCamera">
                                <i class="fas fa-camera me-1"></i> <span id="cameraBtnText">Tutup Kamera</span>
                            </button>
                        </div>

                        <!-- Camera Scanner Container -->
                        <div class="mb-3" id="pos1ScannerWrapper" style="border-radius: 12px; overflow: hidden; background: #000;">
                            <div id="pos1Reader" style="width: 100%;"></div>
                        </div>

                        <!-- Scanned Result Card -->
                        <div id="pos1ScanResultCard" class="card border-success bg-success-subtle mb-3 d-none">
                            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-success fw-bold d-block"><i class="fas fa-check-circle me-1"></i> QR Terbaca</small>
                                    <span class="fw-bold text-dark text-break small" id="pos1ScannedText">-</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-success ms-2" id="btnRescanPos1">
                                    <i class="fas fa-redo me-1"></i> Scan Ulang
                                </button>
                            </div>
                        </div>

                        <div class="input-group mb-2">
                            <span class="input-group-text bg-primary text-white border-0">
                                <i class="fas fa-qrcode"></i>
                            </span>
                            <input type="text" class="form-control form-control-lg" id="trackingCodeInput"
                                   placeholder="Scan QR label kurir atau ketik tracking code..."
                                   autocomplete="off" autofocus>
                            <button class="btn btn-primary" type="button" id="btnLookupItem">
                                <i class="fas fa-search me-1"></i> Cek
                            </button>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1 text-muted"></i>
                            Arahkan kamera ke QR kurir pada box part atau ketik tracking code.
                        </div>
                    </div>
                </div>

                <!-- Item Preview Card -->
                <div id="itemPreviewWrapper" class="d-none">
                    <div class="card shadow-sm mb-3 border-primary" style="border-radius:14px; overflow:hidden;">
                        <div class="bg-primary text-white p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-white-50 text-uppercase d-block" style="font-size: 0.7rem; letter-spacing: 1px;">Tracking Code</small>
                                <span class="fw-bold font-monospace" id="previewTrackingCode">-</span>
                            </div>
                            <span class="badge bg-white text-primary" id="previewStep">-</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-muted small">Kode Part</div>
                                    <div class="fw-semibold text-break" id="previewCodePart">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">No. Rak</div>
                                    <div class="fw-semibold text-break" id="previewRackNo">-</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">Nama Part</div>
                                    <div class="fw-semibold text-break" id="previewNamePart">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Lokasi / Cell</div>
                                    <div class="fw-semibold" id="previewLocCell">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Diterima dari Kurir</div>
                                    <div class="fw-semibold" id="previewCourierAt">-</div>
                                </div>
                                <div class="col-12 border-top pt-2 mt-2">
                                    <div class="text-muted small"><i class="fas fa-user-tag me-1 text-primary"></i>PIC Kurir Sebelumnya</div>
                                    <div class="fw-semibold text-primary" id="previewPicCourier">-</div>
                                </div>
                            </div>

                            <div id="denialBanner" class="d-none mt-3 alert alert-warning mb-0" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <span id="denialMessage"></span>
                            </div>

                            
                            <div id="crossPosBanner" class="d-none mt-3 alert alert-info mb-0" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <span id="crossPosMessage"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Riwayat Hitungan & Akumulasi Foto Part Ini -->
                    <div class="card shadow-sm" style="border-radius:14px;">
                        <div class="card-header py-2" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#collapseHistory">
                            <span class="fw-semibold small"><i class="fas fa-history me-2 text-secondary"></i>Riwayat & Foto Part Ini</span>
                        </div>
                        <div class="collapse" id="collapseHistory">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" style="font-size:0.8rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Waktu</th>
                                                <th>Pos</th>
                                                <th>NIK</th>
                                                <th>Qty</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="historyTableBody">
                                            <tr><td colspan="5" class="text-center text-muted py-2">—</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Akumulasi Foto Tersimpan (Dikelompokkan Pos 1 & Pos 2) -->
                                <div class="p-3 border-top d-none" id="existingPhotosSection">
                                    <div class="mb-3 d-none" id="groupPhotosPos1">
                                        <small class="fw-bold text-primary d-block mb-1">
                                            <i class="fas fa-camera me-1"></i>Foto dari Pos 1:
                                        </small>
                                        <div class="d-flex flex-wrap gap-2" id="containerPhotosPos1"></div>
                                    </div>
                                    <div class="d-none" id="groupPhotosPos2">
                                        <small class="fw-bold text-info d-block mb-1">
                                            <i class="fas fa-camera me-1"></i>Foto dari Pos 2:
                                        </small>
                                        <div class="d-flex flex-wrap gap-2" id="containerPhotosPos2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Double Input Manual Form & Photo -->
            <div class="col-lg-7">
                <!-- Placeholder awal -->
                <div id="formPlaceholder" class="card shadow-sm h-100 d-flex flex-column align-items-center justify-content-center p-5 text-center" style="min-height:360px; border-radius:14px;">
                    <div class="p-4 bg-light rounded-circle mb-3">
                        <i class="fas fa-calculator fa-3x text-primary"></i>
                    </div>
                    <h5 class="fw-bold text-muted">Scan QR Kurir untuk Memulai</h5>
                    <p class="text-muted small mb-0">Formulir perhitungan Pos 1 akan aktif setelah QR part terbaca.</p>
                </div>

                <!-- Form Pos 1 -->
                <div id="formWrapperPos1" class="card shadow-sm d-none" style="border-radius:14px; overflow:hidden;">
                    <div class="pos-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-edit me-2"></i>Form Verifikasi Hitung Pos 1
                        </h5>
                        <span class="badge bg-light text-primary">Double Verification</span>
                    </div>

                    <form id="formSubmitPos1" enctype="multipart/form-data" class="card-body p-4">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" id="soItemId" name="so_item_id" value="">

                        <div class="alert alert-info py-2 px-3 small mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Ketikkan hasil hitung fisik Anda dua kali pada <strong>Input 1</strong> dan <strong>Input 2</strong> untuk mencegah kesalahan input data.
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Input 1 -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Hasil Hitung Fisik (Input 1) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-sort-numeric-up"></i></span>
                                    <input type="number" class="form-control form-control-lg fw-bold" id="input1" name="input_1" min="0" placeholder="0" required>
                                    <span class="input-group-text">Pcs</span>
                                </div>
                            </div>

                            <!-- Input 2 -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Ulangi Hitung (Input 2) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-redo"></i></span>
                                    <input type="number" class="form-control form-control-lg fw-bold" id="input2" name="input_2" min="0" placeholder="0" required>
                                    <span class="input-group-text">Pcs</span>
                                </div>
                            </div>
                        </div>

                        <!-- Match indicator text -->
                        <div id="matchAlert" class="mb-3 d-none"></div>

                        <!-- Upload Foto Bukti (Bebas Jumlahnya) -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold mb-0">Foto Bukti Part (Bebas / Multi-Foto)</label>
                                <span class="badge bg-secondary" id="photoCountBadge">0 foto dipilih</span>
                            </div>
                            
                            <div class="preview-box" id="dropAreaPos1" onclick="document.getElementById('photoInputPos1').click()">
                                <div id="previewPhotoPlaceholder" class="text-center p-2 text-muted">
                                    <i class="fas fa-camera fa-2x mb-1 text-primary"></i><br>
                                    <span class="fw-semibold">Ketuk untuk ambil / tambah foto part</span><br>
                                    <small class="text-muted">Bisa ambil berkali-kali atau pilih beberapa foto sekaligus</small>
                                </div>
                            </div>
                            <!-- Multi-file input with camera capture support -->
                            <input type="file" id="photoInputPos1" accept="image/*" capture="environment" multiple class="d-none">
                            
                            <!-- Grid thumbnail preview foto-foto yang diambil -->
                            <div class="photo-grid d-none" id="photoGridPos1"></div>
                            
                            <div class="form-text mt-2">
                                <i class="fas fa-info-circle me-1"></i>Foto akan otomatis dikompres sebelum dikirim agar cepat & hemat penyimpanan.
                            </div>
                        </div>

                        <!-- Catatan Opsional -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Catatan (Opsional)</label>
                            <input type="text" class="form-control" name="notes" id="notesInput" placeholder="Contoh: Part dalam 2 polybox, kemasan utuh, dsb.">
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary px-4" id="btnResetForm">
                                <i class="fas fa-undo me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn btn-primary flex-grow-1 py-3 fw-bold fs-6" id="btnSubmitPos1" disabled>
                                <i class="fas fa-check-circle me-1"></i> Simpan & Lanjutkan ke Pos 2
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Activities Pos 1 by Current User -->
        <div class="card shadow-sm border-0 mt-4" style="border-radius:14px;">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fas fa-list me-2 text-primary"></i>Riwayat Hitungan Pos 1 Terakhir oleh Anda
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Tracking Code</th>
                                <th>Part</th>
                                <th>Rak</th>
                                <th>Hasil Hitung</th>
                                <th>Waktu</th>
                                <th>Status Part</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $recentHistories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="fw-bold font-monospace text-primary"><?php echo e($h->soItem->tracking_code ?? '-'); ?></td>
                                <td><?php echo e($h->soItem->code_part ?? '-'); ?><br><small class="text-muted"><?php echo e(Str::limit($h->soItem->name_part ?? '-', 25)); ?></small></td>
                                <td><?php echo e($h->soItem->rack_no ?? '-'); ?> (<?php echo e(\App\Models\Area::formatLocDisplay($h->soItem->area ?? null, $h->soItem->cell ?? null)); ?>)</td>
                                <td class="fw-bold fs-6 text-dark"><?php echo e(number_format($h->result_count)); ?> pcs</td>
                                <td><?php echo e($h->created_at->format('H:i:s d/m')); ?></td>
                                <td>
                                    <span class="badge bg-info text-dark"><?php echo e($h->soItem->current_step ?? '-'); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Belum ada riwayat hitungan Pos 1.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(asset('assets/js/plugin/html5-qrcode.min.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/plugin/sweetalert/sweetalert.min.js')); ?>"></script>
<script>
$(document).ready(function() {
    const ROUTE_LOOKUP  = "<?php echo e(route('manual.lookup-item')); ?>";
    const ROUTE_SUBMIT  = "<?php echo e(route('manual.pos1.submit')); ?>";
    const ROUTE_HISTORY = "<?php echo e(route('manual.item-history')); ?>";
    const CSRF_TOKEN    = "<?php echo e(csrf_token()); ?>";

    let currentItem = null;
    let pos1QrCode = null;
    let isPos1ScannerRunning = false;
    let compressedPhotoBlobs = []; // Array of Blobs

    // ─────────────────────────────────────────────────────────
    // 1. Camera Scanner
    // ─────────────────────────────────────────────────────────
    async function initPos1Camera() {
        const readerEl = document.getElementById("pos1Reader");
        if (!readerEl) return;

        if (pos1QrCode) {
            try {
                if (isPos1ScannerRunning) {
                    await pos1QrCode.stop();
                }
                await pos1QrCode.clear();
            } catch (e) {
                console.warn("Cleanup previous pos1 scanner:", e);
            }
        }

        $('#pos1ScannerWrapper').removeClass('d-none');
        $('#pos1ScanResultCard').addClass('d-none');
        $('#cameraBtnText').text('Tutup Kamera');
        readerEl.innerHTML = '';

        try {
            pos1QrCode = new Html5Qrcode("pos1Reader", {
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                }
            });

            const config = {
                fps: 20,
                qrbox: function(viewfinderWidth, viewfinderHeight) {
                    const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                    const qrboxEdgeSize = Math.floor(minEdge * 0.75);
                    return { width: qrboxEdgeSize, height: qrboxEdgeSize };
                },
                aspectRatio: 1.0
            };

            try {
                await pos1QrCode.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText) => {
                        console.log("Pos 1 QR Scanned:", decodedText);
                        stopPos1Camera(decodedText);
                        $('#trackingCodeInput').val(decodedText);
                        lookupItem();
                    },
                    (error) => {}
                );
            } catch (errConstraint) {
                console.warn("Pos 1 fallback to getCameras...", errConstraint);
                const devices = await Html5Qrcode.getCameras();
                if (devices && devices.length) {
                    const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('belakang')) || devices[devices.length - 1];
                    await pos1QrCode.start(
                        backCam.id,
                        config,
                        (decodedText) => {
                            console.log("Pos 1 QR Scanned:", decodedText);
                            stopPos1Camera(decodedText);
                            $('#trackingCodeInput').val(decodedText);
                            lookupItem();
                        },
                        (error) => {}
                    );
                } else {
                    throw errConstraint;
                }
            }
            isPos1ScannerRunning = true;
        } catch (err) {
            console.error("Gagal start kamera Pos 1:", err);
            isPos1ScannerRunning = false;
            $('#pos1ScannerWrapper').html(`
                <div class="p-4 text-center text-white bg-dark rounded">
                    <i class="fas fa-camera-slash fa-2x mb-2 text-warning"></i>
                    <p class="small mb-2 font-weight-bold">Tidak dapat mengakses kamera: ${err.message || 'Izin ditolak'}</p>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="initPos1Camera()">
                        <i class="fas fa-redo me-1"></i> Coba Izin Kamera Lagi
                    </button>
                </div>
            `);
            $('#cameraBtnText').text('Buka Kamera');
        }
    }

    async function stopPos1Camera(codeResult = null) {
        if (pos1QrCode && isPos1ScannerRunning) {
            try {
                await pos1QrCode.stop();
            } catch (e) {}
            isPos1ScannerRunning = false;
        }
        $('#pos1ScannerWrapper').addClass('d-none');
        $('#cameraBtnText').text('Buka Kamera');

        if (codeResult) {
            $('#pos1ScannedText').text(codeResult);
            $('#pos1ScanResultCard').removeClass('d-none');
        }
    }

    $('#btnToggleCamera').on('click', function () {
        if (isPos1ScannerRunning) {
            stopPos1Camera();
        } else {
            initPos1Camera();
        }
    });

    $('#btnRescanPos1').on('click', function () {
        initPos1Camera();
    });

    // Auto-open scanner on load
    initPos1Camera();

    // ─────────────────────────────────────────────────────────
    // 2. Lookup Item
    // ─────────────────────────────────────────────────────────
    $('#btnLookupItem').on('click', lookupItem);
    $('#trackingCodeInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            lookupItem();
        }
    });

    function lookupItem() {
        const code = $('#trackingCodeInput').val().trim();
        if (!code) {
            toastWarn('Silakan scan atau masukkan kode tracking.');
            return;
        }

        $('#btnLookupItem').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: ROUTE_LOOKUP,
            type: 'POST',
            data: { tracking_code: code, pos: 'POS1', _token: CSRF_TOKEN },
            success: function(resp) {
                if (!resp.found) {
                    toastError(resp.message || 'Data tidak ditemukan.');
                    resetFormState();
                    return;
                }

                renderItemPreview(resp.item);

                if (!resp.allowed) {
                    $('#denialMessage').text(resp.message);
                    $('#denialBanner').removeClass('d-none');
                    $('#crossPosBanner').addClass('d-none');
                    $('#formPlaceholder').removeClass('d-none');
                    $('#formWrapperPos1').addClass('d-none');
                    return;
                }

                // Allowed
                $('#denialBanner').addClass('d-none');
                if (resp.warning) {
                    $('#crossPosMessage').text(resp.warning);
                    $('#crossPosBanner').removeClass('d-none');
                    toastWarn('Item sudah dihitung di Pos Timbangan. Hitung manual tetap bisa dilanjutkan.');
                } else {
                    $('#crossPosBanner').addClass('d-none');
                }
                $('#formPlaceholder').addClass('d-none');
                $('#formWrapperPos1').removeClass('d-none');
                $('#soItemId').val(resp.item.id);
                currentItem = resp.item;
                $('#input1').focus();

                loadHistory(resp.item.id);
            },
            error: function(xhr) {
                toastError(xhr.responseJSON?.message || 'Gagal memeriksa tracking code.');
            },
            complete: function() {
                $('#btnLookupItem').prop('disabled', false).html('<i class="fas fa-search me-1"></i> Cek');
            }
        });
    }

    function renderItemPreview(item) {
        $('#previewTrackingCode').text(item.tracking_code);
        $('#previewStep').text(item.current_step);
        $('#previewCodePart').text(item.code_part);
        $('#previewRackNo').text(item.rack_no);
        $('#previewNamePart').text(item.name_part);
        $('#previewLocCell').text(item.loc_cell);
        $('#previewCourierAt').text(item.courier_at);
        $('#previewPicCourier').text(item.pic_courier || item.courier_name || item.courier_nik || '-');
        $('#itemPreviewWrapper').removeClass('d-none');
    }

    function loadHistory(soItemId) {
        $.ajax({
            url: ROUTE_HISTORY,
            type: 'GET',
            data: { so_item_id: soItemId },
            success: function(resp) {
                const list = resp.histories || [];
                if (list.length === 0) {
                    $('#historyTableBody').html('<tr><td colspan="5" class="text-center text-muted py-2">Belum ada riwayat.</td></tr>');
                } else {
                    let rows = '';
                    list.forEach(h => {
                        const badge = h.status === 'OK' ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">NG</span>';
                        rows += `<tr>
                            <td>${h.created_at}</td>
                            <td><span class="badge bg-secondary">${h.count_type}</span></td>
                            <td>${h.member_nik}</td>
                            <td class="fw-bold">${h.result_count}</td>
                            <td>${badge}</td>
                        </tr>`;
                    });
                    $('#historyTableBody').html(rows);
                }

                // Render akumulasi foto yang dikelompokkan Pos 1 & Pos 2
                const photos = resp.photos || [];
                const cPos1 = $('#containerPhotosPos1').empty();
                const cPos2 = $('#containerPhotosPos2').empty();
                let hasPos1 = false;
                let hasPos2 = false;

                if (photos.length > 0) {
                    $('#existingPhotosSection').removeClass('d-none');
                    photos.forEach(p => {
                        const thumb = `
                            <a href="${p.url}" target="_blank" title="Foto [${p.pos_type}] - ${p.created_at}">
                                <img src="${p.url}" style="width:58px; height:58px; object-fit:cover; border-radius:6px; border:1px solid #ccc;">
                            </a>
                        `;
                        if (p.pos_type === 'POS1') {
                            cPos1.append(thumb);
                            hasPos1 = true;
                        } else {
                            cPos2.append(thumb);
                            hasPos2 = true;
                        }
                    });

                    if (hasPos1) $('#groupPhotosPos1').removeClass('d-none');
                    else $('#groupPhotosPos1').addClass('d-none');

                    if (hasPos2) $('#groupPhotosPos2').removeClass('d-none');
                    else $('#groupPhotosPos2').addClass('d-none');
                } else {
                    $('#existingPhotosSection').addClass('d-none');
                }
            }
        });
    }

    // ─────────────────────────────────────────────────────────
    // 3. Double Input Validation
    // ─────────────────────────────────────────────────────────
    $('#input1, #input2').on('input', function() {
        validateDoubleInput();
    });

    function validateDoubleInput() {
        const val1 = $('#input1').val().trim();
        const val2 = $('#input2').val().trim();

        if (val1 === '' || val2 === '') {
            $('#matchAlert').addClass('d-none').html('');
            $('#btnSubmitPos1').prop('disabled', true);
            return false;
        }

        const num1 = parseInt(val1);
        const num2 = parseInt(val2);

        if (isNaN(num1) || isNaN(num2) || num1 < 0 || num2 < 0) {
            $('#matchAlert').removeClass('d-none alert-success').addClass('alert alert-danger py-2 small')
                .html('<i class="fas fa-times-circle me-1"></i> Nilai input harus angka bulat &ge; 0.');
            $('#btnSubmitPos1').prop('disabled', true);
            return false;
        }

        if (num1 === num2) {
            $('#matchAlert').removeClass('d-none alert-danger').addClass('alert alert-success py-2 small')
                .html(`<i class="fas fa-check-circle me-1"></i> <strong>Sesuai:</strong> ${num1} Pcs terverifikasi.`);
            $('#btnSubmitPos1').prop('disabled', false);
            return true;
        } else {
            $('#matchAlert').removeClass('d-none alert-success').addClass('alert alert-danger py-2 small')
                .html(`<i class="fas fa-exclamation-circle me-1"></i> <strong>Tidak Cocok:</strong> Input 1 (${num1}) &ne; Input 2 (${num2}).`);
            $('#btnSubmitPos1').prop('disabled', true);
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────
    // 4. Photo Compression & Multi-Photo Management
    // ─────────────────────────────────────────────────────────
    $('#photoInputPos1').on('change', async function(e) {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        $('#previewPhotoPlaceholder').html('<i class="fas fa-spinner fa-spin fa-2x mb-1 text-primary"></i><br><small>Mengompres foto...</small>');

        for (let i = 0; i < files.length; i++) {
            try {
                const blob = await compressImageFile(files[i]);
                compressedPhotoBlobs.push(blob);
            } catch (err) {
                console.error("Gagal kompres foto:", err);
            }
        }

        renderPhotoGrid();
        // Reset input agar bisa jepret foto baru dengan kamera berulang kali
        $(this).val('');
    });

    function renderPhotoGrid() {
        const grid = $('#photoGridPos1');
        grid.empty();

        if (compressedPhotoBlobs.length === 0) {
            grid.addClass('d-none');
            $('#photoCountBadge').text('0 foto dipilih').removeClass('bg-primary').addClass('bg-secondary');
            $('#previewPhotoPlaceholder').html(`
                <i class="fas fa-camera fa-2x mb-1 text-primary"></i><br>
                <span class="fw-semibold">Ketuk untuk ambil / tambah foto part</span><br>
                <small class="text-muted">Bisa ambil berkali-kali atau pilih beberapa foto sekaligus</small>
            `);
            return;
        }

        grid.removeClass('d-none');
        $('#photoCountBadge').text(compressedPhotoBlobs.length + ' foto dipilih').removeClass('bg-secondary').addClass('bg-primary');
        $('#previewPhotoPlaceholder').html(`
            <i class="fas fa-plus-circle fa-2x mb-1 text-primary"></i><br>
            <span class="fw-semibold">Ketuk untuk menambah foto lainnya</span>
        `);

        compressedPhotoBlobs.forEach((blob, idx) => {
            const objUrl = URL.createObjectURL(blob);
            const thumb = $(`
                <div class="photo-thumb-wrapper">
                    <img src="${objUrl}" alt="Foto ${idx+1}">
                    <button type="button" class="btn-remove-photo" data-index="${idx}" title="Hapus foto">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
            grid.append(thumb);
        });
    }

    $(document).on('click', '.btn-remove-photo', function(e) {
        e.stopPropagation();
        const idx = parseInt($(this).data('index'));
        if (!isNaN(idx) && idx >= 0 && idx < compressedPhotoBlobs.length) {
            compressedPhotoBlobs.splice(idx, 1);
            renderPhotoGrid();
        }
    });

    function compressImageFile(file, maxWidth = 1280, quality = 0.75) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    let w = img.width;
                    let h = img.height;
                    if (w > maxWidth || h > maxWidth) {
                        const ratio = Math.min(maxWidth / w, maxWidth / h);
                        w = Math.round(w * ratio);
                        h = Math.round(h * ratio);
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, w, h);
                    canvas.toBlob((blob) => resolve(blob), 'image/jpeg', quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    // ─────────────────────────────────────────────────────────
    // 5. Submit Form Pos 1
    // ─────────────────────────────────────────────────────────
    $('#formSubmitPos1').on('submit', function(e) {
        e.preventDefault();
        if (!validateDoubleInput()) return;

        const btn = $('#btnSubmitPos1');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

        const formData = new FormData(this);

        // Append multiple compressed photos
        if (compressedPhotoBlobs.length > 0) {
            compressedPhotoBlobs.forEach((blob, i) => {
                formData.append('photos[]', blob, `pos1_photo_${i+1}.jpg`);
            });
        }

        $.ajax({
            url: ROUTE_SUBMIT,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp) {
                if (resp.success) {
                    if (typeof swal === 'function') {
                        swal({
                            title: 'Berhasil!',
                            text: resp.message,
                            type: 'success',
                            confirmButtonClass: 'btn btn-success'
                        }).then(() => {
                            resetFormState();
                            initPos1Camera();
                        });
                    } else {
                        alert(resp.message);
                        resetFormState();
                        initPos1Camera();
                    }
                } else {
                    toastError(resp.message || 'Gagal menyimpan.');
                    btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Simpan & Lanjutkan ke Pos 2');
                }
            },
            error: function(xhr) {
                toastError(xhr.responseJSON?.message || 'Terjadi kesalahan sistem.');
                btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Simpan & Lanjutkan ke Pos 2');
            }
        });
    });

    $('#btnResetForm').on('click', function() {
        resetFormState();
    });

    function resetFormState() {
        currentItem = null;
        compressedPhotoBlobs = [];
        $('#formSubmitPos1')[0].reset();
        $('#soItemId').val('');
        $('#matchAlert').addClass('d-none').html('');
        renderPhotoGrid();
        $('#formWrapperPos1').addClass('d-none');
        $('#formPlaceholder').removeClass('d-none');
        $('#itemPreviewWrapper').addClass('d-none');
        $('#crossPosBanner').addClass('d-none');
        $('#trackingCodeInput').val('').focus();
        $('#btnSubmitPos1').prop('disabled', true).html('<i class="fas fa-check-circle me-1"></i> Simpan & Lanjutkan ke Pos 2');
    }

    function toastWarn(msg) {
        if (typeof swal === 'function') {
            swal({ title: 'Perhatian', text: msg, type: 'warning' });
        } else {
            alert(msg);
        }
    }

    function toastError(msg) {
        if (typeof swal === 'function') {
            swal({ title: 'Gagal', text: msg, type: 'error' });
        } else {
            alert(msg);
        }
    }
});
</script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\iseki_stockopname\resources\views/manual/pos1.blade.php ENDPATH**/ ?>