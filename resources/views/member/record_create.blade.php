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

    /* Custom Camera Styles */
    .camera-container { position: relative; width: 100%; max-width: 500px; margin: 0 auto; overflow: hidden; border-radius: 12px; background: #000; line-height: 0; display: none; }
    .camera-container.camera-fullscreen { max-width: 100%; min-height: 400px; }
    .camera-container.camera-fullscreen video { min-height: 400px; object-fit: cover; }
    #scaleVideo, #countVideo { width: 100%; height: auto; }
    .camera-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; display: flex; flex-direction: column; }
    
    /* Transparent cutout effect */
    .camera-overlay-top, .camera-overlay-bottom { flex: 1; background: rgba(0,0,0,0.6); }
    .camera-overlay-middle { display: flex; height: 120px; }
    .camera-overlay-left, .camera-overlay-right { flex: 1; background: rgba(0,0,0,0.6); }
    .camera-guide-box { width: 260px; height: 120px; border: 3px solid #CE61C1; border-radius: 8px; box-shadow: 0 0 0 5000px rgba(0,0,0,0); position: relative; }
    .camera-guide-box::after { content: "Align Scale Display Here"; position: absolute; top: -30px; left: 0; width: 100%; text-align: center; color: #CE61C1; font-weight: bold; font-size: 12px; text-shadow: 0 1px 3px rgba(0,0,0,0.5); }

    .camera-controls { position: absolute; bottom: 20px; left: 0; width: 100%; display: flex; justify-content: center; gap: 15px; pointer-events: auto; }
    .btn-capture { width: 60px; height: 60px; border-radius: 50%; border: 5px solid #fff; background: #CE61C1; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 10px rgba(0,0,0,0.3); transition: transform 0.1s; }
    .btn-capture:active { transform: scale(0.9); }
    .btn-camera-action { width: 45px; height: 45px; border-radius: 50%; background: rgba(255,255,255,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px); }

    /* Count Mode Styles */
    #countMode .count-capture-area { border: 2px dashed #ccc; border-radius: 8px; padding: 20px; text-align: center; background: #f9f9f9; }
    .count-canvas-wrapper { position: relative; width: 100%; max-width: 500px; margin: 0 auto; cursor: crosshair; }
    #countCanvas { width: 100%; border-radius: 8px; border: 2px solid #CE61C1; display: block; }
    .count-instructions { background: linear-gradient(135deg, #CE61C1, #9b4d96); color: #fff; padding: 10px 15px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 10px; text-align: center; }
    .count-instructions i { margin-right: 5px; }
    .count-badge { position: absolute; top: 10px; right: 10px; background: #CE61C1; color: #fff; font-size: 1.5rem; font-weight: 800; padding: 8px 16px; border-radius: 12px; box-shadow: 0 4px 12px rgba(206,97,193,0.4); z-index: 10; }
    .count-controls { display: flex; gap: 10px; justify-content: center; margin-top: 10px; flex-wrap: wrap; }
    .count-controls .btn { font-size: 0.85rem; }
    .count-processing-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-direction: column; color: #fff; z-index: 20; }
    .count-sensitivity { display: flex; align-items: center; gap: 10px; justify-content: center; margin-top: 10px; font-size: 0.85rem; }
    .count-sensitivity input[type=range] { width: 150px; }

    /* AI Model Loading Styles */
    .count-model-loading { text-align: center; padding: 15px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 8px; color: #fff; margin-bottom: 10px; }
    .count-model-loading .spinner-border { width: 1.5rem; height: 1.5rem; }
    .count-encoding-status { text-align: center; padding: 10px; background: #fdf5fc; border: 1px solid #CE61C1; border-radius: 8px; margin-bottom: 10px; font-size: 0.85rem; color: #9b4d96; }
    .count-encoding-status .spinner-border { width: 1rem; height: 1rem; margin-right: 5px; }
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
                                            <i class="fas fa-weight"></i> Scale
                                        </button>
                                        <button type="button" class="mode-btn" data-mode="count">
                                            <i class="fas fa-calculator"></i> Count
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
                                            <!-- Hidden Fallback Input -->
                                            <input type="file" id="scalePhotoInput" accept="image/*" capture="environment" style="display:none;">

                                            <div id="scaleCapturePrompt">
                                                <i class="fas fa-camera" style="font-size: 2rem; color: #ccc;"></i>
                                                <p class="mt-2 mb-2 text-muted">Capture the scale display</p>
                                                <button type="button" class="btn btn-primary" id="btnStartScaleCamera">
                                                    <i class="fas fa-video"></i> Open Scanner
                                                </button>
                                                <p class="mt-2 mb-0"><small class="text-muted">or <a href="#" id="linkUseFileFallback">upload photo</a></small></p>
                                            </div>

                                            <!-- Custom Camera View -->
                                            <div class="camera-container" id="scaleCameraContainer">
                                                <video id="scaleVideo" autoplay playsinline></video>
                                                <div class="camera-overlay">
                                                    <div class="camera-overlay-top"></div>
                                                    <div class="camera-overlay-middle">
                                                        <div class="camera-overlay-left"></div>
                                                        <div class="camera-guide-box" id="scaleGuideBox"></div>
                                                        <div class="camera-overlay-right"></div>
                                                    </div>
                                                    <div class="camera-overlay-bottom"></div>
                                                </div>
                                                <div class="camera-controls">
                                                    <button type="button" class="btn-camera-action" id="btnCloseScaleCamera">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <button type="button" class="btn-capture" id="btnTakeScalePhoto">
                                                        <i class="fas fa-camera"></i>
                                                    </button>
                                                    <button type="button" class="btn-camera-action" id="btnSwitchCamera">
                                                        <i class="fas fa-sync"></i>
                                                    </button>
                                                </div>
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

                                    <!-- Count Mode -->
                                    <div id="countMode" style="display: none;">
                                        <div class="count-capture-area mb-3" id="countCaptureArea">
                                            <input type="file" id="countPhotoInput" accept="image/*" capture="environment" style="display:none;">

                                            <div id="countCapturePrompt">
                                                <i class="fas fa-cubes" style="font-size: 2rem; color: #ccc;"></i>
                                                <p class="mt-2 mb-2 text-muted">Take a photo of items to count</p>
                                                <button type="button" class="btn btn-primary" id="btnStartCountCamera">
                                                    <i class="fas fa-video"></i> Open Camera
                                                </button>
                                                <p class="mt-2 mb-0"><small class="text-muted">or <a href="#" id="linkCountFileFallback">upload photo</a></small></p>
                                            </div>

                                            <!-- Count Camera View (reuses camera styles) -->
                                            <div class="camera-container camera-fullscreen" id="countCameraContainer">
                                                <video id="countVideo" autoplay playsinline></video>
                                                <div class="camera-controls">
                                                    <button type="button" class="btn-camera-action" id="btnCloseCountCamera">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <button type="button" class="btn-capture" id="btnTakeCountPhoto">
                                                        <i class="fas fa-camera"></i>
                                                    </button>
                                                    <button type="button" class="btn-camera-action" id="btnSwitchCountCamera">
                                                        <i class="fas fa-sync"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Count Interactive Canvas -->
                                            <div id="countCanvasArea" style="display: none;">
                                                <!-- AI Model Loading -->
                                                <div class="count-model-loading" id="countModelLoading" style="display:none;">
                                                    <div class="spinner-border text-light" role="status"></div>
                                                    <p class="mt-2 mb-0">Loading AI model... <span id="countModelProgress"></span></p>
                                                </div>
                                                <!-- Encoding Status -->
                                                <div class="count-encoding-status" id="countEncodingStatus" style="display:none;">
                                                    <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                                    Analyzing image... <span id="countEncodingProgress"></span>
                                                </div>
                                                <div class="count-instructions" id="countInstruction">
                                                    <i class="fas fa-hand-pointer"></i> Tap on one item to select it. The AI will segment and count all similar items.
                                                </div>
                                                <div class="count-canvas-wrapper">
                                                    <canvas id="countCanvas"></canvas>
                                                    <div class="count-badge" id="countBadge" style="display:none;">0</div>
                                                    <div class="count-processing-overlay" id="countProcessing" style="display:none;">
                                                        <div class="spinner-border text-light" role="status"></div>
                                                        <p class="mt-2 mb-0" id="countProcessingText">Analyzing objects...</p>
                                                    </div>
                                                </div>
                                                <div class="count-sensitivity" id="countSensitivityArea" style="display:none;">
                                                    <small>Sensitivity:</small>
                                                    <input type="range" id="countThreshold" min="50" max="95" value="75" step="1">
                                                    <small id="countThresholdLabel">75%</small>
                                                </div>
                                                <div class="count-controls mt-3">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnCountRetake">
                                                        <i class="fas fa-redo"></i> Retake
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCountClear">
                                                        <i class="fas fa-eraser"></i> Clear
                                                    </button>
                                                    <button type="button" class="btn btn-success btn-sm" id="btnCountConfirm" style="display:none;">
                                                        <i class="fas fa-check"></i> Confirm Count
                                                    </button>
                                                </div>
                                                <p class="text-muted mt-2"><small><i class="fas fa-info-circle"></i> Click detected markers to remove false positives. Click empty areas to add missed items.</small></p>
                                            </div>
                                        </div>

                                        <div id="countResultsList" class="mb-3"></div>

                                        <button type="button" class="btn btn-outline-primary w-100 mb-3" id="btnAddMoreCountMode" style="display: none;">
                                            <i class="fas fa-plus"></i> Add More Count
                                        </button>

                                        <div class="scale-total-bar" id="countTotalBar" style="display: none;">
                                            <span>Total Count:</span>
                                            <span id="countTotalValue">0</span>
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
<script>
    // Define OpenCV callback BEFORE loading the script
    let cvReady = false;
    function onOpenCvReady() {
        cvReady = true;
        console.log('OpenCV.js loaded successfully.');
    }
    // Base URL for asset loading (used by SAM modules)
    var baseUrl = "{{ asset('') }}";
</script>
<script src="{{ asset('assets/js/plugin/html5-qrcode.min.js') }}"></script>
<script async src="{{ asset('assets/js/plugin/opencv.js') }}" onload="onOpenCvReady();"></script>
<script src="{{ asset('assets/js/plugin/ort/ort.min.js') }}"></script>
<script src="{{ asset('assets/js/plugin/sam-loader.js') }}"></script>
<script src="{{ asset('assets/js/plugin/sam-inference.js') }}"></script>
<script src="{{ asset('assets/js/plugin/sam-counter.js') }}"></script>
<script>
    // ==========================================
    // OPENCV.JS SSOCR SETUP
    // ==========================================

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

        $('#manualMode').hide();
        $('#scaleMode').hide();
        $('#countMode').hide();

        if (mode === 'manual') {
            $('#manualMode').show();
        } else if (mode === 'scale') {
            $('#scaleMode').show();
        } else if (mode === 'count') {
            $('#countMode').show();
            // Lazy-load SAM models on first Count mode activation
            if (!samReady && typeof loadSAMModels === 'function') {
                loadSAMModels();
            }
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

    let scaleStream = null;
    let scaleFacingMode = 'environment';

    async function startScaleCamera() {
        try {
            if (scaleStream) {
                scaleStream.getTracks().forEach(track => track.stop());
            }

            const constraints = {
                video: {
                    facingMode: scaleFacingMode,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };

            scaleStream = await navigator.mediaDevices.getUserMedia(constraints);
            const video = document.getElementById('scaleVideo');
            video.srcObject = scaleStream;
            
            $('#scaleCapturePrompt').hide();
            $('#scaleCameraContainer').show();
            $('#scalePreviewContainer').hide();
            $('#ocrConfirmBox').hide();

            // Auto-scroll ke area kamera agar tombol "Take" terlihat
            setTimeout(() => {
                document.getElementById('scaleCameraContainer').scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            }, 300);

        } catch (err) {
            console.error("Camera error:", err);
            alert("Could not access camera. Please use the file upload option.");
            $('#linkUseFileFallback').parent().show();
        }
    }

    function stopScaleCamera() {
        if (scaleStream) {
            scaleStream.getTracks().forEach(track => track.stop());
            scaleStream = null;
        }
        $('#scaleCameraContainer').hide();
        $('#scaleCapturePrompt').show();
    }

    function captureAndCrop() {
        const video = document.getElementById('scaleVideo');
        const guideBox = document.getElementById('scaleGuideBox');
        
        // 1. Get visual coordinates
        const videoRect = video.getBoundingClientRect();
        const guideRect = guideBox.getBoundingClientRect();
        
        // 2. Map to internal video resolution
        const scaleX = video.videoWidth / videoRect.width;
        const scaleY = video.videoHeight / videoRect.height;
        
        const cropX = (guideRect.left - videoRect.left) * scaleX;
        const cropY = (guideRect.top - videoRect.top) * scaleY;
        const cropW = guideRect.width * scaleX;
        const cropH = guideRect.height * scaleY;
        
        // 3. Draw to canvas
        const canvas = document.createElement('canvas');
        canvas.width = cropW;
        canvas.height = cropH;
        const ctx = canvas.getContext('2d');
        
        ctx.drawImage(video, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);
        
        // 4. Process
        const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
        stopScaleCamera();
        $('#scaleProcessing').show();
        processOCR(dataUrl);
    }

    $('#btnStartScaleCamera').on('click', startScaleCamera);
    $('#btnCloseScaleCamera').on('click', stopScaleCamera);
    $('#btnTakeScalePhoto').on('click', captureAndCrop);
    
    $('#btnSwitchCamera').on('click', function() {
        scaleFacingMode = (scaleFacingMode === 'user' ? 'environment' : 'user');
        startScaleCamera();
    });

    $('#linkUseFileFallback').on('click', function(e) {
        e.preventDefault();
        $('#scalePhotoInput').click();
    });

    $('#btnAddMoreCount').on('click', function() {
        $('#scalePreviewContainer').hide();
        $('#ocrConfirmBox').hide();
        startScaleCamera();
    });

    $('#scalePhotoInput').on('change', function() {
        const file = this.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            $('#scaleCapturePrompt').hide();
            $('#scaleProcessing').show();
            processOCR(e.target.result);
        }
        reader.readAsDataURL(file);
    });

    // ==========================================
    // COUNT MODE - MobileSAM AI Object Counter
    // ==========================================
    let countStream = null;
    let countFacingMode = 'environment';
    let countOriginalImage = null; // cv.Mat of captured photo
    let countOriginalDataUrl = null;
    let countDetections = []; // [{x, y, w, h, score}]
    let countManualAdds = []; // [{x, y}] manually added points
    let lastClickX = 0, lastClickY = 0;
    let samModeActive = false; // true if SAM loaded, false = fallback

    async function startCountCamera() {
        try {
            if (countStream) countStream.getTracks().forEach(t => t.stop());
            const constraints = { video: { facingMode: countFacingMode, width: { ideal: 1920 }, height: { ideal: 1080 } } };
            countStream = await navigator.mediaDevices.getUserMedia(constraints);
            document.getElementById('countVideo').srcObject = countStream;
            $('#countCapturePrompt').hide();
            $('#countCameraContainer').show();
            $('#countCanvasArea').hide();
            setTimeout(() => {
                document.getElementById('countCameraContainer').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        } catch (err) {
            console.error("Count camera error:", err);
            alert("Could not access camera. Use upload instead.");
        }
    }

    function stopCountCamera() {
        if (countStream) { countStream.getTracks().forEach(t => t.stop()); countStream = null; }
        $('#countCameraContainer').hide();
        $('#countCapturePrompt').show();
    }

    function captureCountPhoto() {
        const video = document.getElementById('countVideo');
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        countOriginalDataUrl = canvas.toDataURL('image/jpeg', 0.92);
        stopCountCamera();
        loadCountImage(countOriginalDataUrl);
    }

    async function loadCountImage(dataUrl) {
        countOriginalDataUrl = dataUrl;
        const img = new Image();
        img.onload = async function() {
            let w = img.width, h = img.height;
            const MAX = 1024; // SAM works best at 1024
            if (w > MAX || h > MAX) {
                const ratio = Math.min(MAX / w, MAX / h);
                w = Math.floor(w * ratio);
                h = Math.floor(h * ratio);
            }

            const canvas = document.getElementById('countCanvas');
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, w, h);

            if (countOriginalImage) countOriginalImage.delete();
            countOriginalImage = cv.imread(canvas);

            countDetections = [];
            countManualAdds = [];
            lastClickX = 0; lastClickY = 0;
            $('#countBadge').hide();
            $('#countSensitivityArea').hide();
            $('#btnCountConfirm').hide();

            $('#countCapturePrompt').hide();
            $('#countCanvasArea').show();

            // Run SAM encoder on the image
            if (samReady) {
                $('#countEncodingStatus').show();
                $('#countEncodingProgress').text('');
                const t0 = Date.now();
                const ok = await encodeSAMImage(canvas);
                const elapsed = ((Date.now() - t0) / 1000).toFixed(1);
                $('#countEncodingStatus').hide();

                if (ok) {
                    samModeActive = true;
                    $('#countInstruction').html('<i class="fas fa-hand-pointer"></i> AI ready (' + elapsed + 's). Tap on one item to select it.');
                } else {
                    samModeActive = false;
                    $('#countInstruction').html('<i class="fas fa-hand-pointer"></i> AI unavailable. Tap an item to count using template matching.');
                }
            } else {
                samModeActive = false;
                $('#countInstruction').html('<i class="fas fa-hand-pointer"></i> Tap an item to count (template matching mode).');
            }

            setTimeout(() => {
                document.getElementById('countCanvasArea').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 200);
        };
        img.src = dataUrl;
    }

    function redrawCountCanvas() {
        if (!countOriginalImage) return;
        const canvas = document.getElementById('countCanvas');
        cv.imshow(canvas, countOriginalImage);
        const ctx = canvas.getContext('2d');

        ctx.strokeStyle = '#00FF00';
        ctx.lineWidth = 2;
        countDetections.forEach((d, i) => {
            ctx.strokeRect(d.x, d.y, d.w, d.h);
            ctx.fillStyle = 'rgba(0,255,0,0.15)';
            ctx.fillRect(d.x, d.y, d.w, d.h);
            ctx.fillStyle = '#00FF00';
            ctx.font = 'bold 12px Arial';
            ctx.fillText(i + 1, d.x + 2, d.y + 12);
        });

        ctx.fillStyle = 'rgba(255,165,0,0.7)';
        ctx.strokeStyle = '#FFA500';
        ctx.lineWidth = 2;
        countManualAdds.forEach((p) => {
            ctx.beginPath();
            ctx.arc(p.x, p.y, 12, 0, Math.PI * 2);
            ctx.stroke();
            ctx.fill();
            ctx.fillStyle = '#fff';
            ctx.font = 'bold 10px Arial';
            ctx.fillText('+', p.x - 4, p.y + 4);
            ctx.fillStyle = 'rgba(255,165,0,0.7)';
        });

        const totalCount = countDetections.length + countManualAdds.length;
        $('#countBadge').text(totalCount).show();
    }

    // Fallback: old template matching (used when SAM unavailable)
    function runFallbackTemplateMatching(clickX, clickY) {
        if (!countOriginalImage || !cvReady) return;
        $('#countProcessing').show();
        $('#countProcessingText').text('Counting (template matching)...');
        setTimeout(() => {
            try {
                const src = countOriginalImage;
                const gray = new cv.Mat();
                cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);
                const halfSize = 60;
                const tx = Math.max(0, Math.floor(clickX - halfSize));
                const ty = Math.max(0, Math.floor(clickY - halfSize));
                const tw = Math.min(halfSize * 2, gray.cols - tx);
                const th = Math.min(halfSize * 2, gray.rows - ty);
                if (tw < 20 || th < 20) { alert('Area too small.'); $('#countProcessing').hide(); gray.delete(); return; }
                const template = gray.roi(new cv.Rect(tx, ty, tw, th));
                const threshold = parseInt($('#countThreshold').val()) / 100;
                let allBoxes = [];
                const scales = [0.8, 0.9, 1.0, 1.1, 1.2];
                const rotations = [0, 90, 180, 270];
                for (const scale of scales) {
                    let sc = new cv.Mat();
                    const nw = Math.max(10, Math.round(template.cols * scale));
                    const nh = Math.max(10, Math.round(template.rows * scale));
                    if (nw >= gray.cols || nh >= gray.rows) { sc.delete(); continue; }
                    cv.resize(template, sc, new cv.Size(nw, nh));
                    for (const angle of rotations) {
                        let rot = sc; let del = false;
                        if (angle !== 0) { rot = new cv.Mat(); del = true;
                            if (angle===90) cv.rotate(sc,rot,cv.ROTATE_90_CLOCKWISE);
                            else if (angle===180) cv.rotate(sc,rot,cv.ROTATE_180);
                            else cv.rotate(sc,rot,cv.ROTATE_90_COUNTERCLOCKWISE);
                        }
                        if (rot.cols >= gray.cols || rot.rows >= gray.rows) { if(del)rot.delete(); continue; }
                        const res = new cv.Mat();
                        cv.matchTemplate(gray, rot, res, cv.TM_CCOEFF_NORMED);
                        for (let r=0;r<res.rows;r++) for (let c=0;c<res.cols;c++) {
                            const v = res.floatPtr(r,c)[0];
                            if (v >= threshold) allBoxes.push({x:c,y:r,w:rot.cols,h:rot.rows,score:v});
                        }
                        res.delete(); if(del)rot.delete();
                    }
                    sc.delete();
                }
                countDetections = nms(allBoxes, 0.3);
                template.delete(); gray.delete();
                redrawCountCanvas();
                $('#countProcessing').hide();
                $('#countSensitivityArea').show();
                $('#btnCountConfirm').show();
                $('#countInstruction').html('<i class="fas fa-check-circle"></i> Found <strong>' + (countDetections.length + countManualAdds.length) + '</strong> items.');
            } catch (err) { console.error(err); $('#countProcessing').hide(); alert('Counting failed: ' + err.message); }
        }, 100);
    }

    // SAM-powered counting
    async function runSAMCounting(clickX, clickY) {
        if (!countOriginalImage || !cvReady) return;
        $('#countProcessing').show();
        $('#countProcessingText').text('AI segmenting object...');

        try {
            const segResult = await segmentAtPoint(clickX, clickY);
            if (!segResult) {
                console.warn('SAM failed, falling back to template matching');
                $('#countProcessingText').text('Falling back to template matching...');
                runFallbackTemplateMatching(clickX, clickY);
                return;
            }

            // Draw SAM mask overlay on the exemplar
            const canvas = document.getElementById('countCanvas');
            const ctx = canvas.getContext('2d');
            const b = segResult.bbox;
            ctx.strokeStyle = '#FFD700';
            ctx.lineWidth = 3;
            ctx.strokeRect(b.x, b.y, b.w, b.h);
            ctx.fillStyle = 'rgba(255, 215, 0, 0.2)';
            ctx.fillRect(b.x, b.y, b.w, b.h);
            ctx.fillStyle = '#FFD700';
            ctx.font = 'bold 14px Arial';
            ctx.fillText('exemplar', b.x + 4, b.y - 5);

            // Find similar objects using SAM bbox + template matching + color filter
            $('#countProcessingText').text('Searching for similar objects...');
            await new Promise(r => setTimeout(r, 50)); // let UI update

            const threshold = parseInt($('#countThreshold').val()) / 100;
            countDetections = findSimilarObjects(segResult, countOriginalImage, threshold);

            redrawCountCanvas();
            $('#countProcessing').hide();
            $('#countSensitivityArea').show();
            $('#btnCountConfirm').show();
            const total = countDetections.length + countManualAdds.length;
            $('#countInstruction').html('<i class="fas fa-check-circle"></i> AI found <strong>' + total + '</strong> items. Adjust sensitivity or click to correct.');
        } catch (err) {
            console.error('SAM counting error:', err);
            $('#countProcessingText').text('Error, falling back...');
            runFallbackTemplateMatching(clickX, clickY);
        }
    }

    // Canvas click handler
    $('#countCanvas').on('click', function(e) {
        if (!countOriginalImage) return;
        const canvas = this;
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const clickX = (e.clientX - rect.left) * scaleX;
        const clickY = (e.clientY - rect.top) * scaleY;

        // Remove existing detection if clicked
        for (let i = countDetections.length - 1; i >= 0; i--) {
            const d = countDetections[i];
            if (Math.abs(clickX - (d.x + d.w/2)) < d.w/2 && Math.abs(clickY - (d.y + d.h/2)) < d.h/2) {
                countDetections.splice(i, 1);
                redrawCountCanvas();
                $('#countInstruction').html('<i class="fas fa-check-circle"></i> Removed 1. Total: <strong>' + (countDetections.length + countManualAdds.length) + '</strong>');
                return;
            }
        }
        // Remove manual add if clicked
        for (let i = countManualAdds.length - 1; i >= 0; i--) {
            const p = countManualAdds[i];
            if (Math.abs(clickX - p.x) < 15 && Math.abs(clickY - p.y) < 15) {
                countManualAdds.splice(i, 1);
                redrawCountCanvas();
                $('#countInstruction').html('<i class="fas fa-check-circle"></i> Removed 1. Total: <strong>' + (countDetections.length + countManualAdds.length) + '</strong>');
                return;
            }
        }

        // First click -> run counting
        if (countDetections.length === 0 && countManualAdds.length === 0) {
            lastClickX = clickX; lastClickY = clickY;
            if (samModeActive) {
                runSAMCounting(clickX, clickY);
            } else {
                runFallbackTemplateMatching(clickX, clickY);
            }
        } else {
            countManualAdds.push({ x: Math.round(clickX), y: Math.round(clickY) });
            redrawCountCanvas();
            $('#countInstruction').html('<i class="fas fa-check-circle"></i> Added 1. Total: <strong>' + (countDetections.length + countManualAdds.length) + '</strong>');
        }
    });

    // Sensitivity slider
    $('#countThreshold').on('input', function() {
        $('#countThresholdLabel').text($(this).val() + '%');
    });
    $('#countThreshold').on('change', function() {
        if (lastClickX > 0 || lastClickY > 0) {
            countManualAdds = [];
            if (samModeActive) { runSAMCounting(lastClickX, lastClickY); }
            else { runFallbackTemplateMatching(lastClickX, lastClickY); }
        }
    });

    // Count camera controls
    $('#btnStartCountCamera').on('click', startCountCamera);
    $('#btnCloseCountCamera').on('click', stopCountCamera);
    $('#btnTakeCountPhoto').on('click', captureCountPhoto);
    $('#btnSwitchCountCamera').on('click', function() {
        countFacingMode = (countFacingMode === 'user' ? 'environment' : 'user');
        startCountCamera();
    });
    $('#linkCountFileFallback').on('click', function(e) {
        e.preventDefault();
        $('#countPhotoInput').click();
    });
    $('#countPhotoInput').on('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) { loadCountImage(e.target.result); };
        reader.readAsDataURL(file);
    });

    // Retake / Clear / Confirm
    $('#btnCountRetake').on('click', function() {
        if (countOriginalImage) { countOriginalImage.delete(); countOriginalImage = null; }
        countDetections = [];
        countManualAdds = [];
        $('#countCanvasArea').hide();
        startCountCamera();
    });
    $('#btnCountClear').on('click', function() {
        countDetections = [];
        countManualAdds = [];
        lastClickX = 0; lastClickY = 0;
        redrawCountCanvas();
        $('#countBadge').hide();
        $('#countSensitivityArea').hide();
        $('#btnCountConfirm').hide();
        $('#countInstruction').html('<i class="fas fa-hand-pointer"></i> Tap on one item to select it. ' + (samModeActive ? 'AI will segment and count.' : 'Template matching mode.'));
    });
    let countModeResults = [];
    let countModeIdCounter = 0;

    $('#btnCountConfirm').on('click', function() {
        const totalCount = countDetections.length + countManualAdds.length;
        countModeIdCounter++;
        countModeResults.push({ id: countModeIdCounter, value: totalCount });
        renderCountModeResults();

        // Reset canvas for next count
        if (countOriginalImage) { countOriginalImage.delete(); countOriginalImage = null; }
        countDetections = [];
        countManualAdds = [];
        lastClickX = 0; lastClickY = 0;
        $('#countCanvasArea').hide();
        $('#countCapturePrompt').show();

        $('#btnAddMoreCountMode').show();
        $('#countTotalBar').show();
    });

    $('#btnAddMoreCountMode').on('click', function() {
        $('#countCanvasArea').hide();
        $('#countCapturePrompt').hide();
        startCountCamera();
    });

    function renderCountModeResults() {
        const container = $('#countResultsList');
        container.empty();
        countModeResults.forEach((item, index) => {
            container.append(`
                <div class="ocr-result-item" data-id="${item.id}">
                    <div>
                        <small class="text-muted">#${index + 1}</small>
                        <span class="ocr-value ms-2">${item.value}</span>
                    </div>
                    <button type="button" class="btn-remove-ocr" onclick="removeCountModeResult(${item.id})">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `);
        });
        updateCountModeTotal();
    }

    function removeCountModeResult(id) {
        countModeResults = countModeResults.filter(item => item.id !== id);
        renderCountModeResults();
        if (countModeResults.length === 0) {
            $('#btnAddMoreCountMode').hide();
            $('#countTotalBar').hide();
        }
    }

    function updateCountModeTotal() {
        const total = countModeResults.reduce((sum, item) => sum + item.value, 0);
        $('#countTotalValue').text(total);
        $('#Count_Record_Final').val(total);
    }

    function preprocess(mat) {
        let dst = new cv.Mat();
        cv.GaussianBlur(mat, dst, new cv.Size(7, 7), 0, 0, cv.BORDER_DEFAULT);
        
        try {
            let clahe = new cv.CLAHE(2.0, new cv.Size(6, 6));
            clahe.apply(dst, dst);
            clahe.delete();
        } catch(e) {
            cv.equalizeHist(dst, dst);
        }

        cv.adaptiveThreshold(dst, dst, 255, cv.ADAPTIVE_THRESH_GAUSSIAN_C, cv.THRESH_BINARY_INV, 127, OCR_THRESHOLD);
        
        let kernel = cv.getStructuringElement(cv.MORPH_CROSS, new cv.Size(5, 5));
        cv.morphologyEx(dst, dst, cv.MORPH_CLOSE, kernel);
        cv.morphologyEx(dst, dst, cv.MORPH_OPEN, kernel);
        kernel.delete();
        
        return dst;
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

    function autoCropDigits(img) {
        let contours = new cv.MatVector();
        let hierarchy = new cv.Mat();
        // Cari kontur area putih
        cv.findContours(img, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_SIMPLE);

        let minX = img.cols, minY = img.rows, maxX = 0, maxY = 0;
        let found = false;

        // Filter kontur: buang noise kecil-kecil, cari kumpulan angka
        for (let i = 0; i < contours.size(); ++i) {
            let cnt = contours.get(i);
            let rect = cv.boundingRect(cnt);
            
            // Angka 7-segmen biasanya cukup tinggi
            if (rect.height > 25 && rect.width > 5) {
                if (rect.x < minX) minX = rect.x;
                if (rect.y < minY) minY = rect.y;
                if (rect.x + rect.width > maxX) maxX = rect.x + rect.width;
                if (rect.y + rect.height > maxY) maxY = rect.y + rect.height;
                found = true;
            }
        }
        contours.delete(); hierarchy.delete();

        if (found) {
            // Tambahkan padding agar tidak terlalu mepet
            // Ditambah extra padding di kiri agar angka 1 atau 7 tidak terpotong
            let pad = 15;
            let padLeft = 35; 
            minX = Math.max(0, minX - padLeft);
            minY = Math.max(0, minY - pad);
            maxX = Math.min(img.cols, maxX + pad);
            maxY = Math.min(img.rows, maxY + pad);
            
            let cropRect = new cv.Rect(minX, minY, maxX - minX, maxY - minY);
            return img.roi(cropRect);
        }
        return img.clone();
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
                
                let dst = preprocess(gray);

                // Otomatis melakukan crop pada area yang terdeteksi sebagai sekumpulan angka
                let croppedDst = autoCropDigits(dst);

                // Show threshold image for user debugging
                cv.imshow('scaleCanvas', croppedDst);
                $('#scaleCanvas').show();
                $('#scalePreviewContainer').show();
                $('#scaleCapturePrompt').hide();

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
                
                // Scroll ke kotak konfirmasi agar terlihat
                document.getElementById('ocrConfirmBox').scrollIntoView({ behavior: 'smooth', block: 'center' });
                
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
        startScaleCamera();
        $('#scalePhotoInput').val('');
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
