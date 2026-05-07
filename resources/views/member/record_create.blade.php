@extends('layouts.main')

@section('style')
<style>
    #reader { width: 100%; margin: 0 auto; }
    .preview-images img { width: 100px; height: 100px; object-fit: cover; margin: 5px; }

    /* Mode Toggle */
    .mode-toggle { display: flex; border-radius: 8px; overflow: hidden; border: 2px solid #CE61C1; }
    .mode-toggle .mode-btn { flex: 1; padding: 10px; text-align: center; cursor: pointer; font-weight: 600; transition: all 0.3s ease; border: none; background: #fff; color: #CE61C1; }
    .mode-toggle .mode-btn.active { background: #CE61C1; color: #fff; }

    /* Scale Mode Styles */
    #scaleMode .scale-capture-area { border: 2px dashed #ccc; border-radius: 8px; padding: 20px; text-align: center; background: #f9f9f9; }
    #scaleMode .scale-capture-area.processing { border-color: #FFAD46; background: #fffdf5; }
    .ocr-result-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 15px; border: 1px solid #eee; border-radius: 6px; margin-bottom: 8px; background: #fff; }
    .ocr-result-item .ocr-value { font-size: 1.3rem; font-weight: 700; color: #1a2035; }
    .ocr-result-item .btn-remove-ocr { color: #F25961; border: none; background: none; font-size: 1.2rem; cursor: pointer; }
    .scale-total-bar { display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; background: linear-gradient(135deg, #CE61C1, #9b4d96); border-radius: 8px; color: #fff; font-weight: 700; font-size: 1.2rem; }
    #scalePreviewContainer { position: relative; max-width: 100%; }
    #scalePreviewContainer img { width: 100%; border-radius: 8px; border: 2px solid #CE61C1; }
    #scalePreviewContainer canvas { display: none; }
    .ocr-confirm-box { border: 2px solid #CE61C1; border-radius: 8px; padding: 15px; background: #fdf5fc; text-align: center; }
    .ocr-confirm-box .ocr-detected-value { font-size: 2rem; font-weight: 800; color: #1a2035; margin: 10px 0; }
    .ocr-confirm-box .ocr-edit-input { font-size: 1.5rem; font-weight: 700; text-align: center; max-width: 200px; margin: 0 auto; }
    .spinner-border-sm { width: 1rem; height: 1rem; border-width: 0.15em; }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Scan Record</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div id="reader-container" style="display: none;">
                            <div id="reader"></div>
                            <button type="button" class="btn btn-danger w-100 mt-2" id="stopScan">Stop Camera</button>
                        </div>
                        <button type="button" class="btn btn-primary w-100 mb-3" id="startScan">Start Scan</button>
                        <hr>
                        <form id="recordForm" action="{{ route('record.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label>NIK</label>
                                    <input type="text" value="{{ Auth::guard('member')->user()->nik }}" class="form-control" readonly>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Member Name</label>
                                    <input type="text" value="{{ Auth::guard('member')->user()->nama }}" class="form-control" readonly>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Code Rack</label>
                                    <input type="text" name="Code_Rack" id="Code_Rack" class="form-control" readonly required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>No Sequence</label>
                                    <input type="text" name="No_Sequence" id="No_Sequence" class="form-control" readonly required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Code Part</label>
                                    <input type="text" name="Code_Part" id="Code_Part" class="form-control" readonly required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Name Part</label>
                                    <input type="text" name="Name_Part" id="Name_Part" class="form-control" readonly required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Area</label>
                                    <input type="text" name="Area" id="Area" class="form-control" readonly required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>No Card</label>
                                    <input type="text" name="No_Card" id="No_Card" class="form-control" readonly required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Location</label>
                                    <input type="text" name="Location" id="Location" class="form-control" readonly required>
                                </div>

                                <!-- Count Record Section -->
                                <div class="col-md-12 mb-3">
                                    <label class="mb-2"><strong>Count Record Mode</strong></label>
                                    <div class="mode-toggle mb-3">
                                        <button type="button" class="mode-btn active" data-mode="manual">
                                            <i class="fas fa-keyboard"></i> Manual
                                        </button>
                                        <button type="button" class="mode-btn" data-mode="scale">
                                            <i class="fas fa-weight"></i> Scale (OCR)
                                        </button>
                                    </div>

                                    <input type="hidden" name="Count_Record" id="Count_Record_Final" required>

                                    <!-- Manual Mode -->
                                    <div id="manualMode">
                                        <div class="mb-3">
                                            <label>Count Record <span class="text-danger">*</span></label>
                                            <input type="number" id="Count_Record_Manual" class="form-control" placeholder="Enter actual count">
                                        </div>
                                        <div class="mb-3">
                                            <label>Count Record Validation <span class="text-danger">*</span></label>
                                            <input type="number" id="Count_Record_Validation" class="form-control" placeholder="Re-enter count to confirm">
                                            <div id="count-validation-msg" class="invalid-feedback">Count does not match. Please re-enter.</div>
                                        </div>
                                    </div>

                                    <!-- Scale Mode -->
                                    <div id="scaleMode" style="display: none;">
                                        <div class="scale-capture-area mb-3" id="scaleCaptureArea">
                                            <div id="scaleCapturePrompt">
                                                <i class="fas fa-camera" style="font-size: 2rem; color: #ccc;"></i>
                                                <p class="mt-2 mb-0 text-muted">Capture the scale display</p>
                                                <input type="file" id="scalePhotoInput" accept="image/*" capture="environment" style="display:none;">
                                                <button type="button" class="btn btn-primary mt-2" id="btnCaptureScale">
                                                    <i class="fas fa-camera"></i> Capture Scale
                                                </button>
                                            </div>

                                            <div id="scaleProcessing" style="display: none;">
                                                <div class="spinner-border text-primary" role="status"></div>
                                                <p class="mt-2 mb-0 text-muted">Processing image with OCR...</p>
                                            </div>

                                            <div id="scalePreviewContainer" style="display: none;">
                                                <p class="text-muted mb-1"><small>Processed Image Preview:</small></p>
                                                <canvas id="scaleCanvas" style="width: 100%; border: 2px solid #CE61C1; border-radius: 8px;"></canvas>
                                            </div>

                                            <div id="ocrConfirmBox" class="ocr-confirm-box mt-3" style="display: none;">
                                                <p class="mb-1 text-muted">Detected Value:</p>
                                                <input type="number" id="ocrDetectedValue" class="form-control ocr-edit-input" step="any">
                                                <small class="text-muted">Edit if incorrect</small>
                                                <div class="mt-3 d-flex gap-2 justify-content-center">
                                                    <button type="button" class="btn btn-success" id="btnConfirmOcr">
                                                        <i class="fas fa-check"></i> Confirm
                                                    </button>
                                                    <button type="button" class="btn btn-warning" id="btnRetakeOcr">
                                                        <i class="fas fa-redo"></i> Retake
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="ocrResultsList" class="mb-3"></div>

                                        <button type="button" class="btn btn-outline-primary w-100 mb-3" id="btnAddMoreCount" style="display: none;">
                                            <i class="fas fa-plus"></i> Add More Count
                                        </button>

                                        <div class="scale-total-bar" id="scaleTotalBar" style="display: none;">
                                            <span>Total Count:</span>
                                            <span id="scaleTotalValue">0</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label>Photos (Multiple)</label>
                                    <input type="file" name="photos[]" id="photos" class="form-control" multiple accept="image/*" capture="environment">
                                    <div class="preview-images mt-2"></div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info w-100 mt-3" id="submitBtn">Submit Record</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('assets/js/plugin/html5-qrcode.min.js') }}"></script>
<script async src="{{ asset('assets/js/plugin/opencv.js') }}" onload="onOpenCvReady();"></script>
<script>
    // ==========================================
    // OPENCV.JS SSOCR SETUP
    // ==========================================
    
    let cvReady = false;
    function onOpenCvReady() {
        cvReady = true;
        console.log('OpenCV.js loaded successfully.');
    }

    const DIGITS_LOOKUP = {
        "1,1,1,1,1,1,0": "0",
        "1,1,0,0,0,0,0": "1",
        "1,0,1,1,0,1,1": "2",
        "1,1,1,0,0,1,1": "3",
        "1,1,0,0,1,0,1": "4",
        "0,1,1,0,1,1,1": "5",
        "0,1,1,1,1,1,1": "6",
        "1,1,0,0,0,1,0": "7",
        "1,1,1,1,1,1,1": "8",
        "1,1,1,0,1,1,1": "9",
        "0,0,0,0,0,1,1": "-"
    };

    // Parameter untuk Threshold (batas kontras hitam putih)
    const OCR_THRESHOLD = 35;
    // ==========================================

    let html5QrcodeScanner = null;
    let currentMode = 'manual';
    let ocrResults = [];
    let ocrIdCounter = 0;

    function onScanSuccess(decodedText, decodedResult) {
        const parts = decodedText.split('|');
        if (parts.length >= 6) {
            document.getElementById('Code_Part').value = parts[0];
            document.getElementById('Name_Part').value = parts[1];

            const rackParts = parts[2].split(';');
            document.getElementById('Code_Rack').value = rackParts[0] || '';
            document.getElementById('No_Sequence').value = rackParts[1] || '';

            document.getElementById('Area').value = parts[3];
            document.getElementById('No_Card').value = parts[4];
            document.getElementById('Location').value = parts[5];

            $('#recordForm input[readonly]').addClass('is-valid');
            stopCamera();
        } else {
            alert('Invalid QR Format');
        }
    }

    $('#startScan').on('click', function() {
        $('#reader-container').show();
        $(this).hide();
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
        }
        html5QrcodeScanner.render(onScanSuccess);
    });

    $('#stopScan').on('click', function() { stopCamera(); });

    function stopCamera() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().then(() => {
                $('#reader-container').hide();
                $('#startScan').show();
            }).catch(err => {
                console.error(err);
                $('#reader-container').hide();
                $('#startScan').show();
            });
        }
    }

    $('.mode-btn').on('click', function() {
        const mode = $(this).data('mode');
        if (mode === currentMode) return;
        currentMode = mode;
        $('.mode-btn').removeClass('active');
        $(this).addClass('active');

        if (mode === 'manual') {
            $('#manualMode').show();
            $('#scaleMode').hide();
        } else {
            $('#manualMode').hide();
            $('#scaleMode').show();
        }
    });

    $('#photos').on('change', function() {
        $('.preview-images').empty();
        const files = this.files;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();
            reader.onload = function(e) { $('.preview-images').append(`<img src="${e.target.result}">`); }
            reader.readAsDataURL(file);
        }
    });

    $('#Count_Record_Validation').on('input', function() {
        const count = $('#Count_Record_Manual').val();
        const validation = $(this).val();
        if (validation && count && validation !== count) {
            $(this).addClass('is-invalid').removeClass('is-valid');
        } else if (validation && count && validation === count) {
            $(this).addClass('is-valid').removeClass('is-invalid');
        }
    });

    $('#btnCaptureScale').on('click', function() { $('#scalePhotoInput').click(); });

    $('#btnAddMoreCount').on('click', function() {
        $('#scalePreviewContainer').hide();
        $('#ocrConfirmBox').hide();
        $('#scaleCapturePrompt').show();
        $('#scalePhotoInput').click();
    });

    $('#scalePhotoInput').on('change', function() {
        const file = this.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            $('#scalePreviewImg').hide(); // Sembunyikan preview gambar asli
            $('#scaleCapturePrompt').hide();
            $('#scalePreviewContainer').show();
            $('#scaleProcessing').show();
            $('#ocrConfirmBox').hide();
            processOCR(e.target.result);
        }
        reader.readAsDataURL(file);
    });

    function preprocess(srcMat) {
        // srcMat adalah matriks RGBA asli
        
        // 1. ISOLASI WARNA HIJAU (LED)
        // Kita gunakan HSV karena lebih stabil terhadap perubahan cahaya
        let hsv = new cv.Mat();
        cv.cvtColor(srcMat, hsv, cv.COLOR_RGBA2RGB); 
        cv.cvtColor(hsv, hsv, cv.COLOR_RGB2HSV);
        
        // Range Hijau LED (Hue: 35-95, Saturation: 50-255, Value: 50-255)
        let lowerGreen = new cv.Scalar(35, 40, 40); 
        let upperGreen = new cv.Scalar(100, 255, 255);
        let mask = new cv.Mat();
        cv.inRange(hsv, lowerGreen, upperGreen, mask);
        
        // 2. PENINGKATAN KONTRAS (Dilation/Erosion)
        // Membantu menyatukan segmen angka yang terputus-putus
        let kernel = cv.getStructuringElement(cv.MORPH_ELLIPSE, new cv.Size(3, 3));
        cv.morphologyEx(mask, mask, cv.MORPH_CLOSE, kernel);
        
        // 3. CLEANUP
        hsv.delete();
        kernel.delete();
        
        return mask; // Hasilnya biner: Angka Hijau = Putih, Lainnya (Kertas Putih/Body Hitam) = Hitam
    }

    function getProjectionProfile(mat, axis) {
        let size = axis === 0 ? mat.cols : mat.rows;
        let other = axis === 0 ? mat.rows : mat.cols;
        let profile = new Float32Array(size);
        for (let i = 0; i < size; i++) {
            let sum = 0;
            for (let j = 0; j < other; j++) {
                let row = axis === 0 ? j : i;
                let col = axis === 0 ? i : j;
                sum += mat.ucharPtr(row, col)[0];
            }
            profile[i] = sum;
        }
        return profile;
    }

    function helperExtract(array, threshold = 20) {
        let res = [];
        let flag = 0;
        let temp = 0;
        let noiseThreshold = 10 * 255; 

        for (let i = 0; i < array.length; i++) {
            if (array[i] < noiseThreshold) {
                if (flag >= threshold) {
                    let start = i - flag;
                    let end = i;
                    temp = end;
                    if (end - start > 10) { 
                        res.push([start, end]);
                    }
                }
                flag = 0;
            } else {
                flag += 1;
            }
        }
        if (flag >= threshold) {
            let start = temp;
            let end = array.length;
            if (end - start > 10) {
                res.push([start, end]);
            }
        }
        return res;
    }

    function autoCropDigits(binaryImg, grayImg) {
        // Kita gunakan grayImg untuk mendeteksi 'Kotak Hitam' LED di tengah 'Kertas Putih'
        let boxBinary = new cv.Mat();
        // Threshold tinggi: Kertas Putih (255) vs Layar LED Gelap (0)
        // THRESH_BINARY_INV akan membuat Layar LED menjadi Putih (untuk dideteksi konturnya)
        cv.threshold(grayImg, boxBinary, 170, 255, cv.THRESH_BINARY_INV);
        
        let contours = new cv.MatVector();
        let hierarchy = new cv.Mat();
        cv.findContours(boxBinary, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_SIMPLE);

        let maxArea = 0;
        let bestRect = null;

        for (let i = 0; i < contours.size(); ++i) {
            let cnt = contours.get(i);
            let rect = cv.boundingRect(cnt);
            let area = rect.width * rect.height;
            let aspect = rect.width / rect.height;
            
            // Layar LED biasanya persegi panjang lebar (aspect > 1.2) dan luas
            if (area > maxArea && aspect > 1.2 && area > 1000) {
                maxArea = area;
                bestRect = rect;
            }
        }
        
        boxBinary.delete(); contours.delete(); hierarchy.delete();

        if (bestRect) {
            // Kita sudah menemukan kotak LED, sekarang kita crop binaryImg (hasil filter hijau) di area itu
            return binaryImg.roi(bestRect);
        }
        
        // Jika tidak ketemu kotak khusus, gunakan deteksi digit biasa sebagai cadangan
        return binaryImg.clone();
    }

    function findDigitsPositions(img) {
        let digits_positions = [];
        let horizon_array = getProjectionProfile(img, 0);
        let horizon_position = helperExtract(horizon_array, 10);
        
        let vertical_array = getProjectionProfile(img, 1);
        let vertical_position = helperExtract(vertical_array, 40);
        
        if (vertical_position.length > 1) {
            vertical_position = [[vertical_position[0][0], vertical_position[vertical_position.length - 1][1]]];
        }
        if (vertical_position.length === 0) {
            vertical_position = [[0, img.rows]];
        }

        for (let h of horizon_position) {
            for (let v of vertical_position) {
                digits_positions.push({ x0: h[0], x1: h[1], y0: v[0], y1: v[1] });
            }
        }
        digits_positions.sort((a, b) => a.x0 - b.x0);
        return digits_positions;
    }

    function recognizeDigits(digits_positions, srcImg) {
        let digits = [];
        let H_W_Ratio = 1.9;
        let arc_tan_theta = 6.0;
        
        for (let c of digits_positions) {
            let x0 = c.x0;
            let x1 = c.x1;
            let y0 = c.y0;
            let y1 = c.y1;
            
            let w = x1 - x0;
            let h = y1 - y0;
            if (w <= 0 || h <= 0) continue;

            let roiRect = new cv.Rect(x0, y0, w, h);
            let roi = srcImg.roi(roiRect);
            let suppose_W = Math.max(1, Math.floor(h / H_W_Ratio));
            
            let nonZero = cv.countNonZero(roi);
            if (w < 25 && nonZero / (h * w) < 0.2) {
                roi.delete();
                continue;
            }
            
            if (w < suppose_W / 2) {
                x0 = Math.max(x0 + w - suppose_W, 0);
                roi.delete();
                w = x1 - x0;
                roiRect = new cv.Rect(x0, y0, w, h);
                roi = srcImg.roi(roiRect);
            }
            
            let center_y = Math.floor(h / 2);
            let quater_y_1 = Math.floor(h / 4);
            let quater_y_3 = quater_y_1 * 3;
            let center_x = Math.floor(w / 2);
            let line_width = 5;
            let width = Math.floor((Math.max(w * 0.15, 1) + Math.max(h * 0.15, 1)) / 2);
            let small_delta = Math.floor((h / arc_tan_theta) / 4);
            
            function clip(v, maxV) { return Math.max(0, Math.min(v, maxV)); }
            
            let segments = [
                [w - 2 * width, quater_y_1 - line_width, w, quater_y_1 + line_width],
                [w - 2 * width, quater_y_3 - line_width, w, quater_y_3 + line_width],
                [center_x - line_width - small_delta, h - 2 * width, center_x - small_delta + line_width, h],
                [0, quater_y_3 - line_width, 2 * width, quater_y_3 + line_width],
                [0, quater_y_1 - line_width, 2 * width, quater_y_1 + line_width],
                [center_x - line_width, 0, center_x + line_width, 2 * width],
                [center_x - line_width, center_y - line_width, center_x + line_width, center_y + line_width]
            ];
            
            let on = [];
            for (let i = 0; i < segments.length; i++) {
                let seg = segments[i];
                let xa = clip(seg[0], w);
                let ya = clip(seg[1], h);
                let xb = clip(seg[2], w);
                let yb = clip(seg[3], h);
                
                if (xb <= xa || yb <= ya) {
                    on.push(0);
                    continue;
                }
                
                let segRect = new cv.Rect(xa, ya, xb - xa, yb - ya);
                let segRoi = roi.roi(segRect);
                let total = cv.countNonZero(segRoi);
                let area = (xb - xa) * (yb - ya) * 0.9;
                on.push((total / area > 0.25) ? 1 : 0);
                segRoi.delete();
            }
            
            let key = on.join(',');
            let digit = DIGITS_LOOKUP[key] !== undefined ? DIGITS_LOOKUP[key] : '*';
            digits.push(digit);
            
            let dot_x0 = clip(w - Math.floor(3 * width / 4), w);
            let dot_y0 = clip(h - Math.floor(3 * width / 4), h);
            let dot_w = w - dot_x0;
            let dot_h = h - dot_y0;
            
            if (dot_w > 0 && dot_h > 0) {
                let dotRect = new cv.Rect(dot_x0, dot_y0, dot_w, dot_h);
                let dotRoi = roi.roi(dotRect);
                let dotTotal = cv.countNonZero(dotRoi);
                let dotArea = (9.0 / 16.0) * width * width;
                if (dotTotal / dotArea > 0.65) {
                    digits.push('.');
                }
                dotRoi.delete();
            }
            roi.delete();
        }
        return digits;
    }

    async function processOCR(imageDataUrl) {
        try {
            if (!cvReady) {
                alert('OpenCV.js is still loading. Please wait a moment.');
                $('#scaleProcessing').hide();
                $('#scaleCapturePrompt').show();
                return;
            }

            const imgElement = new Image();
            imgElement.onload = function() {
                let src = cv.imread(imgElement);
                
                // Resize for consistency
                let ratio = 300 / src.rows;
                let newWidth = Math.max(10, Math.floor(src.cols * ratio));
                cv.resize(src, src, new cv.Size(newWidth, 300), 0, 0, cv.INTER_AREA);

                let gray = new cv.Mat();
                cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY, 0);
                
                // Advanced Preprocessing: Langsung isolasi warna hijau dari matriks asli
                let dst = preprocess(src);

                // Otomatis melakukan crop pada area kotak LED (berdasarkan kontras dengan kertas putih)
                let croppedDst = autoCropDigits(dst, gray);

                // Show threshold image for user debugging (sekarang menampilkan gambar yang sudah di-crop)
                cv.imshow('scaleCanvas', croppedDst);
                $('#scaleCanvas').show();

                let positions = findDigitsPositions(croppedDst);
                let decoded = recognizeDigits(positions, croppedDst);

                console.log('OpenCV Raw Output:', decoded.join(''));

                let cleanText = decoded.join('').replace(/[^0-9.]/g, '').replace(/^\.+|\.+$/g, '');
                const dotIndex = cleanText.indexOf('.');
                if (dotIndex !== -1) {
                    cleanText = cleanText.substring(0, dotIndex + 1) + cleanText.substring(dotIndex + 1).replace(/\./g, '');
                }

                const numericValue = parseFloat(cleanText) || 0;
                
                
                // Cleanup
                src.delete(); gray.delete(); dst.delete(); croppedDst.delete();

                $('#scaleProcessing').hide();
                $('#ocrDetectedValue').val(numericValue);
                $('#ocrConfirmBox').show();
                
                if (numericValue === 0 && cleanText !== '') {
                    console.warn('OCR detected text but failed to parse to number:', cleanText);
                }
            };
            imgElement.src = imageDataUrl;
        } catch (err) {
            console.error('OCR Error:', err);
            $('#scaleProcessing').hide();
            $('#ocrDetectedValue').val(0);
            $('#ocrConfirmBox').show();
            alert('OCR processing failed. Check console for details.');
        }
    }

    $('#btnConfirmOcr').on('click', function() {
        const value = parseFloat($('#ocrDetectedValue').val()) || 0;
        ocrIdCounter++;
        ocrResults.push({ id: ocrIdCounter, value: value });
        renderOcrResults();

        $('#scalePreviewContainer').hide();
        $('#ocrConfirmBox').hide();
        $('#scaleCapturePrompt').show();
        $('#scalePhotoInput').val('');

        $('#btnAddMoreCount').show();
        $('#scaleTotalBar').show();
    });

    $('#btnRetakeOcr').on('click', function() {
        $('#scalePreviewContainer').hide();
        $('#ocrConfirmBox').hide();
        $('#scaleCapturePrompt').show();
        $('#scalePhotoInput').val('');
        $('#scalePhotoInput').click();
    });

    function renderOcrResults() {
        const container = $('#ocrResultsList');
        container.empty();
        ocrResults.forEach((item, index) => {
            container.append(`
                <div class="ocr-result-item" data-id="${item.id}">
                    <div>
                        <small class="text-muted">#${index + 1}</small>
                        <span class="ocr-value ms-2">${item.value}</span>
                    </div>
                    <button type="button" class="btn-remove-ocr" onclick="removeOcrResult(${item.id})">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `);
        });
        updateScaleTotal();
    }

    function removeOcrResult(id) {
        ocrResults = ocrResults.filter(item => item.id !== id);
        renderOcrResults();
        if (ocrResults.length === 0) {
            $('#btnAddMoreCount').hide();
            $('#scaleTotalBar').hide();
        }
    }

    function updateScaleTotal() {
        const total = ocrResults.reduce((sum, item) => sum + item.value, 0);
        const rounded = Math.round(total * 100) / 100;
        $('#scaleTotalValue').text(rounded);
    }

    $('#recordForm').on('submit', function(e) {
        if (currentMode === 'manual') {
            const count = $('#Count_Record_Manual').val();
            const validation = $('#Count_Record_Validation').val();

            if (!count) {
                e.preventDefault();
                alert('Please enter count record.');
                $('#Count_Record_Manual').focus();
                return;
            }

            if (count !== validation) {
                e.preventDefault();
                $('#Count_Record_Validation').addClass('is-invalid').removeClass('is-valid');
                $('#count-validation-msg').show();
                $('#Count_Record_Validation').focus();
                return;
            }

            $('#Count_Record_Final').val(count);
        } else {
            if (ocrResults.length === 0) {
                e.preventDefault();
                alert('Please capture at least one scale reading.');
                return;
            }
            const total = ocrResults.reduce((sum, item) => sum + item.value, 0);
            const rounded = Math.round(total * 100) / 100;
            $('#Count_Record_Final').val(rounded);
        }
    });
</script>
@endsection
