/**
 * S-Logic 48mm / 58mm Thermal Printer Bluetooth Driver (Web Bluetooth API)
 * Standard ESC/POS Command Implementation for Android Web Browsers (Chrome)
 * Effective print width: 48mm (~384 dots per line / 32 characters per line)
 */

class ThermalPrinterBluetooth {
    constructor() {
        this.device = null;
        this.server = null;
        this.characteristic = null;
        this.isConnected = false;
        this.printerName = null;
        this.STORAGE_KEY_DEVICE_ID = 'iseki_bt_printer_device_id';
        this.STORAGE_KEY_DEVICE_NAME = 'iseki_bt_printer_device_name';
        this.TARGET_MAC = '5A:4A:FA:75:20:A8';
    }

    /**
     * Check if previous printer was paired and attempt auto-connect without user prompt
     * (Chrome Android supports getDevices() on already permitted devices)
     */
    async autoConnect() {
        if (!navigator.bluetooth) return false;

        try {
            if (navigator.bluetooth.getDevices) {
                const devices = await navigator.bluetooth.getDevices();
                const savedId = localStorage.getItem(this.STORAGE_KEY_DEVICE_ID);

                let matchedDevice = null;
                if (savedId) {
                    matchedDevice = devices.find(d => d.id === savedId);
                }

                if (!matchedDevice && devices.length > 0) {
                    matchedDevice = devices.find(d =>
                        d.name && (
                            d.name === 'BlueTooth Printer' ||
                            d.name === 'RPP02N' ||
                            d.name.startsWith('RPP02N') ||
                            d.name === 'RPP210A' ||
                            d.name.startsWith('RPP210A') ||
                            d.name.toLowerCase().includes('rpp02n') ||
                            d.name.toLowerCase().includes('rpp210a') ||
                            d.name.toLowerCase().includes('printer') ||
                            d.name.toLowerCase().includes('rpp')
                        )
                    ) || devices.find(d =>
                        d.name && (
                            d.name.toLowerCase().includes('bluetooth printer') ||
                            d.name.toLowerCase().includes('rpp02n') ||
                            d.name.toLowerCase().includes('rpp210a')
                        )
                    ) || devices[0];
                }

                if (matchedDevice) {
                    console.log('Auto-connecting to previously approved device:', matchedDevice.name);
                    this.device = matchedDevice;
                    return await this._connectGatt();
                }
            }
        } catch (e) {
            console.warn('Auto-connect check failed or not permitted yet:', e);
        }
        return false;
    }

    /**
     * Request connection to Bluetooth Thermal Printer (User gesture required if first time)
     */
    async connect() {
        if (!navigator.bluetooth) {
            const isHttp = window.location.protocol === 'http:' && !['localhost', '127.0.0.1'].includes(window.location.hostname);
            if (isHttp) {
                throw new Error('Web Bluetooth dinonaktifkan browser karena web dibuka via HTTP (bukan HTTPS). Buka web via HTTPS atau aktifkan "Insecure origins treated as secure" di chrome://flags.');
            }
            throw new Error('Web Bluetooth tidak didukung pada browser ini. Pastikan menggunakan browser Google Chrome atau Edge.');
        }

        try {
            console.log('Requesting Bluetooth Device...');

            // Banyak printer thermal BLE menyiarkan service 0x18f0 atau ISSC.
            // Kita siapkan 2 metode:
            // 1. Coba request dengan filter services printer BLE umum (sangat kompatibel dengan Android Chrome)
            // 2. Jika tidak ada / fallback: acceptAllDevices: true
            let device = null;
            try {
                device = await navigator.bluetooth.requestDevice({
                    filters: [
                        { services: ['000018f0-0000-1000-8000-00805f9b34fb'] },
                        { services: ['49535343-fe7d-4ae5-8fa9-9fafd205e455'] },
                        { services: [0x18f0] },
                        { namePrefix: 'BlueTooth' },
                        { namePrefix: 'RPP' },
                        { namePrefix: 'MPT' },
                        { namePrefix: 'POS' },
                        { namePrefix: 'Printer' }
                    ],
                    optionalServices: [
                        '000018f0-0000-1000-8000-00805f9b34fb',
                        'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
                        '49535343-fe7d-4ae5-8fa9-9fafd205e455',
                        '0000ff00-0000-1000-8000-00805f9b34fb',
                        '0000fee7-0000-1000-8000-00805f9b34fb',
                        '0000af30-0000-1000-8000-00805f9b34fb',
                        0x18f0,
                        0xff00,
                        0xfee7,
                        0xaf30
                    ]
                });
            } catch (filterErr) {
                // Jika user membatalkan (User cancelled), jangan request ulang
                if (filterErr.name === 'NotFoundError' && filterErr.message && filterErr.message.includes('User cancelled')) {
                    throw filterErr;
                }
                console.warn('Filter request failed, falling back to acceptAllDevices...', filterErr);
                device = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalServices: [
                        '000018f0-0000-1000-8000-00805f9b34fb',
                        'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
                        '49535343-fe7d-4ae5-8fa9-9fafd205e455',
                        '0000ff00-0000-1000-8000-00805f9b34fb',
                        '0000fee7-0000-1000-8000-00805f9b34fb',
                        '0000af30-0000-1000-8000-00805f9b34fb',
                        0x18f0,
                        0xff00,
                        0xfee7,
                        0xaf30
                    ]
                });
            }

            this.device = device;
            return await this._connectGatt();
        } catch (err) {
            this.isConnected = false;
            console.error('Connection Error:', err);
            throw err;
        }
    }

    /**
     * Internal GATT connection and characteristic discovery
     */
    async _connectGatt() {
        if (!this.device) {
            throw new Error('Device tidak ditemukan.');
        }

        this.printerName = this.device.name || 'Printer Bluetooth (' + this.TARGET_MAC + ')';
        console.log('Connecting to GATT Server:', this.printerName);

        this.device.addEventListener('gattserverdisconnected', () => {
            this.isConnected = false;
            this.characteristic = null;
            console.warn('Printer Bluetooth terputus.');
            if (typeof this.onDisconnected === 'function') {
                this.onDisconnected();
            }
        });

        this.server = await this.device.gatt.connect();

        // Save device ID and name for automatic future reconnection
        try {
            localStorage.setItem(this.STORAGE_KEY_DEVICE_ID, this.device.id);
            localStorage.setItem(this.STORAGE_KEY_DEVICE_NAME, this.printerName);
        } catch (e) { }

        // Find valid writable characteristic
        const services = await this.server.getPrimaryServices();
        for (const service of services) {
            const characteristics = await service.getCharacteristics();
            for (const char of characteristics) {
                if (char.properties.write || char.properties.writeWithoutResponse) {
                    this.characteristic = char;
                    break;
                }
            }
            if (this.characteristic) break;
        }

        if (!this.characteristic) {
            throw new Error('Tidak ditemukan writable characteristic pada printer ini.');
        }

        this.isConnected = true;
        return {
            name: this.printerName,
            id: this.device.id,
            mac: this.TARGET_MAC
        };
    }

    /**
     * Send raw buffer in chunks to avoid BLE characteristic buffer overflow
     */
    async sendBuffer(buffer) {
        if (!this.isConnected || !this.characteristic) {
            throw new Error('Printer belum terhubung via Bluetooth.');
        }

        const maxChunk = 100; // BLE safe MTU chunk size
        for (let i = 0; i < buffer.length; i += maxChunk) {
            const chunk = buffer.slice(i, i + maxChunk);
            if (this.characteristic.properties.writeWithoutResponse) {
                await this.characteristic.writeValueWithoutResponse(chunk);
            } else {
                await this.characteristic.writeValue(chunk);
            }
            await new Promise(r => setTimeout(r, 20));
        }
    }

    /**
     * Build ESC/POS command array for Courier QR Label
     * Optimized for S-Logic 48mm (384 dots width)
     */
    buildCourierLabelCommands({ trackingCode, codePart, namePart, rackNo, codeRack, area, cell, courierName, courierNik, createdAt }) {
        const encoder = new TextEncoder();
        let commands = [];

        // 1. Initialize printer (ESC @)
        commands.push(0x1B, 0x40);

        // 2. Center Align (ESC a 1)
        commands.push(0x1B, 0x61, 0x01);

        // Header
        commands.push(0x1B, 0x45, 0x01); // Bold ON
        this.addText(commands, encoder, "ISEKI STOCKOPNAME\n");
        this.addText(commands, encoder, "-- LABEL KURIR --\n\n"); // Jarak space sebelum QR
        commands.push(0x1B, 0x45, 0x00); // Bold OFF

        // QR Code data: part|name|rak|loc
        const locDisplay = (area || '-') + '-' + (cell || '-');
        const qrPayload = trackingCode || (codePart + '|' + namePart + '|' + rackNo + '|' + locDisplay);

        // QR Code Generation via ESC/POS GS ( k commands
        // Model 2, Size 5 (ideal for 48mm)
        this.addQRCode(commands, encoder, qrPayload, 5);

        // Line break after QR code without printing string text
        this.addText(commands, encoder, "\n\n");

        // Details - Left Align (ESC a 0)
        commands.push(0x1B, 0x61, 0x00);
        commands.push(0x1B, 0x45, 0x01); // Bold ON
        this.addText(commands, encoder, "PART: " + codePart + "\n");
        commands.push(0x1B, 0x45, 0x00); // Bold OFF
        this.addText(commands, encoder, "NAME: " + namePart.substring(0, 30) + "\n");
        this.addText(commands, encoder, "RAK : " + rackNo + "\n");
        this.addText(commands, encoder, "LOC : " + locDisplay + "\n");

        // PIC Kurir (hanya tampil di baris cetak, tidak di string QR)
        const pic = courierName || courierNik || '-';
        this.addText(commands, encoder, "PIC : " + pic + "\n");

        const dateStr = createdAt || new Date().toLocaleString('id-ID');
        this.addText(commands, encoder, "TIME: " + dateStr + "\n");

        // Separator line
        this.addText(commands, encoder, "--------------------------------\n");
        this.addText(commands, encoder, "STATUS: READY TO COUNT\n\n");

        // Feed paper minimal just enough to reach tear bar (1 line feed)
        commands.push(0x1B, 0x64, 0x01);

        return new Uint8Array(commands);
    }

    /**
     * Build ESC/POS command array for Final QR Label (Timbangan / Pos 2)
     */
    buildFinalLabelCommands({ trackingCode, codePart, namePart, rackNo, loc, qty, totalWithSample, totalWithoutSample, sampleQty, roundCount, roundDetails, method, user, partner, pic1, pic2, pos1Count, pos1Time, pos2Count, pos2Time, hasUpdates, updateHistory, pos1History, pos2History, scaleNo, isManual, completedAt, time }) {
        const encoder = new TextEncoder();
        let commands = [];
        const fmt = (v) => (v === null || v === undefined || v === '') ? '-' : Number(v).toString();

        commands.push(0x1B, 0x40); // Init
        commands.push(0x1B, 0x61, 0x01); // Center

        commands.push(0x1B, 0x45, 0x01); // Bold ON
        this.addText(commands, encoder, "ISEKI STOCKOPNAME\n");

        const isManualMode = isManual || (method && method.includes('MANUAL'));
        if (isManualMode) {
            this.addText(commands, encoder, "-- LABEL MANUAL --\n\n");
            commands.push(0x1B, 0x45, 0x00); // Bold OFF

            // QR Code Final payload Manual: part|name|rak|loc|qty
            const qrPayload = trackingCode && trackingCode.includes('|')
                ? `${trackingCode}|${qty}`
                : `${codePart}|${namePart}|${rackNo}|${loc || '-'}|${qty}`;
            this.addQRCode(commands, encoder, qrPayload, 5);
        } else {
            this.addText(commands, encoder, "-- LABEL FINAL OK --\n\n");
            commands.push(0x1B, 0x45, 0x00); // Bold OFF

            const finalQty = (totalWithSample !== null && totalWithSample !== undefined && totalWithSample !== '')
                ? fmt(totalWithSample)
                : fmt(qty);

            // QR Code Final payload Timbangan
            const qrPayload = (totalWithSample !== null && totalWithSample !== undefined && totalWithSample !== '')
                ? (trackingCode && trackingCode.includes('|')
                    ? `${trackingCode}|${finalQty}|${fmt(totalWithSample)}|${fmt(totalWithoutSample)}`
                    : `${codePart}|${namePart}|${rackNo}|${loc || '-'}|${finalQty}|${fmt(totalWithSample)}|${fmt(totalWithoutSample)}`)
                : (trackingCode && trackingCode.includes('|')
                    ? `${trackingCode}|${qty}`
                    : `${codePart}|${namePart}|${rackNo}|${loc || '-'}|${qty}`);
            this.addQRCode(commands, encoder, qrPayload, 5);
        }

        // Line break after QR code without string text
        this.addText(commands, encoder, "\n\n");

        // Qty Display (Double width & height)
        const displayQty = (!isManualMode && totalWithSample !== null && totalWithSample !== undefined && totalWithSample !== '')
            ? fmt(totalWithSample)
            : qty;
        commands.push(0x1D, 0x21, 0x11); // Double size
        commands.push(0x1B, 0x45, 0x01); // Bold ON
        this.addText(commands, encoder, "QTY: " + displayQty + "\n");
        commands.push(0x1D, 0x21, 0x00); // Normal size
        commands.push(0x1B, 0x45, 0x00); // Bold OFF

        commands.push(0x1B, 0x61, 0x00); // Left align

        if (!isManualMode && (totalWithSample !== null && totalWithSample !== undefined && totalWithSample !== '')) {
            commands.push(0x1B, 0x45, 0x01);
            this.addText(commands, encoder, "TOTAL +SAMPLE : " + fmt(totalWithSample) + " PCS\n");
            this.addText(commands, encoder, "TOTAL -SAMPLE : " + fmt(totalWithoutSample) + " PCS\n");
            commands.push(0x1B, 0x45, 0x00);
            this.addText(commands, encoder, "SAMPLE        : " + fmt(sampleQty) + " PCS\n");
            this.addText(commands, encoder, "HITUNGAN      : " + fmt(roundCount) + "X\n");
            this.addText(commands, encoder, "--------------------------------\n");
        }

        commands.push(0x1B, 0x45, 0x01);
        this.addText(commands, encoder, "PART: " + codePart + "\n");
        commands.push(0x1B, 0x45, 0x00);
        this.addText(commands, encoder, "NAME: " + namePart.substring(0, 30) + "\n");
        this.addText(commands, encoder, "RAK : " + rackNo + "\n");
        if (loc) {
            this.addText(commands, encoder, "LOC : " + loc + "\n");
        }

        // TYPE
        let typeDisplay = 'MANUAL POS 1 & 2';
        if (isManualMode) {
            typeDisplay = 'MANUAL POS 1 & 2';
        } else {
            typeDisplay = (method || 'TIMBANGAN').replace(/[\(\)\-]/g, '').trim();
            if (scaleNo && scaleNo !== '-') {
                typeDisplay += " " + scaleNo;
            }
        }
        this.addText(commands, encoder, "TYPE: " + typeDisplay + "\n");

        // PIC
        if (pic1 || pic2) {
            this.addText(commands, encoder, "PIC 1: " + (pic1 || '-') + "\n");
            this.addText(commands, encoder, "PIC 2: " + (pic2 || '-') + "\n");
        } else {
            this.addText(commands, encoder, "PIC  : " + user + (partner ? " & " + partner : "") + "\n");
        }

        if (isManualMode) {
            // Mode Manual: Format POS 1 dan POS 2 dengan waktu di sebelahnya
            const p1Str = pos1Count !== undefined && pos1Count !== null ? pos1Count : (pos1History ? pos1History.split(',').pop().trim() : '-');
            const p1TimeStr = pos1Time ? ` (${pos1Time})` : '';
            this.addText(commands, encoder, "POS 1: " + p1Str + p1TimeStr + "\n");

            const p2Str = pos2Count !== undefined && pos2Count !== null ? pos2Count : (pos2History ? pos2History.split(',').pop().trim() : (qty || '-'));
            const p2TimeStr = pos2Time ? ` (${pos2Time})` : '';
            this.addText(commands, encoder, "POS 2: " + p2Str + p2TimeStr + "\n");

            // Baris Riwayat Update: hanya jika pernah ada update hitungan
            if (updateHistory) {
                this.addText(commands, encoder, "Riwayat Update: " + updateHistory + "\n");
            }
        } else {
            // Mode Timbangan
            // Detail Timbangan jika ada
            if (roundDetails && Array.isArray(roundDetails) && roundDetails.length > 0) {
                this.addText(commands, encoder, "--------------------------------\n");
                commands.push(0x1B, 0x45, 0x01);
                this.addText(commands, encoder, "QTY SAMPLE: " + fmt(sampleQty) + " PCS\n");
                roundDetails.forEach(rd => {
                    this.addText(commands, encoder, "  ROUND " + rd.round_no + " : " + fmt(rd.weight) + " PCS\n");
                });
                this.addText(commands, encoder, "TOTAL TANPA SAMPLE: " + fmt(totalWithoutSample) + " PCS\n");
                commands.push(0x1B, 0x45, 0x00);
            }

            const printTime = completedAt || time || new Date().toLocaleString('id-ID');
            this.addText(commands, encoder, "TIME: " + printTime + "\n");
        }

        this.addText(commands, encoder, "--------------------------------\n");
        this.addText(commands, encoder, "STATUS: READY TO RETURN\n\n");

        // 1 line feed to tear bar
        commands.push(0x1B, 0x64, 0x01);

        return new Uint8Array(commands);
    }

    addText(cmdArray, encoder, text) {
        const bytes = encoder.encode(text);
        for (let b of bytes) {
            cmdArray.push(b);
        }
    }

    addQRCode(cmdArray, encoder, qrData, size = 5) {
        const qrBytes = encoder.encode(qrData);
        const len = qrBytes.length + 3;
        const pL = len % 256;
        const pH = Math.floor(len / 256);

        // Model 2
        cmdArray.push(0x1D, 0x28, 0x6B, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00);
        // Size (modul size)
        cmdArray.push(0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x43, size);
        // Error Correction Level M (0x31) - recovery up to 15% untuk mengatasi print thermal yang buram/pudar
        cmdArray.push(0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x45, 0x31);
        // Store data in symbol storage
        cmdArray.push(0x1D, 0x28, 0x6B, pL, pH, 0x31, 0x50, 0x30);
        for (let b of qrBytes) {
            cmdArray.push(b);
        }
        // Print symbol
        cmdArray.push(0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x51, 0x30);
    }

    async printCourierLabel(data) {
        const buffer = this.buildCourierLabelCommands(data);
        await this.sendBuffer(buffer);
    }

    async printFinalLabel(data) {
        const buffer = this.buildFinalLabelCommands(data);
        await this.sendBuffer(buffer);
    }
}

// Global instance for window
window.thermalPrinter = new ThermalPrinterBluetooth();
