@extends('layouts.main')

@section('style')
<style>
    .auditor-header-card {
        background: linear-gradient(135deg, #2b2d42 0%, #1d3557 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.5rem;
    }
    .stat-badge-box {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        padding: 0.75rem 1rem;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">

        <!-- Header Auditor Portal -->
        <div class="auditor-header-card shadow-sm mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h3 class="fw-bold mb-1">
                        <i class="fas fa-clipboard-check text-warning me-2"></i> Portal Ruang Kerja Auditor
                    </h3>
                    <p class="mb-0 text-white-50 small">
                        Pemeriksaan fisik rak &bull; Auditor: <strong>{{ $auditor->name }}</strong>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('auditor.unaudited') }}" class="btn btn-warning fw-bold shadow-sm">
                        <i class="fas fa-list-ul me-1"></i> Daftar Rak Belum Diaudit
                    </a>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-6 col-md-3">
                    <div class="stat-badge-box text-center">
                        <small class="text-white-50 d-block">Total Audit Sistem</small>
                        <span class="fs-4 fw-bold text-white" id="statTotalAudited">{{ number_format($totalAudited) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge-box text-center">
                        <small class="text-white-50 d-block">Diaudit oleh Anda</small>
                        <span class="fs-4 fw-bold text-warning" id="statMyAudited">{{ number_format($myAudited) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- LEFT: Camera Scanner & Lookup -->
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm mb-3" style="border-radius:14px;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-qrcode text-primary me-2"></i>Scan Label Box Pengembalian
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleCamera">
                                <i class="fas fa-camera me-1"></i> <span id="cameraBtnText">Tutup Kamera</span>
                            </button>
                        </div>

                        <!-- Camera Scanner Container -->
                        <div class="mb-3" id="auditorScannerWrapper" style="border-radius: 12px; overflow: hidden; background: #000;">
                            <div id="auditorReader" style="width: 100%;"></div>
                        </div>

                        <!-- Scanned Result Card -->
                        <div id="auditorScanResultCard" class="card border-success bg-success-subtle mb-3 d-none">
                            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-success fw-bold d-block"><i class="fas fa-check-circle me-1"></i> QR Terbaca</small>
                                    <span class="fw-bold text-dark text-break small" id="auditorScannedText">-</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-success ms-2" id="btnRescanAuditor">
                                    <i class="fas fa-redo me-1"></i> Scan Ulang
                                </button>
                            </div>
                        </div>

                        <div class="input-group mb-2">
                            <span class="input-group-text bg-primary text-white border-0">
                                <i class="fas fa-qrcode"></i>
                            </span>
                            <input type="text" class="form-control form-control-lg" id="rackInput"
                                   placeholder="Scan QR label pengembalian / tracking code..."
                                   autocomplete="off" autofocus>
                            <button class="btn btn-primary" type="button" id="btnLookupRack">
                                <i class="fas fa-search me-1"></i> Cek
                            </button>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1 text-muted"></i>
                            Arahkan kamera ke QR stiker label kertas box pengembalian (Label Final / Label Manual) atau ketikkan kode tracking/rak secara manual.
                        </div>
                    </div>
                </div>

                <!-- Info Preview Rak -->
                <div id="rackPreviewWrapper" class="d-none">
                    <div class="card shadow-sm mb-3 border-dark" style="border-radius:14px; overflow:hidden;">
                        <div class="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-white-50 text-uppercase d-block" style="font-size: 0.7rem; letter-spacing: 1px;">NOMOR RAK</small>
                                <span class="fw-bold fs-5 font-monospace" id="previewRackNo">-</span>
                            </div>
                            <span class="badge bg-primary" id="previewSoStep">-</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-muted small">Kode Part</div>
                                    <div class="fw-semibold text-break" id="previewCodePart">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Lokasi / Cell</div>
                                    <div class="fw-semibold text-break" id="previewLocCell">-</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">Nama Part</div>
                                    <div class="fw-semibold text-break" id="previewNamePart">-</div>
                                </div>
                            </div>

                            <!-- Peringatan Rak Sudah Pernah Diaudit Sebelumnya -->
                            <div id="alreadyAuditedBanner" class="alert alert-warning py-2 px-3 small mt-3 mb-0 d-none">
                                <i class="fas fa-history me-1"></i>
                                <strong>Perhatian:</strong> Rak ini sudah pernah diaudit sebanyak <span id="auditCountBadge" class="fw-bold">0</span> kali. Anda tetap dapat melakukan audit ulang / update qty.
                            </div>
                        </div>
                    </div>

                    <!-- Riwayat Audit Sebelumnya untuk Rak Ini -->
                    <div class="card shadow-sm" style="border-radius:14px;">
                        <div class="card-header py-2" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#collapseAuditHistory">
                            <span class="fw-semibold small"><i class="fas fa-history me-2 text-secondary"></i>Riwayat Audit Rak Ini</span>
                        </div>
                        <div class="collapse" id="collapseAuditHistory">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" style="font-size:0.8rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Waktu</th>
                                                <th>Auditor</th>
                                                <th>Qty Audit</th>
                                                <th>Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody id="auditHistoryTableBody">
                                            <tr><td colspan="4" class="text-center text-muted py-2">—</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Form Input Audit -->
            <div class="col-lg-7">
                <!-- Placeholder awal -->
                <div id="formPlaceholder" class="card shadow-sm h-100 d-flex flex-column align-items-center justify-content-center p-5 text-center" style="min-height:360px; border-radius:14px;">
                    <div class="p-4 bg-light rounded-circle mb-3">
                        <i class="fas fa-clipboard-list fa-3x text-primary"></i>
                    </div>
                    <h5 class="fw-bold text-muted">Scan QR Rak Fisik untuk Memulai Audit</h5>
                    <p class="text-muted small mb-0">Formulir pemeriksaan qty audit akan aktif setelah nomor rak terdeteksi.</p>
                </div>

                <!-- Form Audit -->
                <div id="formAuditWrapper" class="card shadow-sm d-none" style="border-radius:14px; overflow:hidden;">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-check-circle me-2"></i>Form Hasil Pemeriksaan Audit
                        </h5>
                    </div>

                    <form id="formSubmitAudit" class="card-body p-4">
                        @csrf
                        <input type="hidden" id="auditRackNo" name="rack_no" value="">
                        <input type="hidden" id="auditCodePart" name="code_part" value="">

                        <!-- Card Komparasi Live Perhitungan SO vs Audit -->
                        <div class="card border-0 bg-light mb-3" style="border-radius:10px;">
                            <div class="card-body p-3">
                                <small class="text-muted text-uppercase fw-bold d-block mb-2" style="font-size:0.7rem; letter-spacing:0.5px;">
                                    <i class="fas fa-balance-scale me-1"></i>Data Hasil Hitung Stock Opname
                                </small>
                                <div class="row g-2 align-items-center">
                                    <div class="col-sm-6">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge" id="badgeProcessType" style="font-size:0.75rem;">-</span>
                                            <div>
                                                <div class="text-muted small">Qty Akhir SO:</div>
                                                <h4 class="fw-bold mb-0 text-dark"><span id="previewSoCount">-</span> <small class="fs-6 fw-normal text-muted">pcs</small></h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 border-start-sm">
                                        <div id="breakdownManual" class="small text-muted d-none">
                                            <div>Pos 1: <strong class="text-dark" id="previewPos1">-</strong> pcs</div>
                                            <div>Pos 2: <strong class="text-dark" id="previewPos2">-</strong> pcs</div>
                                        </div>
                                        <div id="breakdownScale" class="small text-muted d-none">
                                            <div>Metode: <strong class="text-dark">Timbangan Digital</strong></div>
                                            <div>No Scale: <strong class="text-dark" id="previewScaleNo">-</strong></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selisih Live Calculation -->
                                <div id="liveDiffBox" class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center d-none">
                                    <span class="small fw-semibold text-muted">Selisih (Audit - SO):</span>
                                    <span class="badge fs-6" id="badgeLiveDiff">0 pcs</span>
                                </div>
                            </div>
                        </div>

                        <!-- Qty Input -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Qty Fisik Hasil Audit <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-boxes"></i></span>
                                <input type="number" class="form-control form-control-lg fw-bold" id="auditedCount" name="audited_count" min="0" placeholder="0" required>
                                <span class="input-group-text">Pcs</span>
                            </div>
                            <div class="form-text">Masukkan jumlah fisik aktual part yang Anda temukan di rak.</div>
                        </div>

                        <!-- Notes Input -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Catatan Audit (Opsional)</label>
                            <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Contoh: Part tersebar di 2 box, kondisi label rak jelas, selisih kemasan rusak, dsb."></textarea>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary px-4" id="btnResetAudit">
                                <i class="fas fa-undo me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn btn-success flex-grow-1 py-3 fw-bold fs-6" id="btnSubmitAudit">
                                <i class="fas fa-save me-1"></i> Simpan Hasil Audit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Audits by Current Auditor -->
        <div class="card shadow-sm border-0 mt-4" style="border-radius:14px;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fas fa-history me-2 text-primary"></i>Riwayat Audit & Perbandingan Hasil Stock Opname
                </h6>
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Otomatis terupdate secara langsung</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Rak</th>
                                <th>Part</th>
                                <th>Metode SO</th>
                                <th class="text-center">Hasil SO</th>
                                <th class="text-center">Hasil Audit</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Selisih</th>
                                <th>Catatan</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody id="recentAuditsTableBody">
                            @forelse($recentAudits as $ra)
                            <tr>
                                <td class="fw-bold font-monospace text-primary">{{ $ra['rack_no'] }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $ra['code_part'] }}</div>
                                    <small class="text-muted text-truncate d-block" style="max-width:180px;">{{ $ra['name_part'] }}</small>
                                </td>
                                <td>
                                    @if($ra['process_type'] === 'SCALE')
                                        <span class="badge bg-primary-subtle text-primary border border-primary small">
                                            <i class="fas fa-balance-scale me-1"></i>Timbangan
                                        </span>
                                    @elseif($ra['process_type'] === 'MANUAL')
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary small">
                                            <i class="fas fa-hand-paper me-1"></i>Manual
                                        </span>
                                        @if($ra['pos1_count'] !== null || $ra['pos2_count'] !== null)
                                            <div class="text-muted mt-1" style="font-size:0.7rem;">
                                                P1: {{ $ra['pos1_count'] ?? '-' }} | P2: {{ $ra['pos2_count'] ?? '-' }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge bg-light text-muted border">-</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold text-secondary">
                                    {{ $ra['so_count'] !== null ? number_format($ra['so_count']) . ' pcs' : '-' }}
                                </td>
                                <td class="text-center fw-bold text-dark fs-6">
                                    {{ number_format($ra['audited_count']) }} pcs
                                </td>
                                <td class="text-center">
                                    @if($ra['so_count'] === null)
                                        <span class="badge bg-light text-muted border">-</span>
                                    @elseif((int)$ra['audited_count'] === (int)$ra['so_count'])
                                        <span class="badge bg-success px-3 py-1 fw-bold fs-6">
                                            <i class="fas fa-check-circle me-1"></i>OK
                                        </span>
                                    @else
                                        <span class="badge bg-danger px-3 py-1 fw-bold fs-6">
                                            <i class="fas fa-times-circle me-1"></i>NG
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($ra['diff'] === null)
                                        <span class="badge bg-light text-muted">-</span>
                                    @elseif($ra['diff'] === 0)
                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                            0 pcs
                                        </span>
                                    @elseif($ra['diff'] > 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                                            <i class="fas fa-arrow-up me-1"></i>+{{ number_format($ra['diff']) }} (Over)
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1">
                                            <i class="fas fa-arrow-down me-1"></i>{{ number_format($ra['diff']) }} (Under)
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ Str::limit($ra['notes'] ?? '-', 35) }}</td>
                                <td class="text-muted font-monospace small">{{ $ra['created_at'] }}</td>
                            </tr>
                            @empty
                            <tr id="emptyAuditRow">
                                <td colspan="9" class="text-center text-muted py-4">Belum ada riwayat audit yang Anda lakukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Konfirmasi Re-Audit jika rak sudah pernah diaudit -->
<div class="modal fade" id="modalConfirmReaudit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Audit Ulang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-2">Rak <strong id="modalReauditRackNo">-</strong> sudah pernah diaudit sebelumnya.</p>
                <div class="card bg-light border-0 p-3 mb-3 small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Auditor Terakhir:</span>
                        <strong id="modalLastAuditor">-</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Qty Terakhir:</span>
                        <strong id="modalLastQty">-</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Waktu Audit:</span>
                        <strong id="modalLastTime">-</strong>
                    </div>
                </div>
                <p class="mb-0 text-muted small">Apakah Anda ingin melanjutkan penginputan hasil audit baru untuk rak ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal / Ganti Rak</button>
                <button type="button" class="btn btn-primary fw-bold" id="btnProceedReaudit">Lanjutkan Audit Ulang</button>
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
    const ROUTE_LOOKUP = "{{ route('auditor.lookup-rack') }}";
    const ROUTE_SUBMIT = "{{ route('auditor.submit-audit') }}";
    const CSRF_TOKEN   = "{{ csrf_token() }}";

    let auditorQrCode = null;
    let isAuditorScannerRunning = false;
    let pendingLookupData = null;

    // ─────────────────────────────────────────────────────────
    // 1. Camera QR Scanner
    // ─────────────────────────────────────────────────────────
    async function initAuditorCamera() {
        const readerEl = document.getElementById("auditorReader");
        if (!readerEl) return;

        if (auditorQrCode) {
            try {
                if (isAuditorScannerRunning) {
                    await auditorQrCode.stop();
                }
                await auditorQrCode.clear();
            } catch (e) {
                console.warn("Cleanup previous auditor scanner:", e);
            }
        }

        $('#auditorScannerWrapper').removeClass('d-none');
        $('#auditorScanResultCard').addClass('d-none');
        $('#cameraBtnText').text('Tutup Kamera');
        readerEl.innerHTML = '';

        try {
            auditorQrCode = new Html5Qrcode("auditorReader", {
                experimentalFeatures: { useBarCodeDetectorIfSupported: true }
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
                await auditorQrCode.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText) => {
                        console.log("Auditor QR Scanned:", decodedText);
                        stopAuditorCamera(decodedText);
                        $('#rackInput').val(decodedText);
                        lookupRack();
                    },
                    (error) => {}
                );
            } catch (errConstraint) {
                console.warn("Auditor camera fallback...", errConstraint);
                const devices = await Html5Qrcode.getCameras();
                if (devices && devices.length) {
                    const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('belakang')) || devices[devices.length - 1];
                    await auditorQrCode.start(
                        backCam.id,
                        config,
                        (decodedText) => {
                            console.log("Auditor QR Scanned:", decodedText);
                            stopAuditorCamera(decodedText);
                            $('#rackInput').val(decodedText);
                            lookupRack();
                        },
                        (error) => {}
                    );
                } else {
                    throw errConstraint;
                }
            }
            isAuditorScannerRunning = true;
        } catch (err) {
            console.error("Gagal start kamera auditor:", err);
            isAuditorScannerRunning = false;
            $('#auditorScannerWrapper').html(`
                <div class="p-4 text-center text-white bg-dark rounded">
                    <i class="fas fa-camera-slash fa-2x mb-2 text-warning"></i>
                    <p class="small mb-2 font-weight-bold">Tidak dapat mengakses kamera: ${err.message || 'Izin ditolak'}</p>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="initAuditorCamera()">
                        <i class="fas fa-redo me-1"></i> Coba Izin Kamera Lagi
                    </button>
                </div>
            `);
            $('#cameraBtnText').text('Buka Kamera');
        }
    }

    async function stopAuditorCamera(codeResult = null) {
        if (auditorQrCode && isAuditorScannerRunning) {
            try {
                await auditorQrCode.stop();
            } catch (e) {}
            isAuditorScannerRunning = false;
        }
        $('#auditorScannerWrapper').addClass('d-none');
        $('#cameraBtnText').text('Buka Kamera');

        if (codeResult) {
            $('#auditorScannedText').text(codeResult);
            $('#auditorScanResultCard').removeClass('d-none');
        }
    }

    $('#btnToggleCamera').on('click', function () {
        if (isAuditorScannerRunning) {
            stopAuditorCamera();
        } else {
            initAuditorCamera();
        }
    });

    $('#btnRescanAuditor').on('click', function () {
        initAuditorCamera();
    });

    // Auto-open scanner on load
    initAuditorCamera();

    // Auto lookup jika ada parameter ?rack=... dari halaman unaudited list
    const urlParams = new URLSearchParams(window.location.search);
    const paramRack = urlParams.get('rack');
    if (paramRack) {
        $('#rackInput').val(paramRack);
        setTimeout(() => {
            lookupRack();
        }, 300);
    }

    // ─────────────────────────────────────────────────────────
    // 2. Lookup Rack
    // ─────────────────────────────────────────────────────────
    $('#btnLookupRack').on('click', lookupRack);
    $('#rackInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            lookupRack();
        }
    });

    function lookupRack() {
        const query = $('#rackInput').val().trim();
        if (!query) {
            toastWarn('Masukkan atau scan nomor rak terlebih dahulu.');
            return;
        }

        $('#btnLookupRack').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: ROUTE_LOOKUP,
            type: 'POST',
            data: { rack_no: query, _token: CSRF_TOKEN },
            success: function(resp) {
                if (!resp.found) {
                    toastError(resp.message || 'Rak tidak ditemukan.');
                    resetAuditForm();
                    return;
                }

                pendingLookupData = resp;

                if (resp.already_audited) {
                    // Tampilkan modal konfirmasi re-audit
                    const lastAudit = resp.previous_audits[0] || {};
                    $('#modalReauditRackNo').text(resp.rack_no);
                    $('#modalLastAuditor').text(lastAudit.auditor_name || '-');
                    $('#modalLastQty').text((lastAudit.audited_count || 0) + ' pcs');
                    $('#modalLastTime').text(lastAudit.created_at || '-');
                    $('#modalConfirmReaudit').modal('show');
                } else {
                    renderFormAndPreview(resp);
                }
            },
            error: function(xhr) {
                toastError(xhr.responseJSON?.message || 'Gagal mencari data rak.');
            },
            complete: function() {
                $('#btnLookupRack').prop('disabled', false).html('<i class="fas fa-search me-1"></i> Cek');
            }
        });
    }

    $('#btnProceedReaudit').on('click', function() {
        $('#modalConfirmReaudit').modal('hide');
        if (pendingLookupData) {
            renderFormAndPreview(pendingLookupData);
        }
    });

    function renderFormAndPreview(data) {
        $('#previewRackNo').text(data.rack_no);
        $('#previewSoStep').text(data.so_step || 'BELUM_SO');
        $('#previewCodePart').text(data.code_part || '-');
        $('#previewLocCell').text((data.loc || '-') + ' / ' + (data.cell || '-'));
        $('#previewNamePart').text(data.name_part || '-');

        // SO Calculation preview & comparison
        const soCount = data.so_final_count !== null ? parseInt(data.so_final_count) : null;
        $('#previewSoCount').text(soCount !== null ? soCount.toLocaleString('id-ID') : '-');

        if (data.process_type === 'SCALE') {
            $('#badgeProcessType').attr('class', 'badge bg-primary-subtle text-primary border border-primary').html('<i class="fas fa-balance-scale me-1"></i>Timbangan');
            $('#breakdownScale').removeClass('d-none');
            $('#breakdownManual').addClass('d-none');
            $('#previewScaleNo').text(data.scale_number || '-');
        } else {
            $('#badgeProcessType').attr('class', 'badge bg-secondary-subtle text-secondary border border-secondary').html('<i class="fas fa-hand-paper me-1"></i>Manual');
            $('#breakdownManual').removeClass('d-none');
            $('#breakdownScale').addClass('d-none');
            $('#previewPos1').text(data.pos1_count !== null ? data.pos1_count : '-');
            $('#previewPos2').text(data.pos2_count !== null ? data.pos2_count : '-');
        }

        // Reset live diff box
        $('#liveDiffBox').addClass('d-none');

        if (data.already_audited) {
            $('#auditCountBadge').text(data.total_history);
            $('#alreadyAuditedBanner').removeClass('d-none');
        } else {
            $('#alreadyAuditedBanner').addClass('d-none');
        }

        // Render previous audits
        const tbody = $('#auditHistoryTableBody');
        tbody.empty();
        if (data.previous_audits && data.previous_audits.length > 0) {
            data.previous_audits.forEach(a => {
                tbody.append(`
                    <tr>
                        <td>${a.created_at}</td>
                        <td>${a.auditor_name}</td>
                        <td class="fw-bold">${a.audited_count}</td>
                        <td class="text-muted">${a.notes || '-'}</td>
                    </tr>
                `);
            });
        } else {
            tbody.html('<tr><td colspan="4" class="text-center text-muted py-2">Belum ada riwayat audit.</td></tr>');
        }

        $('#rackPreviewWrapper').removeClass('d-none');
        $('#formPlaceholder').addClass('d-none');
        $('#formAuditWrapper').removeClass('d-none');

        $('#auditRackNo').val(data.rack_no);
        $('#auditCodePart').val(data.code_part);
        $('#auditedCount').val('').focus();
    }

    // Live calculation of difference while auditor types
    $('#auditedCount').on('input', function() {
        if (!pendingLookupData || pendingLookupData.so_final_count === null) {
            $('#liveDiffBox').addClass('d-none');
            return;
        }

        const auditVal = $(this).val().trim();
        if (auditVal === '') {
            $('#liveDiffBox').addClass('d-none');
            return;
        }

        const auditInt = parseInt(auditVal, 10);
        if (isNaN(auditInt)) {
            $('#liveDiffBox').addClass('d-none');
            return;
        }

        const soInt = parseInt(pendingLookupData.so_final_count, 10);
        const diff = auditInt - soInt;

        $('#liveDiffBox').removeClass('d-none');
        const badge = $('#badgeLiveDiff');

        if (diff === 0) {
            badge.attr('class', 'badge bg-success px-3 py-2').html('<i class="fas fa-check-circle me-1"></i>Sesuai (Match: 0 pcs)');
        } else if (diff > 0) {
            badge.attr('class', 'badge bg-danger px-3 py-2').html(`<i class="fas fa-arrow-up me-1"></i>Selisih Lebih: +${diff.toLocaleString('id-ID')} pcs (Over)`);
        } else {
            badge.attr('class', 'badge bg-warning text-dark px-3 py-2').html(`<i class="fas fa-arrow-down me-1"></i>Selisih Kurang: ${diff.toLocaleString('id-ID')} pcs (Under)`);
        }
    });

    // ─────────────────────────────────────────────────────────
    // 3. Submit Audit
    // ─────────────────────────────────────────────────────────
    $('#formSubmitAudit').on('submit', function(e) {
        e.preventDefault();

        const countVal = $('#auditedCount').val().trim();
        if (countVal === '' || parseInt(countVal) < 0) {
            toastWarn('Masukkan angka hasil audit &ge; 0.');
            return;
        }

        const btn = $('#btnSubmitAudit');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: ROUTE_SUBMIT,
            type: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                if (resp.success) {
                    // Update statistik header counter
                    if (resp.total_audited !== undefined) {
                        $('#statTotalAudited').text(parseInt(resp.total_audited).toLocaleString('id-ID'));
                    }
                    if (resp.my_audited !== undefined) {
                        $('#statMyAudited').text(parseInt(resp.my_audited).toLocaleString('id-ID'));
                    }

                    // Update live tabel riwayat audit di bagian bawah tanpa refresh
                    if (resp.recent_audits) {
                        renderRecentAuditsTable(resp.recent_audits);
                    }

                    if (typeof swal === 'function') {
                        swal({
                            title: 'Audit Berhasil!',
                            text: resp.message,
                            type: 'success'
                        }).then(() => {
                            resetAuditForm();
                            initAuditorCamera();
                        });
                    } else {
                        alert(resp.message);
                        resetAuditForm();
                        initAuditorCamera();
                    }
                } else {
                    toastError(resp.message || 'Gagal menyimpan audit.');
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Hasil Audit');
                }
            },
            error: function(xhr) {
                toastError(xhr.responseJSON?.message || 'Terjadi kesalahan sistem.');
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Hasil Audit');
            }
        });
    });

    // Helper untuk merender tabel riwayat audit secara reaktif tanpa reload
    function renderRecentAuditsTable(audits) {
        const tbody = $('#recentAuditsTableBody');
        tbody.empty();

        if (!audits || audits.length === 0) {
            tbody.html(`
                <tr id="emptyAuditRow">
                    <td colspan="9" class="text-center text-muted py-4">Belum ada riwayat audit yang Anda lakukan.</td>
                </tr>
            `);
            return;
        }

        audits.forEach(ra => {
            let badgeMethod = '<span class="badge bg-light text-muted border">-</span>';
            if (ra.process_type === 'SCALE') {
                badgeMethod = `
                    <span class="badge bg-primary-subtle text-primary border border-primary small">
                        <i class="fas fa-balance-scale me-1"></i>Timbangan
                    </span>
                `;
            } else if (ra.process_type === 'MANUAL') {
                let pSub = '';
                if (ra.pos1_count !== null || ra.pos2_count !== null) {
                    pSub = `<div class="text-muted mt-1" style="font-size:0.7rem;">P1: ${ra.pos1_count ?? '-'} | P2: ${ra.pos2_count ?? '-'}</div>`;
                }
                badgeMethod = `
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary small">
                        <i class="fas fa-hand-paper me-1"></i>Manual
                    </span>
                    ${pSub}
                `;
            }

            // Badge Status OK / NG
            let badgeStatus = '<span class="badge bg-light text-muted border">-</span>';
            if (ra.so_count !== null) {
                if (parseInt(ra.audited_count, 10) === parseInt(ra.so_count, 10)) {
                    badgeStatus = `
                        <span class="badge bg-success px-3 py-1 fw-bold fs-6">
                            <i class="fas fa-check-circle me-1"></i>OK
                        </span>
                    `;
                } else {
                    badgeStatus = `
                        <span class="badge bg-danger px-3 py-1 fw-bold fs-6">
                            <i class="fas fa-times-circle me-1"></i>NG
                        </span>
                    `;
                }
            }

            let badgeDiff = '<span class="badge bg-light text-muted">-</span>';
            if (ra.diff !== null) {
                if (ra.diff === 0) {
                    badgeDiff = `
                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                            0 pcs
                        </span>
                    `;
                } else if (ra.diff > 0) {
                    badgeDiff = `
                        <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                            <i class="fas fa-arrow-up me-1"></i>+${parseInt(ra.diff).toLocaleString('id-ID')} (Over)
                        </span>
                    `;
                } else {
                    badgeDiff = `
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1">
                            <i class="fas fa-arrow-down me-1"></i>${parseInt(ra.diff).toLocaleString('id-ID')} (Under)
                        </span>
                    `;
                }
            }

            const soCountFormatted = ra.so_count !== null ? parseInt(ra.so_count).toLocaleString('id-ID') + ' pcs' : '-';
            const auditedFormatted = parseInt(ra.audited_count).toLocaleString('id-ID') + ' pcs';
            const safeNotes = ra.notes ? $('<div>').text(ra.notes).html() : '-';

            tbody.append(`
                <tr>
                    <td class="fw-bold font-monospace text-primary">${ra.rack_no}</td>
                    <td>
                        <div class="fw-semibold text-dark">${ra.code_part || '-'}</div>
                        <small class="text-muted text-truncate d-block" style="max-width:180px;">${ra.name_part || '-'}</small>
                    </td>
                    <td>${badgeMethod}</td>
                    <td class="text-center fw-bold text-secondary">${soCountFormatted}</td>
                    <td class="text-center fw-bold text-dark fs-6">${auditedFormatted}</td>
                    <td class="text-center">${badgeStatus}</td>
                    <td class="text-center">${badgeDiff}</td>
                    <td class="text-muted small">${safeNotes}</td>
                    <td class="text-muted font-monospace small">${ra.created_at}</td>
                </tr>
            `);
        });
    }

    $('#btnResetAudit').on('click', function() {
        resetAuditForm();
        initAuditorCamera();
    });

    function resetAuditForm() {
        pendingLookupData = null;
        $('#formSubmitAudit')[0].reset();
        $('#auditRackNo').val('');
        $('#auditCodePart').val('');
        $('#rackPreviewWrapper').addClass('d-none');
        $('#formAuditWrapper').addClass('d-none');
        $('#formPlaceholder').removeClass('d-none');
        $('#rackInput').val('').focus();
        $('#btnSubmitAudit').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Hasil Audit');
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
