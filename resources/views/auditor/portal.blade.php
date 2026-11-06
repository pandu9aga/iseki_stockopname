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
    .nav-pills .nav-link.active {
        background-color: #6861ce;
        color: #fff;
    }
    .preview-box {
        width: 100%;
        min-height: 120px;
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
        border-color: #6861ce;
    }
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        gap: 8px;
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
        width: 22px;
        height: 22px;
        font-size: 11px;
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
                        <small class="text-white-50 d-block">Audit Saya</small>
                        <span class="fs-4 fw-bold text-warning" id="statMyAudited">{{ number_format($myAudited) }}</span>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="stat-badge-box">
                        <small class="text-white-50 d-block">Status Validasi</small>
                        <span class="small text-white">
                            <i class="fas fa-shield-alt text-success me-1"></i> Part harus selesai dikembalikan oleh tim Return sebelum dapat diaudit.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">

            <!-- LEFT: Scan QR & Info Rak -->
            <div class="col-lg-5">

                <!-- Scanner & Lookup Box -->
                <div class="card shadow-sm mb-3" style="border-radius:14px;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-qrcode text-primary me-2"></i>Scan QR Rak / Part
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleCamera">
                                <i class="fas fa-camera me-1"></i> <span id="cameraBtnText">Buka Kamera</span>
                            </button>
                        </div>

                        <!-- Scanner Viewfinder -->
                        <div class="mb-3" id="auditorScannerWrapper" style="border-radius: 12px; overflow: hidden; background: #000;">
                            <div id="auditorReader" style="width: 100%;"></div>
                        </div>

                        <!-- Scanned Result Card -->
                        <div id="auditorScanResultCard" class="card bg-light border mb-3 d-none">
                            <div class="card-body p-2 d-flex justify-content-between align-items-center">
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
                            Arahkan kamera ke QR stiker label pengembalian / label final atau ketikkan kode tracking/rak secara manual.
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
                                                <th>Metode</th>
                                                <th>Qty Audit</th>
                                                <th>Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody id="auditHistoryTableBody">
                                            <tr><td colspan="5" class="text-center text-muted py-2">—</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Form Input Audit (Tab Hitungan Manual & Timbangan) -->
            <div class="col-lg-7">
                <!-- Placeholder awal -->
                <div id="formPlaceholder" class="card shadow-sm h-100 d-flex flex-column align-items-center justify-content-center p-5 text-center" style="min-height:360px; border-radius:14px;">
                    <div class="p-4 bg-light rounded-circle mb-3">
                        <i class="fas fa-clipboard-list fa-3x text-primary"></i>
                    </div>
                    <h5 class="fw-bold text-muted">Scan QR Rak Fisik untuk Memulai Audit</h5>
                    <p class="text-muted small mb-0">Formulir pemeriksaan qty audit (Manual / Timbangan) akan aktif setelah nomor rak terdeteksi.</p>
                </div>

                <!-- Form Audit Card -->
                <div id="formAuditWrapper" class="card shadow-sm d-none" style="border-radius:14px; overflow:hidden;">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-check-circle me-2"></i>Form Hasil Pemeriksaan Audit
                        </h5>
                    </div>

                    <div class="card-body p-4">
                        <!-- Card Komparasi Live Perhitungan SO vs Audit -->
                        <div class="card border-0 bg-light mb-4" style="border-radius:10px;">
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
                                    <div>
                                        <span class="small fw-semibold text-muted d-block">Perbandingan Hasil (Audit vs SO Member):</span>
                                        <small class="text-muted" id="liveDiffDetail">Toleransi maksimal &le; 2.0%</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge fs-6" id="badgeLiveDiff">0 pcs</span>
                                        <div class="mt-1" id="badgeLiveStatusWrap"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- NAV PILLS SEPERTI DI MENU LOGIN -->
                        <ul class="nav nav-pills nav-justified mb-4" id="auditMethodTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active fw-bold" id="manual-tab" data-bs-toggle="pill" href="#tabManual" role="tab">
                                    <i class="fas fa-edit me-1"></i> Hitung Manual (Input 2x)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fw-bold" id="scale-tab" data-bs-toggle="pill" href="#tabScale" role="tab">
                                    <i class="fas fa-balance-scale me-1"></i> Hitung Timbangan
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content" id="auditMethodContent">
                            <!-- TAB 1: MANUAL (INPUT 2X) -->
                            <div class="tab-pane fade show active" id="tabManual" role="tabpanel">
                                <form id="formAuditManual" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="method" value="MANUAL">
                                    <input type="hidden" class="hidden-audit-rack" name="rack_no" value="">
                                    <input type="hidden" class="hidden-audit-code" name="code_part" value="">

                                    <div class="alert alert-info py-2 px-3 small mb-3">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Ketikkan hasil hitung fisik Anda dua kali pada <strong>Input 1</strong> dan <strong>Input 2</strong> untuk verifikasi ganda (mencegah salah input).
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Hasil Hitung Fisik (Input 1) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-sort-numeric-up"></i></span>
                                                <input type="number" class="form-control form-control-lg fw-bold manual-calc-input" id="manualInput1" name="input_1" min="0" placeholder="0" required>
                                                <span class="input-group-text">Pcs</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Ulangi Hitung (Input 2) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-redo"></i></span>
                                                <input type="number" class="form-control form-control-lg fw-bold manual-calc-input" id="manualInput2" name="input_2" min="0" placeholder="0" required>
                                                <span class="input-group-text">Pcs</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Match Alert -->
                                    <div id="manualMatchAlert" class="mb-3 d-none"></div>

                                    <!-- Upload Foto Bukti Manual -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label fw-bold mb-0">Foto Bukti Part (Bebas / Multi-Foto)</label>
                                            <span class="badge bg-secondary" id="manualPhotoBadge">0 foto</span>
                                        </div>
                                        <div class="preview-box" id="dropAreaManual" onclick="document.getElementById('manualPhotoInput').click()">
                                            <div id="placeholderManualPhoto" class="text-center p-2 text-muted">
                                                <i class="fas fa-camera fa-2x mb-1 text-primary"></i><br>
                                                <span class="fw-semibold">Ketuk untuk ambil / tambah foto</span>
                                            </div>
                                        </div>
                                        <input type="file" id="manualPhotoInput" accept="image/*" capture="environment" multiple class="d-none">
                                        <div class="photo-grid d-none" id="manualPhotoGrid"></div>
                                    </div>

                                    <!-- Notes Input -->
                                    <div class="mb-4">
                                        <label class="form-label small fw-bold">Catatan Audit (Opsional)</label>
                                        <textarea class="form-control" name="notes" id="manualNotes" rows="2" placeholder="Contoh: Part dalam 2 box, kondisi fisik baik, dsb."></textarea>
                                    </div>

                                    <!-- Submit Buttons -->
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary px-4 btnResetAll">
                                            <i class="fas fa-undo me-1"></i> Batal
                                        </button>
                                        <button type="submit" class="btn btn-success flex-grow-1 py-3 fw-bold fs-6" id="btnSubmitManual">
                                            <i class="fas fa-save me-1"></i> Simpan Hasil Audit Manual
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- TAB 2: TIMBANGAN -->
                            <div class="tab-pane fade" id="tabScale" role="tabpanel">
                                <form id="formAuditScale" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="method" value="SCALE">
                                    <input type="hidden" class="hidden-audit-rack" name="rack_no" value="">
                                    <input type="hidden" class="hidden-audit-code" name="code_part" value="">

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Pilih Nomor Timbangan <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-lg" name="scale_id" id="scaleSelect" required>
                                            <option value="">— Pilih Timbangan —</option>
                                            @foreach($scales as $s)
                                            <option value="{{ $s->id }}">
                                                {{ $s->scale_number }} @if($s->scale_name)— {{ $s->scale_name }}@endif @if($s->location)({{ $s->location }})@endif
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Jumlah Sample <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" class="form-control fw-bold scale-calc-input" name="sample_qty" id="scaleSampleQty" min="1" placeholder="10" value="10" required>
                                                <span class="input-group-text">Pcs</span>
                                            </div>
                                            <div class="form-text small">Sample yang dihitung manual.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Total Tanpa Sample <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" class="form-control fw-bold scale-calc-input" name="weight_without_sample" id="scaleWeightWithout" min="0" placeholder="0" required>
                                                <span class="input-group-text">Pcs</span>
                                            </div>
                                            <div class="form-text small">Part di luar sample.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Total Dgn Sample <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" class="form-control fw-bold scale-calc-input" name="weight_with_sample" id="scaleWeightWith" min="1" placeholder="0" required>
                                                <span class="input-group-text">Pcs</span>
                                            </div>
                                            <div class="form-text small">Timbang semua part + sample.</div>
                                        </div>
                                    </div>

                                    <!-- Margin & Tolerance Box -->
                                    <div id="scaleMarginBox" class="card bg-light border p-3 mb-3 small d-none">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Hitungan Target (Tanpa Sample + Sample):</span>
                                            <strong id="scaleCalcResult">0 pcs</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Hasil Timbang Aktual (Dengan Sample):</span>
                                            <strong id="scaleActualResult">0 pcs</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Selisih Sample Fisik:</span>
                                            <strong id="scaleDiffResult">0 pcs</strong>
                                        </div>
                                        <div class="d-flex justify-content-between pt-2 border-top align-items-center">
                                            <div>
                                                <span class="fw-bold d-block">Margin Error Timbangan:</span>
                                                <small class="text-muted">Batas toleransi maksimal &le; 2.0%</small>
                                            </div>
                                            <span class="badge fs-6" id="scaleMarginBadge">0%</span>
                                        </div>
                                    </div>

                                    <!-- Upload Foto Bukti Timbangan -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label fw-bold mb-0">Foto Layar / Bukti Timbangan (Bebas / Multi-Foto)</label>
                                            <span class="badge bg-secondary" id="scalePhotoBadge">0 foto</span>
                                        </div>
                                        <div class="preview-box" id="dropAreaScale" onclick="document.getElementById('scalePhotoInput').click()">
                                            <div id="placeholderScalePhoto" class="text-center p-2 text-muted">
                                                <i class="fas fa-camera fa-2x mb-1 text-primary"></i><br>
                                                <span class="fw-semibold">Ketuk untuk ambil / tambah foto timbangan</span>
                                            </div>
                                        </div>
                                        <input type="file" id="scalePhotoInput" accept="image/*" capture="environment" multiple class="d-none">
                                        <div class="photo-grid d-none" id="scalePhotoGrid"></div>
                                    </div>

                                    <!-- Notes Input -->
                                    <div class="mb-4">
                                        <label class="form-label small fw-bold">Catatan Audit (Opsional)</label>
                                        <textarea class="form-control" name="notes" id="scaleNotes" rows="2" placeholder="Contoh: Berat jenis stabil, toleransi aman, dsb."></textarea>
                                    </div>

                                    <!-- Submit Buttons -->
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary px-4 btnResetAll">
                                            <i class="fas fa-undo me-1"></i> Batal
                                        </button>
                                        <button type="submit" class="btn btn-success flex-grow-1 py-3 fw-bold fs-6" id="btnSubmitScale">
                                            <i class="fas fa-save me-1"></i> Simpan Hasil Audit Timbangan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Audits by Current Auditor -->
        <div class="card shadow-sm border-0 mt-4" style="border-radius:14px;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="fas fa-history me-2 text-primary"></i>5 Riwayat Audit Terakhir oleh Anda
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
                                <th>Metode Audit</th>
                                <th class="text-center">Hasil SO</th>
                                <th class="text-center">Hasil Audit</th>
                                <th class="text-center">Selisih</th>
                                <th class="text-center">Margin Error</th>
                                <th class="text-center">Status</th>
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
                                <td>
                                    @if(($ra['method'] ?? 'MANUAL') === 'SCALE')
                                        <span class="badge bg-info-subtle text-info border border-info small">
                                            <i class="fas fa-balance-scale me-1"></i>Timbangan
                                        </span>
                                        @if(!empty($ra['scale_number']))
                                            <div class="text-muted mt-1" style="font-size:0.7rem;">Scale: {{ $ra['scale_number'] }}</div>
                                        @endif
                                        @if($ra['error_margin_percent'] !== null)
                                            <div class="mt-1" style="font-size:0.7rem;">
                                                MoE Alat: <strong class="{{ $ra['error_margin_percent'] < 2.0 ? 'text-success' : 'text-danger' }}">{{ $ra['error_margin_percent'] }}%</strong>
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary small">
                                            <i class="fas fa-hand-paper me-1"></i>Manual (2x)
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold text-secondary">
                                    {{ $ra['so_count'] !== null ? number_format($ra['so_count']) . ' pcs' : '-' }}
                                </td>
                                <td class="text-center fw-bold text-dark fs-6">
                                    {{ number_format($ra['audited_count']) }} pcs
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
                                <td class="text-center">
                                    @if($ra['member_error_margin_percent'] !== null)
                                        <span class="badge {{ $ra['member_error_margin_percent'] < 2.0 ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger' }} px-2 py-1">
                                            {{ $ra['member_error_margin_percent'] }}%
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $finalStatus = $ra['audit_status'] ?? ($ra['so_count'] !== null ? ((int)$ra['audited_count'] === (int)$ra['so_count'] ? 'OK' : 'NG') : 'UNKNOWN');
                                    @endphp
                                    @if($finalStatus === 'OK')
                                        <span class="badge bg-success px-3 py-1 fw-bold fs-6">
                                            <i class="fas fa-check-circle me-1"></i>OK
                                        </span>
                                    @elseif($finalStatus === 'NG')
                                        <span class="badge bg-danger px-3 py-1 fw-bold fs-6">
                                            <i class="fas fa-times-circle me-1"></i>NG
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border">-</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ Str::limit($ra['notes'] ?? '-', 35) }}</td>
                                <td class="text-muted font-monospace small">{{ $ra['created_at'] }}</td>
                            </tr>
                            @empty
                            <tr id="emptyAuditRow">
                                <td colspan="11" class="text-center text-muted py-4">Belum ada riwayat audit yang Anda lakukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Konfirmasi Re-Audit jika rak/part sudah pernah diaudit -->
<div class="modal fade" id="modalConfirmReaudit" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>Peringatan: Part Sudah Pernah Diaudit!
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning py-2 px-3 small mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Part <strong id="modalReauditCodePart">-</strong> pada rak <strong id="modalReauditRackNo">-</strong> sudah pernah diaudit sebanyak <strong id="modalReauditTotalCount">0</strong> kali. Seluruh riwayat audit sebelumnya <strong>tetap tersimpan aman dan tidak akan dihapus</strong> jika Anda mengaudit ulang.
                </div>

                <h6 class="fw-bold mb-2 text-dark"><i class="fas fa-history me-1 text-primary"></i>Riwayat Hasil Audit Sebelumnya:</h6>
                <div class="table-responsive border rounded mb-3" style="max-height: 240px; overflow-y: auto;">
                    <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.82rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>#</th>
                                <th>Waktu Audit</th>
                                <th>Auditor</th>
                                <th>Metode</th>
                                <th class="text-center">Qty Audit</th>
                                <th class="text-center">Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody id="modalReauditHistoryBody">
                            <tr><td colspan="7" class="text-center text-muted py-2">—</td></tr>
                        </tbody>
                    </table>
                </div>

                <p class="mb-0 text-muted small">
                    <i class="fas fa-question-circle me-1 text-secondary"></i>
                    Apakah Anda ingin melanjutkan dan menambahkan data audit baru untuk part ini?
                </p>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Batal / Ganti Part
                </button>
                <button type="button" class="btn btn-primary fw-bold px-4" id="btnProceedReaudit">
                    <i class="fas fa-check-circle me-1"></i> Lanjutkan &amp; Tambah Audit
                </button>
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

    // Foto files management
    let manualFiles = [];
    let scaleFiles = [];

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
                    // Tampilkan modal peringatan re-audit beserta semua riwayat sebelumnya
                    $('#modalReauditRackNo').text(resp.rack_no);
                    $('#modalReauditCodePart').text(resp.code_part || '-');
                    $('#modalReauditTotalCount').text(resp.total_history || (resp.previous_audits ? resp.previous_audits.length : 1));

                    const modalTbody = $('#modalReauditHistoryBody');
                    modalTbody.empty();
                    if (resp.previous_audits && resp.previous_audits.length > 0) {
                        resp.previous_audits.forEach((a, idx) => {
                            let badgeMethod = a.method === 'SCALE'
                                ? `<span class="badge bg-info-subtle text-info border border-info">Timbangan ${a.scale_number ? '('+a.scale_number+')' : ''}</span>`
                                : `<span class="badge bg-secondary-subtle text-secondary border border-secondary">Manual (2x)</span>`;

                            let badgeStatus = '<span class="badge bg-light text-muted border">-</span>';
                            if (a.audit_status === 'OK') {
                                badgeStatus = `<span class="badge bg-success">OK</span>`;
                            } else if (a.audit_status === 'NG') {
                                badgeStatus = `<span class="badge bg-danger">NG</span>`;
                            }

                            modalTbody.append(`
                                <tr>
                                    <td>${idx + 1}</td>
                                    <td class="font-monospace text-muted">${a.created_at}</td>
                                    <td class="fw-bold">${a.auditor_name}</td>
                                    <td>${badgeMethod}</td>
                                    <td class="text-center fw-bold text-dark">${parseInt(a.audited_count).toLocaleString('id-ID')} pcs</td>
                                    <td class="text-center">${badgeStatus}</td>
                                    <td class="text-muted small">${a.notes || '-'}</td>
                                </tr>
                            `);
                        });
                    } else {
                        modalTbody.html('<tr><td colspan="7" class="text-center text-muted py-2">—</td></tr>');
                    }

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
                        <td><span class="badge bg-light text-dark border">${a.method || 'MANUAL'}</span></td>
                        <td class="fw-bold">${a.audited_count}</td>
                        <td class="text-muted">${a.notes || '-'}</td>
                    </tr>
                `);
            });
        } else {
            tbody.html('<tr><td colspan="5" class="text-center text-muted py-2">Belum ada riwayat audit.</td></tr>');
        }

        $('#rackPreviewWrapper').removeClass('d-none');
        $('#formPlaceholder').addClass('d-none');
        $('#formAuditWrapper').removeClass('d-none');

        $('.hidden-audit-rack').val(data.rack_no);
        $('.hidden-audit-code').val(data.code_part);

        // Reset inputs
        manualFiles = [];
        scaleFiles = [];
        renderPhotoGrid('manual');
        renderPhotoGrid('scale');
        $('#formAuditManual')[0].reset();
        $('#formAuditScale')[0].reset();
        $('#manualMatchAlert').addClass('d-none');
        $('#scaleMarginBox').addClass('d-none');
        $('#manualInput1').focus();
    }

    // ─────────────────────────────────────────────────────────
    // 3. Tab & Live Calculation
    // ─────────────────────────────────────────────────────────
    function getActiveAuditedCount() {
        const isScaleActive = $('#scale-tab').hasClass('active');
        if (isScaleActive) {
            const wWith = $('#scaleWeightWith').val().trim();
            return wWith !== '' ? parseInt(wWith, 10) : null;
        } else {
            const in1 = $('#manualInput1').val().trim();
            const in2 = $('#manualInput2').val().trim();
            if (in1 !== '' && in1 === in2) {
                return parseInt(in1, 10);
            }
            return in1 !== '' ? parseInt(in1, 10) : null;
        }
    }

    function updateLiveDiff() {
        if (!pendingLookupData || pendingLookupData.so_final_count === null) {
            $('#liveDiffBox').addClass('d-none');
            return;
        }

        const auditInt = getActiveAuditedCount();
        if (auditInt === null || isNaN(auditInt)) {
            $('#liveDiffBox').addClass('d-none');
            return;
        }

        const soInt = parseInt(pendingLookupData.so_final_count, 10);
        const diff = auditInt - soInt;
        const absDiff = Math.abs(diff);

        // Hitung Margin of Error terhadap hasil Member: (|audit - member| / member) * 100%
        let memberMarginPercent = 0;
        if (soInt > 0) {
            memberMarginPercent = ((absDiff / soInt) * 100);
        } else {
            memberMarginPercent = auditInt === 0 ? 0 : 100;
        }
        const memberMarginRounded = Math.round(memberMarginPercent * 100) / 100;

        $('#liveDiffBox').removeClass('d-none');
        const badgeDiff = $('#badgeLiveDiff');
        const statusWrap = $('#badgeLiveStatusWrap');

        if (diff === 0) {
            badgeDiff.attr('class', 'badge bg-secondary px-3 py-1').html('<i class="fas fa-check-circle me-1"></i>0 pcs (Match)');
        } else if (diff > 0) {
            badgeDiff.attr('class', 'badge bg-warning text-dark px-3 py-1').html(`<i class="fas fa-arrow-up me-1"></i>+${diff.toLocaleString('id-ID')} pcs (Over)`);
        } else {
            badgeDiff.attr('class', 'badge bg-warning text-dark px-3 py-1').html(`<i class="fas fa-arrow-down me-1"></i>${diff.toLocaleString('id-ID')} pcs (Under)`);
        }

        // Cek toleransi margin 2%
        const isScaleActive = $('#scale-tab').hasClass('active');
        let isScaleOk = true;
        if (isScaleActive) {
            const withSample = parseInt($('#scaleWeightWith').val() || 0, 10);
            const withoutSample = parseInt($('#scaleWeightWithout').val() || 0, 10);
            const sample = parseInt($('#scaleSampleQty').val() || 0, 10);
            if (withSample > 0) {
                const sDiff = Math.abs(withSample - (withoutSample + sample));
                const sMargin = Math.round(((sDiff / withSample) * 100) * 100) / 100;
                if (sMargin >= 2.0) {
                    isScaleOk = false;
                }
            }
        }

        if (memberMarginRounded < 2.0 && isScaleOk) {
            statusWrap.html(`
                <span class="badge bg-success px-2 py-1">
                    <i class="fas fa-check-circle me-1"></i>OK (Margin: ${memberMarginRounded}%)
                </span>
            `);
        } else {
            let reason = `Margin vs Member: ${memberMarginRounded}% (&ge; 2%)`;
            if (!isScaleOk) {
                reason = `Margin Timbangan &ge; 2% &bull; Margin vs Member: ${memberMarginRounded}%`;
            }
            statusWrap.html(`
                <span class="badge bg-danger px-2 py-1">
                    <i class="fas fa-times-circle me-1"></i>NG (${reason})
                </span>
            `);
        }
    }

    // Manual input verification
    $('.manual-calc-input').on('input', function() {
        const in1 = $('#manualInput1').val().trim();
        const in2 = $('#manualInput2').val().trim();
        const alertBox = $('#manualMatchAlert');

        if (in1 !== '' && in2 !== '') {
            if (in1 === in2) {
                alertBox.removeClass('d-none alert-danger').addClass('alert alert-success py-2 px-3 small')
                    .html('<i class="fas fa-check-circle me-1"></i><strong>Verifikasi Cocok:</strong> Input 1 & 2 sama (' + in1 + ' pcs).');
                $('#btnSubmitManual').prop('disabled', false);
            } else {
                alertBox.removeClass('d-none alert-success').addClass('alert alert-danger py-2 px-3 small')
                    .html('<i class="fas fa-exclamation-triangle me-1"></i><strong>Tidak Cocok:</strong> Input 1 (' + in1 + ') beda dengan Input 2 (' + in2 + ').');
                $('#btnSubmitManual').prop('disabled', true);
            }
        } else {
            alertBox.addClass('d-none');
            $('#btnSubmitManual').prop('disabled', false);
        }

        updateLiveDiff();
    });

    // Scale calculation & margin check
    $('.scale-calc-input').on('input', function() {
        const sample = parseInt($('#scaleSampleQty').val() || 0, 10);
        const withoutSample = parseInt($('#scaleWeightWithout').val() || 0, 10);
        const withSample = parseInt($('#scaleWeightWith').val() || 0, 10);

        if (withSample > 0) {
            const calculated = withoutSample + sample;
            const diff = Math.abs(withSample - calculated);
            const marginPercent = Math.round(((diff / withSample) * 100) * 100) / 100;

            $('#scaleCalcResult').text(calculated.toLocaleString('id-ID') + ' pcs');
            $('#scaleActualResult').text(withSample.toLocaleString('id-ID') + ' pcs');
            $('#scaleDiffResult').text(diff.toLocaleString('id-ID') + ' pcs');

            const badge = $('#scaleMarginBadge');
            if (marginPercent < 2.0) {
                badge.attr('class', 'badge bg-success').html(`<i class="fas fa-check me-1"></i>OK (${marginPercent}%)`);
            } else {
                badge.attr('class', 'badge bg-danger').html(`<i class="fas fa-exclamation-triangle me-1"></i>NG (${marginPercent}% &ge; 2%)`);
            }

            $('#scaleMarginBox').removeClass('d-none');
        } else {
            $('#scaleMarginBox').addClass('d-none');
        }

        updateLiveDiff();
    });

    // Update diff when switching tabs
    $('a[data-bs-toggle="pill"]').on('shown.bs.tab', function () {
        updateLiveDiff();
    });

    // ─────────────────────────────────────────────────────────
    // 4. Photo Upload Handling
    // ─────────────────────────────────────────────────────────
    $('#manualPhotoInput').on('change', function(e) {
        addPhotos(e.target.files, 'manual');
        $(this).val('');
    });

    $('#scalePhotoInput').on('change', function(e) {
        addPhotos(e.target.files, 'scale');
        $(this).val('');
    });

    function addPhotos(files, type) {
        const arr = type === 'manual' ? manualFiles : scaleFiles;
        for (let i = 0; i < files.length; i++) {
            arr.push(files[i]);
        }
        renderPhotoGrid(type);
    }

    window.removeAuditPhoto = function(index, type) {
        const arr = type === 'manual' ? manualFiles : scaleFiles;
        arr.splice(index, 1);
        renderPhotoGrid(type);
    };

    function renderPhotoGrid(type) {
        const arr = type === 'manual' ? manualFiles : scaleFiles;
        const grid = $(`#${type}PhotoGrid`);
        const badge = $(`#${type}PhotoBadge`);

        badge.text(arr.length + ' foto');
        grid.empty();

        if (arr.length === 0) {
            grid.addClass('d-none');
            return;
        }

        grid.removeClass('d-none');
        arr.forEach((file, idx) => {
            const url = URL.createObjectURL(file);
            grid.append(`
                <div class="photo-thumb-wrapper">
                    <img src="${url}" alt="Foto ${idx+1}">
                    <button type="button" class="btn-remove-photo" onclick="removeAuditPhoto(${idx}, '${type}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
        });
    }

    // ─────────────────────────────────────────────────────────
    // 5. Submit Audit (Manual & Scale)
    // ─────────────────────────────────────────────────────────
    $('#formAuditManual').on('submit', function(e) {
        e.preventDefault();
        const in1 = $('#manualInput1').val().trim();
        const in2 = $('#manualInput2').val().trim();

        if (in1 === '' || in2 === '') {
            toastWarn('Input 1 dan Input 2 harus diisi.');
            return;
        }
        if (in1 !== in2) {
            toastError('Input 1 dan Input 2 harus sama persis.');
            return;
        }

        const formData = new FormData(this);
        manualFiles.forEach(file => {
            formData.append('photos[]', file);
        });

        submitAuditData(formData, '#btnSubmitManual', 'Simpan Hasil Audit Manual');
    });

    $('#formAuditScale').on('submit', function(e) {
        e.preventDefault();
        const scaleId = $('#scaleSelect').val();
        if (!scaleId) {
            toastWarn('Pilih nomor timbangan terlebih dahulu.');
            return;
        }

        const formData = new FormData(this);
        scaleFiles.forEach(file => {
            formData.append('photos[]', file);
        });

        submitAuditData(formData, '#btnSubmitScale', 'Simpan Hasil Audit Timbangan');
    });

    function submitAuditData(formData, btnSelector, btnOriginalText) {
        const btn = $(btnSelector);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: ROUTE_SUBMIT,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            success: function(resp) {
                if (resp.success) {
                    if (resp.total_audited !== undefined) {
                        $('#statTotalAudited').text(parseInt(resp.total_audited).toLocaleString('id-ID'));
                    }
                    if (resp.my_audited !== undefined) {
                        $('#statMyAudited').text(parseInt(resp.my_audited).toLocaleString('id-ID'));
                    }

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
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ' + btnOriginalText);
                }
            },
            error: function(xhr) {
                toastError(xhr.responseJSON?.message || 'Terjadi kesalahan sistem.');
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ' + btnOriginalText);
            }
        });
    }

    // Render Recent Audits Table
    function renderRecentAuditsTable(audits) {
        const tbody = $('#recentAuditsTableBody');
        tbody.empty();

        if (!audits || audits.length === 0) {
            tbody.html(`
                <tr id="emptyAuditRow">
                    <td colspan="10" class="text-center text-muted py-4">Belum ada riwayat audit yang Anda lakukan.</td>
                </tr>
            `);
            return;
        }

        audits.forEach(ra => {
            let badgeMethodSO = '<span class="badge bg-light text-muted border">-</span>';
            if (ra.process_type === 'SCALE') {
                badgeMethodSO = `
                    <span class="badge bg-primary-subtle text-primary border border-primary small">
                        <i class="fas fa-balance-scale me-1"></i>Timbangan
                    </span>
                `;
            } else if (ra.process_type === 'MANUAL') {
                let pSub = '';
                if (ra.pos1_count !== null || ra.pos2_count !== null) {
                    pSub = `<div class="text-muted mt-1" style="font-size:0.7rem;">P1: ${ra.pos1_count ?? '-'} | P2: ${ra.pos2_count ?? '-'}</div>`;
                }
                badgeMethodSO = `
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary small">
                        <i class="fas fa-hand-paper me-1"></i>Manual
                    </span>
                    ${pSub}
                `;
            }

            let badgeMethodAudit = '<span class="badge bg-secondary-subtle text-secondary border border-secondary small"><i class="fas fa-hand-paper me-1"></i>Manual (2x)</span>';
            if ((ra.method || 'MANUAL') === 'SCALE') {
                let scaleSub = ra.scale_number ? `<div class="text-muted mt-1" style="font-size:0.7rem;">Scale: ${ra.scale_number}</div>` : '';
                let scaleMoe = ra.error_margin_percent !== null ? `<div class="mt-1" style="font-size:0.7rem;">MoE Alat: <strong class="${ra.error_margin_percent < 2.0 ? 'text-success' : 'text-danger'}">${ra.error_margin_percent}%</strong></div>` : '';
                badgeMethodAudit = `
                    <span class="badge bg-info-subtle text-info border border-info small">
                        <i class="fas fa-balance-scale me-1"></i>Timbangan
                    </span>
                    ${scaleSub}
                    ${scaleMoe}
                `;
            }

            let badgeStatus = '<span class="badge bg-light text-muted border">-</span>';
            const finalStatus = ra.audit_status || (ra.so_count !== null ? (parseInt(ra.audited_count, 10) === parseInt(ra.so_count, 10) ? 'OK' : 'NG') : null);
            if (finalStatus === 'OK') {
                badgeStatus = `
                    <span class="badge bg-success px-3 py-1 fw-bold fs-6">
                        <i class="fas fa-check-circle me-1"></i>OK
                    </span>
                `;
            } else if (finalStatus === 'NG') {
                badgeStatus = `
                    <span class="badge bg-danger px-3 py-1 fw-bold fs-6">
                        <i class="fas fa-times-circle me-1"></i>NG
                    </span>
                `;
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

            let badgeMarginMember = '<span class="badge bg-light text-muted">-</span>';
            if (ra.member_error_margin_percent !== null) {
                const isMemberOk = ra.member_error_margin_percent < 2.0;
                badgeMarginMember = `
                    <span class="badge ${isMemberOk ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger'} px-2 py-1">
                        ${ra.member_error_margin_percent}%
                    </span>
                `;
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
                    <td>${badgeMethodSO}</td>
                    <td>${badgeMethodAudit}</td>
                    <td class="text-center fw-bold text-secondary">${soCountFormatted}</td>
                    <td class="text-center fw-bold text-dark fs-6">${auditedFormatted}</td>
                    <td class="text-center">${badgeDiff}</td>
                    <td class="text-center">${badgeMarginMember}</td>
                    <td class="text-center">${badgeStatus}</td>
                    <td class="text-muted small">${safeNotes}</td>
                    <td class="text-muted font-monospace small">${ra.created_at}</td>
                </tr>
            `);
        });
    }

    $('.btnResetAll').on('click', function() {
        resetAuditForm();
        initAuditorCamera();
    });

    function resetAuditForm() {
        pendingLookupData = null;
        manualFiles = [];
        scaleFiles = [];
        renderPhotoGrid('manual');
        renderPhotoGrid('scale');
        $('#formAuditManual')[0].reset();
        $('#formAuditScale')[0].reset();
        $('#manualMatchAlert').addClass('d-none');
        $('#scaleMarginBox').addClass('d-none');
        $('.hidden-audit-rack').val('');
        $('.hidden-audit-code').val('');
        $('#rackPreviewWrapper').addClass('d-none');
        $('#formAuditWrapper').addClass('d-none');
        $('#formPlaceholder').removeClass('d-none');
        $('#rackInput').val('').focus();
        $('#btnSubmitManual').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Hasil Audit Manual');
        $('#btnSubmitScale').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan Hasil Audit Timbangan');
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
