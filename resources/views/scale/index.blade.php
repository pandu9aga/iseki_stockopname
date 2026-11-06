@extends('layouts.main')

@section('style')
<style>
    /* ── Scale Module — Fase 2B ── */

    /* Partner / Scale Active Bar */
    .scale-active-bar {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        border-radius: 12px;
        padding: 6px 12px;
        color: #fff;
        margin-bottom: 10px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
    }

    .scale-active-bar .partner-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
    }

    .scale-active-bar .scale-avatar {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: linear-gradient(135deg, #f093fb, #f5576c);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        color: #fff;
        flex-shrink: 0;
    }

    .bar-text {
        line-height: 1.1;
        min-width: 0;
    }

    .bar-text .bar-label {
        font-size: 0.55rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.55);
    }

    .bar-text .bar-value {
        font-size: 0.76rem;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 36vw;
    }

    .bar-text .bar-value strong {
        color: #fff;
        font-weight: 700;
    }

    .bar-text .bar-sub {
        font-size: 0.64rem;
        color: rgba(255, 255, 255, 0.55);
        margin-left: 5px;
    }

    .bar-sep {
        width: 1px;
        height: 22px;
        background: rgba(255, 255, 255, 0.15);
        flex-shrink: 0;
    }

    .btn-bar-compact {
        font-size: 0.7rem;
        padding: 0.15rem 0.5rem;
    }

    /* Scan QR Box */
    .qr-scan-box {
        border: 2px dashed #dee2e6;
        border-radius: 16px;
        padding: 32px 20px;
        text-align: center;
        transition: border-color 0.3s, background 0.3s;
        cursor: pointer;
        background: #fafafa;
    }

    .qr-scan-box:hover,
    .qr-scan-box.focused {
        border-color: #0d6efd;
        background: #f0f4ff;
    }

    .qr-scan-box .scan-icon {
        font-size: 3rem;
        color: #0d6efd;
        opacity: 0.7;
    }

    /* Item Preview Card */
    .item-preview-card {
        border-radius: 14px;
        border: 2px solid #e9ecef;
        background: #fff;
        transition: border-color 0.3s;
        overflow: hidden;
    }

    .item-preview-card.found-ok {
        border-color: #0d6efd;
    }

    .item-preview-card .item-header {
        background: linear-gradient(135deg, #0d6efd, #6610f2);
        color: #fff;
        padding: 8px 14px;
    }

    .item-preview-card .item-body .text-muted.small {
        font-size: 0.7rem;
        line-height: 1.15;
    }

    .item-preview-card .item-body .fw-semibold {
        font-size: 0.82rem;
        line-height: 1.2;
    }

    .item-badge-step {
        font-size: 0.7rem;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* Weighing Form */
    .weigh-form-card {
        border-radius: 14px;
        border: 2px solid #e9ecef;
        background: #fff;
    }

    .weigh-input-group {
        margin-bottom: 0.6rem;
    }

    .weigh-input-group label {
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
    }

    .weigh-input-group .input-group-text {
        background: #f8f9fa;
        border-color: #dee2e6;
        font-size: 0.8rem;
        color: #6c757d;
    }

    .weigh-input-group .form-text {
        font-size: 0.72rem;
        line-height: 1.2;
        margin-top: 0.15rem;
    }

    /* Tetap nyaman diketik, tapi tinggi input dipangkas */
    .weigh-input-group .form-control-lg {
        font-size: 1.05rem;
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
    }

    /* Stepper ronde timbangan */
    .scale-stepper {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .scale-stepper .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        flex: 0 0 auto;
        opacity: 0.45;
        transition: opacity 0.2s;
    }

    .scale-stepper .step-item.active,
    .scale-stepper .step-item.done {
        opacity: 1;
    }

    .scale-stepper .step-dot {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.68rem;
        font-weight: 700;
        background: #e9ecef;
        color: #6c757d;
    }

    .scale-stepper .step-item.active .step-dot {
        background: #0d6efd;
        color: #fff;
    }

    .scale-stepper .step-item.done .step-dot {
        background: #198754;
        color: #fff;
    }

    .scale-stepper .step-label {
        font-size: 0.58rem;
        color: #6c757d;
        text-align: center;
    }

    .scale-stepper .step-item.active .step-label {
        color: #0d6efd;
        font-weight: 600;
    }

    .scale-stepper .step-line {
        flex: 1 1 auto;
        height: 2px;
        background: #e9ecef;
        margin-bottom: 12px;
    }

    /* Photo capture */
    .photo-preview img {
        max-height: 180px;
        width: auto;
        border-radius: 10px;
        border: 1px solid #e9ecef;
    }

    /* Live Preview Box */
    #livePreviewBox {
        border-radius: 12px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        padding: 10px 12px;
        min-height: 48px;
        display: flex;
        align-items: center;
        transition: background 0.3s;
    }

    #livePreviewBox.status-ok {
        background: #d1e7dd;
        border-color: #badbcc;
    }

    #livePreviewBox.status-ng {
        background: #f8d7da;
        border-color: #f5c2c7;
    }

    /* NG Denial Banner */
    .ng-denial-banner {
        border-radius: 14px;
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: #fff;
        padding: 20px;
        text-align: center;
        animation: pulseNG 1.5s infinite;
    }

    @keyframes pulseNG {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.5);
        }

        50% {
            box-shadow: 0 0 0 12px rgba(220, 53, 69, 0);
        }
    }

    /* History Table */
    .history-table td,
    .history-table th {
        vertical-align: middle;
        font-size: 0.83rem;
    }

    /* Setup Partner Modal */
    .setup-modal-header {
        background: linear-gradient(135deg, #0f3460, #533483);
        color: #fff;
    }

    /* Responsive tweaks */
    @media (min-width: 576px) {

        /* Ruang atas/bawah dirapatkan agar form input muat tanpa scroll */
        .page-inner {
            padding-top: 10px;
            padding-bottom: 10px;
        }
    }

    @media (max-width: 576px) {
        .scale-active-bar {
            flex-direction: column;
            align-items: stretch !important;
            gap: 8px;
        }

        .bar-sep {
            display: none;
        }

        .bar-text .bar-value {
            max-width: 72vw;
        }
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- SETUP REQUIRED: Jika partner/timbangan belum diatur --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        @if(!$session->scale_partner_nik || !$session->scale_id)
        <div class="row justify-content-center py-5">
            <div class="col-md-7 col-lg-5">
                <div class="card shadow-lg" style="border-radius: 20px; border:none; overflow:hidden;">
                    <div class="setup-modal-header p-4 text-center">
                        <div class="mb-3" style="font-size:3rem;"><i class="fas fa-user-friends"></i></div>
                        <h4 class="fw-bold mb-1">Setup Timbangan</h4>
                        <p class="mb-0 opacity-75 small">Isi NIK Partner dan pilih nomor timbangan sebelum memulai.</p>
                    </div>
                    <div class="card-body p-4">
                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                        @endif

                        <form action="{{ route('scale.setup-partner') }}" method="POST" id="formSetupPartner">
                            @csrf

                            {{-- Partner (searchable by Nama / NIK) --}}
                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="partner_nik">Partner <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg select2-partner" id="partner_nik" name="partner_nik">
                                    <option value="">— Cari Nama / NIK Partner —</option>
                                    @if(old('partner_nik'))
                                    <option value="{{ old('partner_nik') }}" selected>{{ old('partner_nik') }}</option>
                                    @endif
                                </select>
                                <div class="form-text">Ketik nama atau NIK untuk mencari partner dari database karyawan (iseki_rifa).</div>
                            </div>

                            {{-- Nomor Timbangan --}}
                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="scale_id">Nomor Timbangan <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg" id="scale_id" name="scale_id" required>
                                    <option value="">— Pilih timbangan —</option>
                                    @foreach($scales as $s)
                                    <option value="{{ $s->id }}" {{ old('scale_id') == $s->id ? 'selected' : '' }}>
                                        {{ $s->scale_number }}
                                        @if($s->scale_name) — {{ $s->scale_name }} @endif
                                        @if($s->location) ({{ $s->location }}) @endif
                                    </option>
                                    @endforeach
                                </select>
                                @if($scales->isEmpty())
                                <div class="alert alert-warning mt-2 py-2 small">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Belum ada timbangan terdaftar. Hubungi admin untuk menambahkan.
                                </div>
                                @endif
                            </div>

                            <button type="submit" class="btn btn-primary w-100 btn-lg fw-bold" id="btnStartSetup">
                                <i class="fas fa-play me-2"></i>Mulai Sesi Timbangan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @else
        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- MAIN SCALE INTERFACE                               --}}
        {{-- ═══════════════════════════════════════════════════ --}}

        {{-- Active Bar: Partner & Scale Info (compact) --}}
        <div class="scale-active-bar d-flex align-items-center justify-content-between flex-wrap gap-2" id="activeBar">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                {{-- Partner --}}
                <div class="d-flex align-items-center gap-2">
                    <div class="partner-avatar">
                        {{ strtoupper(substr($partner->nama ?? $session->scale_partner_nik, 0, 1)) }}
                    </div>
                    <div class="bar-text">
                        <div class="bar-label"><i class="fas fa-user me-1"></i>Partner</div>
                        <div class="bar-value">
                            <strong>{{ $partner->nama ?? $session->scale_partner_nik }}</strong>
                            <span class="bar-sub">NIK {{ $session->scale_partner_nik }}</span>
                        </div>
                    </div>
                </div>

                {{-- Separator --}}
                <div class="bar-sep"></div>

                {{-- Timbangan --}}
                <div class="d-flex align-items-center gap-2">
                    <div class="scale-avatar"><i class="fas fa-balance-scale"></i></div>
                    <div class="bar-text">
                        <div class="bar-label"><i class="fas fa-weight me-1"></i>Timbangan</div>
                        <div class="bar-value">
                            <strong>{{ $session->scale->scale_number ?? '-' }}</strong>
                            @if($session->scale && ($session->scale->scale_name || $session->scale->location))
                            <span class="bar-sub">
                                {{ $session->scale->scale_name }}{{ $session->scale->location ? ' — ' . $session->scale->location : '' }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Change Buttons & Bluetooth --}}
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <button type="button" id="btnConnectPrinterScale" class="btn btn-sm btn-outline-light btn-bar-compact">
                    <i class="fas fa-print me-1"></i> <span id="printerScaleStatusText">Printer Bluetooth</span>
                </button>
                <button class="btn btn-sm btn-outline-light btn-bar-compact" data-bs-toggle="modal" data-bs-target="#modalChangeSetup"
                    id="btnChangeSetup">
                    <i class="fas fa-sync-alt me-1"></i> Ganti Partner / Timbangan
                </button>
            </div>
        </div>

        {{-- Session Success / Error Alerts --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row g-4">

            {{-- LEFT: Scan QR & Item Preview --}}
            <div class="col-lg-5">

                {{-- QR Scan Input & Camera Scanner --}}
                <div class="card shadow-sm mb-3" style="border-radius:14px;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-qrcode text-primary me-2"></i>Scan QR Kurir
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleCamera">
                                <i class="fas fa-camera me-1"></i> <span id="cameraBtnText">Buka Kamera</span>
                            </button>
                        </div>

                        <!-- Camera Scanner Container -->
                        <div class="mb-3" id="scaleScannerWrapper" style="border-radius: 12px; overflow: hidden; background: #000;">
                            <div id="scaleReader" style="width: 100%;"></div>
                        </div>

                        <!-- Scanned Result Card (when camera closes) -->
                        <div id="scaleScanResultCard" class="card border-success bg-success-subtle mb-3 d-none">
                            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-success fw-bold d-block"><i class="fas fa-check-circle me-1"></i> QR Terbaca</small>
                                    <span class="fw-bold text-dark text-break small" id="scaleScannedText">-</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-success ms-2" id="btnRescanScale">
                                    <i class="fas fa-redo me-1"></i> Scan Ulang
                                </button>
                            </div>
                        </div>

                        <div class="input-group mb-2">
                            <span class="input-group-text bg-primary text-white border-0">
                                <i class="fas fa-qrcode"></i>
                            </span>
                            <input type="text" class="form-control form-control-lg" id="trackingCodeInput"
                                placeholder="Arahkan scanner atau ketik kode..."
                                autocomplete="off" autofocus
                                aria-label="Kode tracking QR kurir">
                            <button class="btn btn-primary" type="button" id="btnLookupItem">
                                <i class="fas fa-search me-1"></i>Cek
                            </button>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1 text-muted"></i>
                            Scan QR label kurir menggunakan kamera di atas atau ketik tracking code / format QR.
                        </div>
                    </div>
                </div>

                {{-- Item Info Card (hidden until item found) --}}
                <div id="itemPreviewWrapper" class="d-none">
                    <div class="item-preview-card mb-3" id="itemPreviewCard">
                        <div class="item-header d-flex justify-content-between align-items-center">
                            <span class="item-badge-step bg-white text-primary" id="previewStep">-</span>
                        </div>
                        <div class="p-2 item-body">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-muted small">Kode Part</div>
                                    <div class="fw-semibold" id="previewCodePart">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">No. Rak</div>
                                    <div class="fw-semibold" id="previewRackNo">-</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">Nama Part</div>
                                    <div class="fw-semibold" id="previewNamePart">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Area / Cell</div>
                                    <div class="fw-semibold" id="previewArea">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Diproses Kurir</div>
                                    <div class="fw-semibold" id="previewCourierAt">-</div>
                                </div>
                            </div>

                            {{-- Denial Banner (step != READY_TO_COUNT) --}}
                            <div id="denialBanner" class="d-none mt-3 alert alert-warning mb-0" role="alert">
                                <i class="fas fa-ban me-2"></i>
                                <span id="denialMessage"></span>
                            </div>

                            {{-- Info hitung ulang (tidak memblokir) --}}
                            <div id="recountBanner" class="d-none mt-3 alert alert-info mb-0" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <span id="recountMessage"></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- RIGHT: Weighing Form --}}
            <div class="col-lg-7">

                {{-- Default placeholder --}}
                <div id="formPlaceholder" class="qr-scan-box h-100 d-flex flex-column align-items-center justify-content-center" style="min-height:320px;">
                    <div class="scan-icon mb-3"><i class="fas fa-balance-scale"></i></div>
                    <h5 class="fw-bold text-muted">Scan QR Kurir untuk Mulai</h5>
                    <p class="text-muted small mb-0">Formulir penimbangan akan muncul di sini setelah item ditemukan dan berstatus <strong>READY_TO_COUNT</strong>.</p>
                </div>

                {{-- Weighing Form (hidden until item is OK) --}}
                <div id="weighFormWrapper" class="d-none">
                    <div class="weigh-form-card shadow-sm p-3">

                        {{-- Stepper --}}
                        <div class="scale-stepper mb-2">
                            <div class="step-item active" data-step="1">
                                <span class="step-dot">1</span><span class="step-label">Sample</span>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item" data-step="2">
                                <span class="step-dot">2</span><span class="step-label">Hitungan</span>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item" data-step="3">
                                <span class="step-dot">3</span><span class="step-label">Final</span>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item" data-step="4">
                                <span class="step-dot">4</span><span class="step-label">Cetak QR</span>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item" data-step="5">
                                <span class="step-dot">5</span><span class="step-label">Foto Bukti</span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-weight text-success me-2"></i><span id="formStageTitle">Jumlah Sample</span>
                            </h6>
                            <span class="badge bg-primary" id="roundBadge">Sample</span>
                        </div>

                        <input type="hidden" id="soItemId" name="so_item_id" value="">

                        {{-- ══ STAGE 1: JUMLAH SAMPLE ══ --}}
                        <div id="sampleQtyStage">
                            <div class="alert alert-light border small py-2 px-3 mb-2">
                                <i class="fas fa-info-circle text-primary me-1"></i>
                                Tentukan dulu jumlah sample (pcs) yang akan ditimbang.
                            </div>

                            <div class="mb-2 weigh-input-group">
                                <label for="sampleQtyInput">Jumlah Sample <span class="text-danger">*</span></label>
                                <div class="input-group mt-1">
                                    <span class="input-group-text"><i class="fas fa-vials"></i></span>
                                    <input type="number" class="form-control form-control-lg" id="sampleQtyInput"
                                        min="1" step="1" inputmode="numeric" placeholder="10">
                                    <span class="input-group-text">pcs</span>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success fw-bold" id="btnSaveSampleQty">
                                    <i class="fas fa-save me-1"></i>Simpan Jumlah Sample
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-scale">
                                    <i class="fas fa-undo me-1"></i>Reset / Scan Item Lain
                                </button>
                            </div>
                        </div>

                        {{-- ══ STAGE 2: HITUNGAN TANPA SAMPLE ══ --}}
                        <div id="roundStage" class="d-none">
                            <div class="alert alert-light border small py-2 px-3 mb-2 d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-vials text-primary me-1"></i>Sample: <strong id="roundSampleQty">-</strong> pcs</span>
                                <span>Total tanpa sample: <strong id="roundTotalWithout">0</strong> pcs</span>
                            </div>

                            <div class="mb-2 weigh-input-group">
                                <label for="sampleWeight">Total Tanpa Sample <span class="text-danger">*</span></label>
                                <div class="input-group mt-1">
                                    <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                                    <input type="number" class="form-control form-control-lg" id="sampleWeight"
                                        min="1" step="1" inputmode="numeric" placeholder="0">
                                    <span class="input-group-text">pcs</span>
                                </div>
                                <div class="form-text">Jumlah part pada hitungan ini (tanpa sample).</div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold small mb-1" for="samplePhoto">
                                    Foto Hitungan <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control form-control-sm" id="samplePhoto"
                                    accept="image/*" capture="environment">
                                <div class="form-text">Ambil foto part saat hitungan ini.</div>
                                <div class="photo-preview d-none mt-2" id="samplePhotoPreviewWrap">
                                    <img id="samplePhotoPreview" alt="Preview foto hitungan" class="img-fluid rounded">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary" id="btnNextRound">
                                    <i class="fas fa-redo me-1"></i>Simpan &amp; Tambah Hitungan
                                </button>
                                <button type="button" class="btn btn-success fw-bold" id="btnGoFinal">
                                    <i class="fas fa-arrow-right me-1"></i>Simpan &amp; Lanjut ke Final
                                </button>
                                <button type="button" class="btn btn-outline-success d-none" id="btnResumeFinal">
                                    <i class="fas fa-arrow-right me-1"></i>Kembali ke Timbang Final
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-scale">
                                    <i class="fas fa-undo me-1"></i>Reset / Scan Item Lain
                                </button>
                            </div>
                        </div>

                        {{-- ══ STAGE 3: FINAL (TOTAL DENGAN SAMPLE) ══ --}}
                        <div id="finalStage" class="d-none">
                            <div id="finalResumeInfo" class="alert alert-info py-2 px-3 small d-none mb-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Melanjutkan tahap final. <strong id="finalResumeRounds">0</strong> hitungan tanpa sample sudah tersimpan.
                                Gunakan tombol <strong>Kembali</strong> jika ingin menambah hitungan.
                            </div>

                            <div class="alert alert-light border small py-2 px-3 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Jumlah Sample</span><strong id="finalSampleQty">-</strong> pcs
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Jumlah Hitungan</span><strong id="finalRoundCount">0</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Total Tanpa Sample</span><strong id="finalTotalWithout">0</strong> pcs
                                </div>
                            </div>

                            <div class="mb-2 weigh-input-group">
                                <label for="weightWithSample">Total Dengan Sample <span class="text-danger">*</span></label>
                                <div class="input-group mt-1">
                                    <span class="input-group-text"><i class="fas fa-vials"></i></span>
                                    <input type="number" class="form-control form-control-lg" id="weightWithSample"
                                        name="weight_with_sample" min="1" step="1" inputmode="numeric"
                                        placeholder="0" required>
                                    <span class="input-group-text">pcs</span>
                                </div>
                                <div class="form-text">Jumlah total part setelah ditambah sample.</div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold small mb-1" for="finalPhoto">
                                    Foto Timbang Final <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control form-control-sm" id="finalPhoto"
                                    accept="image/*" capture="environment">
                                <div class="form-text">Ambil foto part pada penimbangan final.</div>
                                <div class="photo-preview d-none mt-2" id="finalPhotoPreviewWrap">
                                    <img id="finalPhotoPreview" alt="Preview foto final" class="img-fluid rounded">
                                </div>
                            </div>

                            <div id="livePreviewBox" class="mb-3">
                                <span class="text-muted"><i class="fas fa-calculator me-2"></i>Masukkan total dengan sample untuk melihat margin.</span>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success fw-bold" id="btnSubmitWeigh">
                                    <i class="fas fa-calculator me-2"></i>Hitung &amp; Cek Margin
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnBackToRounds">
                                    <i class="fas fa-arrow-left me-1"></i>Kembali &amp; Tambah Hitungan
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-reset-scale">
                                    <i class="fas fa-undo me-1"></i>Reset / Scan Item Lain
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Result Panel (shown after submit) --}}
                <div id="resultPanel" class="d-none mt-3">
                    {{-- Filled by JS --}}
                </div>

            </div>
        </div>

        {{-- Riwayat Penimbangan (full width, di bawah) --}}
        <div id="historyWrapper" class="d-none mt-3">
            <div class="card shadow-sm" style="border-radius:14px;">
                <div class="card-header d-flex justify-content-between align-items-center py-2"
                    style="cursor:pointer; border-radius:14px 14px 0 0;"
                    data-bs-toggle="collapse" data-bs-target="#historyCollapse">
                    <span class="fw-semibold small">
                        <i class="fas fa-history me-2 text-secondary"></i>Riwayat Penimbangan Item Ini
                    </span>
                    <i class="fas fa-chevron-down small text-muted"></i>
                </div>
                <div class="collapse show" id="historyCollapse">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm history-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Tahap</th>
                                        <th>Jumlah (pcs)</th>
                                        <th>NIK</th>
                                        <th>Margin</th>
                                        <th>Qty</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">—</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @endif {{-- end if partner/scale set --}}

    </div>
</div>

{{-- ═══════════════════════ MODALS ═══════════════════════ --}}

{{-- Change Partner / Scale Modal --}}
@if($session->scale_partner_nik && $session->scale_id)
<div class="modal fade" id="modalChangeSetup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg" style="border-radius:20px; overflow:hidden;">
            <div class="setup-modal-header p-3 d-flex justify-content-between align-items-center">
                <h5 class="modal-title text-white fw-bold mb-0">
                    <i class="fas fa-sync-alt me-2"></i>Ganti Partner / Timbangan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning small mb-3">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Mengganti partner atau timbangan akan memperbarui sesi aktif Anda.
                </div>
                <form action="{{ route('scale.setup-partner') }}" method="POST" id="formChangeSetup">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="change_partner_nik">Partner Baru <span class="text-danger">*</span></label>
                        <select class="form-select select2-partner" id="change_partner_nik" name="partner_nik">
                            <option value="">— Cari Nama / NIK Partner —</option>
                            @if($session->scale_partner_nik)
                            <option value="{{ $session->scale_partner_nik }}" selected>
                                {{ $partner->nama ? $partner->nama . ' — ' . $session->scale_partner_nik : $session->scale_partner_nik }}
                            </option>
                            @endif
                        </select>
                        <div class="form-text">Ketik nama atau NIK untuk mencari partner pengganti.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="change_scale_id">Timbangan <span class="text-danger">*</span></label>
                        <select class="form-select" id="change_scale_id" name="scale_id" required>
                            <option value="">— Pilih timbangan —</option>
                            @foreach($scales as $s)
                            <option value="{{ $s->id }}" {{ $session->scale_id == $s->id ? 'selected' : '' }}>
                                {{ $s->scale_number }}
                                @if($s->scale_name) — {{ $s->scale_name }} @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="fas fa-save me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('script')
<script src="{{ asset('assets/js/plugin/html5-qrcode.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/select2/select2.full.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/js/scale-calc.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/bt-thermal-printer.js') }}?v={{ time() }}"></script>
<script>
    $(document).ready(function() {

        // ── Cached routes ──
        const ROUTE_VALIDATE_PARTNER = "{{ route('scale.validate-partner') }}";
        const ROUTE_LOOKUP_ITEM = "{{ route('scale.lookup-item') }}";
        const ROUTE_SAMPLE_QTY = "{{ route('scale.sample-qty') }}";
        const ROUTE_SAMPLE_ROUND = "{{ route('scale.sample-round') }}";
        const ROUTE_SUBMIT_FINAL = "{{ route('scale.submit-final') }}";
        const ROUTE_MARK_PRINTED = "{{ route('scale.mark-printed') }}";
        const ROUTE_LABEL_PROOF = "{{ route('scale.label-proof') }}";
        const ROUTE_CANCEL_SESSION = "{{ route('scale.cancel-session') }}";

        const ROUTE_ITEM_HISTORY = "{{ route('scale.item-history') }}";
        const CSRF_TOKEN = "{{ csrf_token() }}";

        // ── State ──
        let currentItem = null;
        let currentSession = null;
        let currentPrintData = null;
        let isSubmitting = false;

        // ─────────────────────────────────────────────────────────
        // CAMERA QR SCANNER (HTML5 QR Scanner)
        // ─────────────────────────────────────────────────────────
        let scaleQrCode = null;
        let isScaleScannerRunning = false;

        async function initScaleCamera() {
            let readerEl = document.getElementById("scaleReader");
            // Jika sebelumnya gagal dan wrapper diganti pesan error, buat ulang elemen reader
            if (!readerEl) {
                const wrapper = document.getElementById('scaleScannerWrapper');
                if (!wrapper) return;
                wrapper.innerHTML = '<div id="scaleReader" style="width: 100%;"></div>';
                readerEl = document.getElementById("scaleReader");
            }
            if (!readerEl) return;

            if (scaleQrCode) {
                try {
                    if (isScaleScannerRunning) {
                        await scaleQrCode.stop();
                    }
                    await scaleQrCode.clear();
                } catch (e) {
                    console.warn("Scale cleanup previous scanner:", e);
                }
            }

            $('#scaleScannerWrapper').removeClass('d-none');
            $('#cameraBtnText').text('Tutup Kamera');
            readerEl.innerHTML = '';

            try {
                scaleQrCode = new Html5Qrcode("scaleReader", {
                    experimentalFeatures: {
                        useBarCodeDetectorIfSupported: true
                    }
                });

                const config = {
                    fps: 20,
                    qrbox: function(viewfinderWidth, viewfinderHeight) {
                        const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                        const qrboxEdgeSize = Math.floor(minEdge * 0.75);
                        return {
                            width: qrboxEdgeSize,
                            height: qrboxEdgeSize
                        };
                    },
                    aspectRatio: 1.0
                };

                // Coba jalankan scanner dengan fallback otomatis jika browser menolak constraint spesifik
                try {
                    await scaleQrCode.start({
                            facingMode: "environment"
                        },
                        config,
                        (decodedText) => {
                            console.log("Scale QR Scanned:", decodedText);
                            stopScaleCamera();
                            $('#trackingCodeInput').val(decodedText);
                            lookupItem();
                        },
                        (error) => {}
                    );
                } catch (errConstraint) {
                    console.warn("Retrying with camera id list...", errConstraint);
                    const devices = await Html5Qrcode.getCameras();
                    if (devices && devices.length) {
                        const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('belakang')) || devices[devices.length - 1];
                        await scaleQrCode.start(
                            backCam.id,
                            config,
                            (decodedText) => {
                                console.log("Scale QR Scanned:", decodedText);
                                stopScaleCamera();
                                $('#trackingCodeInput').val(decodedText);
                                lookupItem();
                            },
                            (error) => {}
                        );
                    } else {
                        throw errConstraint;
                    }
                }
                isScaleScannerRunning = true;
            } catch (err) {
                console.error("Gagal start kamera timbangan:", err);
                isScaleScannerRunning = false;
                $('#scaleScannerWrapper').html(`
                <div class="p-4 text-center text-white bg-dark rounded">
                    <i class="fas fa-camera-slash fa-2x mb-2 text-warning"></i>
                    <p class="small mb-2 font-weight-bold">Tidak dapat mengakses kamera: ${err.message || 'Izin ditolak'}</p>
                    <button type="button" class="btn btn-sm btn-outline-light" id="btnRetryCamera">
                        <i class="fas fa-redo me-1"></i> Coba Izin Kamera Lagi
                    </button>
                </div>
            `);
                $('#cameraBtnText').text('Buka Kamera');
            }
        }

        // Auto-buka kamera jika partner sudah disetup (elemen #scaleReader tersedia)
        if ($('#scaleReader').length) {
            initScaleCamera();
        }

        async function stopScaleCamera() {
            if (scaleQrCode && isScaleScannerRunning) {
                try {
                    await scaleQrCode.stop();
                } catch (e) {}
                isScaleScannerRunning = false;
            }
            $('#scaleScannerWrapper').addClass('d-none');
            $('#cameraBtnText').text('Buka Kamera');
        }

        $('#btnToggleCamera').on('click', function() {
            if (isScaleScannerRunning) {
                stopScaleCamera();
            } else {
                initScaleCamera();
            }
        });

        $(document).on('click', '#btnRetryCamera', function() {
            initScaleCamera();
        });

        // ─────────────────────────────────────────────────────────
        // PARTNER SEARCHABLE DROPDOWN (Select2 — cari by Nama / NIK)
        // ─────────────────────────────────────────────────────────
        const ROUTE_SEARCH_PARTNERS = "{{ route('scale.search-partners') }}";

        function initPartnerSelect(selector) {
            const $el = $(selector);
            if (!$el.length || typeof $el.select2 !== 'function') return;

            const $modal = $el.closest('.modal');

            $el.select2({
                theme: 'bootstrap',
                width: '100%',
                placeholder: '— Cari Nama / NIK Partner —',
                allowClear: true,
                minimumInputLength: 0,
                dropdownParent: $modal.length ? $modal : $(document.body),
                ajax: {
                    url: ROUTE_SEARCH_PARTNERS,
                    dataType: 'json',
                    delay: 300,
                    cache: true,
                    data: function(params) {
                        return {
                            q: params.term || '',
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results || [],
                            pagination: {
                                more: !!(data.pagination && data.pagination.more)
                            }
                        };
                    }
                },
                templateResult: function(item) {
                    if (item.loading) return item.text;
                    if (!item.id) return item.text;

                    const nama = item.nama || item.text || '';
                    const nik = item.nik || item.id || '';

                    // Escape text to avoid HTML injection from employee data.
                    return $('<div class="d-flex justify-content-between align-items-center">')
                        .append($('<span class="fw-semibold"></span>').text(nama))
                        .append($('<span class="text-muted small ms-2"></span>').text(nik));
                },
                templateSelection: function(item) {
                    if (!item.id) return item.text;
                    // Static preselected option already contains "Nama — NIK".
                    if (!item.nama) return item.text;
                    return (item.nama || item.id) + ' — ' + (item.nik || item.id);
                },
                language: {
                    searching: function() {
                        return 'Mencari partner...';
                    },
                    noResults: function() {
                        return 'Partner tidak ditemukan.';
                    },
                    loadingMore: function() {
                        return 'Memuat lebih banyak...';
                    },
                    errorLoading: function() {
                        return 'Gagal memuat data partner.';
                    }
                }
            });
        }

        initPartnerSelect('#partner_nik');
        initPartnerSelect('#change_partner_nik');

        // Ensure a partner is selected before submitting the setup form.
        $('#formSetupPartner').on('submit', function(e) {
            if (!$('#partner_nik').val()) {
                e.preventDefault();
                toastWarn('Pilih partner terlebih dahulu.');
            }
        });

        // Same guard for the change partner/scale modal.
        $('#formChangeSetup').on('submit', function(e) {
            if (!$('#change_partner_nik').val()) {
                e.preventDefault();
                toastWarn('Pilih partner terlebih dahulu.');
            }
        });

        // ─────────────────────────────────────────────────────────
        // QR ITEM LOOKUP
        // ─────────────────────────────────────────────────────────
        function lookupItem() {
            const code = $('#trackingCodeInput').val().trim();
            if (!code) {
                toastWarn('Masukkan kode tracking terlebih dahulu.');
                return;
            }
            $.ajax({
                url: ROUTE_LOOKUP_ITEM,
                method: 'POST',
                data: {
                    tracking_code: code,
                    _token: CSRF_TOKEN
                },
                success(resp) {
                    $('#itemPreviewWrapper').removeClass('d-none');
                    $('#formPlaceholder').addClass('d-none');

                    if (!resp.found) {
                        currentItem = null;
                        currentSession = null;
                        currentPrintData = null;
                        $('#itemPreviewWrapper').addClass('d-none');
                        clearWeighForm();
                        $('#historyWrapper').addClass('d-none');
                        $('#recountBanner').addClass('d-none');
                        toastError(resp.message);
                        return;
                    }

                    const item = resp.item;
                    currentItem = item;

                    // Populate preview
                    $('#previewStep').text((item.current_step || '').replace(/_/g, ' '));
                    $('#previewCodePart').text(item.code_part);
                    $('#previewRackNo').text(item.rack_no);
                    $('#previewNamePart').text(item.name_part);
                    $('#previewArea').text([item.area, item.cell].filter(Boolean).join(' / ') || '-');
                    $('#previewCourierAt').text(item.courier_at);
                    $('#itemPreviewCard').addClass('found-ok');

                    if (!resp.allowed) {
                        currentSession = null;
                        currentPrintData = null;
                        $('#recountBanner').addClass('d-none');
                        showDenial(resp.message);
                        clearWeighForm();
                    } else {
                        $('#denialBanner').addClass('d-none');

                        // Pemberitahuan saja: item sudah pernah dihitung,
                        // tetapi hitung ulang tetap diperbolehkan.
                        if (resp.warning) {
                            $('#recountBanner').removeClass('d-none');
                            $('#recountMessage').text(resp.warning);
                            toastWarn(resp.warning);
                        } else {
                            $('#recountBanner').addClass('d-none');
                        }

                        $('#soItemId').val(item.id);

                        currentSession = resp.session || null;
                        currentPrintData = resp.printData || null;

                        if (resp.needs_final_flow || (currentSession && (currentSession.status === 'COMPLETED' || currentSession.status === 'PRINTED' || currentSession.status === 'FINAL_WEIGHED'))) {
                            // Skenario Cetak Ulang / Melanjutkan cetak QR dengan scan QR kurir
                            showOKResult({
                                errorMargin: currentSession ? currentSession.final_margin : 0,
                                resultCount: resp.final_count || (currentSession ? currentSession.final_count : 0),
                                printData: currentPrintData,
                                isReprint: resp.is_reprint === true || (currentSession && currentSession.status === 'COMPLETED'),
                                canRecount: !!resp.can_recount,
                            });
                        } else if (currentSession && currentSession.status === 'DRAFT') {
                            if (!currentSession.sample_qty) {
                                // Belum input jumlah sample → mulai dari awal.
                                showWeighForm();
                            } else if (Number(currentSession.round_count || 0) > 0) {
                                // Sudah ada hitungan tanpa sample → langsung ke final.
                                showWeighForm();
                                showFinalStage();
                            } else {
                                // Jumlah sample sudah ada, belum ada hitungan.
                                showWeighForm();
                                showRoundStage();
                            }
                        } else {
                            showWeighForm();
                        }
                    }

                    // Load history (ditampilkan di bagian bawah halaman)
                    $('#historyWrapper').removeClass('d-none');
                    loadHistory(item.id);
                },
                error(xhr) {
                    const msg = xhr.responseJSON?.message || 'Terjadi kesalahan server.';
                    toastError(msg);
                }
            });
        }

        $('#btnLookupItem').on('click', lookupItem);
        $('#trackingCodeInput').on('keydown', function(e) {
            if (e.key === 'Enter') lookupItem();
        });

        function showDenial(msg) {
            $('#denialBanner').removeClass('d-none');
            $('#denialMessage').text(msg);
            $('#weighFormWrapper').addClass('d-none');
            $('#formPlaceholder').addClass('d-none');
        }

        function clearWeighForm() {
            $('#weighFormWrapper').addClass('d-none');
            $('#formPlaceholder').addClass('d-none');
        }

        function showWeighForm() {
            $('#weighFormWrapper').removeClass('d-none');
            $('#resultPanel').addClass('d-none').html('');
            resetLivePreview();
            showSampleQtyStage();
        }

        function setStepper(step) {
            $('.scale-stepper .step-item').each(function() {
                const n = parseInt($(this).data('step'), 10);
                $(this).toggleClass('active', n === step);
                $(this).toggleClass('done', n < step);
            });
        }

        function refreshSessionSummary() {
            const rounds = Number((currentSession && currentSession.round_count) || 0);
            const sampleQty = currentSession && currentSession.sample_qty !== null && currentSession.sample_qty !== undefined ?
                currentSession.sample_qty : '-';
            const totalWithout = currentSession && currentSession.total_without_sample !== null && currentSession.total_without_sample !== undefined ?
                currentSession.total_without_sample : 0;

            $('#roundSampleQty, #finalSampleQty').text(sampleQty);
            $('#roundTotalWithout, #finalTotalWithout').text(Number(totalWithout).toLocaleString('id-ID'));
            $('#finalRoundCount').text(rounds);
        }

        function showSampleQtyStage() {
            $('#formStageTitle').text('Jumlah Sample');
            $('#sampleQtyStage').removeClass('d-none');
            $('#roundStage').addClass('d-none');
            $('#finalStage').addClass('d-none');
            $('#roundBadge').text('Sample');
            const sampleQty = currentSession && currentSession.sample_qty ? currentSession.sample_qty : '';
            $('#sampleQtyInput').val(sampleQty);
            setStepper(1);
        }

        function showRoundStage() {
            $('#formStageTitle').text('Hitungan Tanpa Sample');
            $('#sampleQtyStage').addClass('d-none');
            $('#roundStage').removeClass('d-none');
            $('#finalStage').addClass('d-none');

            const rounds = Number((currentSession && currentSession.round_count) || 0);
            const nextRound = rounds + 1;
            $('#roundBadge').text('Hitungan ' + nextRound).removeClass('d-none');
            $('#btnNextRound').html('<i class="fas fa-redo me-1"></i>Simpan &amp; Tambah Hitungan ke-' + (nextRound + 1));
            $('#btnResumeFinal').toggleClass('d-none', rounds < 1);
            refreshSessionSummary();
            setStepper(2);
        }

        function showFinalStage() {
            $('#formStageTitle').text('Timbang Final');
            $('#sampleQtyStage').addClass('d-none');
            $('#roundStage').addClass('d-none');
            $('#finalStage').removeClass('d-none');
            $('#roundBadge').text('Final');

            const rounds = Number((currentSession && currentSession.round_count) || 0);
            if (rounds > 0) {
                $('#finalResumeRounds').text(rounds);
                $('#finalResumeInfo').removeClass('d-none');
            } else {
                $('#finalResumeInfo').addClass('d-none');
            }

            refreshSessionSummary();
            refreshFinalPreview();
            setStepper(3);
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function bindPhotoPreview(inputId, wrapId, imgId) {
            $('#' + inputId).on('change', function() {
                const file = this.files && this.files[0];
                if (!file) {
                    $('#' + wrapId).addClass('d-none');
                    return;
                }
                const url = URL.createObjectURL(file);
                $('#' + imgId).attr('src', url);
                $('#' + wrapId).removeClass('d-none');
            });
        }

        bindPhotoPreview('samplePhoto', 'samplePhotoPreviewWrap', 'samplePhotoPreview');
        bindPhotoPreview('finalPhoto', 'finalPhotoPreviewWrap', 'finalPhotoPreview');

        // ─────────────────────────────────────────────────────────
        // LIVE PREVIEW — Margin of Error (total dengan sample)
        // ─────────────────────────────────────────────────────────
        const liveElems = {
            wWithInput: document.getElementById('weightWithSample'),
            previewBox: document.getElementById('livePreviewBox'),
        };

        function resetLivePreview() {
            if (liveElems.previewBox) {
                liveElems.previewBox.className = 'mb-3';
                liveElems.previewBox.innerHTML = '<span class="text-muted"><i class="fas fa-calculator me-2"></i>Masukkan total dengan sample untuk melihat margin.</span>';
            }
            ['sampleWeight', 'weightWithSample', 'sampleQtyInput'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            ['samplePhoto', 'finalPhoto'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            $('#samplePhotoPreviewWrap, #finalPhotoPreviewWrap').addClass('d-none');
            $('#samplePhotoPreview, #finalPhotoPreview').attr('src', '');
        }

        function refreshFinalPreview() {
            const box = liveElems.previewBox;
            if (!box) return;

            box.classList.remove('status-ok', 'status-ng');

            const totalWithout = Number((currentSession && currentSession.total_without_sample) || 0);
            const qty = Number((currentSession && currentSession.sample_qty) || 0);

            if (totalWithout <= 0 || qty <= 0) {
                box.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Belum ada hitungan tanpa sample / jumlah sample.</span>';
                return;
            }

            let result;
            try {
                result = ScaleCalc.calculate(totalWithout, liveElems.wWithInput.value, qty);
            } catch (err) {
                console.error('ScaleCalc error:', err);
                box.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Kalkulator gagal dimuat (scale-calc.js).</span>';
                return;
            }

            if (!result.valid) {
                box.innerHTML = `<span class="text-muted"><i class="fas fa-calculator me-2"></i>${result.error}</span>`;
                return;
            }

            box.classList.add(result.status === 'OK' ? 'status-ok' : 'status-ng');
            box.innerHTML = `
                <div class="row g-2 align-items-center w-100">
                    <div class="col-4 text-center">
                        <div class="text-muted small mb-1">Target Total</div>
                        <div class="fw-bold">${Number(result.target).toLocaleString('id-ID')} pcs</div>
                        <div class="text-muted" style="font-size:0.7rem;">selisih ${result.selisih} pcs</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-muted small mb-1">Total QTY (Final)</div>
                        <div class="fw-bold fs-3 text-primary">${Number(result.estimatedCount).toLocaleString('id-ID')}</div>
                        <div class="text-muted" style="font-size:0.7rem;">pcs (dengan sample)</div>
                    </div>
                    <div class="col-4 text-center">
                        ${ScaleCalc.renderStatusBadge(result.status, result.errorMarginPercent)}
                    </div>
                </div>
            `;
        }

        if (liveElems.wWithInput) {
            liveElems.wWithInput.addEventListener('input', refreshFinalPreview);
        }

        // ─────────────────────────────────────────────────────────
        // STAGE 1 — SIMPAN JUMLAH SAMPLE
        // ─────────────────────────────────────────────────────────
        $('#btnSaveSampleQty').on('click', function() {
            if (!currentItem) {
                toastWarn('Tidak ada item yang dipilih.');
                return;
            }
            if (isSubmitting) return;

            const qty = $('#sampleQtyInput').val();
            if (!qty || parseInt(qty, 10) < 1) {
                toastError('Isi jumlah sample minimal 1 pcs.');
                return;
            }

            isSubmitting = true;
            const btn = $(this);
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Menyimpan...');

            $.ajax({
                url: ROUTE_SAMPLE_QTY,
                method: 'POST',
                data: {
                    _token: CSRF_TOKEN,
                    so_item_id: $('#soItemId').val(),
                    sample_qty: qty
                },
                success(resp) {
                    if (!resp.success) {
                        toastError(resp.message || 'Gagal menyimpan jumlah sample.');
                        return;
                    }

                    currentSession = resp.session;
                    if (currentItem) {
                        currentItem.current_step = 'READY_TO_COUNT';
                        $('#previewStep').text('READY TO COUNT');
                    }
                    showRoundStage();
                },
                error(xhr) {
                    toastError(xhr.responseJSON?.message || 'Gagal menyimpan jumlah sample.');
                },
                complete() {
                    btn.prop('disabled', false).html(originalHtml);
                    isSubmitting = false;
                }
            });
        });

        // ─────────────────────────────────────────────────────────
        // STAGE 2 — HITUNGAN TANPA SAMPLE (bisa diulang)
        // ─────────────────────────────────────────────────────────
        function clearRoundInputs() {
            $('#sampleWeight').val('');
            $('#samplePhoto').val('');
            $('#samplePhotoPreviewWrap').addClass('d-none');
            $('#samplePhotoPreview').attr('src', '');
        }

        function submitRound(goFinal) {
            if (!currentItem) {
                toastWarn('Tidak ada item yang dipilih.');
                return;
            }
            if (isSubmitting) return;

            const weight = $('#sampleWeight').val();
            const photo = document.getElementById('samplePhoto');

            if (!weight || parseFloat(weight) <= 0) {
                toastError('Isi total tanpa sample terlebih dahulu.');
                return;
            }
            if (!Number.isInteger(Number(weight))) {
                toastError('Total tanpa sample harus bilangan bulat (pcs).');
                return;
            }
            if (!photo || !photo.files || !photo.files[0]) {
                toastError('Foto hitungan wajib diambil.');
                return;
            }

            isSubmitting = true;

            const btn = goFinal ? $('#btnGoFinal') : $('#btnNextRound');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Menyimpan...');

            const fd = new FormData();
            fd.append('_token', CSRF_TOKEN);
            fd.append('so_item_id', $('#soItemId').val());
            fd.append('weight_no_sample', weight);
            fd.append('photo', photo.files[0]);

            $.ajax({
                url: ROUTE_SAMPLE_ROUND,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success(resp) {
                    if (!resp.success) {
                        toastError(resp.message || 'Gagal menyimpan hitungan.');
                        return;
                    }

                    currentSession = resp.session;
                    loadHistory(currentItem.id);
                    clearRoundInputs();

                    if (goFinal) {
                        showFinalStage();
                    } else {
                        showRoundStage();
                        toastWarn('Hitungan ke-' + resp.round.round_no + ' tersimpan. Tambah hitungan berikutnya bila perlu.');
                    }
                },
                error(xhr) {
                    toastError(xhr.responseJSON?.message || 'Gagal menyimpan hitungan.');
                },
                complete() {
                    btn.prop('disabled', false).html(originalHtml);
                    isSubmitting = false;
                }
            });
        }

        $('#btnNextRound').on('click', function() {
            submitRound(false);
        });
        $('#btnGoFinal').on('click', function() {
            submitRound(true);
        });
        $('#btnBackToRounds').on('click', function() {
            showRoundStage();
        });
        $('#btnResumeFinal').on('click', function() {
            showFinalStage();
        });

        // ─────────────────────────────────────────────────────────
        // SUBMIT FINAL WEIGHING
        // ─────────────────────────────────────────────────────────
        $('#btnSubmitWeigh').on('click', function() {
            if (!currentItem) {
                toastWarn('Tidak ada item yang dipilih.');
                return;
            }
            if (isSubmitting) return;

            const withSample = $('#weightWithSample').val();
            if (!withSample || parseFloat(withSample) <= 0) {
                toastError('Isi total dengan sample terlebih dahulu.');
                return;
            }
            if (!Number.isInteger(Number(withSample))) {
                toastError('Total dengan sample harus bilangan bulat (pcs).');
                return;
            }

            const totalWithout = Number((currentSession && currentSession.total_without_sample) || 0);
            if (totalWithout <= 0) {
                toastError('Belum ada hitungan tanpa sample. Simpan hitungan terlebih dahulu.');
                return;
            }

            const photo = document.getElementById('finalPhoto');
            if (!photo || !photo.files || !photo.files[0]) {
                toastError('Foto timbang final wajib diambil.');
                return;
            }

            isSubmitting = true;
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Menghitung...');

            const fd = new FormData();
            fd.append('_token', CSRF_TOKEN);
            fd.append('so_item_id', $('#soItemId').val());
            fd.append('weight_with_sample', withSample);
            fd.append('photo', photo.files[0]);

            $.ajax({
                url: ROUTE_SUBMIT_FINAL,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success(resp) {
                    currentSession = resp.session || currentSession;

                    if (resp.success) {
                        currentPrintData = resp.printData || null;
                        showOKResult(resp);
                    } else {
                        showNGResult(resp);
                    }

                    if (currentItem) loadHistory(currentItem.id);
                },
                error(xhr) {
                    toastError(xhr.responseJSON?.message || 'Terjadi kesalahan server.');
                },
                complete() {
                    btn.prop('disabled', false).html('<i class="fas fa-calculator me-2"></i>Hitung &amp; Cek Margin');
                    isSubmitting = false;
                }
            });
        });

        function showOKResult(resp) {
            $('#weighFormWrapper').addClass('d-none');
            setStepper(4);

            const pd = resp.printData || currentPrintData || {};
            const margin = (resp.errorMargin !== null && resp.errorMargin !== undefined) ?
                resp.errorMargin :
                ((currentSession && currentSession.final_margin) || '-');
            const qty = (resp.resultCount !== null && resp.resultCount !== undefined) ?
                resp.resultCount :
                ((currentSession && currentSession.final_count) || 0);
            const totalWith = Number(pd.total_with_sample ?? (currentSession && currentSession.total_with_sample) ?? 0);
            const totalWithout = Number(pd.total_without_sample ?? (currentSession && currentSession.total_without_sample) ?? 0);
            const sampleQty = pd.sample_qty ?? ((currentSession && currentSession.sample_qty) ?? '-');
            const roundCount = pd.round_count ?? ((currentSession && currentSession.round_count) ?? 0);

            // Detail per hitungan tanpa sample
            const roundDetails = pd.round_details || [];
            let roundDetailHtml = '';
            if (roundDetails.length > 0) {
                roundDetailHtml = `
                    <div class="col-12">
                        <div class="text-muted small mb-1"><i class="fas fa-list-ol me-1"></i>Detail Hitungan (Tanpa Sample)</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-1" style="font-size:0.82rem;">
                                <thead class="table-light">
                                    <tr><th>Hitungan</th><th>Jumlah (pcs)</th></tr>
                                </thead>
                                <tbody>
                                    ${roundDetails.map(rd => `<tr><td>Hitungan ${rd.round_no}</td><td class="fw-bold">${Number(rd.weight).toLocaleString('id-ID', {maximumFractionDigits:4})} pcs</td></tr>`).join('')}
                                    <tr class="table-warning"><td class="fw-bold">Total Tanpa Sample</td><td class="fw-bold text-primary">${totalWithout.toLocaleString('id-ID', {maximumFractionDigits:4})} pcs</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>`;
            }

            const isReprint = !!resp.isReprint;
            const canRecount = !!resp.canRecount;
            const subtitleText = isReprint 
                ? '<span class="badge bg-info text-dark mb-2"><i class="fas fa-redo me-1"></i> Mode Cetak Ulang QR Label</span>' 
                : `<p class="text-muted small mb-3">Margin Error: <strong>${margin}%</strong> — Status: <span class="badge bg-success">OK</span></p>`;

            const alertInstruction = isReprint
                ? `<div class="alert alert-info small text-start mb-3">
                        <i class="fas fa-info-circle me-1"></i> Part ini sudah berstatus selesai dihitung (<strong>${currentItem.current_step}</strong>). Anda dapat mencetak ulang stiker label final kapan saja.
                   </div>`
                : `<div class="alert alert-info small text-start mb-3">
                        <i class="fas fa-print me-1"></i> <strong>Langkah 1:</strong> cetak QR terlebih dahulu. Setelah tercetak, ambil foto bukti, lalu simpan.
                   </div>`;

            // Seperti Pos 1 & Pos 2: selama item belum RETURNED, operator tetap
            // boleh menghitung ulang di timbangan atau hanya mencetak ulang QR.
            const recountHtml = canRecount
                ? `<div class="alert alert-info small text-start mb-3">
                        <i class="fas fa-info-circle me-1"></i> Item <strong>belum dikembalikan ke rak</strong>. Anda tetap bisa <strong>menghitung ulang</strong> di timbangan; hasil sebelumnya tetap tersimpan sebagai riwayat.
                   </div>
                   <button class="btn btn-outline-success w-100 mt-2" id="btnRecountScale">
                        <i class="fas fa-redo me-1"></i> Hitung Ulang di Timbangan
                   </button>`
                : '';

            const btnText = isReprint ? 'Cetak Ulang Label QR Final' : '1. Cetak Label QR Final';

            $('#resultPanel').removeClass('d-none').html(`
            <div class="card shadow-sm" style="border-radius:14px; border:2px solid #28a745; overflow:hidden;">
                <div class="card-body p-3 text-center">
                    <div style="font-size:2.5rem; color:#28a745;" class="mb-1"><i class="fas fa-check-circle"></i></div>
                    <h5 class="fw-bold text-success mb-1">${isReprint ? 'Cetak Ulang Label QR Final' : 'Hasil Final OK — Siap Cetak QR'}</h5>
                    ${subtitleText}
                    <div class="row g-2 text-start mb-3">
                        <div class="col-6"><div class="text-muted small">Total QTY (dengan sample)</div><div class="fw-bold fs-4 text-primary">${Number(qty).toLocaleString('id-ID')} pcs</div></div>
                        <div class="col-6"><div class="text-muted small">Timbangan</div><div class="fw-bold">${pd.scale_number || '-'}</div></div>
                        <div class="col-6"><div class="text-muted small">Total Dengan Sample</div><div class="fw-bold">${totalWith.toLocaleString('id-ID', {maximumFractionDigits:4})} pcs</div></div>
                        <div class="col-6"><div class="text-muted small">Total Tanpa Sample</div><div class="fw-bold">${totalWithout.toLocaleString('id-ID', {maximumFractionDigits:4})} pcs</div></div>
                        <div class="col-6"><div class="text-muted small">Jumlah Sample</div><div class="fw-bold">${Number(sampleQty).toLocaleString('id-ID')} pcs (${roundCount} hitungan)</div></div>
                        <div class="col-6"><div class="text-muted small">Operator</div><div class="fw-bold">${pd.user_nik || '-'} — ${pd.user_name || '-'}</div></div>
                        <div class="col-12"><div class="text-muted small">Partner</div><div class="fw-bold">${pd.partner_nik || '-'} — ${pd.partner_name || '-'}</div></div>
                        <div class="col-12"><div class="text-muted small">Kode Part</div><div class="fw-bold">${pd.code_part || '-'} — ${pd.name_part || '-'}</div></div>
                        ${roundDetailHtml}
                    </div>
                    ${alertInstruction}
                    <button class="btn btn-primary w-100" id="btnPrintFinal" data-reprint="${isReprint ? '1' : '0'}">
                        <i class="fas fa-print me-1"></i> ${btnText}
                    </button>
                    ${recountHtml}
                    ${isReprint ? `
                    <button class="btn btn-outline-secondary w-100 mt-2 btn-reset-scale">
                        <i class="fas fa-arrow-left me-1"></i> Scan Part Lainnya
                    </button>` : ''}
                </div>
            </div>
        `);
        }

        function showNGResult(resp) {
            setStepper(3);
            $('#resultPanel').removeClass('d-none').html(`
            <div class="ng-denial-banner">
                <div style="font-size:2.2rem;" class="mb-2"><i class="fas fa-times-circle"></i></div>
                <h6 class="fw-bold mb-1">NG — Margin Error ≥ 2%</h6>
                <p class="mb-0 small">${resp.message}</p>
                <div class="mt-2">
                    <span class="badge bg-light text-dark">Margin: ${resp.errorMargin}%</span>
                    <span class="badge bg-light text-dark ms-2">Qty Hasil: ${resp.resultCount} pcs</span>
                </div>
            </div>
            <div class="mt-2 text-center">
                <p class="text-muted small mb-2">Hitungan final ini tersimpan sebagai riwayat. Lakukan hitung ulang.</p>
                <button class="btn btn-sm btn-outline-secondary" id="btnRetryWeigh">
                    <i class="fas fa-redo me-1"></i> Hitung Ulang
                </button>
            </div>
        `);
            $('#btnSubmitWeigh').prop('disabled', false)
                .html('<i class="fas fa-calculator me-2"></i>Hitung &amp; Cek Margin');
        }

        // ─────────────────────────────────────────────────────────
        // EVENT DELEGATION (dynamic buttons)
        // ─────────────────────────────────────────────────────────
        $(document).on('click', '#btnRetryWeigh', function() {
            $('#resultPanel').addClass('d-none').html('');
            $('#weighFormWrapper').removeClass('d-none');
            $('#weightWithSample').val('');
            $('#finalPhoto').val('');
            $('#finalPhotoPreviewWrap').addClass('d-none');
            $('#finalPhotoPreview').attr('src', '');
            showFinalStage();
        });

        // ─────────────────────────────────────────────────────────
        // HITUNG ULANG SETELAH CETAK / SELESAI (selama belum RETURNED)
        // Sesi lama tidak dihapus; hasilnya tetap ada di riwayat.
        // ─────────────────────────────────────────────────────────
        $(document).on('click', '#btnRecountScale', function() {
            const startRecount = () => {
                currentSession = null;
                currentPrintData = null;
                clearRoundInputs();
                $('#weightWithSample').val('');
                resetLivePreview();
                showWeighForm();
                $('#soItemId').val(currentItem ? currentItem.id : $('#soItemId').val());
                toastWarn('Mode hitung ulang. Masukkan jumlah sample baru; hasil sebelumnya tetap tersimpan.');
            };

            if (window.Swal && typeof window.Swal.fire === 'function') {
                Swal.fire({
                    icon: 'question',
                    title: 'Hitung ulang di timbangan?',
                    text: 'Hasil penimbangan sebelumnya tetap tersimpan sebagai riwayat. Setelah selesai, cetak label QR baru.',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hitung ulang',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) startRecount();
                });
            } else if (confirm('Hitung ulang di timbangan? Hasil sebelumnya tetap tersimpan.')) {
                startRecount();
            }
        });

        $(document).on('click', '#btnPrintFinal', async function() {
            const pd = currentPrintData || {};
            const btn = $(this);
            const isReprint = btn.data('reprint') === '1' || (currentSession && currentSession.status === 'COMPLETED');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Mencetak...');

            try {
                if (!window.thermalPrinter.isConnected) {
                    // Auto request connection if not connected
                    await window.thermalPrinter.connect();
                }

                await window.thermalPrinter.printFinalLabel({
                    trackingCode: pd.tracking_code,
                    codePart: pd.code_part,
                    namePart: pd.name_part,
                    rackNo: pd.rack_no,
                    loc: pd.loc,
                    qty: pd.final_count,
                    totalWithSample: pd.total_with_sample,
                    totalWithoutSample: pd.total_without_sample,
                    sampleQty: pd.sample_qty,
                    roundCount: pd.round_count,
                    roundDetails: pd.round_details || [],
                    method: 'TIMBANGAN',
                    user: pd.user_name || pd.user_nik,
                    partner: pd.partner_name || pd.partner_nik,
                    scaleNo: pd.scale_number,
                    time: pd.completed_at
                });

                if (isReprint) {
                    // Mode cetak ulang: tidak perlu upload bukti foto ulang, langsung selesai
                    btn.prop('disabled', false).html('<i class="fas fa-print me-1"></i> Cetak Ulang Label QR Final');
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Label QR Final berhasil dicetak ulang!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else if (typeof window.swal === 'function') {
                        swal({
                            title: 'Berhasil',
                            text: 'Label QR Final berhasil dicetak ulang!',
                            type: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    // Cetak pertama kali → tandai PRINTED & lanjutkan ke panel foto bukti
                    markPrintedThenProof(btn);
                }
            } catch (err) {
                console.error('Print Error:', err);
                toastError('Gagal mencetak: ' + (err.message || 'Periksa koneksi Bluetooth printer.'));
                btn.prop('disabled', false).html(isReprint ? '<i class="fas fa-print me-1"></i> Cetak Ulang Label QR Final' : '<i class="fas fa-print me-1"></i> 1. Cetak Label QR Final');
            }
        });

        function markPrintedThenProof(btn) {
            $.ajax({
                url: ROUTE_MARK_PRINTED,
                method: 'POST',
                data: {
                    _token: CSRF_TOKEN,
                    so_item_id: (currentItem && currentItem.id) || $('#soItemId').val()
                },
                success(resp) {
                    if (!resp.success) {
                        onMarkPrintedFailed(btn, resp.message);
                        return;
                    }

                    if (currentSession) currentSession.status = 'PRINTED';
                    $('#btnMarkPrintedRetry').remove();
                    showProofPanel();

                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Label QR Final berhasil dicetak!',
                            timer: 1400,
                            showConfirmButton: false
                        });
                    } else if (typeof window.swal === 'function') {
                        swal({
                            title: 'Berhasil',
                            text: 'Label QR Final berhasil dicetak!',
                            type: 'success',
                            timer: 1400,
                            showConfirmButton: false
                        });
                    }
                },
                error(xhr) {
                    onMarkPrintedFailed(
                        btn,
                        xhr.responseJSON?.message || 'Gagal menyimpan status cetak ke server.'
                    );
                }
            });
        }

        function onMarkPrintedFailed(btn, message) {
            if (btn && btn.length) {
                if (btn.attr('id') === 'btnMarkPrintedRetry') {
                    btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Tandai Sudah Dicetak');
                } else {
                    btn.prop('disabled', false).html('<i class="fas fa-print me-1"></i> Cetak Label QR Final');
                }
            }

            toastError(message + ' Jika label sudah tercetak, tekan "Tandai Sudah Dicetak" agar tidak mencetak ulang.');

            if (!$('#btnMarkPrintedRetry').length) {
                $('#resultPanel').append(`
                <button class="btn btn-outline-success w-100 mt-2" id="btnMarkPrintedRetry">
                    <i class="fas fa-check-circle me-1"></i> Tandai Sudah Dicetak
                </button>
            `);
            }
        }

        $(document).on('click', '#btnMarkPrintedRetry', function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');
            markPrintedThenProof(btn);
        });

        function showProofPanel() {
            $('#weighFormWrapper').addClass('d-none');
            setStepper(5);

            $('#resultPanel').removeClass('d-none').html(`
            <div class="card shadow-sm" style="border-radius:14px; border:2px solid #0d6efd; overflow:hidden;">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-1"><i class="fas fa-camera text-primary me-2"></i>Langkah 2 — Foto Bukti Setelah Cetak</h6>
                    <p class="text-muted small mb-2">Ambil foto label QR yang sudah dicetak / ditempel pada part. Simpan hanya bisa dilakukan setelah foto diambil.</p>
                    <input type="file" class="form-control form-control-sm" id="labelProofPhoto"
                           accept="image/*" capture="environment">
                    <div class="photo-preview d-none mt-2" id="labelProofPreviewWrap">
                        <img id="labelProofPreview" alt="Preview foto bukti" class="img-fluid rounded">
                    </div>
                    <button class="btn btn-success w-100 mt-3" id="btnSubmitProof" disabled>
                        <i class="fas fa-save me-1"></i> Simpan &amp; Selesai
                    </button>
                </div>
            </div>
        `);

            $('#labelProofPhoto').on('change', function() {
                const file = this.files && this.files[0];
                $('#btnSubmitProof').prop('disabled', !file);

                if (!file) {
                    $('#labelProofPreviewWrap').addClass('d-none');
                    return;
                }

                $('#labelProofPreview').attr('src', URL.createObjectURL(file));
                $('#labelProofPreviewWrap').removeClass('d-none');
            });
        }

        $(document).on('click', '#btnSubmitProof', function() {
            if (isSubmitting) return;

            const input = document.getElementById('labelProofPhoto');
            if (!input || !input.files || !input.files[0]) {
                toastError('Foto bukti wajib diambil.');
                return;
            }

            isSubmitting = true;
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

            const fd = new FormData();
            fd.append('_token', CSRF_TOKEN);
            fd.append('so_item_id', (currentItem && currentItem.id) || $('#soItemId').val());
            fd.append('photo', input.files[0]);

            $.ajax({
                url: ROUTE_LABEL_PROOF,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success(resp) {
                    if (!resp.success) {
                        toastError(resp.message || 'Gagal menyimpan foto bukti.');
                        return;
                    }

                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Tersimpan',
                            text: 'Penimbangan selesai! Part siap dikembalikan. Silakan scan part berikutnya.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => resetAll());
                    } else {
                        alert('Penimbangan selesai! Part siap dikembalikan.');
                        resetAll();
                    }
                },
                error(xhr) {
                    toastError(xhr.responseJSON?.message || 'Gagal menyimpan foto bukti.');
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan &amp; Selesai');
                },
                complete() {
                    isSubmitting = false;
                }
            });
        });

        // ─────────────────────────────────────────────────────────
        // RESET / CANCEL DRAFT
        // ─────────────────────────────────────────────────────────
        $(document).on('click', '.btn-reset-scale', function() {
            requestReset();
        });

        function requestReset() {
            const hasDraft = currentSession && currentSession.status === 'DRAFT';

            if (!hasDraft) {
                resetAll();
                return;
            }

            if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                if (confirm('Batalkan draft penimbangan item ini?')) {
                    $.post(ROUTE_CANCEL_SESSION, {
                            _token: CSRF_TOKEN,
                            so_item_id: currentSession.so_item_id
                        })
                        .always(resetAll);
                } else {
                    resetAll();
                }
                return;
            }

            Swal.fire({
                icon: 'warning',
                title: 'Batalkan draft?',
                text: 'Hitungan yang sudah diinput akan dihapus. Pilih "Tidak" untuk menyimpan draft dan melanjutkan nanti.',
                showCancelButton: true,
                confirmButtonText: 'Ya, batalkan',
                cancelButtonText: 'Tidak, simpan draft',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(ROUTE_CANCEL_SESSION, {
                            _token: CSRF_TOKEN,
                            so_item_id: currentSession.so_item_id
                        })
                        .always(resetAll);
                } else {
                    resetAll();
                }
            });
        }

        function resetAll() {
            currentItem = null;
            currentSession = null;
            currentPrintData = null;
            isSubmitting = false;
            $('#trackingCodeInput').val('').focus();
            $('#itemPreviewWrapper').addClass('d-none');
            $('#historyWrapper').addClass('d-none');
            $('#itemPreviewCard').removeClass('found-ok');
            $('#weighFormWrapper').addClass('d-none');
            $('#resultPanel').addClass('d-none').html('');
            $('#formPlaceholder').removeClass('d-none');
            $('#denialBanner').addClass('d-none');
            $('#recountBanner').addClass('d-none');
            setStepper(1);
            resetLivePreview();
            $('#historyTableBody').html('<tr><td colspan="7" class="text-center text-muted py-3">—</td></tr>');
        }

        // ─────────────────────────────────────────────────────────
        // LOAD HISTORY
        // ─────────────────────────────────────────────────────────
        function loadHistory(soItemId) {
            $.ajax({
                url: ROUTE_ITEM_HISTORY,
                method: 'GET',
                data: {
                    so_item_id: soItemId
                },
                success(resp) {
                    const histories = resp.histories;
                    if (!histories || histories.length === 0) {
                        $('#historyTableBody').html('<tr><td colspan="7" class="text-center text-muted py-3">Belum ada riwayat.</td></tr>');
                        return;
                    }
                    let rows = '';
                    histories.forEach(h => {
                        const badge = h.status === 'OK' ?
                            '<span class="badge bg-success">OK</span>' :
                            (h.status === 'NG' ?
                                '<span class="badge bg-danger">NG</span>' :
                                '<span class="badge bg-secondary">DRAFT</span>');

                        let stage;
                        if (h.stage === 'SAMPLE') {
                            stage = `<span class="badge bg-info text-dark">Hitungan ${h.round_no}</span>`;
                        } else if (h.stage === 'FINAL') {
                            stage = `<span class="badge bg-primary">Final ${h.round_no}</span>`;
                        } else {
                            stage = '<span class="badge bg-dark">Final</span>';
                        }

                        const fmt = v => (v === null || v === undefined) ?
                            '—' :
                            Number(v).toLocaleString('id-ID', {
                                maximumFractionDigits: 4
                            });
                        const fmtPcs = v => (v === null || v === undefined) ? '—' : fmt(v) + ' pcs';

                        let jumlah;
                        if (h.stage === 'SAMPLE') {
                            jumlah = fmtPcs(h.weight_no);
                        } else {
                            jumlah = `+${fmtPcs(h.weight_with)}<div class="text-muted" style="font-size:0.7rem;">tanpa: ${fmtPcs(h.weight_no)}</div>`;
                        }

                        const margin = (h.error_margin === null || h.error_margin === undefined) ?
                            '—' :
                            h.error_margin + '%';

                        const qty = (h.result_count === null || h.result_count === undefined) ?
                            '—' :
                            Number(h.result_count).toLocaleString('id-ID');

                        rows += `<tr>
                        <td>${h.created_at}</td>
                        <td>${stage}</td>
                        <td>${jumlah}</td>
                        <td>${h.member_nik || '-'}</td>
                        <td>${margin}</td>
                        <td>${qty}</td>
                        <td>${badge}</td>
                    </tr>`;
                    });
                    $('#historyTableBody').html(rows);
                }
            });
        }

        // ─────────────────────────────────────────────────────────
        // TOAST HELPERS
        // ─────────────────────────────────────────────────────────
        function toastWarn(msg) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: msg,
                    timer: 2500,
                    showConfirmButton: false
                });
            } else if (typeof window.swal === 'function') {
                swal({
                    title: 'Perhatian',
                    text: msg,
                    type: 'warning',
                    timer: 2500,
                    showConfirmButton: false
                });
            } else {
                alert(msg);
            }
        }

        function toastError(msg) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: msg
                });
            } else if (typeof window.swal === 'function') {
                swal({
                    title: 'Gagal',
                    text: msg,
                    type: 'error'
                });
            } else {
                alert(msg);
            }
        }

    });
</script>
@endsection