@extends('layouts.main')

@section('style')
<style>
    .scanner-container {
        position: relative;
        width: 100%;
        max-width: 480px;
        margin: 0 auto;
        border-radius: 8px;
        overflow: hidden;
        background: #000;
    }
    #reader {
        width: 100%;
    }
    .preview-box {
        width: 100%;
        height: 160px;
        border: 2px dashed #ccc;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        overflow: hidden;
        cursor: pointer;
    }
    .preview-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .status-badge {
        font-size: 0.85rem;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">
        <!-- Header & Bluetooth Toolbar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
            <div>
                <h3 class="fw-bold mb-1">
                    @if($activeEvent && $activeEvent->is_courier_return_merged)
                        <i class="fas fa-sync-alt text-primary me-2"></i> Pos Kerja Kurir &amp; Pengembalian
                    @else
                        <i class="fas fa-truck-loading text-primary me-2"></i> Pos Kerja Kurir
                    @endif
                </h3>
                <span class="text-muted small">
                    @if($activeEvent && $activeEvent->is_courier_return_merged)
                        Scan <strong>QR Part / Rak Fisik</strong> &rarr; Ambil Foto Box Kosong &rarr; Print Label 2x &rarr; Simpan &amp; Kirim
                    @else
                        Scan QR Part &rarr; Ambil Foto Box Kosong &rarr; Print Label 2x &rarr; Simpan &amp; Kirim
                    @endif
                </span>
            </div>

            <!-- Bluetooth Connection Button -->
            <div>
                <button type="button" id="btnConnectPrinter" class="btn btn-outline-primary btn-sm rounded-pill shadow-sm">
                    <i class="fas fa-print me-1"></i> <span id="printerStatusText">Sambungkan Printer</span>
                </button>
            </div>
        </div>

        @if(!$activeEvent)
            <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center mb-4" role="alert" style="border-radius: 10px;">
                <i class="fas fa-exclamation-triangle fa-2x me-3 text-danger"></i>
                <div>
                    <h5 class="fw-bold mb-1">Tidak Ada Jadwal Stock Opname yang Aktif</h5>
                    <p class="mb-0 small">
                        Saat ini belum ada periode Stock Opname yang diaktifkan oleh Admin. Proses pengambilan part dan pemindaian rak sementara <strong>dinonaktifkan</strong>. Silakan hubungi koordinator / Admin SO.
                    </p>
                </div>
            </div>
        @else
            <div class="alert alert-success shadow-sm border-0 d-flex align-items-center justify-content-between mb-3 py-2 px-3" style="border-radius: 10px;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-calendar-check me-2 text-success"></i>
                    <span class="small">Periode SO Aktif: <strong>{{ $activeEvent->event_name }}</strong> ({{ $activeEvent->event_code }})</span>
                </div>
                <span class="badge bg-success rounded-pill px-3 py-1 text-uppercase" style="font-size: 0.7rem;">Active</span>
            </div>
        @endif

        <div class="row">
            <!-- Left Col: QR Scanner & Lookup -->
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            @if($activeEvent && $activeEvent->is_courier_return_merged)
                                1. Scan QR Part / Rak Fisik
                            @else
                                1. Scan QR Part / Rak
                            @endif
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- QR Scanner -->
                        <div class="scanner-container mb-3" id="scannerWrapper">
                            <div id="reader"></div>
                        </div>

                        <!-- Scanned Result Box (Shown when camera is closed) -->
                        <div id="scanResultCard" class="card border-success bg-success-subtle mb-3 d-none">
                            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-success fw-bold d-block"><i class="fas fa-check-circle me-1"></i> QR Terbaca</small>
                                    <span class="fs-5 fw-bold text-dark" id="scannedCodeText">-</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-success" id="btnRescan">
                                    <i class="fas fa-redo me-1"></i> Scan Ulang
                                </button>
                            </div>
                        </div>

                        <!-- Manual Input Fallback -->
                        <div class="input-group mb-2">
                            <input type="text" id="manualRackInput" class="form-control" placeholder="{{ ($activeEvent && $activeEvent->is_courier_return_merged) ? 'Scan QR Part / No Rak (BP-A010)' : 'Scan QR Part / No Rak (cth: BP-A010 / 123456)' }}">
                            <button class="btn btn-primary" type="button" id="btnManualSearch">
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </div>
                        <small class="text-muted d-block mb-3">
                            Arahkan kamera ke QR kode part pada box/rak atau ketik manual.
                        </small>

                        <!-- Unregistered Alert Box -->
                        <div id="unregisteredAlert" class="alert alert-danger d-none shadow-sm" role="alert">
                            <div class="d-flex">
                                <i class="fas fa-exclamation-circle fa-2x me-3 mt-1"></i>
                                <div>
                                    <h6 class="alert-heading fw-bold mb-1">Rak Belum Didaftarkan!</h6>
                                    <p id="unregisteredMsg" class="mb-0 small"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Col: Form Flow Kurir (Foto Box Kosong -> Print Label 2x -> Submit) -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold">2. Data Part & Alur Pengambilan</h5>
                        <span id="selectedPartBadge" class="badge bg-secondary d-none">Siap Diproses</span>
                    </div>
                    <div class="card-body">
                        <!-- Placeholder when no rack scanned -->
                        <div id="emptyRackPrompt" class="text-center py-5 text-muted">
                            <i class="fas fa-qrcode fa-3x mb-3 text-secondary"></i>
                            <p>Silakan scan QR part / rak fisik di sebelah kiri untuk memuat data.</p>
                        </div>

                        <!-- Content when rack found -->
                        <div id="rackDataContainer" class="d-none">
                            <!-- Multiple Part / Location-Cell Selection -->
                            <div id="multiplePartWrapper" class="d-none mb-3">
                                <label class="form-label fw-bold text-primary">
                                    <i class="fas fa-layer-group me-1"></i> Pilih Area (Loc-Cell) & Part di Rak Ini:
                                </label>
                                <select id="partSelector" class="form-select form-select-lg"></select>
                                <small class="text-danger fw-bold d-block mt-1">
                                    <i class="fas fa-info-circle me-1"></i> Data rak ini memiliki beberapa lokasi/cell atau part. Kurir wajib memilih salah satu.
                                </small>
                            </div>

                            <!-- Selected Part Info Card -->
                            <div class="card bg-light border-0 mb-3 p-3">
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <small class="text-muted">Nomor Rak:</small>
                                        <div class="fw-bold fs-6" id="infoRackNo">-</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted">Location - Cell:</small>
                                        <div class="fw-bold fs-6 text-primary" id="infoLocCell">-</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted">Kode Part:</small>
                                        <div class="fw-bold fs-6" id="infoItemCode">-</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted">Supplier:</small>
                                        <div class="fw-bold" id="infoSupplier">-</div>
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted">Nama Part:</small>
                                        <div class="fw-bold" id="infoPartName">-</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Kurir: Step A (Foto Box Kosong), Step B (Print Label 2x), Step C (Submit) -->
                            <form id="formCourierSubmit" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="rack_label_id" id="form_rack_label_id">
                                <input type="hidden" name="rack_no" id="form_rack_no">
                                <input type="hidden" name="code_rack" id="form_code_rack">
                                <input type="hidden" name="area" id="form_area">
                                <input type="hidden" name="cell" id="form_cell">
                                <input type="hidden" name="code_part" id="form_code_part">
                                <input type="hidden" name="name_part" id="form_name_part">

                                <!-- LANGKAH 2: Ambil Foto Box Kosong -->
                                <div class="card border-0 shadow-sm mb-3" style="background: #fdfdfe; border: 1px solid #e9ecef !important; border-radius: 10px;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">2</span>
                                            <h6 class="fw-bold mb-0 text-dark">Ambil Foto Box Kosong <span class="text-danger">*</span></h6>
                                        </div>
                                        <p class="small text-muted mb-2">Foto kondisi rak / box kosong setelah part diambil oleh kurir.</p>
                                        
                                        <div class="preview-box" onclick="document.getElementById('photoRackInput').click()" style="height: 180px;">
                                            <div id="previewRackPlaceholder" class="text-center p-3 text-muted">
                                                <i class="fas fa-camera fa-3x mb-2 text-primary"></i><br>
                                                <span class="fw-bold text-dark">Ketuk untuk Ambil Foto Box Kosong</span><br>
                                                <small class="text-muted">Kamera langsung aktif untuk menjepret box kosong</small>
                                            </div>
                                            <img id="imgPreviewRack" class="d-none" alt="Preview Box Kosong">
                                        </div>
                                        <input type="file" id="photoRackInput" name="photo_rack" accept="image/*" capture="environment" class="d-none" required>
                                    </div>
                                </div>

                                <!-- LANGKAH 3: Print Label 2x -->
                                <div class="card border-primary-subtle bg-primary-subtle p-3 mb-3" style="border-radius: 10px;">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">3</span>
                                        <h6 class="fw-bold text-primary mb-0">Cetak Label Kurir (Otomatis 2x Copy)</h6>
                                    </div>
                                    <p class="small text-muted mb-3">Klik tombol cetak untuk mencetak 2 rangkap label kurir 48mm via printer Bluetooth.</p>

                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <button type="button" id="btnPrintCourierLabel" class="btn btn-primary btn-lg fw-bold flex-grow-1 shadow-sm">
                                            <i class="fas fa-print me-1"></i> Print Label (2x)
                                        </button>
                                    </div>

                                    <div id="printSuccessBadge" class="mt-2 text-success fw-bold small d-none">
                                        <i class="fas fa-check-circle me-1"></i> Label berhasil dicetak 2x! Kode: <span id="displayTrackingCode"></span>
                                    </div>
                                    <div id="returnedCourierAlert" class="alert alert-info mt-3 py-2 px-3 small d-none" role="alert">
                                        <i class="fas fa-info-circle me-1"></i> <strong>Mode Cetak Ulang:</strong> Part ini sudah berstatus <strong>RETURNED</strong> (sudah selesai dihitung dan dikembalikan ke rak). Anda dapat mencetak ulang label kurir di atas, namun form submit dinonaktifkan.
                                    </div>
                                </div>

                                <!-- LANGKAH 4: Submit Simpan -->
                                <button type="submit" id="btnSubmitCourier" class="btn btn-success w-100 py-3 fw-bold fs-6 shadow" disabled>
                                    <i class="fas fa-check-circle me-1"></i> Done Submit &amp; Kirim ke Meja Hitung
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-header bg-white py-3">
                        <h6 class="card-title mb-0 fw-bold">Riwayat Pengambilan Terakhir oleh Anda</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tracking Code</th>
                                        <th>Part</th>
                                        <th>Rak</th>
                                        <th>Waktu</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="recentCourierTbody">
                                    @forelse($recentItems as $r)
                                    <tr>
                                        <td class="fw-bold text-primary">{{ $r->tracking_code }}</td>
                                        <td>{{ $r->code_part }}<br><small class="text-muted">{{ Str::limit($r->name_part, 20) }}</small></td>
                                        <td>{{ $r->rack_no }} ({{ $r->code_rack }})</td>
                                        <td>{{ $r->created_at->format('H:i') }}</td>
                                        <td><span class="badge bg-warning text-dark status-badge">{{ $r->current_step }}</span></td>
                                    </tr>
                                    @empty
                                    <tr id="emptyRecentRow">
                                        <td colspan="5" class="text-center py-3 text-muted">Belum ada data pengambilan hari ini.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/html5-qrcode.min.js') }}"></script>
<script src="{{ asset('assets/js/bt-thermal-printer.js') }}?v={{ time() }}"></script>
<script>
    let scannedItemsList = [];
    let currentSelectedItem = null;
    let generatedTrackingCode = null;
    let html5QrCode = null;

    $(document).ready(function() {
        initScanner();
        bindBluetoothButton();
        bindPhotoPreviews();
        bindManualSearch();
        bindPartSelector();
        bindFormSubmit();

        // Attempt silent auto-connect if device was previously granted
        setTimeout(async () => {
            try {
                const connected = await window.thermalPrinter.autoConnect();
                if (connected) {
                    $('#btnConnectPrinter').removeClass('btn-outline-primary').addClass('btn-success');
                    $('#printerStatusText').html(`<i class="fas fa-check"></i> Terhubung: ${connected.name || 'Printer Bluetooth'}`);
                }
            } catch (e) {
                console.log('Background auto-connect:', e);
            }
        }, 600);
    });

    // 1. Bluetooth Connection
    function bindBluetoothButton() {
        $('#btnConnectPrinter').on('click', async function() {
            try {
                const btn = $(this);
                btn.prop('disabled', true);
                $('#printerStatusText').text('Menghubungkan...');

                const device = await window.thermalPrinter.connect();
                btn.removeClass('btn-outline-primary').addClass('btn-success');
                $('#printerStatusText').html(`<i class="fas fa-check"></i> Terhubung: ${device.name}`);
                $.notify({ message: `Printer ${device.name} terhubung!` }, { type: 'success' });
            } catch (err) {
                console.error(err);
                $('#printerStatusText').text('Sambungkan Printer');
                $.notify({ message: err.message || 'Gagal menyambungkan Bluetooth.' }, { type: 'danger' });
            } finally {
                $('#btnConnectPrinter').prop('disabled', false);
            }
        });
    }

    // 2. HTML5 Camera QR Scanner
    let isScannerRunning = false;
    const IS_COURIER_RETURN_MERGED = {{ ($activeEvent && $activeEvent->is_courier_return_merged) ? 'true' : 'false' }};
    const RETURN_INDEX_URL = "{{ route('return.index') }}";

    function handleSmartScan(decodedText) {
        const trimmed = decodedText.trim();
        // Cek apakah format QR final (memiliki minimal 5 segmen pemisah | : part|name|rak|loc|qty)
        if (IS_COURIER_RETURN_MERGED && trimmed.includes('|')) {
            const segments = trimmed.split('|');
            if (segments.length >= 5 && segments[4].trim() !== '') {
                // Ini adalah QR final label pengembalian! Arahkan langsung ke modul pengembalian
                stopScannerAndShowResult(trimmed);
                sessionStorage.setItem('auto_scan_return_qr', trimmed);
                $.notify({
                    icon: 'fas fa-undo-alt',
                    title: 'QR Final Terdeteksi!',
                    message: 'Mengarahkan ke modul Pengembalian part ke rak...'
                }, {
                    type: 'info',
                    placement: { from: 'top', align: 'center' }
                });
                setTimeout(function() {
                    window.location.href = RETURN_INDEX_URL + '?auto_qr=' + encodeURIComponent(trimmed);
                }, 700);
                return;
            }
        }

        // Jika bukan QR final, jalankan alur kurir normal
        stopScannerAndShowResult(trimmed);
        fetchRackData(trimmed);
    }

    async function initScanner() {
        const readerElement = document.getElementById("reader");
        if (!readerElement) {
            console.error("Elemen #reader tidak ditemukan di DOM!");
            return;
        }

        // Clean up previous instance if running
        if (html5QrCode) {
            try {
                if (isScannerRunning) {
                    await html5QrCode.stop();
                }
                await html5QrCode.clear();
            } catch (e) {
                console.warn("Cleanup previous scanner:", e);
            }
        }

        // Reset UI container
        $('#scannerWrapper').removeClass('d-none');
        $('#scanResultCard').addClass('d-none');
        readerElement.innerHTML = '';

        try {
            html5QrCode = new Html5Qrcode("reader", {
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

            try {
                await html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText) => {
                        console.log("QR Scanned:", decodedText);
                        handleSmartScan(decodedText);
                    },
                    (error) => {}
                );
            } catch (errConstraint) {
                console.warn("Courier retry with device id list...", errConstraint);
                const devices = await Html5Qrcode.getCameras();
                if (devices && devices.length) {
                    const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('belakang')) || devices[devices.length - 1];
                    await html5QrCode.start(
                        backCam.id,
                        config,
                        (decodedText) => {
                            console.log("QR Scanned:", decodedText);
                            handleSmartScan(decodedText);
                        },
                        (error) => {}
                    );
                } else {
                    throw errConstraint;
                }
            }

            isScannerRunning = true;
            console.log("Kamera scanner QR berhasil aktif.");
        } catch (err) {
            console.error("Gagal memulai scanner kamera:", err);
            isScannerRunning = false;
            $('#scannerWrapper').html(`
                <div class="p-4 text-center text-white bg-dark rounded">
                    <i class="fas fa-camera-slash fa-2x mb-2 text-warning"></i>
                    <p class="small mb-2 font-weight-bold">Tidak dapat mengakses kamera: ${err.message || 'Izin ditolak'}</p>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="initScanner()">
                        <i class="fas fa-redo me-1"></i> Coba Izin Kamera Lagi
                    </button>
                </div>
            `);
        }
    }

    function stopScannerAndShowResult(code) {
        if (html5QrCode && isScannerRunning) {
            html5QrCode.stop().then(() => {
                isScannerRunning = false;
                $('#scannerWrapper').addClass('d-none');
                $('#scannedCodeText').text(code);
                $('#manualRackInput').val(code);
                $('#scanResultCard').removeClass('d-none');
            }).catch(err => {
                console.error("Failed to stop scanner:", err);
                $('#scannerWrapper').addClass('d-none');
                $('#scannedCodeText').text(code);
                $('#manualRackInput').val(code);
                $('#scanResultCard').removeClass('d-none');
            });
        } else {
            $('#scannerWrapper').addClass('d-none');
            $('#scannedCodeText').text(code);
            $('#manualRackInput').val(code);
            $('#scanResultCard').removeClass('d-none');
        }
    }

    $('#btnRescan').on('click', function() {
        initScanner();
    });

    // 3. Manual Search
    function bindManualSearch() {
        $('#btnManualSearch').on('click', function() {
            const query = $('#manualRackInput').val().trim();
            if (query) {
                handleSmartScan(query);
            }
        });
        $('#manualRackInput').on('keypress', function(e) {
            if (e.which === 13) {
                $('#btnManualSearch').click();
            }
        });
    }

    // 4. Fetch Rack Data from iseki_label
    function fetchRackData(rackQuery) {
        $('#unregisteredAlert').addClass('d-none');
        $('#emptyRackPrompt').removeClass('d-none');
        $('#rackDataContainer').addClass('d-none');

        $.ajax({
            url: "{{ route('courier.lookup-rack') }}",
            type: "GET",
            data: { rack: rackQuery },
            beforeSend: function() {
                $('#btnManualSearch').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            },
            success: function(res) {
                if (!res.success) {
                    if (res.status === 'UNREGISTERED') {
                        $('#unregisteredMsg').text(res.message);
                        $('#unregisteredAlert').removeClass('d-none');
                    } else {
                        $.notify({ message: res.message }, { type: 'warning' });
                    }
                    return;
                }

                scannedItemsList = res.items;
                populatePartSelection(scannedItemsList);
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Gagal memuat data rak.';
                $.notify({ message: msg }, { type: 'danger' });
            },
            complete: function() {
                $('#btnManualSearch').prop('disabled', false).html('<i class="fas fa-search"></i> Cari');
            }
        });
    }

    // 5. Populate Part Selector
    function populatePartSelection(items) {
        if (items.length === 0) return;

        $('#emptyRackPrompt').addClass('d-none');
        $('#rackDataContainer').removeClass('d-none');

        if (items.length > 1) {
            $('#multiplePartWrapper').removeClass('d-none');
            const select = $('#partSelector').empty();
            items.forEach((it, idx) => {
                select.append(`<option value="${idx}">LOC: ${it.loc_cell_display} | [${it.item_code}] ${it.part_name}</option>`);
            });
            selectItem(items[0]);
        } else {
            $('#multiplePartWrapper').addClass('d-none');
            selectItem(items[0]);
        }
    }

    function bindPartSelector() {
        $('#partSelector').on('change', function() {
            const idx = parseInt($(this).val());
            if (scannedItemsList[idx]) {
                selectItem(scannedItemsList[idx]);
            }
        });
    }

    function selectItem(item) {
        currentSelectedItem = item;
        // Format QR Payload: part|name|rak|loc
        const formatLoc = (l, c) => {
            const locClean = (l || '').trim().replace(/^--?$/, '');
            const cellClean = (c || '').trim().replace(/^--?$/, '');
            if (locClean && cellClean) return `${locClean}-${cellClean}`;
            return locClean || cellClean || '-';
        };
        const locDisplay = item.loc_cell_display || formatLoc(item.location, item.cell);
        generatedTrackingCode = `${item.item_code}|${item.part_name}|${item.rack_no}|${locDisplay}`;

        $('#infoRackNo').text(item.rack_no);
        $('#infoLocCell').text(item.loc_cell_display);
        $('#infoItemCode').text(item.item_code);
        $('#infoSupplier').text(item.supplier || '-');
        $('#infoPartName').text(item.part_name);

        $('#form_rack_label_id').val(item.id);
        $('#form_rack_no').val(item.rack_no);
        $('#form_code_rack').val(item.location);
        $('#form_area').val(item.location);
        $('#form_cell').val(item.loc_cell_display || item.cell);
        $('#form_code_part').val(item.item_code);
        $('#form_name_part').val(item.part_name);

        $('#printSuccessBadge').addClass('d-none');
        if (item.current_step === 'RETURNED') {
            $('#returnedCourierAlert').removeClass('d-none');
        } else {
            $('#returnedCourierAlert').addClass('d-none');
        }
        validateSubmitButton();
    }

    let hasPrintedLabel = false;

    // 6. Print Courier Label via Bluetooth (Otomatis 2x Copy)
    $('#btnPrintCourierLabel').on('click', async function() {
        if (!currentSelectedItem) {
            $.notify({ message: 'Pilih part terlebih dahulu!' }, { type: 'warning' });
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Mencetak 2x...');

        try {
            if (!window.thermalPrinter.isConnected) {
                // Auto request connection if not connected
                await window.thermalPrinter.connect();
                $('#btnConnectPrinter').removeClass('btn-outline-primary').addClass('btn-success');
                $('#printerStatusText').html(`<i class="fas fa-check"></i> Terhubung: ${window.thermalPrinter.printerName}`);
            }

            // Gunakan waktu pembuatan awal jika item sudah pernah dibuat sebelumnya
            if (!currentSelectedItem.existing_created_at) {
                const now = new Date();
                const pad = (n) => String(n).padStart(2, '0');
                currentSelectedItem.existing_created_at = `${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()}, ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
            }

            // Cetak 2x
            await window.thermalPrinter.printCourierLabel({
                trackingCode: generatedTrackingCode,
                codePart: currentSelectedItem.item_code,
                namePart: currentSelectedItem.part_name,
                rackNo: currentSelectedItem.rack_no,
                area: currentSelectedItem.location,
                cell: currentSelectedItem.cell,
                locDisplay: currentSelectedItem.loc_cell_display,
                courierName: "{{ Auth::guard('member')->user()->nama ?? Auth::guard('member')->user()->nik }}",
                courierNik: "{{ Auth::guard('member')->user()->nik }}",
                createdAt: currentSelectedItem.existing_created_at
            }, 2);

            hasPrintedLabel = true;
            $('#displayTrackingCode').text(generatedTrackingCode);
            $('#printSuccessBadge').removeClass('d-none');
            $.notify({ message: '2x Label QR Kurir berhasil dicetak!' }, { type: 'success' });
            validateSubmitButton();
        } catch (err) {
            console.error('Print Error:', err);
            $.notify({ message: 'Gagal mencetak: ' + (err.message || 'Periksa koneksi Bluetooth printer.') }, { type: 'danger' });
        } finally {
            btn.prop('disabled', false).html('<i class="fas fa-print me-1"></i> Print Label (2x)');
        }
    });

    // 7. Client-side Image Compression & Preview (Instan & Ringan)
    let compressedBlobs = {
        photoRack: null
    };

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
                    canvas.toBlob((blob) => {
                        resolve(blob);
                    }, 'image/jpeg', quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    function bindPhotoPreviews() {
        $('#photoRackInput').on('change', async function(e) {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                $('#previewRackPlaceholder').html('<i class="fas fa-spinner fa-spin fa-2x mb-1"></i><br><small>Mengompres foto...</small>');
                const blob = await compressImageFile(file);
                compressedBlobs.photoRack = blob;

                const url = URL.createObjectURL(blob);
                $('#imgPreviewRack').attr('src', url).removeClass('d-none');
                $('#previewRackPlaceholder').addClass('d-none');
                validateSubmitButton();
            }
        });
    }

    function validateSubmitButton() {
        if (currentSelectedItem && currentSelectedItem.current_step === 'RETURNED') {
            $('#btnSubmitCourier').prop('disabled', true).html('<i class="fas fa-check-double me-1"></i> Part Sudah Selesai (RETURNED)');
            return;
        }

        const hasPart = currentSelectedItem !== null;
        const hasPhotoRack = compressedBlobs.photoRack !== null || ($('#photoRackInput')[0] && $('#photoRackInput')[0].files.length > 0);

        if (hasPart && hasPhotoRack) {
            $('#btnSubmitCourier').prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Done Submit &amp; Kirim ke Meja Hitung');
        } else {
            $('#btnSubmitCourier').prop('disabled', true).html('<i class="fas fa-save me-1"></i> Selesaikan Foto Box Kosong untuk Submit');
        }
    }

    // Reset Form Kurir setelah sukses tanpa reload halaman agar koneksi Bluetooth tetap hidup
    function resetCourierForm() {
        // Reset form inputs & previews
        $('#formCourierSubmit')[0].reset();
        compressedBlobs.photoRack = null;
        hasPrintedLabel = false;
        $('#imgPreviewRack').addClass('d-none').attr('src', '');
        $('#previewRackPlaceholder').removeClass('d-none');
        $('#btnSubmitCourier').prop('disabled', true).html('<i class="fas fa-check-circle me-1"></i> Done Submit &amp; Kirim ke Meja Hitung');

        // Reset Scanner & Selection
        currentSelectedItem = null;
        scannedItemsList = [];
        $('#selectedPartBadge').addClass('d-none');
        $('#printSuccessBadge').addClass('d-none');
        $('#rackDataContainer').addClass('d-none');
        $('#emptyRackPrompt').removeClass('d-none');
        $('#manualRackInput').val('');
        $('#scannedCodeText').text('-');

        // Re-open camera scanner
        initScanner();

        // Refresh recent items table via AJAX
        $.get("{{ route('courier.recent-items') }}", function(data) {
            const tbody = $('#recentCourierTbody');
            tbody.empty();
            if (data && data.length > 0) {
                data.forEach(item => {
                    tbody.append(`
                        <tr>
                            <td class="fw-bold text-primary">${item.tracking_code}</td>
                            <td>${item.code_part}<br><small class="text-muted" title="${item.full_name}">${item.name_part}</small></td>
                            <td>${item.rack_no} (${item.code_rack || '-'})</td>
                            <td>${item.time}</td>
                            <td><span class="badge bg-warning text-dark status-badge">${item.current_step}</span></td>
                        </tr>
                    `);
                });
            } else {
                tbody.append('<tr><td colspan="5" class="text-center py-3 text-muted">Belum ada data pengambilan hari ini.</td></tr>');
            }
        });
    }

    // 8. Submit Form (Upload file yang sudah dikompresi)
    function bindFormSubmit() {
        $('#formCourierSubmit').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            if (compressedBlobs.photoRack) {
                formData.set('photo_rack', compressedBlobs.photoRack, 'photo_rack.jpg');
            }

            const btn = $('#btnSubmitCourier');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

            $.ajax({
                url: "{{ route('courier.store') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success) {
                        const successMsg = res.already_exists
                            ? res.message
                            : `Data part ${res.item.code_part} berhasil disimpan! Part siap diproses timbangan/pos manual.`;
                        
                        if (typeof swal === 'function') {
                            swal(res.already_exists ? "Info / Duplikasi" : "Berhasil!", successMsg, {
                                icon: res.already_exists ? "info" : "success",
                                timer: 2500,
                                buttons: {
                                    confirm: {
                                        className: 'btn btn-success'
                                    }
                                }
                            }).then(() => {
                                resetCourierForm();
                            });
                        } else {
                            alert(successMsg);
                            resetCourierForm();
                        }
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.message || 'Gagal menyimpan data kurir.';
                    $.notify({ message: msg }, { type: 'danger' });
                    btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Done Submit &amp; Kirim ke Meja Hitung');
                }
            });
        });
    }
                            <td class="fw-bold text-primary">${item.tracking_code}</td>
                            <td>${item.code_part}<br><small class="text-muted" title="${item.full_name}">${item.name_part}</small></td>
                            <td>${item.rack_no} (${item.code_rack || '-'})</td>
                            <td>${item.time}</td>
                            <td><span class="badge bg-warning text-dark status-badge">${item.current_step}</span></td>
                        </tr>
                    `);
                });
            } else {
                tbody.append('<tr><td colspan="5" class="text-center py-3 text-muted">Belum ada data pengambilan hari ini.</td></tr>');
            }
        });
    }

    // 8. Submit Form (Upload file yang sudah dikompresi)
    function bindFormSubmit() {
        $('#formCourierSubmit').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Replace raw heavy camera files with compressed blobs (size ~150KB - 300KB)
            if (compressedBlobs.photoPart) {
                formData.set('photo_part', compressedBlobs.photoPart, 'photo_part.jpg');
            }
            if (compressedBlobs.photoRack) {
                formData.set('photo_rack', compressedBlobs.photoRack, 'photo_rack.jpg');
            }

            const btn = $('#btnSubmitCourier');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

            $.ajax({
                url: "{{ route('courier.store') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success) {
                        const successMsg = res.already_exists
                            ? res.message
                            : `Data part ${res.item.code_part} berhasil disimpan! Part siap diproses timbangan/pos manual.`;
                        
                        if (typeof swal === 'function') {
                            swal(res.already_exists ? "Info / Duplikasi" : "Berhasil!", successMsg, {
                                icon: res.already_exists ? "info" : "success",
                                timer: 2500,
                                buttons: {
                                    confirm: {
                                        className: 'btn btn-success'
                                    }
                                }
                            }).then(() => {
                                resetCourierForm();
                            });
                        } else {
                            alert(successMsg);
                            resetCourierForm();
                        }
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.message || 'Gagal menyimpan data kurir.';
                    $.notify({ message: msg }, { type: 'danger' });
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Simpan & Kirim ke Meja Hitung');
                }
            });
        });
    }
</script>
@endsection

