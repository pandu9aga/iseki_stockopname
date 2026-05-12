/**
 * MobileSAM Model Loader
 * Handles loading encoder + decoder ONNX models via ONNX Runtime Web
 */

// SAM State
var samWorker = null;
var samDecoder = null;
var samReady = false;
var imageEmbedding = null;
var samImageWidth = 0;
var samImageHeight = 0;

// Configure ONNX Runtime WASM paths (must be before any session creation)
if (typeof ort !== 'undefined') {
    ort.env.wasm.wasmPaths = baseUrl + 'assets/js/plugin/ort/';
    // Option 1: Multi-threading (max 4 to avoid memory issues on budget phones)
    ort.env.wasm.numThreads = Math.min(navigator.hardwareConcurrency || 4, 4);
}

/**
 * Load MobileSAM encoder and decoder models
 * Shows progress in #countModelLoading element
 * @returns {Promise<boolean>} true if models loaded successfully
 */
async function loadSAMModels() {
    if (samReady) return true;
    if (typeof ort === 'undefined') {
        console.warn('ONNX Runtime not available');
        return false;
    }

    $('#countModelLoading').show();
    try {
        $('#countModelProgress').text('decoder...');
        samDecoder = await ort.InferenceSession.create(
            baseUrl + 'assets/models/mobilesam.decoder.quant.onnx',
            { executionProviders: ['webgl', 'wasm'] }
        );

        $('#countModelProgress').text('encoder worker...');
        await new Promise((resolve, reject) => {
            samWorker = new Worker(baseUrl + 'assets/js/plugin/sam-worker.js');
            samWorker.onmessage = function(e) {
                if (e.data.type === 'init_done') {
                    if (e.data.success) resolve();
                    else reject(new Error(e.data.error));
                }
            };
            samWorker.postMessage({ type: 'init' });
        });

        samReady = true;
        console.log('MobileSAM loaded. Decoder inputs:', samDecoder.inputNames);
    } catch (err) {
        console.error('SAM load failed:', err);
    }
    $('#countModelLoading').hide();
    return samReady;
}
