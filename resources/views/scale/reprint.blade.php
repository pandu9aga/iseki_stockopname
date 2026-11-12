@extends('layouts.main')

@section('style')
<style>
    .reprint-header-card {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        border-radius: 14px;
        padding: 16px 20px;
        color: #fff;
        margin-bottom: 20px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    }

    .reprint-preview-card {
        border-radius: 14px;
        border: 2px solid #0d6efd;
        background: #fff;
        overflow: hidden;
    }

    .time-badge-ok {
        background: linear-gradient(135deg, #198754, #0f5132);
        color: #fff;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 700;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .table-reprint-list th,
    .table-reprint-list td {
        vertical-align: middle;
        font-size: 0.85rem;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner py-3">

        {{-- Top Bar Header --}}
        <div class="reprint-header-card d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('scale.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Timbangan
                </a>
                <div>
                    <h5 class="fw-bold mb-0 text-white"><i class="fas fa-print me-2 text-info"></i>Cetak Ulang Label QR Part</h5>
                    <p class="mb-0 text-white-50 small">Cetak ulang label QR menggunakan data &amp; waktu hitungan OK terakhir.</p>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button" id="btnConnectPrinterReprint" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-bluetooth me-1"></i> <span id="printerReprintStatusText">Hubungkan Printer Bluetooth</span>
                </button>
            </div>
        </div>

        <div class="row g-4">
            {{-- LEFT COLUMN: Scan / Input Part --}}
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 mb-3" style="border-radius:14px;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-qrcode text-primary me-2"></i>Cari Part / Scan QR
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleCameraReprint">
                                <i class="fas fa-camera me-1"></i> <span id="cameraBtnTextReprint">Buka Kamera</span>
                            </button>
                        </div>

                        <!-- Camera Scanner Container -->
                        <div class="mb-3 d-none" id="reprintScannerWrapper" style="border-radius: 12px; overflow: hidden; background: #000;">
                            <div id="reprintReader" style="width: 100%;"></div>
                        </div>

                        <div class="input-group mb-2">
                            <span class="input-group-text bg-primary text-white border-0">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control form-control-lg" id="reprintSearchInput"
                                placeholder="Scan QR atau ketik Kode Part / Tracking..."
                                autocomplete="off" autofocus>
                            <button class="btn btn-primary fw-bold" type="button" id="btnLookupReprint">
                                <i class="fas fa-search me-1"></i>Cari
                            </button>
                        </div>
                        <div class="form-text small text-muted">
                            Ketik Kode Part, Nomor Rak, atau arahkan kamera / scanner ke QR code.
                        </div>
                    </div>
                </div>

                {{-- Status Alert (jika part belum selesai atau tidak ditemukan) --}}
                <div id="reprintAlertBox" class="alert alert-warning d-none" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><span id="reprintAlertMessage">-</span>
                </div>
            </div>

            {{-- RIGHT COLUMN: Preview & Action Cetak --}}
            <div class="col-lg-7">
                {{-- Placeholder jika belum memilih part --}}
                <div id="reprintPlaceholder" class="card shadow-sm border-0 p-4 text-center text-muted" style="border-radius:14px; min-height: 280px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                    <div style="font-size:3rem;" class="mb-2 text-primary opacity-50"><i class="fas fa-qrcode"></i></div>
                    <h5 class="fw-bold">Pilih atau Scan Part untuk Cetak Ulang</h5>
                    <p class="small text-muted mb-0">Data hasil hitungan OK terakhir dan waktu aslinya akan muncul di sini untuk dicetak ulang ke label thermal.</p>
                </div>

                {{-- Preview Panel (Hidden until loaded) --}}
                <div id="reprintPreviewPanel" class="d-none">
                    <div class="reprint-preview-card shadow-sm mb-3">
                        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="badge bg-success me-1">Hitungan OK Terakhir</span>
                                <span class="badge bg-primary" id="prevReprintStep">-</span>
                            </div>
                            <div class="time-badge-ok" id="prevReprintTimeOkWrap">
                                <i class="fas fa-clock"></i>
                                <span id="prevReprintTimeOk">-</span>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="alert alert-info py-2 px-3 small mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Waktu Cetak Terjaga:</strong> Label yang dicetak ulang akan tetap mencantumkan waktu selesai hitung OK di atas (bukan waktu saat ini).
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="text-muted small">Kode Part</div>
                                    <div class="fw-bold fs-5 text-dark" id="prevReprintCodePart">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">No. Rak &amp; Lokasi</div>
                                    <div class="fw-bold fs-5 text-dark" id="prevReprintRackLoc">-</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">Nama Part</div>
                                    <div class="fw-bold text-dark" id="prevReprintNamePart">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Total QTY (Final dengan Sample)</div>
                                    <div class="fw-bold fs-3 text-primary" id="prevReprintFinalQty">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Jumlah Sample</div>
                                    <div class="fw-bold fs-4 text-secondary" id="prevReprintSampleQty">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Operator (PIC)</div>
                                    <div class="fw-semibold" id="prevReprintOperator">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Partner</div>
                                    <div class="fw-semibold" id="prevReprintPartner">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Nomor Timbangan</div>
                                    <div class="fw-semibold" id="prevReprintScaleNo">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Total Tanpa Sample</div>
                                    <div class="fw-semibold text-dark" id="prevReprintTotalWithout">-</div>
                                </div>
                            </div>

                            {{-- Tabel Detail Hitungan (Tanpa Sample & Dengan Sample) --}}
                            <div class="mb-3" id="prevReprintRoundsWrap">
                                <div class="text-muted small mb-1 fw-bold">
                                    <i class="fas fa-list-ol me-1"></i>Rincian Detail Hitungan:
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0 align-middle" style="font-size:0.83rem;">
                                        <thead class="table-light">
                                            <tr class="text-center">
                                                <th style="width: 50%;">Tanpa Sample</th>
                                                <th style="width: 50%;">Dengan Sample</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="p-1 align-top">
                                                    <table class="table table-sm table-borderless mb-0">
                                                        <tbody id="prevReprintRoundsTableBody">
                                                        </tbody>
                                                    </table>
                                                </td>
                                                <td class="p-1 align-top bg-light">
                                                    <table class="table table-sm table-borderless mb-0">
                                                        <tbody id="prevReprintWithSampleBody">
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" id="btnDoReprint">
                                <i class="fas fa-print me-2"></i>Cetak Ulang Label QR Final
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Riwayat Part yang Sudah Selesai Dihitung --}}
        <div class="card shadow-sm border-0 mt-4" style="border-radius:14px;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">
                    <i class="fas fa-history text-secondary me-2"></i>Daftar Part Selesai Dihitung di Timbangan (50 Terakhir)
                </h6>
                <span class="text-muted small">Klik pada baris untuk cetak ulang</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-reprint-list mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Kode Part</th>
                                <th>Nama Part</th>
                                <th>Rak</th>
                                <th>Lokasi</th>
                                <th>Total QTY</th>
                                <th>Waktu Selesai</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($completedItems as $index => $cItem)
                            <tr style="cursor: pointer;" class="btn-select-completed-item" data-code="{{ $cItem->tracking_code ?: $cItem->code_part }}">
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-bold text-primary">{{ $cItem->code_part }}</td>
                                <td>{{ Str::limit($cItem->name_part, 35) }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $cItem->rack_no }}</span></td>
                                <td>{{ [ $cItem->area, $cItem->cell ] ? implode(' / ', array_filter([$cItem->area, $cItem->cell])) : '-' }}</td>
                                <td class="fw-bold fs-6 text-success">{{ number_format($cItem->final_count ?? 0, 0, ',', '.') }} pcs</td>
                                <td class="text-muted small">
                                    {{ $cItem->count_completed_at ? $cItem->count_completed_at->format('d/m/Y H:i:s') : '-' }}
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-print me-1"></i>Pilih
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    Belum ada data part yang selesai dihitung.
                                </td>
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
<script src="{{ asset('assets/js/bt-thermal-printer.js') }}?v={{ time() }}"></script>
<script>
$(document).ready(function() {
    const ROUTE_REPRINT_LOOKUP = "{{ route('scale.reprint-lookup') }}";
    const CSRF_TOKEN = "{{ csrf_token() }}";

    let currentPrintData = null;
    let isReprintSubmitting = false;

    // ─────────────────────────────────────────────────────────
    // PRINTER BLUETOOTH CONNECTION
    // ─────────────────────────────────────────────────────────
    function updatePrinterStatus() {
        if (window.thermalPrinter && window.thermalPrinter.isConnected) {
            $('#printerReprintStatusText').text(window.thermalPrinter.printerName || 'Printer Terhubung');
            $('#btnConnectPrinterReprint').removeClass('btn-outline-light').addClass('btn-success');
        } else {
            $('#printerReprintStatusText').text('Hubungkan Printer Bluetooth');
            $('#btnConnectPrinterReprint').removeClass('btn-success').addClass('btn-outline-light');
        }
    }

    if (window.thermalPrinter) {
        window.thermalPrinter.onDisconnected = updatePrinterStatus;
        window.thermalPrinter.autoConnect().then(() => updatePrinterStatus());
    }

    $('#btnConnectPrinterReprint').on('click', async function() {
        try {
            await window.thermalPrinter.connect();
            updatePrinterStatus();
            if (typeof swal === 'function') {
                swal('Terhubung!', 'Printer Bluetooth berhasil terhubung.', 'success');
            }
        } catch (err) {
            console.error('BT Connect Error:', err);
            if (typeof swal === 'function') {
                swal('Koneksi Gagal', err.message || 'Gagal menghubungkan printer bluetooth.', 'error');
            }
        }
    });

    // ─────────────────────────────────────────────────────────
    // CAMERA SCANNER
    // ─────────────────────────────────────────────────────────
    let reprintQrCode = null;
    let isReprintCameraRunning = false;

    async function initReprintCamera() {
        let readerEl = document.getElementById("reprintReader");
        if (!readerEl) return;

        if (reprintQrCode) {
            try {
                if (isReprintCameraRunning) await reprintQrCode.stop();
                await reprintQrCode.clear();
            } catch (e) {}
        }

        $('#reprintScannerWrapper').removeClass('d-none');
        $('#cameraBtnTextReprint').text('Tutup Kamera');
        readerEl.innerHTML = '';

        try {
            reprintQrCode = new Html5Qrcode("reprintReader", {
                experimentalFeatures: { useBarCodeDetectorIfSupported: true }
            });

            const config = {
                fps: 20,
                qrbox: { width: 220, height: 220 },
                aspectRatio: 1.0
            };

            await reprintQrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    stopReprintCamera();
                    $('#reprintSearchInput').val(decodedText);
                    lookupReprint(decodedText);
                },
                (error) => {}
            );
            isReprintCameraRunning = true;
        } catch (err) {
            console.error('Kamera reprint gagal:', err);
            isReprintCameraRunning = false;
            $('#reprintScannerWrapper').addClass('d-none');
            $('#cameraBtnTextReprint').text('Buka Kamera');
            if (typeof swal === 'function') {
                swal('Kamera', 'Tidak dapat mengakses kamera: ' + (err.message || 'Izin ditolak'), 'warning');
            }
        }
    }

    async function stopReprintCamera() {
        if (reprintQrCode && isReprintCameraRunning) {
            try { await reprintQrCode.stop(); } catch(e){}
            isReprintCameraRunning = false;
        }
        $('#reprintScannerWrapper').addClass('d-none');
        $('#cameraBtnTextReprint').text('Buka Kamera');
    }

    $('#btnToggleCameraReprint').on('click', function() {
        if (isReprintCameraRunning) {
            stopReprintCamera();
        } else {
            initReprintCamera();
        }
    });

    // ─────────────────────────────────────────────────────────
    // LOOKUP ITEM REPRINT
    // ─────────────────────────────────────────────────────────
    function lookupReprint(searchVal) {
        const val = (searchVal || $('#reprintSearchInput').val() || '').trim();
        if (!val) {
            if (typeof swal === 'function') {
                swal('Perhatian', 'Masukkan kode tracking, kode part, atau nomor rak.', 'warning');
            }
            return;
        }

        $('#reprintAlertBox').addClass('d-none');
        $('#reprintPlaceholder').removeClass('d-none');
        $('#reprintPreviewPanel').addClass('d-none');
        currentPrintData = null;

        const btn = $('#btnLookupReprint');
        const origBtnText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Mencari...');

        $.ajax({
            url: ROUTE_REPRINT_LOOKUP,
            method: 'POST',
            data: {
                _token: CSRF_TOKEN,
                search: val
            },
            success(resp) {
                if (!resp.found) {
                    $('#reprintAlertBox').removeClass('d-none');
                    $('#reprintAlertMessage').text(resp.message || 'Data part tidak ditemukan.');
                    return;
                }

                if (!resp.allowed) {
                    $('#reprintAlertBox').removeClass('d-none');
                    $('#reprintAlertMessage').text(resp.message || 'Part ini belum selesai dihitung.');
                    return;
                }

                const pd = resp.printData || {};
                currentPrintData = pd;

                // Tampilkan data pada preview
                $('#prevReprintStep').text(resp.item.current_step ? resp.item.current_step.replace(/_/g, ' ') : 'COMPLETED');
                $('#prevReprintTimeOk').text(pd.completed_at || '-');
                $('#prevReprintCodePart').text(pd.code_part || '-');
                $('#prevReprintRackLoc').text((pd.rack_no || '-') + (pd.loc ? ' (' + pd.loc + ')' : ''));
                $('#prevReprintNamePart').text(pd.name_part || '-');
                $('#prevReprintFinalQty').text(Number(pd.final_count || 0).toLocaleString('id-ID') + ' pcs');
                $('#prevReprintSampleQty').text(Number(pd.sample_qty || 0).toLocaleString('id-ID') + ' pcs');
                $('#prevReprintOperator').text(pd.user_name || pd.user_nik || '-');
                $('#prevReprintPartner').text(pd.partner_name || pd.partner_nik || '-');
                $('#prevReprintScaleNo').text(pd.scale_number || '-');
                $('#prevReprintTotalWithout').text(Number(pd.total_without_sample || 0).toLocaleString('id-ID') + ' pcs');

                // Rincian Ronde (Tanpa Sample & Dengan Sample)
                const rList = pd.round_details || [];
                let rRows = '';
                let rWithRows = '';
                if (rList.length > 0) {
                    const totalWithout = Number(pd.total_without_sample || 0);
                    const totalWith = Number(pd.total_with_sample || pd.final_count || 0);
                    const sampleQty = pd.sample_qty !== undefined && pd.sample_qty !== null ? Number(pd.sample_qty) : 0;

                    let lastRoundWithSampleVal = pd.last_round_with_sample ?? null;
                    if (lastRoundWithSampleVal === null) {
                        if (rList.length === 1) {
                            lastRoundWithSampleVal = totalWith;
                        } else {
                            const lastR = rList[rList.length - 1];
                            lastRoundWithSampleVal = Number(lastR.weight) + sampleQty;
                        }
                    }

                    rList.forEach(rd => {
                        rRows += `<tr><td class="text-muted py-0 ps-1">Hitungan ${rd.round_no}</td><td class="text-end fw-bold py-0 pe-1">${Number(rd.weight).toLocaleString('id-ID', {maximumFractionDigits:4})} pcs</td></tr>`;
                    });
                    rRows += `<tr class="border-top table-warning"><td class="fw-bold py-1 ps-1 text-dark">Total Tanpa Sample</td><td class="text-end fw-bold py-1 pe-1 text-primary">${totalWithout.toLocaleString('id-ID', {maximumFractionDigits:4})} pcs</td></tr>`;

                    const lastRoundLabel = `Hitungan ${rList.length} (Terakhir) + Sample`;
                    rWithRows += `<tr><td class="text-muted py-0 ps-1">${lastRoundLabel}</td><td class="text-end fw-bold text-success py-0 pe-1">${Number(lastRoundWithSampleVal).toLocaleString('id-ID', {maximumFractionDigits:4})} pcs</td></tr>`;
                    rWithRows += `<tr class="border-top table-success"><td class="fw-bold py-1 ps-1 text-dark">Total Keseluruhan (+Sample)</td><td class="text-end fw-bold py-1 pe-1 text-success fs-6">${totalWith.toLocaleString('id-ID', {maximumFractionDigits:4})} pcs</td></tr>`;

                    $('#prevReprintRoundsTableBody').html(rRows);
                    $('#prevReprintWithSampleBody').html(rWithRows);
                    $('#prevReprintRoundsWrap').removeClass('d-none');
                } else {
                    $('#prevReprintRoundsWrap').addClass('d-none');
                }

                $('#reprintPlaceholder').addClass('d-none');
                $('#reprintPreviewPanel').removeClass('d-none');

                window.scrollTo({
                    top: $('#reprintPreviewPanel').offset().top - 20,
                    behavior: 'smooth'
                });
            },
            error(xhr) {
                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan pada server.';
                $('#reprintAlertBox').removeClass('d-none');
                $('#reprintAlertMessage').text(msg);
            },
            complete() {
                btn.prop('disabled', false).html(origBtnText);
            }
        });
    }

    $('#btnLookupReprint').on('click', function() {
        lookupReprint();
    });

    $('#reprintSearchInput').on('keydown', function(e) {
        if (e.key === 'Enter') lookupReprint();
    });

    // Pilih dari baris tabel list
    $(document).on('click', '.btn-select-completed-item', function() {
        const code = $(this).data('code');
        if (code) {
            $('#reprintSearchInput').val(code);
            lookupReprint(code);
        }
    });

    // ─────────────────────────────────────────────────────────
    // ACTION CETAK ULANG
    // ─────────────────────────────────────────────────────────
    $('#btnDoReprint').on('click', async function() {
        if (!currentPrintData) return;
        if (isReprintSubmitting) return;

        const pd = currentPrintData;
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Mencetak...');
        isReprintSubmitting = true;

        try {
            if (!window.thermalPrinter.isConnected) {
                await window.thermalPrinter.connect();
                updatePrinterStatus();
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
                lastRoundWithSample: pd.last_round_with_sample ?? null,
                method: 'TIMBANGAN (REPRINT)',
                user: pd.user_name || pd.user_nik,
                partner: pd.partner_name || pd.partner_nik,
                scaleNo: pd.scale_number,
                time: pd.completed_at // Waktu tetap dari waktu OK terakhir!
            });

            if (typeof swal === 'function') {
                swal({
                    title: 'Berhasil Dicetak',
                    text: 'Label QR Final berhasil dicetak ulang dengan waktu: ' + pd.completed_at,
                    icon: 'success',
                    type: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } catch (err) {
            console.error('Print Reprint Error:', err);
            if (typeof swal === 'function') {
                swal('Gagal Mencetak', err.message || 'Periksa koneksi Bluetooth printer.', 'error');
            }
        } finally {
            btn.prop('disabled', false).html('<i class="fas fa-print me-2"></i>Cetak Ulang Label QR Final');
            isReprintSubmitting = false;
        }
    });

});
</script>
@endsection

