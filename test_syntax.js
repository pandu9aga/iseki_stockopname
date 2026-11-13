
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
    const IS_COURIER_RETURN_MERGED = 0;
    const RETURN_INDEX_URL = "0";

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
        // Ensure DOM container exists even if previously overwritten by error message
        let readerElement = document.getElementById("reader");
        if (!readerElement) {
            $('#scannerWrapper').html('<div id="reader"></div>');
            readerElement = document.getElementById("reader");
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
            html5QrCode = new Html5Qrcode("reader");

            const config = {
                fps: 20,
                qrbox: function(w, h) {
                    const min = Math.min(w, h);
                    const s = Math.floor(min * 0.75);
                    return { width: s, height: s };
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
                try {
                    const devices = await Html5Qrcode.getCameras();
                    if (devices && devices.length) {
                        const backCam = devices.find(d => {
                            const lbl = (d.label || '').toLowerCase();
                            return lbl.includes('back') || lbl.includes('belakang') || lbl.includes('rear') || lbl.includes('environment');
                        }) || devices[devices.length - 1];

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
                        // Fallback: try opening camera without constraint
                        await html5QrCode.start(
                            {},
                            config,
                            (decodedText) => {
                                console.log("QR Scanned:", decodedText);
                                handleSmartScan(decodedText);
                            },
                            (error) => {}
                        );
                    }
                } catch (fallbackErr) {
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
                    <p class="small mb-2 font-weight-bold">Tidak dapat mengakses kamera: ${err.message || 'Izin ditolak atau sedang digunakan aplikasi lain'}</p>
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
            url: "0",
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

        printCount = 0;
        updatePrintStatusUI();
        $('#printSuccessBadge').addClass('d-none');
        if (item.current_step === 'RETURNED') {
            $('#returnedCourierAlert').removeClass('d-none');
        } else {
            $('#returnedCourierAlert').addClass('d-none');
        }
        validateSubmitButton();
    }

    let printCount = 0;

    function updatePrintStatusUI() {
        if (printCount === 0) {
            $('#printCountBadge').text('0 / 2 Dicetak').removeClass('bg-success bg-info').addClass('bg-secondary');
            $('#printBtnText').text('Cetak Label QR (Lembar 1)');
        } else if (printCount === 1) {
            $('#printCountBadge').text('1 / 2 Dicetak').removeClass('bg-secondary bg-success').addClass('bg-info text-white');
            $('#printBtnText').text('Cetak Label QR (Lembar 2)');
        } else {
            $('#printCountBadge').text(printCount + 'x Dicetak (Selesai)').removeClass('bg-secondary bg-info').addClass('bg-success');
            $('#printBtnText').text('Cetak Lagi (Opsional)');
        }
    }

    // 6. Print Courier Label via Bluetooth (Manual klik per lembar)
    $('#btnPrintCourierLabel').on('click', async function() {
        if (!currentSelectedItem) {
            $.notify({ message: 'Pilih part terlebih dahulu!' }, { type: 'warning' });
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Mencetak...');

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

            // Cetak 1 lembar saja tiap kali tombol diklik
            await window.thermalPrinter.printCourierLabel({
                trackingCode: generatedTrackingCode,
                codePart: currentSelectedItem.item_code,
                namePart: currentSelectedItem.part_name,
                rackNo: currentSelectedItem.rack_no,
                area: currentSelectedItem.location,
                cell: currentSelectedItem.cell,
                locDisplay: currentSelectedItem.loc_cell_display,
                courierName: "0",
                courierNik: "0",
                createdAt: currentSelectedItem.existing_created_at
            }, 1);

            printCount++;
            updatePrintStatusUI();

            $('#displayTrackingCode').text(generatedTrackingCode);
            $('#printSuccessBadge').removeClass('d-none');
            $.notify({ message: `Label lembar ke-${printCount} berhasil dicetak! Silakan potong kertas.` }, { type: 'success' });
            validateSubmitButton();
        } catch (err) {
            console.error('Print Error:', err);
            $.notify({ message: 'Gagal mencetak: ' + (err.message || 'Periksa koneksi Bluetooth printer.') }, { type: 'danger' });
        } finally {
            btn.prop('disabled', false);
            updatePrintStatusUI();
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
        printCount = 0;
        updatePrintStatusUI();
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
        $.get("0", function(data) {
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
                url: "0",
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
