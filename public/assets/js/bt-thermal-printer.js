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
                        d.name && d.name === 'BlueTooth Printer'
                    ) || devices.find(d => 
                        d.name && d.name.toLowerCase().includes('bluetooth printer')
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
            throw new Error('Web Bluetooth tidak didukung pada browser ini. Gunakan Google Chrome di Android.');
        }

        try {
            console.log('Requesting Bluetooth Device with exact name "BlueTooth Printer"...');
            
            // Exact name filter to show only this specific printer
            this.device = await navigator.bluetooth.requestDevice({
                filters: [
                    { name: 'BlueTooth Printer' },
                    { namePrefix: 'BlueTooth Printer' }
                ],
                optionalServices: [
                    '000018f0-0000-1000-8000-00805f9b34fb', // Standard Serial/Printer Service
                    'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
                    '49535343-fe7d-4ae5-8fa9-9fafd205e455', // ISSC
                    '0000ff00-0000-1000-8000-00805f9b34fb',
                    0x18f0,
                    0xff00
                ],
                acceptAllDevices: false
            });

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

        this.printerName = this.device.name || 'S-Logic 48mm (' + this.TARGET_MAC + ')';
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
        } catch (e) {}

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
    buildCourierLabelCommands({ trackingCode, codePart, namePart, rackNo, codeRack, area, cell }) {
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
        this.addText(commands, encoder, "TGL : " + new Date().toLocaleString('id-ID') + "\n");

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
    buildFinalLabelCommands({ trackingCode, codePart, namePart, rackNo, qty, method, user, partner, scaleNo }) {
        const encoder = new TextEncoder();
        let commands = [];

        commands.push(0x1B, 0x40); // Init
        commands.push(0x1B, 0x61, 0x01); // Center

        commands.push(0x1B, 0x45, 0x01); // Bold ON
        this.addText(commands, encoder, "ISEKI STOCKOPNAME\n");
        this.addText(commands, encoder, "-- VERIFIED OK --\n");
        commands.push(0x1B, 0x45, 0x00); // Bold OFF

        // QR Code Final
        this.addQRCode(commands, encoder, trackingCode, 5);

        // Qty Display (Double width & height)
        commands.push(0x1D, 0x21, 0x11); // Double size
        commands.push(0x1B, 0x45, 0x01); // Bold ON
        this.addText(commands, encoder, "QTY: " + qty + "\n");
        commands.push(0x1D, 0x21, 0x00); // Normal size
        commands.push(0x1B, 0x45, 0x00); // Bold OFF

        commands.push(0x1B, 0x61, 0x00); // Left align
        commands.push(0x1B, 0x45, 0x01);
        this.addText(commands, encoder, "PART: " + codePart + "\n");
        commands.push(0x1B, 0x45, 0x00);
        this.addText(commands, encoder, "NAME: " + namePart.substring(0, 30) + "\n");
        this.addText(commands, encoder, "RAK : " + rackNo + "\n");
        this.addText(commands, encoder, "TYPE: " + method + (scaleNo ? " (" + scaleNo + ")" : "") + "\n");
        this.addText(commands, encoder, "PIC : " + user + (partner ? " & " + partner : "") + "\n");
        this.addText(commands, encoder, "TIME: " + new Date().toLocaleString('id-ID') + "\n");
        this.addText(commands, encoder, "--------------------------------\n");
        this.addText(commands, encoder, "SIAP DIKEMBALIKAN KE RAK\n\n\n\n");

        commands.push(0x1B, 0x64, 0x03);

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

        // Model
        cmdArray.push(0x1D, 0x28, 0x6B, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00);
        // Size
        cmdArray.push(0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x43, size);
        // Error Correction level L
        cmdArray.push(0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x45, 0x30);
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
