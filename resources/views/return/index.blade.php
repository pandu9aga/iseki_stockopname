@extends('layouts.main')

@section('style')
<style>
    .return-header-card {
        background: linear-gradient(135deg, #0582ca 0%, #006494 100%);
        color: #fff;
        border-radius: 12px;
        padding: 0.75rem 1rem;
    }
    @media (min-width: 768px) {
        .return-header-card {
            padding: 1rem 1.25rem;
        }
    }
    .step-box {
        border: 2px dashed #dee2e6;
        border-radius: 12px;
        padding: 1rem;
        transition: all 0.2s ease;
        background: #fff;
    }
    .step-box.active {
        border-color: #0582ca;
        background: #f0f8ff;
    }
    .step-box.completed {
        border-color: #28a745;
        background: #f4fbf5;
    }
    .step-badge {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
    }
    .preview-box {
        width: 100%;
        min-height: 110px;
        border: 2px dashed #ccc;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        cursor: pointer;
        padding: 0.75rem;
    }
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        gap: 8px;
        width: 100%;
        margin-top: 8px;
    }
    .photo-thumb-wrapper {
        position: relative;
        width: 100%;
        padding-top: 100%;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #dee2e6;
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
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">

        <!-- Top Header & Role Switcher Button (Compact for Mobile) -->
        <div class="return-header-card shadow-sm mb-3">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                        <i class="fas fa-undo-alt text-warning"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-white" style="font-size:0.95rem;">Pos Pengembalian (Return)</h6>
                        <small class="text-white-50" style="font-size:0.75rem;">
                            User: <strong>{{ $member->nama ?? $member->nik }}</strong>
                        </small>
                    </div>
                </div>
                <div>
                    <a href="{{ route('member.select-role') }}" class="btn btn-outline-light btn-xs px-2 py-1 rounded-pill" style="font-size:0.75rem;">
                        <i class="fas fa-exchange-alt me-1"></i> Ganti Peran
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- LEFT: Dual Scanner Workflow Steps -->
            <div class="col-lg-5 mb-4">

                <!-- STEP 1: Scan Label Final (QR box part) -->
                <div class="step-box active shadow-sm mb-2" id="boxStep1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <span class="step-badge bg-primary text-white" id="badgeStep1">1</span>
                            <span class="fw-bold small mb-0">Scan QR Box Part</span>
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" id="btnToggleCam1" style="font-size:0.75rem;">
                            <i class="fas fa-camera me-1"></i><span id="txtToggleCam1">Kamera</span>
                        </button>
                    </div>

                    <!-- Scanner Container 1 -->
                    <div class="mb-2" id="wrapperCam1" style="border-radius: 8px; overflow: hidden; background: #000;">
                        <div id="reader1" style="width: 100%;"></div>
                    </div>

                    <!-- Scanned Label Card -->
                    <div id="resultLabelCard" class="card border-primary bg-primary-subtle p-2 mb-2 d-none">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-primary fw-bold d-block" style="font-size:0.7rem;"><i class="fas fa-check-circle me-1"></i> Label Terbaca</small>
                                <span class="fw-bold text-dark small font-monospace" id="txtScannedLabel">-</span>
                            </div>
                            <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2" id="btnRescanLabel" style="font-size:0.75rem;">
                                <i class="fas fa-redo me-1"></i> Scan Ulang
                            </button>
                        </div>
                    </div>

                    <div class="input-group input-group-sm mb-1">
                        <input type="text" class="form-control" id="inputLabelQr" placeholder="Arahkan kamera ke QR box..." autocomplete="off">
                        <button class="btn btn-primary" type="button" id="btnCheckLabel">Cek</button>
                    </div>
                </div>

                <!-- STEP 2: Scan QR Rak Fisik Tujuan -->
                <div class="step-box shadow-sm mb-2" id="boxStep2" style="opacity: 0.6; pointer-events: none;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <span class="step-badge bg-secondary text-white" id="badgeStep2">2</span>
                            <span class="fw-bold small mb-0">Scan QR Rak Fisik</span>
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" id="btnToggleCam2" style="font-size:0.75rem;">
                            <i class="fas fa-camera me-1"></i><span id="txtToggleCam2">Kamera</span>
                        </button>
                    </div>

                    <!-- Scanner Container 2 -->
                    <div class="mb-2 d-none" id="wrapperCam2" style="border-radius: 8px; overflow: hidden; background: #000;">
                        <div id="reader2" style="width: 100%;"></div>
                    </div>

                    <!-- Scanned Rack Card -->
                    <div id="resultRackCard" class="card border-success bg-success-subtle p-2 mb-2 d-none">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-success fw-bold d-block" style="font-size:0.7rem;"><i class="fas fa-check-circle me-1"></i> Rak Terverifikasi</small>
                                <span class="fw-bold text-dark font-monospace small" id="txtScannedRack">-</span>
                            </div>
                            <button type="button" class="btn btn-xs btn-outline-success py-1 px-2" id="btnRescanRack" style="font-size:0.75rem;">
                                <i class="fas fa-redo me-1"></i> Scan Ulang
                            </button>
                        </div>
                    </div>

                    <div class="input-group input-group-sm mb-1">
                        <input type="text" class="form-control" id="inputRackQr" placeholder="Scan barcode/QR stiker rak..." autocomplete="off">
                        <button class="btn btn-secondary" type="button" id="btnVerifyRack">Verifikasi</button>
                    </div>

                    <!-- Feedback Alert Verifikasi Rak -->
                    <div id="rackVerifyAlert" class="mt-2 d-none"></div>
                </div>

            </div>

            <!-- RIGHT: Item Summary & Proof Photo Form -->
            <div class="col-lg-7">

                <!-- Placeholder awal -->
                <div id="returnPlaceholder" class="card shadow-sm h-100 d-flex flex-column align-items-center justify-content-center p-4 text-center" style="min-height:220px; border-radius:12px;">
                    <div class="p-3 bg-light rounded-circle mb-2">
                        <i class="fas fa-dolly fa-2x text-primary"></i>
                    </div>
                    <span class="fw-bold text-muted small">Scan QR Label Box Part</span>
                    <p class="text-muted mb-0" style="font-size:0.75rem;">Informasi part dan rak tujuan akan muncul di sini.</p>
                </div>

                <!-- Form Konfirmasi & Upload Foto Bukti Pengembalian -->
                <div id="returnFormWrapper" class="card shadow-sm d-none mb-3" style="border-radius:12px; overflow:hidden;">
                    <div class="card-header bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center">
                        <span class="fw-bold small mb-0">
                            <i class="fas fa-box-open text-warning me-1"></i>Pengembalian Part
                        </span>
                        <span class="badge bg-warning text-dark py-1 px-2" id="headerStepBadge" style="font-size:0.7rem;">READY TO RETURN</span>
                    </div>

                    <div class="card-body p-3">
                        <!-- Detail Part -->
                        <div class="card bg-light border-0 p-2 mb-3" style="border-radius:8px;">
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size:0.7rem;">Kode Part</small>
                                    <span class="fw-bold text-primary small" id="infoCodePart">-</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size:0.7rem;">Rak Tujuan</small>
                                    <span class="fw-bold text-danger font-monospace fs-6" id="infoTargetRack">-</span>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block" style="font-size:0.7rem;">Nama Part</small>
                                    <span class="fw-semibold text-dark small" id="infoNamePart">-</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size:0.7rem;">Area / Cell</small>
                                    <span class="fw-semibold small" id="infoLocCell">-</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size:0.7rem;">Qty Final SO</small>
                                    <span class="fw-bold text-success fs-6" id="infoFinalCount">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Upload Foto Bukti Penataan Rak -->
                        <form id="formSubmitReturn" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" id="soItemId" name="so_item_id" value="">
                            <input type="hidden" id="verifiedRackScanned" name="return_rack_scanned" value="">

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold mb-0 small">
                                        <span class="badge bg-primary me-1">3</span>Foto Part di Rak <span class="text-danger">*</span>
                                    </label>
                                    <span class="badge bg-secondary" id="returnPhotoCountBadge" style="font-size:0.7rem;">0 foto</span>
                                </div>

                                <div class="preview-box" id="dropAreaReturn" onclick="document.getElementById('photoInputReturn').click()">
                                    <div id="previewReturnPlaceholder" class="text-center p-2 text-muted">
                                        <i class="fas fa-camera fa-lg mb-1 text-primary"></i><br>
                                        <span class="fw-semibold small">Ketuk ambil foto di rak</span><br>
                                        <small class="text-muted" style="font-size:0.7rem;">Bisa ambil lebih dari 1 foto</small>
                                    </div>
                                </div>
                                <input type="file" id="photoInputReturn" accept="image/*" capture="environment" multiple class="d-none">

                                <div class="photo-grid d-none" id="photoGridReturn"></div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-3" id="btnCancelReturn">
                                    <i class="fas fa-undo me-1"></i> Batal
                                </button>
                                <button type="submit" class="btn btn-success flex-grow-1 py-2 fw-bold" id="btnCompleteReturn" disabled>
                                    <i class="fas fa-check-double me-1"></i> Selesaikan (RETURNED)
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        <!-- Recent Returns by Current User -->
        <div class="card shadow-sm border-0 mt-4" style="border-radius:14px;">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fas fa-history me-2 text-primary"></i>Riwayat Pengembalian Terakhir oleh Anda
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Tracking Code</th>
                                <th>Part</th>
                                <th>Rak Tujuan</th>
                                <th>Rak Terverifikasi</th>
                                <th>Qty Final</th>
                                <th>Waktu Kembali</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentReturns as $r)
                            <tr>
                                <td class="fw-bold font-monospace text-primary">{{ $r->tracking_code }}</td>
                                <td>{{ $r->code_part }}<br><small class="text-muted">{{ Str::limit($r->name_part, 25) }}</small></td>
                                <td class="fw-bold text-dark">{{ $r->rack_no }}</td>
                                <td class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>{{ $r->return_rack_scanned }}</td>
                                <td class="fw-bold">{{ number_format($r->final_count) }} pcs</td>
                                <td>{{ $r->returned_at ? $r->returned_at->format('H:i:s d/m/Y') : '-' }}</td>
                                <td><span class="badge bg-success">RETURNED</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">Belum ada riwayat pengembalian yang Anda lakukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('assets/js/plugin/html5-qrcode.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/sweetalert/sweetalert.min.js') }}"></script>
<script>
$(document).ready(function() {
    const ROUTE_LOOKUP_LABEL = "{{ route('return.lookup-label') }}";
    const ROUTE_VERIFY_RACK  = "{{ route('return.verify-rack') }}";
    const ROUTE_SUBMIT       = "{{ route('return.submit') }}";
    const CSRF_TOKEN         = "{{ csrf_token() }}";

    let qrCodeCam1 = null;
    let isCam1Running = false;

    let qrCodeCam2 = null;
    let isCam2Running = false;

    let currentItem = null;
    let isRackVerified = false;
    let returnPhotosBlobs = [];

    // ─────────────────────────────────────────────────────────
    // 1. Camera Scanner 1: Label Final
    // ─────────────────────────────────────────────────────────
    async function initCamera1() {
        const readerEl = document.getElementById("reader1");
        if (!readerEl) return;

        if (qrCodeCam1) {
            try {
                if (isCam1Running) await qrCodeCam1.stop();
                await qrCodeCam1.clear();
            } catch (e) {}
        }

        $('#wrapperCam1').removeClass('d-none');
        $('#resultLabelCard').addClass('d-none');
        $('#txtToggleCam1').text('Tutup Kamera');
        readerEl.innerHTML = '';

        try {
            qrCodeCam1 = new Html5Qrcode("reader1", {
                experimentalFeatures: { useBarCodeDetectorIfSupported: true }
            });
            const config = {
                fps: 20,
                qrbox: function(w, h) {
                    const min = Math.min(w, h);
                    const s = Math.floor(min * 0.75);
                    return { width: s, height: s };
                },
                aspectRatio: 1.0
            };

            await qrCodeCam1.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    stopCamera1(decodedText);
                    $('#inputLabelQr').val(decodedText);
                    checkLabelQr();
                },
                (err) => {}
            );
            isCam1Running = true;
        } catch (err) {
            console.warn("Cam 1 fallback...", err);
            isCam1Running = false;
            try {
                const devices = await Html5Qrcode.getCameras();
                if (devices && devices.length) {
                    const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('belakang')) || devices[devices.length - 1];
                    await qrCodeCam1.start(
                        backCam.id,
                        config,
                        (decodedText) => {
                            stopCamera1(decodedText);
                            $('#inputLabelQr').val(decodedText);
                            checkLabelQr();
                        },
                        (err) => {}
                    );
                    isCam1Running = true;
                }
            } catch (e) {
                $('#wrapperCam1').html(`
                    <div class="p-3 text-center text-white bg-dark rounded">
                        <i class="fas fa-camera-slash me-1 text-warning"></i> Izin kamera ditolak
                    </div>
                `);
                $('#txtToggleCam1').text('Buka Kamera');
            }
        }
    }

    async function stopCamera1(code = null) {
        if (qrCodeCam1 && isCam1Running) {
            try { await qrCodeCam1.stop(); } catch (e) {}
            isCam1Running = false;
        }
        $('#wrapperCam1').addClass('d-none');
        $('#txtToggleCam1').text('Buka Kamera');
        if (code) {
            $('#txtScannedLabel').text(code);
            $('#resultLabelCard').removeClass('d-none');
        }
    }

    $('#btnToggleCam1').on('click', function() {
        if (isCam1Running) stopCamera1();
        else initCamera1();
    });
    $('#btnRescanLabel').on('click', function() {
        initCamera1();
    });

    // Auto open camera 1 on page load
    initCamera1();

    // ─────────────────────────────────────────────────────────
    // 2. Camera Scanner 2: Rak Fisik Tujuan
    // ─────────────────────────────────────────────────────────
    async function initCamera2() {
        const readerEl = document.getElementById("reader2");
        if (!readerEl) return;

        if (qrCodeCam2) {
            try {
                if (isCam2Running) await qrCodeCam2.stop();
                await qrCodeCam2.clear();
            } catch (e) {}
        }

        $('#wrapperCam2').removeClass('d-none');
        $('#resultRackCard').addClass('d-none');
        $('#txtToggleCam2').text('Tutup Kamera');
        readerEl.innerHTML = '';

        try {
            qrCodeCam2 = new Html5Qrcode("reader2", {
                experimentalFeatures: { useBarCodeDetectorIfSupported: true }
            });
            const config = {
                fps: 20,
                qrbox: function(w, h) {
                    const min = Math.min(w, h);
                    const s = Math.floor(min * 0.75);
                    return { width: s, height: s };
                },
                aspectRatio: 1.0
            };

            await qrCodeCam2.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    stopCamera2(decodedText);
                    $('#inputRackQr').val(decodedText);
                    verifyRackQr();
                },
                (err) => {}
            );
            isCam2Running = true;
        } catch (err) {
            isCam2Running = false;
            try {
                const devices = await Html5Qrcode.getCameras();
                if (devices && devices.length) {
                    const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('belakang')) || devices[devices.length - 1];
                    await qrCodeCam2.start(
                        backCam.id,
                        config,
                        (decodedText) => {
                            stopCamera2(decodedText);
                            $('#inputRackQr').val(decodedText);
                            verifyRackQr();
                        },
                        (err) => {}
                    );
                    isCam2Running = true;
                }
            } catch (e) {
                $('#wrapperCam2').html(`
                    <div class="p-3 text-center text-white bg-dark rounded">
                        <i class="fas fa-camera-slash me-1 text-warning"></i> Izin kamera ditolak
                    </div>
                `);
                $('#txtToggleCam2').text('Buka Kamera');
            }
        }
    }

    async function stopCamera2(code = null) {
        if (qrCodeCam2 && isCam2Running) {
            try { await qrCodeCam2.stop(); } catch (e) {}
            isCam2Running = false;
        }
        $('#wrapperCam2').addClass('d-none');
        $('#txtToggleCam2').text('Buka Kamera');
        if (code) {
            $('#txtScannedRack').text(code);
            $('#resultRackCard').removeClass('d-none');
        }
    }

    $('#btnToggleCam2').on('click', function() {
        if (isCam2Running) stopCamera2();
        else initCamera2();
    });
    $('#btnRescanRack').on('click', function() {
        initCamera2();
    });

    // ─────────────────────────────────────────────────────────
    // 3. Check Label Final QR
    // ─────────────────────────────────────────────────────────
    $('#btnCheckLabel').on('click', checkLabelQr);
    $('#inputLabelQr').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            checkLabelQr();
        }
    });

    function checkLabelQr() {
        const qr = $('#inputLabelQr').val().trim();
        if (!qr) {
            toastWarn('Scan atau masukkan QR label final terlebih dahulu.');
            return;
        }

        $('#btnCheckLabel').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: ROUTE_LOOKUP_LABEL,
            type: 'POST',
            data: { qr_code: qr, _token: CSRF_TOKEN },
            success: function(resp) {
                if (!resp.found) {
                    toastError(resp.message || 'Data label tidak ditemukan.');
                    return;
                }

                if (!resp.allowed) {
                    toastWarn(resp.message);
                    return;
                }

                currentItem = resp.item;
                renderItemInfo(resp.item);

                // Tandai Step 1 Complete & Aktifkan Step 2
                $('#boxStep1').removeClass('active').addClass('completed');
                $('#badgeStep1').removeClass('bg-primary').addClass('bg-success').html('<i class="fas fa-check"></i>');

                $('#boxStep2').css({ 'opacity': '1', 'pointer-events': 'auto' }).addClass('active');
                $('#badgeStep2').removeClass('bg-secondary').addClass('bg-primary');
                $('#btnVerifyRack').removeClass('btn-secondary').addClass('btn-primary');

                // Buka otomatis kamera 2 untuk scan rak fisik tujuan
                initCamera2();
            },
            error: function(xhr) {
                toastError(xhr.responseJSON?.message || 'Gagal memeriksa label.');
            },
            complete: function() {
                $('#btnCheckLabel').prop('disabled', false).html('Cek');
            }
        });
    }

    function renderItemInfo(item) {
        $('#infoCodePart').text(item.code_part);
        $('#infoTargetRack').text(item.rack_no);
        $('#infoNamePart').text(item.name_part);
        $('#infoLocCell').text(item.loc_cell);
        $('#infoFinalCount').text(Number(item.final_count).toLocaleString('id-ID') + ' Pcs');
        $('#soItemId').val(item.id);

        $('#returnPlaceholder').addClass('d-none');
        $('#returnFormWrapper').removeClass('d-none');
    }

    // ─────────────────────────────────────────────────────────
    // 4. Verify Rack Match
    // ─────────────────────────────────────────────────────────
    $('#btnVerifyRack').on('click', verifyRackQr);
    $('#inputRackQr').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            verifyRackQr();
        }
    });

    function verifyRackQr() {
        if (!currentItem) {
            toastWarn('Scan label final part terlebih dahulu.');
            return;
        }

        const rackInput = $('#inputRackQr').val().trim();
        if (!rackInput) {
            toastWarn('Scan atau ketik kode rak fisik.');
            return;
        }

        $('#btnVerifyRack').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: ROUTE_VERIFY_RACK,
            type: 'POST',
            data: {
                so_item_id: currentItem.id,
                scanned_rack: rackInput,
                _token: CSRF_TOKEN
            },
            success: function(resp) {
                if (resp.match) {
                    isRackVerified = true;
                    $('#verifiedRackScanned').val(resp.scanned_rack);

                    $('#rackVerifyAlert').removeClass('d-none alert-danger').addClass('alert alert-success py-2 px-3 small')
                        .html(`<i class="fas fa-check-circle me-1"></i> <strong>COCOK:</strong> Rak fisik sesuai (${resp.scanned_rack}).`);

                    $('#boxStep2').removeClass('active').addClass('completed');
                    $('#badgeStep2').removeClass('bg-primary').addClass('bg-success').html('<i class="fas fa-check"></i>');

                    validateCompleteButton();

                    if (typeof swal === 'function') {
                        swal({
                            title: 'Rak Fisik Cocok!',
                            text: 'Part ini sesuai untuk rak ' + resp.expected_rack + '. Silakan letakkan box part di rak dan ambil foto bukti penataan.',
                            type: 'success'
                        });
                    } else {
                        alert('Rak fisik cocok! Silakan ambil foto penataan.');
                    }
                } else {
                    isRackVerified = false;
                    $('#verifiedRackScanned').val('');
                    $('#rackVerifyAlert').removeClass('d-none alert-success').addClass('alert alert-danger py-2 px-3 small')
                        .html(`<i class="fas fa-times-circle me-1"></i> <strong>MISMATCH:</strong> Rak salah! Seharusnya <strong>${resp.expected_rack}</strong>, Anda scan <strong>${resp.scanned_rack}</strong>.`);

                    validateCompleteButton();

                    if (typeof swal === 'function') {
                        swal({
                            title: 'Rak Fisik Tidak Cocok!',
                            text: resp.message,
                            type: 'error'
                        });
                    } else {
                        alert(resp.message);
                    }
                }
            },
            error: function(xhr) {
                toastError(xhr.responseJSON?.message || 'Gagal memverifikasi rak.');
            },
            complete: function() {
                $('#btnVerifyRack').prop('disabled', false).html('Verifikasi');
            }
        });
    }

    // ─────────────────────────────────────────────────────────
    // 5. Photo Proof Upload
    // ─────────────────────────────────────────────────────────
    $('#photoInputReturn').on('change', async function(e) {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        $('#previewReturnPlaceholder').html('<i class="fas fa-spinner fa-spin fa-2x mb-1 text-primary"></i><br><small>Mengompres foto...</small>');

        for (let i = 0; i < files.length; i++) {
            try {
                const blob = await compressImageFile(files[i]);
                returnPhotosBlobs.push(blob);
            } catch (err) {
                console.error("Gagal kompres foto:", err);
            }
        }

        renderReturnPhotoGrid();
        validateCompleteButton();
        $(this).val('');
    });

    function renderReturnPhotoGrid() {
        const grid = $('#photoGridReturn');
        grid.empty();

        if (returnPhotosBlobs.length === 0) {
            grid.addClass('d-none');
            $('#returnPhotoCountBadge').text('0 foto dipilih').removeClass('bg-primary').addClass('bg-secondary');
            $('#previewReturnPlaceholder').html(`
                <i class="fas fa-camera fa-2x mb-1 text-primary"></i><br>
                <span class="fw-semibold">Ketuk untuk ambil foto part tertata di rak</span><br>
                <small class="text-muted">Bisa ambil berkali-kali jika butuh lebih dari 1 sudut foto</small>
            `);
            return;
        }

        grid.removeClass('d-none');
        $('#returnPhotoCountBadge').text(returnPhotosBlobs.length + ' foto dipilih').removeClass('bg-secondary').addClass('bg-primary');
        $('#previewReturnPlaceholder').html(`
            <i class="fas fa-plus-circle fa-2x mb-1 text-primary"></i><br>
            <span class="fw-semibold">Ketuk untuk menambah foto lainnya</span>
        `);

        returnPhotosBlobs.forEach((blob, idx) => {
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

    $(document).on('click', '#photoGridReturn .btn-remove-photo', function(e) {
        e.stopPropagation();
        const idx = parseInt($(this).data('index'));
        if (!isNaN(idx) && idx >= 0 && idx < returnPhotosBlobs.length) {
            returnPhotosBlobs.splice(idx, 1);
            renderReturnPhotoGrid();
            validateCompleteButton();
        }
    });

    function validateCompleteButton() {
        if (isRackVerified && returnPhotosBlobs.length > 0) {
            $('#btnCompleteReturn').prop('disabled', false);
        } else {
            $('#btnCompleteReturn').prop('disabled', true);
        }
    }

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
    // 6. Submit Return
    // ─────────────────────────────────────────────────────────
    $('#formSubmitReturn').on('submit', function(e) {
        e.preventDefault();

        if (!isRackVerified) {
            toastWarn('Verifikasi rak fisik terlebih dahulu.');
            return;
        }

        if (returnPhotosBlobs.length === 0) {
            toastWarn('Ambil minimal 1 foto bukti part tertata di rak.');
            return;
        }

        const btn = $('#btnCompleteReturn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan Pengembalian...');

        const fd = new FormData();
        fd.append('so_item_id', $('#soItemId').val());
        fd.append('return_rack_scanned', $('#verifiedRackScanned').val());
        fd.append('_token', CSRF_TOKEN);

        returnPhotosBlobs.forEach((blob, i) => {
            fd.append('photos[]', blob, `return_photo_${i+1}.jpg`);
        });

        $.ajax({
            url: ROUTE_SUBMIT,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(resp) {
                if (resp.success) {
                    if (typeof swal === 'function') {
                        swal({
                            title: 'Pengembalian Selesai!',
                            text: resp.message,
                            type: 'success'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        alert(resp.message);
                        window.location.reload();
                    }
                } else {
                    toastError(resp.message || 'Gagal menyimpan.');
                    btn.prop('disabled', false).html('<i class="fas fa-check-double me-1"></i> Selesaikan Pengembalian (RETURNED)');
                }
            },
            error: function(xhr) {
                toastError(xhr.responseJSON?.message || 'Terjadi kesalahan sistem.');
                btn.prop('disabled', false).html('<i class="fas fa-check-double me-1"></i> Selesaikan Pengembalian (RETURNED)');
            }
        });
    });

    $('#btnCancelReturn').on('click', function() {
        window.location.reload();
    });

    function toastWarn(msg) {
        if (typeof swal === 'function') swal({ title: 'Perhatian', text: msg, type: 'warning' });
        else alert(msg);
    }
    function toastError(msg) {
        if (typeof swal === 'function') swal({ title: 'Gagal', text: msg, type: 'error' });
        else alert(msg);
    }
});
</script>
@endsection

