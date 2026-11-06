@extends('layouts.main')

@section('style')
<style>
    .pos2-card-header {
        background: linear-gradient(135deg, #118ab2 0%, #073b4c 100%);
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
        border-color: #118ab2;
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
    .mismatch-panel {
        background: #fff5f5;
        border: 2px solid #e63946;
        border-radius: 12px;
        padding: 1.5rem;
    }
    .match-panel {
        background: #f0fdf4;
        border: 2px solid #2a9d8f;
        border-radius: 12px;
        padding: 1.5rem;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">

        <!-- Top Header & Role Badge & Bluetooth Toolbar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
            <div>
                <h3 class="fw-bold mb-1">
                    <i class="fas fa-check-double text-info me-2"></i> Pos 2: Verifikasi & Cetak Final
                </h3>
                <span class="text-muted small">Input ganda mandiri Pos 2 &rarr; Bandingkan dengan Pos 1 &rarr; Cetak Label Final</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <!-- Bluetooth Connection Button -->
                <button type="button" id="btnConnectPrinterPos2" class="btn btn-outline-info btn-sm rounded-pill shadow-sm">
                    <i class="fas fa-print me-1"></i> <span id="printerPos2StatusText">Printer Bluetooth</span>
                </button>
                <a href="{{ route('member.select-role') }}" class="btn btn-outline-primary btn-sm rounded-pill">
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
                                <i class="fas fa-qrcode text-info me-2"></i>Scan QR Kurir
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-info" id="btnToggleCamera">
                                <i class="fas fa-camera me-1"></i> <span id="cameraBtnText">Tutup Kamera</span>
                            </button>
                        </div>

                        <!-- Camera Scanner Container -->
                        <div class="mb-3" id="pos2ScannerWrapper" style="border-radius: 12px; overflow: hidden; background: #000;">
                            <div id="pos2Reader" style="width: 100%;"></div>
                        </div>

                        <!-- Scanned Result Card -->
                        <div id="pos2ScanResultCard" class="card border-success bg-success-subtle mb-3 d-none">
                            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-success fw-bold d-block"><i class="fas fa-check-circle me-1"></i> QR Terbaca</small>
                                    <span class="fw-bold text-dark text-break small" id="pos2ScannedText">-</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-success ms-2" id="btnRescanPos2">
                                    <i class="fas fa-redo me-1"></i> Scan Ulang
                                </button>
                            </div>
                        </div>

                        <div class="input-group mb-2">
                            <span class="input-group-text bg-info text-white border-0">
                                <i class="fas fa-qrcode"></i>
                            </span>
                            <input type="text" class="form-control form-control-lg" id="trackingCodeInput"
                                   placeholder="Scan QR label kurir atau ketik tracking code..."
                                   autocomplete="off" autofocus>
                            <button class="btn btn-info text-white" type="button" id="btnLookupItem">
                                <i class="fas fa-search me-1"></i> Cek
                            </button>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1 text-muted"></i>
                            Scan QR kurir dari box yang telah selesai dihitung pada Pos 1.
                        </div>
                    </div>
                </div>

                <!-- Item Preview Card -->
                <div id="itemPreviewWrapper" class="d-none">
                    <div class="card shadow-sm mb-3 border-info" style="border-radius:14px; overflow:hidden;">
                        <div class="bg-info text-white p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-white-50 text-uppercase d-block" style="font-size: 0.7rem; letter-spacing: 1px;">Tracking Code</small>
                                <span class="fw-bold font-monospace" id="previewTrackingCode">-</span>
                            </div>
                            <span class="badge bg-white text-info" id="previewStep">-</span>
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
                            </div>

                            <div id="denialBanner" class="d-none mt-3 alert alert-warning mb-0" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <span id="denialMessage"></span>
                            </div>

                            {{-- Info lintas pos: sudah dihitung di pos timbangan (tidak memblokir) --}}
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

            <!-- RIGHT: Double Input Manual Form Pos 2 & Verification Result -->
            <div class="col-lg-7">
                <!-- Placeholder awal -->
                <div id="formPlaceholder" class="card shadow-sm h-100 d-flex flex-column align-items-center justify-content-center p-5 text-center" style="min-height:360px; border-radius:14px;">
                    <div class="p-4 bg-light rounded-circle mb-3">
                        <i class="fas fa-check-double fa-3x text-info"></i>
                    </div>
                    <h5 class="fw-bold text-muted">Scan QR Kurir yang Telah Selesai Pos 1</h5>
                    <p class="text-muted small mb-0">Formulir verifikasi Pos 2 akan aktif setelah QR part berstatus <strong>POS1_DONE</strong> terbaca.</p>
                </div>

                <!-- Form Pos 2 -->
                <div id="formWrapperPos2" class="card shadow-sm d-none" style="border-radius:14px; overflow:hidden;">
                    <div class="pos2-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-balance-scale me-2"></i>Form Verifikasi Hitung Pos 2
                        </h5>
                        <span class="badge bg-light text-info">Pos 2 Blind Verification</span>
                    </div>

                    <form id="formSubmitPos2" enctype="multipart/form-data" class="card-body p-4">
                        @csrf
                        <input type="hidden" id="soItemId" name="so_item_id" value="">

                        <div class="alert alert-info py-2 px-3 small mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Hitung fisik seluruh part secara mandiri tanpa melihat hasil Pos 1 terlebih dahulu (Blind Count). Masukkan hasil pada <strong>Input 1</strong> dan <strong>Input 2</strong>.
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Input 1 -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Hasil Hitung Fisik Pos 2 (Input 1) <span class="text-danger">*</span></label>
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

                        <!-- Match indicator text Pos 2 -->
                        <div id="matchAlert" class="mb-3 d-none"></div>

                        <!-- Upload Foto Bukti Pos 2 (Bebas Jumlahnya) -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold mb-0">Foto Bukti Part Pos 2 (Bebas / Multi-Foto)</label>
                                <span class="badge bg-secondary" id="photoCountBadge">0 foto dipilih</span>
                            </div>
                            
                            <div class="preview-box" id="dropAreaPos2" onclick="document.getElementById('photoInputPos2').click()">
                                <div id="previewPhotoPlaceholder" class="text-center p-2 text-muted">
                                    <i class="fas fa-camera fa-2x mb-1 text-info"></i><br>
                                    <span class="fw-semibold">Ketuk untuk ambil / tambah foto part</span><br>
                                    <small class="text-muted">Bisa ambil berkali-kali atau pilih beberapa foto sekaligus</small>
                                </div>
                            </div>
                            <!-- Multi-file input with camera capture support -->
                            <input type="file" id="photoInputPos2" accept="image/*" capture="environment" multiple class="d-none">
                            
                            <!-- Grid thumbnail preview foto-foto yang diambil -->
                            <div class="photo-grid d-none" id="photoGridPos2"></div>
                            
                            <div class="form-text mt-2">
                                <i class="fas fa-info-circle me-1"></i>Foto akan otomatis dikompres sebelum dikirim agar cepat & hemat penyimpanan.
                            </div>
                        </div>

                        <!-- Catatan Opsional -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Catatan Pos 2 (Opsional)</label>
                            <input type="text" class="form-control" name="notes" id="notesInput" placeholder="Catatan kondisi part, dsb.">
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary px-4" id="btnResetForm">
                                <i class="fas fa-undo me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn btn-info text-white flex-grow-1 py-3 fw-bold fs-6" id="btnSubmitPos2" disabled>
                                <i class="fas fa-check-double me-1"></i> Verifikasi Pos 1 vs Pos 2
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Result Panel (Match OK vs Mismatch NG) -->
                <div id="resultPanel" class="d-none mt-3">
                    <!-- Dinamis terisi oleh JS -->
                </div>
            </div>
        </div>

        <!-- Recent Activities Pos 2 by Current User -->
        <div class="card shadow-sm border-0 mt-4" style="border-radius:14px;">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fas fa-list me-2 text-info"></i>Riwayat Verifikasi Pos 2 Terakhir oleh Anda
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
                                <th>Status Hasil</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentHistories as $h)
                            <tr>
                                <td class="fw-bold font-monospace text-primary">{{ $h->soItem->tracking_code ?? '-' }}</td>
                                <td>{{ $h->soItem->code_part ?? '-' }}<br><small class="text-muted">{{ Str::limit($h->soItem->name_part ?? '-', 25) }}</small></td>
                                <td>{{ $h->soItem->rack_no ?? '-' }} ({{ ($h->soItem->area ?? '-') . '-' . ($h->soItem->cell ?? '-') }})</td>
                                <td class="fw-bold fs-6 text-dark">{{ number_format($h->result_count) }} pcs</td>
                                <td>{{ $h->created_at->format('H:i:s d/m') }}</td>
                                <td>
                                    @if($h->status === 'OK')
                                        <span class="badge bg-success">COCOK (OK)</span>
                                    @else
                                        <span class="badge bg-danger">MISMATCH (NG)</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Belum ada riwayat hitungan Pos 2.</td>
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
<script src="{{ asset('assets/js/bt-thermal-printer.js') }}"></script>
<script>
$(document).ready(function() {
    const ROUTE_LOOKUP       = "{{ route('manual.lookup-item') }}";
    const ROUTE_SUBMIT       = "{{ route('manual.pos2.submit') }}";
    const ROUTE_UPLOAD_FINAL = "{{ route('manual.pos2.upload-final') }}";
    const ROUTE_HISTORY      = "{{ route('manual.item-history') }}";
    const CSRF_TOKEN         = "{{ csrf_token() }}";

    let currentItem = null;
    let pos2QrCode = null;
    let isPos2ScannerRunning = false;
    let compressedPhotoBlobs = []; // Array of Blobs
    let lastPrintData = null;
    let compressedFinalPhotoBlob = null;
    let isQrPrinted = false;
    let isFinalPhotoUploaded = false;

    // ─────────────────────────────────────────────────────────
    // 1. Bluetooth Auto Connect
    // ─────────────────────────────────────────────────────────
    setTimeout(async () => {
        try {
            const connected = await window.thermalPrinter.autoConnect();
            if (connected) {
                $('#btnConnectPrinterPos2').removeClass('btn-outline-info').addClass('btn-success text-white');
                $('#printerPos2StatusText').html(`<i class="fas fa-check"></i> ${connected.name || 'Printer Terhubung'}`);
            }
        } catch (e) {
            console.log('Pos 2 auto-connect:', e);
        }
    }, 600);

    $('#btnConnectPrinterPos2').on('click', async function () {
        const btn = $(this);
        btn.prop('disabled', true);
        $('#printerPos2StatusText').text('Menghubungkan...');
        try {
            const device = await window.thermalPrinter.connect();
            btn.removeClass('btn-outline-info').addClass('btn-success text-white');
            $('#printerPos2StatusText').html(`<i class="fas fa-check"></i> ${device.name}`);
        } catch (err) {
            $('#printerPos2StatusText').text('Printer Bluetooth');
            toastError(err.message || 'Gagal menyambungkan Bluetooth.');
        } finally {
            btn.prop('disabled', false);
        }
    });

    // ─────────────────────────────────────────────────────────
    // 2. Camera Scanner Pos 2
    // ─────────────────────────────────────────────────────────
    async function initPos2Camera() {
        const readerEl = document.getElementById("pos2Reader");
        if (!readerEl) return;

        if (pos2QrCode) {
            try {
                if (isPos2ScannerRunning) {
                    await pos2QrCode.stop();
                }
                await pos2QrCode.clear();
            } catch (e) {
                console.warn("Cleanup previous pos2 scanner:", e);
            }
        }

        $('#pos2ScannerWrapper').removeClass('d-none');
        $('#pos2ScanResultCard').addClass('d-none');
        $('#cameraBtnText').text('Tutup Kamera');
        readerEl.innerHTML = '';

        try {
            pos2QrCode = new Html5Qrcode("pos2Reader", {
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
                await pos2QrCode.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText) => {
                        console.log("Pos 2 QR Scanned:", decodedText);
                        stopPos2Camera(decodedText);
                        $('#trackingCodeInput').val(decodedText);
                        lookupItem();
                    },
                    (error) => {}
                );
            } catch (errConstraint) {
                console.warn("Pos 2 fallback to getCameras...", errConstraint);
                const devices = await Html5Qrcode.getCameras();
                if (devices && devices.length) {
                    const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('belakang')) || devices[devices.length - 1];
                    await pos2QrCode.start(
                        backCam.id,
                        config,
                        (decodedText) => {
                            console.log("Pos 2 QR Scanned:", decodedText);
                            stopPos2Camera(decodedText);
                            $('#trackingCodeInput').val(decodedText);
                            lookupItem();
                        },
                        (error) => {}
                    );
                } else {
                    throw errConstraint;
                }
            }
            isPos2ScannerRunning = true;
        } catch (err) {
            console.error("Gagal start kamera Pos 2:", err);
            isPos2ScannerRunning = false;
            $('#pos2ScannerWrapper').html(`
                <div class="p-4 text-center text-white bg-dark rounded">
                    <i class="fas fa-camera-slash fa-2x mb-2 text-warning"></i>
                    <p class="small mb-2 font-weight-bold">Tidak dapat mengakses kamera: ${err.message || 'Izin ditolak'}</p>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="initPos2Camera()">
                        <i class="fas fa-redo me-1"></i> Coba Izin Kamera Lagi
                    </button>
                </div>
            `);
            $('#cameraBtnText').text('Buka Kamera');
        }
    }

    async function stopPos2Camera(codeResult = null) {
        if (pos2QrCode && isPos2ScannerRunning) {
            try {
                await pos2QrCode.stop();
            } catch (e) {}
            isPos2ScannerRunning = false;
        }
        $('#pos2ScannerWrapper').addClass('d-none');
        $('#cameraBtnText').text('Buka Kamera');

        if (codeResult) {
            $('#pos2ScannedText').text(codeResult);
            $('#pos2ScanResultCard').removeClass('d-none');
        }
    }

    $('#btnToggleCamera').on('click', function () {
        if (isPos2ScannerRunning) {
            stopPos2Camera();
        } else {
            initPos2Camera();
        }
    });

    $('#btnRescanPos2').on('click', function () {
        initPos2Camera();
    });

    // Auto-open scanner on load
    initPos2Camera();

    // ─────────────────────────────────────────────────────────
    // 3. Lookup Item
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
            data: { tracking_code: code, pos: 'POS2', _token: CSRF_TOKEN },
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
                    $('#formWrapperPos2').addClass('d-none');
                    $('#resultPanel').addClass('d-none');
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
                $('#soItemId').val(resp.item.id);
                currentItem = resp.item;

                loadHistory(resp.item.id);

                // Jika item sudah match (baik dari hasil koreksi Pos 1 vs Pos 2 atau cetak ulang QR di Pos 2)
                if (resp.needs_final_flow) {
                    $('#formWrapperPos2').addClass('d-none');
                    lastPrintData = resp.printData;
                    renderMatchSuccess({
                        final_count: resp.final_count,
                        printData: resp.printData
                    });
                    if (resp.already_photo) {
                        isFinalPhotoUploaded = true;
                        $('#sectionUploadFinalPhoto').removeClass('d-none');
                        $('#btnUploadFinalPhoto').removeClass('btn-success').addClass('btn-secondary').html('<i class="fas fa-check-circle me-1"></i> Foto Sudah Tersimpan');
                        $('#btnNextItem').prop('disabled', false).removeClass('btn-outline-secondary').addClass('btn-success text-white shadow');
                        $('#nextItemHelpText').html('<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>Foto sudah tersimpan sebelumnya. Anda dapat mencetak ulang QR di atas atau lanjut scan part berikutnya.</span>');
                    }
                } else {
                    $('#resultPanel').addClass('d-none');
                    $('#formWrapperPos2').removeClass('d-none');
                    $('#input1').focus();
                }
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
    // 4. Double Input Validation Pos 2
    // ─────────────────────────────────────────────────────────
    $('#input1, #input2').on('input', function() {
        validateDoubleInput();
    });

    function validateDoubleInput() {
        const val1 = $('#input1').val().trim();
        const val2 = $('#input2').val().trim();

        if (val1 === '' || val2 === '') {
            $('#matchAlert').addClass('d-none').html('');
            $('#btnSubmitPos2').prop('disabled', true);
            return false;
        }

        const num1 = parseInt(val1);
        const num2 = parseInt(val2);

        if (isNaN(num1) || isNaN(num2) || num1 < 0 || num2 < 0) {
            $('#matchAlert').removeClass('d-none alert-success').addClass('alert alert-danger py-2 small')
                .html('<i class="fas fa-times-circle me-1"></i> Nilai input harus angka bulat &ge; 0.');
            $('#btnSubmitPos2').prop('disabled', true);
            return false;
        }

        if (num1 === num2) {
            $('#matchAlert').removeClass('d-none alert-danger').addClass('alert alert-success py-2 small')
                .html(`<i class="fas fa-check-circle me-1"></i> <strong>Sesuai:</strong> ${num1} Pcs siap diverifikasi dengan Pos 1.`);
            $('#btnSubmitPos2').prop('disabled', false);
            return true;
        } else {
            $('#matchAlert').removeClass('d-none alert-success').addClass('alert alert-danger py-2 small')
                .html(`<i class="fas fa-exclamation-circle me-1"></i> <strong>Tidak Cocok:</strong> Input 1 (${num1}) &ne; Input 2 (${num2}).`);
            $('#btnSubmitPos2').prop('disabled', true);
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────
    // 5. Photo Compression & Multi-Photo Management Pos 2
    // ─────────────────────────────────────────────────────────
    $('#photoInputPos2').on('change', async function(e) {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        $('#previewPhotoPlaceholder').html('<i class="fas fa-spinner fa-spin fa-2x mb-1 text-info"></i><br><small>Mengompres foto...</small>');

        for (let i = 0; i < files.length; i++) {
            try {
                const blob = await compressImageFile(files[i]);
                compressedPhotoBlobs.push(blob);
            } catch (err) {
                console.error("Gagal kompres foto:", err);
            }
        }

        renderPhotoGrid();
        $(this).val('');
    });

    function renderPhotoGrid() {
        const grid = $('#photoGridPos2');
        grid.empty();

        if (compressedPhotoBlobs.length === 0) {
            grid.addClass('d-none');
            $('#photoCountBadge').text('0 foto dipilih').removeClass('bg-info text-white').addClass('bg-secondary');
            $('#previewPhotoPlaceholder').html(`
                <i class="fas fa-camera fa-2x mb-1 text-info"></i><br>
                <span class="fw-semibold">Ketuk untuk ambil / tambah foto part</span><br>
                <small class="text-muted">Bisa ambil berkali-kali atau pilih beberapa foto sekaligus</small>
            `);
            return;
        }

        grid.removeClass('d-none');
        $('#photoCountBadge').text(compressedPhotoBlobs.length + ' foto dipilih').removeClass('bg-secondary').addClass('bg-info text-white');
        $('#previewPhotoPlaceholder').html(`
            <i class="fas fa-plus-circle fa-2x mb-1 text-info"></i><br>
            <span class="fw-semibold">Ketuk untuk menambah foto lainnya</span>
        `);

        compressedPhotoBlobs.forEach((blob, idx) => {
            const objUrl = URL.createObjectURL(blob);
            const thumb = $(`
                <div class="photo-thumb-wrapper">
                    <img src="${objUrl}" alt="Foto Pos 2 ${idx+1}">
                    <button type="button" class="btn-remove-photo" data-index="${idx}" title="Hapus foto">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
            grid.append(thumb);
        });
    }

    $(document).on('click', '#photoGridPos2 .btn-remove-photo', function(e) {
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
    // 6. Submit Form Pos 2 & Compare Result
    // ─────────────────────────────────────────────────────────
    $('#formSubmitPos2').on('submit', function(e) {
        e.preventDefault();
        if (!validateDoubleInput()) return;

        const btn = $('#btnSubmitPos2');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Membandingkan dengan Pos 1...');

        const formData = new FormData(this);

        // Append multiple compressed photos
        if (compressedPhotoBlobs.length > 0) {
            compressedPhotoBlobs.forEach((blob, i) => {
                formData.append('photos[]', blob, `pos2_photo_${i+1}.jpg`);
            });
        }

        $.ajax({
            url: ROUTE_SUBMIT,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp) {
                $('#formWrapperPos2').addClass('d-none');

                if (resp.status === 'OK') {
                    // Match OK!
                    lastPrintData = resp.printData;
                    renderMatchSuccess(resp);
                    loadHistory($('#soItemId').val());
                } else if (resp.status === 'MISMATCH_NG') {
                    // Mismatch NG!
                    renderMismatchNG(resp);
                    loadHistory($('#soItemId').val());
                } else {
                    toastError(resp.message || 'Gagal memproses data.');
                    $('#formWrapperPos2').removeClass('d-none');
                }
            },
            error: function(xhr) {
                toastError(xhr.responseJSON?.message || 'Terjadi kesalahan sistem.');
                $('#formWrapperPos2').removeClass('d-none');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-check-double me-1"></i> Verifikasi Pos 1 vs Pos 2');
            }
        });
    });

    function renderMatchSuccess(resp) {
        const html = `
            <div class="match-panel shadow-sm">
                <div class="text-center mb-3">
                    <div class="d-inline-flex p-3 bg-success text-white rounded-circle mb-2">
                        <i class="fas fa-check-circle fa-3x"></i>
                    </div>
                    <h4 class="fw-bold text-success mb-1">HASIL VERIFIKASI COCOK (MATCH OK)</h4>
                    <p class="text-muted small">Perhitungan Pos 1 dan Pos 2 identik.</p>
                </div>
                <div class="card bg-white border-0 shadow-sm mb-3">
                    <div class="card-body p-3 text-center">
                        <div class="text-muted small">Jumlah Akhir Terverifikasi</div>
                        <div class="display-5 fw-bold text-dark">${resp.final_count.toLocaleString('id-ID')} <span class="fs-4 text-muted">Pcs</span></div>
                        <span class="badge bg-success-subtle text-success border border-success mt-2">READY TO RETURN</span>
                    </div>
                </div>

                <!-- Step 1: Print QR Final -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-dark mb-2">
                            <span class="badge bg-primary me-2">1</span>Cetak Label QR Final
                        </h6>
                        <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-6 shadow-sm" id="btnPrintFinalPos2">
                            <i class="fas fa-print me-2"></i> Cetak Label QR Final (Printer)
                        </button>
                        <div id="printFinalSuccessAlert" class="alert alert-success py-2 px-3 small mt-2 d-none">
                            <i class="fas fa-check-circle me-1"></i> Label QR Final berhasil dicetak! Silakan tempelkan pada box part.
                        </div>
                    </div>
                </div>

                <!-- Step 2: Upload Foto Part dengan QR Final Ditempel (Wajib sebelum lanjut) -->
                <div class="card border-0 shadow-sm mb-3 d-none" id="sectionUploadFinalPhoto">
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-dark mb-2">
                            <span class="badge bg-primary me-2">2</span>Foto Part Ditempel Label Final <span class="text-danger">*</span>
                        </h6>
                        <div class="preview-box" id="boxUploadFinalPhoto" onclick="document.getElementById('photoFinalInput').click()">
                            <div id="previewFinalPhotoPlaceholder" class="text-center p-2 text-muted">
                                <i class="fas fa-camera fa-2x mb-1 text-success"></i><br>
                                <span class="fw-semibold">Ambil foto part dengan label QR final yang sudah ditempel</span>
                            </div>
                            <img id="imgPreviewFinalPhoto" class="d-none" style="max-height:160px; object-fit:contain; border-radius:8px;">
                        </div>
                        <input type="file" id="photoFinalInput" accept="image/*" capture="environment" class="d-none">

                        <button type="button" class="btn btn-success w-100 py-2 fw-bold mt-2" id="btnUploadFinalPhoto" disabled>
                            <i class="fas fa-upload me-1"></i> Simpan Foto Label & Selesaikan Part
                        </button>
                    </div>
                </div>

                <!-- Step 3: Tombol Part Berikutnya (Hanya aktif setelah Step 1 dan Step 2 selesai) -->
                <div class="d-grid mt-3">
                    <button type="button" class="btn btn-outline-secondary py-3 fw-bold" id="btnNextItem" disabled>
                        <i class="fas fa-arrow-right me-1"></i> Selesai & Hitung Part Berikutnya
                    </button>
                    <small class="text-muted text-center mt-1" id="nextItemHelpText">
                        <i class="fas fa-lock me-1"></i>Cetak QR dan ambil foto part ber-QR terlebih dahulu untuk membuka tombol ini.
                    </small>
                </div>
            </div>
        `;
        $('#resultPanel').html(html).removeClass('d-none');
    }

    function renderMismatchNG(resp) {
        const html = `
            <div class="mismatch-panel shadow-sm">
                <div class="text-center mb-3">
                    <div class="d-inline-flex p-3 bg-danger text-white rounded-circle mb-2">
                        <i class="fas fa-times-circle fa-3x"></i>
                    </div>
                    <h4 class="fw-bold text-danger mb-1">HASIL TIDAK COCOK (MISMATCH NG)</h4>
                    <p class="text-muted small mb-0">Terdapat selisih antara hitungan Pos 1 dan Pos 2.</p>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="card bg-white border text-center p-3">
                            <small class="text-muted d-block">Hasil Pos 1</small>
                            <span class="fs-3 fw-bold text-dark">${resp.pos1_count.toLocaleString('id-ID')}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-white border text-center p-3">
                            <small class="text-muted d-block">Hasil Pos 2</small>
                            <span class="fs-3 fw-bold text-danger">${resp.pos2_count.toLocaleString('id-ID')}</span>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning py-2 px-3 small mb-3">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Status item berubah menjadi <strong>MISMATCH_NG</strong>. Silakan lakukan hitung ulang bersama untuk mengoreksi selisih hitung.
                </div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-warning fw-bold py-2" id="btnRecountPos2">
                        <i class="fas fa-redo me-1"></i> Hitung Ulang Pos 2 Sekarang
                    </button>
                    <button type="button" class="btn btn-outline-secondary py-2" id="btnNextItem">
                        <i class="fas fa-times me-1"></i> Lewati / Ganti Item Lain
                    </button>
                </div>
            </div>
        `;
        $('#resultPanel').html(html).removeClass('d-none');
    }

    // ─────────────────────────────────────────────────────────
    // 7. Print Final Label via Web Bluetooth (Auto Connect Popup jika belum konek)
    // ─────────────────────────────────────────────────────────
    $(document).on('click', '#btnPrintFinalPos2', async function() {
        if (!lastPrintData) {
            toastError('Data cetak tidak ditemukan.');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Mencetak ke Printer...');

        try {
            // Jika belum konek ke printer Bluetooth, panggil connect() (munculkan popup scanner BLE browser)
            if (!window.thermalPrinter.isConnected) {
                const device = await window.thermalPrinter.connect();
                $('#btnConnectPrinterPos2').removeClass('btn-outline-info').addClass('btn-success text-white');
                $('#printerPos2StatusText').html(`<i class="fas fa-check"></i> ${device.name || window.thermalPrinter.printerName}`);
            }

            await window.thermalPrinter.printFinalLabel({
                trackingCode: lastPrintData.tracking_code,
                codePart:     lastPrintData.code_part,
                namePart:     lastPrintData.name_part,
                rackNo:       lastPrintData.rack_no,
                loc:          lastPrintData.loc,
                qty:          lastPrintData.final_count,
                method:       'MANUAL POS 1 & 2',
                pic1:         lastPrintData.pic1,
                pic2:         lastPrintData.pic2,
                pos1History:  lastPrintData.pos1_history,
                pos2History:  lastPrintData.pos2_history,
                completedAt:  lastPrintData.completed_at,
                user:         lastPrintData.user_name || lastPrintData.user_nik,
                partner:      null,
                scaleNo:      null,
                isManual:     true
            });

            isQrPrinted = true;
            $('#printFinalSuccessAlert').removeClass('d-none');
            $('#sectionUploadFinalPhoto').removeClass('d-none');
            btn.removeClass('btn-primary').addClass('btn-outline-primary').html('<i class="fas fa-redo me-2"></i> Cetak Ulang Label QR Final');

            if (typeof swal === 'function') {
                swal({
                    title: 'Berhasil Dicetak!',
                    text: 'Label Manual berhasil dicetak. Silakan tempelkan pada box part, lalu ambil foto part yang sudah ditempel QR untuk menyelesaikan.',
                    type: 'success'
                });
            } else {
                alert('Label Manual berhasil dicetak! Silakan tempelkan pada box part.');
            }
        } catch (err) {
            console.error('Print final error:', err);
            toastError('Gagal mencetak: ' + (err.message || 'Periksa koneksi Bluetooth printer.'));
        } finally {
            btn.prop('disabled', false);
        }
    });

    // ─────────────────────────────────────────────────────────
    // 8. Upload Foto Part Ditempel QR Final
    // ─────────────────────────────────────────────────────────
    $(document).on('change', '#photoFinalInput', async function(e) {
        if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];
            $('#previewFinalPhotoPlaceholder').html('<i class="fas fa-spinner fa-spin fa-2x mb-1 text-success"></i><br><small>Mengompres foto...</small>');
            try {
                compressedFinalPhotoBlob = await compressImageFile(file);
                const objUrl = URL.createObjectURL(compressedFinalPhotoBlob);
                $('#imgPreviewFinalPhoto').attr('src', objUrl).removeClass('d-none');
                $('#previewFinalPhotoPlaceholder').addClass('d-none');
                $('#btnUploadFinalPhoto').prop('disabled', false);
            } catch (err) {
                console.error(err);
                toastError('Gagal memproses foto.');
            }
        }
    });

    $(document).on('click', '#btnUploadFinalPhoto', function() {
        if (!compressedFinalPhotoBlob) {
            toastWarn('Silakan ambil foto part ber-QR terlebih dahulu.');
            return;
        }

        const soItemId = $('#soItemId').val();
        if (!soItemId) {
            toastError('ID Item tidak valid.');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Mengunggah Foto...');

        const fd = new FormData();
        fd.append('so_item_id', soItemId);
        fd.append('photo', compressedFinalPhotoBlob, 'final_labeled_part.jpg');
        fd.append('_token', CSRF_TOKEN);

        $.ajax({
            url: ROUTE_UPLOAD_FINAL,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    isFinalPhotoUploaded = true;
                    btn.removeClass('btn-success').addClass('btn-secondary').html('<i class="fas fa-check-circle me-1"></i> Foto Tersimpan!');
                    $('#btnNextItem').prop('disabled', false).removeClass('btn-outline-secondary').addClass('btn-success text-white shadow');
                    $('#nextItemHelpText').html('<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>Seluruh tahapan selesai! Silakan klik tombol di atas untuk scan part selanjutnya.</span>');

                    if (typeof swal === 'function') {
                        swal({
                            title: 'Selesai!',
                            text: 'Foto berhasil disimpan! Part siap dikembalikan ke rak oleh tim Returner.',
                            type: 'success'
                        });
                    } else {
                        alert('Foto berhasil disimpan!');
                    }
                }
            },
            error: function(xhr) {
                toastError(xhr.responseJSON?.message || 'Gagal mengunggah foto.');
                btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Simpan Foto Label & Selesaikan Part');
            }
        });
    });

    $(document).on('click', '#btnRecountPos2', function() {
        $('#resultPanel').addClass('d-none');
        $('#formWrapperPos2').removeClass('d-none');
        $('#input1').val('').focus();
        $('#input2').val('');
        $('#matchAlert').addClass('d-none');
    });

    $(document).on('click', '#btnNextItem', function() {
        if (!isQrPrinted || !isFinalPhotoUploaded) {
            toastWarn('Wajib mencetak label QR final dan mengunggah foto part ber-QR terlebih dahulu sebelum melanjutkan!');
            return;
        }
        resetFormState();
        initPos2Camera();
    });

    $(document).on('click', '#btnResetForm', function() {
        resetFormState();
        initPos2Camera();
    });

    function resetFormState() {
        currentItem = null;
        compressedPhotoBlobs = [];
        compressedFinalPhotoBlob = null;
        isQrPrinted = false;
        isFinalPhotoUploaded = false;
        lastPrintData = null;
        $('#formSubmitPos2')[0].reset();
        $('#soItemId').val('');
        $('#matchAlert').addClass('d-none').html('');
        renderPhotoGrid();
        $('#formWrapperPos2').addClass('d-none');
        $('#resultPanel').addClass('d-none').html('');
        $('#formPlaceholder').removeClass('d-none');
        $('#itemPreviewWrapper').addClass('d-none');
        $('#crossPosBanner').addClass('d-none');
        $('#trackingCodeInput').val('').focus();
        $('#btnSubmitPos2').prop('disabled', true).html('<i class="fas fa-check-double me-1"></i> Verifikasi Pos 1 vs Pos 2');
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
@endsection

