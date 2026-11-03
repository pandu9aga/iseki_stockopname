@extends('layouts.main')

@section('style')
<style>
/* ── Scale Module — Fase 2B ── */

/* Partner / Scale Active Bar */
.scale-active-bar {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-radius: 16px;
    padding: 16px 20px;
    color: #fff;
    margin-bottom: 20px;
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}
.scale-active-bar .partner-avatar {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; font-weight: 700; color: #fff;
    flex-shrink: 0;
}
.scale-active-bar .scale-avatar {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f093fb, #f5576c);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: #fff;
    flex-shrink: 0;
}
.info-chip {
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 0.78rem;
    color: rgba(255,255,255,0.7);
}
.info-chip strong { color: #fff; }

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
.qr-scan-box:hover, .qr-scan-box.focused {
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
.item-preview-card.found-ok { border-color: #0d6efd; }
.item-preview-card .item-header {
    background: linear-gradient(135deg, #0d6efd, #6610f2);
    color: #fff;
    padding: 12px 16px;
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
.weigh-input-group label { font-weight: 600; font-size: 0.87rem; }
.weigh-input-group .input-group-text {
    background: #f8f9fa;
    border-color: #dee2e6;
    font-size: 0.85rem;
    color: #6c757d;
}

/* Live Preview Box */
#livePreviewBox {
    border-radius: 12px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    padding: 16px;
    min-height: 64px;
    display: flex; align-items: center;
    transition: background 0.3s;
}
#livePreviewBox.status-ok  { background: #d1e7dd; border-color: #badbcc; }
#livePreviewBox.status-ng  { background: #f8d7da; border-color: #f5c2c7; }

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
    0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.5); }
    50%       { box-shadow: 0 0 0 12px rgba(220, 53, 69, 0); }
}

/* History Table */
.history-table td, .history-table th { vertical-align: middle; font-size: 0.83rem; }

/* Setup Partner Modal */
.setup-modal-header {
    background: linear-gradient(135deg, #0f3460, #533483);
    color: #fff;
}

/* Responsive tweaks */
@media (max-width: 576px) {
    .scale-active-bar { flex-direction: column; gap: 12px; }
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
                            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
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

        {{-- Active Bar: Partner & Scale Info --}}
        <div class="scale-active-bar d-flex align-items-center justify-content-between flex-wrap gap-3" id="activeBar">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                {{-- Partner --}}
                <div class="d-flex align-items-center gap-2">
                    <div class="partner-avatar">
                        {{ strtoupper(substr($partner->nama ?? $session->scale_partner_nik, 0, 1)) }}
                    </div>
                    <div>
                        <div class="info-chip">
                            <i class="fas fa-user me-1 opacity-75"></i>
                            <strong>Partner: {{ $partner->nama ?? $session->scale_partner_nik }}</strong>
                        </div>
                        <div style="font-size:0.7rem; color:rgba(255,255,255,0.5); margin-top:2px;">
                            NIK: {{ $session->scale_partner_nik }}
                        </div>
                    </div>
                </div>

                {{-- Separator --}}
                <div style="width:1px; height:36px; background:rgba(255,255,255,0.15);"></div>

                {{-- Timbangan --}}
                <div class="d-flex align-items-center gap-2">
                    <div class="scale-avatar"><i class="fas fa-balance-scale"></i></div>
                    <div>
                        <div class="info-chip">
                            <i class="fas fa-weight me-1 opacity-75"></i>
                            <strong>Timbangan: {{ $session->scale->scale_number ?? '-' }}</strong>
                        </div>
                        @if($session->scale && $session->scale->scale_name)
                        <div style="font-size:0.7rem; color:rgba(255,255,255,0.5); margin-top:2px;">
                            {{ $session->scale->scale_name }}
                            @if($session->scale->location)— {{ $session->scale->location }}@endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Change Buttons --}}
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#modalChangeSetup"
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

                {{-- QR Scan Input --}}
                <div class="card shadow-sm mb-3" style="border-radius:14px;">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-qrcode text-primary me-2"></i>Scan QR Kurir
                        </h6>
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
                            Scan QR atau ketik tracking code dari label kurir. Tekan Enter atau klik Cek.
                        </div>
                    </div>
                </div>

                {{-- Item Info Card (hidden until item found) --}}
                <div id="itemPreviewWrapper" class="d-none">
                    <div class="item-preview-card mb-3" id="itemPreviewCard">
                        <div class="item-header d-flex justify-content-between align-items-start">
                            <div>
                                <div style="font-size:0.7rem; opacity:0.75; letter-spacing:1px;">TRACKING CODE</div>
                                <div class="fw-bold font-monospace" id="previewTrackingCode">-</div>
                            </div>
                            <span class="item-badge-step bg-white text-primary" id="previewStep">-</span>
                        </div>
                        <div class="p-3">
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
                        </div>
                    </div>

                    {{-- History (collapsed) --}}
                    <div class="card shadow-sm" style="border-radius:14px;">
                        <div class="card-header d-flex justify-content-between align-items-center py-2"
                             style="cursor:pointer; border-radius:14px 14px 0 0;"
                             data-bs-toggle="collapse" data-bs-target="#historyCollapse">
                            <span class="fw-semibold small">
                                <i class="fas fa-history me-2 text-secondary"></i>Riwayat Penimbangan Item Ini
                            </span>
                            <i class="fas fa-chevron-down small text-muted"></i>
                        </div>
                        <div class="collapse" id="historyCollapse">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm history-table mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Waktu</th>
                                                <th>NIK</th>
                                                <th>Margin</th>
                                                <th>Qty</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="historyTableBody">
                                            <tr><td colspan="5" class="text-center text-muted py-3">—</td></tr>
                                        </tbody>
                                    </table>
                                </div>
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
                    <div class="weigh-form-card shadow-sm p-4">

                        <h6 class="fw-bold mb-4">
                            <i class="fas fa-weight text-success me-2"></i>Form Input Timbangan
                        </h6>

                        <input type="hidden" id="soItemId" name="so_item_id" value="">

                        {{-- Berat Tanpa Sample --}}
                        <div class="mb-3 weigh-input-group">
                            <label for="weightNoSample">Berat Tanpa Sample <span class="text-danger">*</span></label>
                            <div class="input-group mt-1">
                                <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                                <input type="number" class="form-control form-control-lg" id="weightNoSample"
                                       name="weight_no_sample" min="0.0001" step="any"
                                       placeholder="0.0000" required>
                                <span class="input-group-text">gram/kg</span>
                            </div>
                            <div class="form-text">Berat seluruh part tanpa sample diambil.</div>
                        </div>

                        {{-- Berat Dengan Sample --}}
                        <div class="mb-3 weigh-input-group">
                            <label for="weightWithSample">Berat Dengan Sample <span class="text-danger">*</span></label>
                            <div class="input-group mt-1">
                                <span class="input-group-text"><i class="fas fa-vials"></i></span>
                                <input type="number" class="form-control form-control-lg" id="weightWithSample"
                                       name="weight_with_sample" min="0.0001" step="any"
                                       placeholder="0.0000" required>
                                <span class="input-group-text">gram/kg</span>
                            </div>
                            <div class="form-text">Berat setelah menambahkan sejumlah sample.</div>
                        </div>

                        {{-- Jumlah Sample --}}
                        <div class="mb-4 weigh-input-group">
                            <label for="sampleQty">Jumlah Sample <span class="text-danger">*</span></label>
                            <div class="input-group mt-1">
                                <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                                <input type="number" class="form-control form-control-lg" id="sampleQty"
                                       name="sample_qty" min="1" step="1"
                                       placeholder="10" required>
                                <span class="input-group-text">pcs</span>
                            </div>
                            <div class="form-text">Jumlah piece yang diambil sebagai sample timbang.</div>
                        </div>

                        {{-- Live Preview --}}
                        <div id="livePreviewBox" class="mb-4">
                            <span class="text-muted"><i class="fas fa-calculator me-2"></i>Isi semua field untuk melihat preview kalkulasi.</span>
                        </div>

                        {{-- Notes --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold small" for="weighNotes">Catatan (opsional)</label>
                            <textarea class="form-control" id="weighNotes" name="notes" rows="2"
                                      placeholder="Catatan jika ada kondisi khusus..."></textarea>
                        </div>

                        {{-- Submit --}}
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success btn-lg fw-bold" id="btnSubmitWeigh" disabled>
                                <i class="fas fa-check-circle me-2"></i>Submit Hasil Timbangan
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetForm">
                                <i class="fas fa-undo me-1"></i>Reset / Scan Item Lain
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Result Panel (shown after submit) --}}
                <div id="resultPanel" class="d-none mt-3">
                    {{-- Filled by JS --}}
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
<script src="{{ asset('assets/js/plugin/select2/select2.full.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/js/scale-calc.js') }}"></script>
<script>
$(document).ready(function () {

    // ── Cached routes ──
    const ROUTE_VALIDATE_PARTNER = "{{ route('scale.validate-partner') }}";
    const ROUTE_LOOKUP_ITEM      = "{{ route('scale.lookup-item') }}";
    const ROUTE_SUBMIT_WEIGH     = "{{ route('scale.submit-weighing') }}";
    const ROUTE_ITEM_HISTORY     = "{{ route('scale.item-history') }}";
    const CSRF_TOKEN             = "{{ csrf_token() }}";

    // ── State ──
    let currentItem = null;

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
                data: function (params) {
                    return {
                        q: params.term || '',
                        page: params.page || 1
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.results || [],
                        pagination: {
                            more: !!(data.pagination && data.pagination.more)
                        }
                    };
                }
            },
            templateResult: function (item) {
                if (item.loading) return item.text;
                if (!item.id) return item.text;

                const nama = item.nama || item.text || '';
                const nik  = item.nik || item.id || '';

                // Escape text to avoid HTML injection from employee data.
                return $('<div class="d-flex justify-content-between align-items-center">')
                    .append($('<span class="fw-semibold"></span>').text(nama))
                    .append($('<span class="text-muted small ms-2"></span>').text(nik));
            },
            templateSelection: function (item) {
                if (!item.id) return item.text;
                // Static preselected option already contains "Nama — NIK".
                if (!item.nama) return item.text;
                return (item.nama || item.id) + ' — ' + (item.nik || item.id);
            },
            language: {
                searching: function () { return 'Mencari partner...'; },
                noResults: function () { return 'Partner tidak ditemukan.'; },
                loadingMore: function () { return 'Memuat lebih banyak...'; },
                errorLoading: function () { return 'Gagal memuat data partner.'; }
            }
        });
    }

    initPartnerSelect('#partner_nik');
    initPartnerSelect('#change_partner_nik');

    // Ensure a partner is selected before submitting the setup form.
    $('#formSetupPartner').on('submit', function (e) {
        if (!$('#partner_nik').val()) {
            e.preventDefault();
            toastWarn('Pilih partner terlebih dahulu.');
        }
    });

    // Same guard for the change partner/scale modal.
    $('#formChangeSetup').on('submit', function (e) {
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
            data: { tracking_code: code, _token: CSRF_TOKEN },
            success(resp) {
                $('#itemPreviewWrapper').removeClass('d-none');
                $('#formPlaceholder').addClass('d-none');

                if (!resp.found) {
                    showDenial(resp.message);
                    clearWeighForm();
                    return;
                }

                const item = resp.item;
                currentItem = item;

                // Populate preview
                $('#previewTrackingCode').text(item.tracking_code);
                $('#previewStep').text(item.current_step);
                $('#previewCodePart').text(item.code_part);
                $('#previewRackNo').text(item.rack_no);
                $('#previewNamePart').text(item.name_part);
                $('#previewArea').text([item.area, item.cell].filter(Boolean).join(' / ') || '-');
                $('#previewCourierAt').text(item.courier_at);
                $('#itemPreviewCard').addClass('found-ok');

                if (!resp.allowed) {
                    showDenial(resp.message);
                    clearWeighForm();
                } else {
                    $('#denialBanner').addClass('d-none');
                    $('#soItemId').val(item.id);
                    showWeighForm();
                }

                // Load history
                loadHistory(item.id);
            },
            error(xhr) {
                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan server.';
                toastError(msg);
            }
        });
    }

    $('#btnLookupItem').on('click', lookupItem);
    $('#trackingCodeInput').on('keydown', function (e) {
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
    }

    // ─────────────────────────────────────────────────────────
    // LIVE PREVIEW — Margin of Error Calculator
    // ─────────────────────────────────────────────────────────
    const liveElems = {
        wNoInput:   document.getElementById('weightNoSample'),
        wWithInput: document.getElementById('weightWithSample'),
        qtyInput:   document.getElementById('sampleQty'),
        previewBox: document.getElementById('livePreviewBox'),
        submitBtn:  document.getElementById('btnSubmitWeigh'),
    };

    function resetLivePreview() {
        if (liveElems.previewBox) {
            liveElems.previewBox.className = 'mb-4';
            liveElems.previewBox.innerHTML = '<span class="text-muted"><i class="fas fa-calculator me-2"></i>Isi semua field untuk melihat preview kalkulasi.</span>';
        }
        if (liveElems.submitBtn) liveElems.submitBtn.disabled = true;
        ['weightNoSample','weightWithSample','sampleQty'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        if (document.getElementById('weighNotes')) document.getElementById('weighNotes').value = '';
    }

    ['weightNoSample','weightWithSample','sampleQty'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', function () {
            const result = ScaleCalc.calculate(
                liveElems.wNoInput.value,
                liveElems.wWithInput.value,
                liveElems.qtyInput.value
            );
            const box = liveElems.previewBox;

            // Remove old status classes
            box.classList.remove('status-ok', 'status-ng');

            if (!result.valid) {
                box.innerHTML = `<span class="text-muted"><i class="fas fa-calculator me-2"></i>${result.error}</span>`;
                liveElems.submitBtn.disabled = true;
                return;
            }

            box.classList.add(result.status === 'OK' ? 'status-ok' : 'status-ng');
            box.innerHTML = `
                <div class="row g-2 align-items-center w-100">
                    <div class="col-4 text-center">
                        <div class="text-muted small mb-1">Berat / pcs</div>
                        <div class="fw-bold">${result.weightPerPiece.toFixed(4)}</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-muted small mb-1">Estimasi Qty</div>
                        <div class="fw-bold fs-3 text-primary">${result.estimatedCount.toLocaleString('id-ID')}</div>
                        <div class="text-muted" style="font-size:0.7rem;">pcs</div>
                    </div>
                    <div class="col-4 text-center">
                        ${ScaleCalc.renderStatusBadge(result.status, result.errorMarginPercent)}
                    </div>
                </div>
            `;

            liveElems.submitBtn.disabled = result.status === 'NG';
        });
    });

    // ─────────────────────────────────────────────────────────
    // SUBMIT WEIGHING
    // ─────────────────────────────────────────────────────────
    $('#btnSubmitWeigh').on('click', function () {
        if (!currentItem) { toastWarn('Tidak ada item yang dipilih.'); return; }

        const payload = {
            _token:              CSRF_TOKEN,
            so_item_id:          $('#soItemId').val(),
            weight_no_sample:    $('#weightNoSample').val(),
            weight_with_sample:  $('#weightWithSample').val(),
            sample_qty:          $('#sampleQty').val(),
            notes:               $('#weighNotes').val(),
        };

        // Validate locally first
        const check = ScaleCalc.calculate(payload.weight_no_sample, payload.weight_with_sample, payload.sample_qty);
        if (!check.valid) { toastError(check.error); return; }
        if (check.status === 'NG') {
            toastError('Margin of Error ≥ 2%! Tidak bisa submit. Kalibrasi ulang terlebih dahulu.');
            return;
        }

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...');

        $.ajax({
            url: ROUTE_SUBMIT_WEIGH,
            method: 'POST',
            data: payload,
            success(resp) {
                if (resp.success) {
                    showOKResult(resp);
                } else {
                    showNGResult(resp);
                }
            },
            error(xhr) {
                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan server.';
                toastError(msg);
                $('#btnSubmitWeigh').prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Submit Hasil Timbangan');
            }
        });
    });

    function showOKResult(resp) {
        $('#weighFormWrapper').addClass('d-none');
        const pd = resp.printData;
        $('#resultPanel').removeClass('d-none').html(`
            <div class="card shadow-sm" style="border-radius:14px; border:2px solid #28a745; overflow:hidden;">
                <div class="card-body p-4 text-center">
                    <div style="font-size:3rem; color:#28a745;" class="mb-2"><i class="fas fa-check-circle"></i></div>
                    <h4 class="fw-bold text-success mb-1">Timbangan OK!</h4>
                    <p class="text-muted small mb-3">Margin Error: <strong>${resp.errorMargin}%</strong> — Status: <span class="badge bg-success">OK</span></p>
                    <div class="row g-2 text-start mb-4">
                        <div class="col-6"><div class="text-muted small">Estimasi Qty</div><div class="fw-bold fs-4 text-primary">${resp.resultCount.toLocaleString('id-ID')} pcs</div></div>
                        <div class="col-6"><div class="text-muted small">Timbangan</div><div class="fw-bold">${pd.scale_number}</div></div>
                        <div class="col-6"><div class="text-muted small">Operator</div><div class="fw-bold">${pd.user_nik} — ${pd.user_name}</div></div>
                        <div class="col-6"><div class="text-muted small">Partner</div><div class="fw-bold">${pd.partner_nik} — ${pd.partner_name}</div></div>
                        <div class="col-12"><div class="text-muted small">Kode Part</div><div class="fw-bold">${pd.code_part} — ${pd.name_part}</div></div>
                    </div>
                    <div class="alert alert-info small text-start mb-3">
                        <i class="fas fa-print me-1"></i> Label QR Final sudah siap dicetak via Bluetooth Thermal Printer.
                        Status item berubah ke <strong>READY_TO_RETURN</strong>.
                    </div>
                    <button class="btn btn-outline-primary w-100 mb-2" id="btnPrintFinal" data-print='${JSON.stringify(pd)}'>
                        <i class="fas fa-print me-1"></i> Cetak Label QR Final
                    </button>
                    <button class="btn btn-outline-secondary w-100" id="btnScanNext">
                        <i class="fas fa-qrcode me-1"></i> Scan Item Berikutnya
                    </button>
                </div>
            </div>
        `);
    }

    function showNGResult(resp) {
        $('#livePreviewBox').removeClass('status-ok').addClass('status-ng');
        $('#resultPanel').removeClass('d-none').html(`
            <div class="ng-denial-banner">
                <div style="font-size:2.5rem;" class="mb-2"><i class="fas fa-times-circle"></i></div>
                <h5 class="fw-bold mb-1">DITOLAK — Margin Error ≥ 2%</h5>
                <p class="mb-0">${resp.message}</p>
                <div class="mt-3">
                    <span class="badge bg-light text-dark fs-6">Margin: ${resp.errorMargin}%</span>
                    <span class="badge bg-light text-dark fs-6 ms-2">Estimasi Qty: ${resp.resultCount} pcs</span>
                </div>
            </div>
            <div class="mt-3 text-center">
                <p class="text-muted small">Lakukan kalibrasi ulang atau periksa nilai input, lalu coba lagi.</p>
                <button class="btn btn-outline-secondary" id="btnRetryWeigh">
                    <i class="fas fa-redo me-1"></i> Coba Lagi
                </button>
            </div>
        `);
        $('#btnSubmitWeigh').prop('disabled', false)
            .html('<i class="fas fa-check-circle me-2"></i>Submit Hasil Timbangan');
    }

    // ─────────────────────────────────────────────────────────
    // EVENT DELEGATION (dynamic buttons)
    // ─────────────────────────────────────────────────────────
    $(document).on('click', '#btnScanNext', function () {
        resetAll();
    });
    $(document).on('click', '#btnRetryWeigh', function () {
        $('#resultPanel').addClass('d-none').html('');
        resetLivePreview();
        $('#weighFormWrapper').removeClass('d-none');
    });
    $(document).on('click', '#btnPrintFinal', function () {
        const pd = $(this).data('print');
        // Placeholder: integrate with bt-thermal-printer.js when available
        alert('PRINT: ' + pd.tracking_code + ' | Qty: ' + pd.final_count + ' | Partner: ' + pd.partner_name);
    });

    $('#btnResetForm').on('click', resetAll);

    function resetAll() {
        currentItem = null;
        $('#trackingCodeInput').val('').focus();
        $('#itemPreviewWrapper').addClass('d-none');
        $('#itemPreviewCard').removeClass('found-ok');
        $('#weighFormWrapper').addClass('d-none');
        $('#resultPanel').addClass('d-none').html('');
        $('#formPlaceholder').removeClass('d-none');
        resetLivePreview();
        $('#historyTableBody').html('<tr><td colspan="5" class="text-center text-muted py-3">—</td></tr>');
    }

    // ─────────────────────────────────────────────────────────
    // LOAD HISTORY
    // ─────────────────────────────────────────────────────────
    function loadHistory(soItemId) {
        $.ajax({
            url: ROUTE_ITEM_HISTORY,
            method: 'GET',
            data: { so_item_id: soItemId },
            success(resp) {
                const histories = resp.histories;
                if (!histories || histories.length === 0) {
                    $('#historyTableBody').html('<tr><td colspan="5" class="text-center text-muted py-3">Belum ada riwayat.</td></tr>');
                    return;
                }
                let rows = '';
                histories.forEach(h => {
                    const badge = h.status === 'OK'
                        ? '<span class="badge bg-success">OK</span>'
                        : '<span class="badge bg-danger">NG</span>';
                    rows += `<tr>
                        <td>${h.created_at}</td>
                        <td>${h.member_nik}</td>
                        <td>${h.error_margin}%</td>
                        <td>${h.result_count.toLocaleString('id-ID')}</td>
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
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: msg, timer: 2500, showConfirmButton: false });
        } else if (typeof window.swal === 'function') {
            swal({ title: 'Perhatian', text: msg, type: 'warning', timer: 2500, showConfirmButton: false });
        } else {
            alert(msg);
        }
    }
    function toastError(msg) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
        } else if (typeof window.swal === 'function') {
            swal({ title: 'Gagal', text: msg, type: 'error' });
        } else {
            alert(msg);
        }
    }

});
</script>
@endsection
