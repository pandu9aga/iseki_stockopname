/**
 * MobileSAM Model Loader
 * Handles loading encoder + decoder ONNX models via ONNX Runtime Web
 */

// SAM State
var samEncoder = null;
var samDecoder = null;
var samReady = false;
var imageEmbedding = null;
var samImageWidth = 0;
var samImageHeight = 0;

// Configure ONNX Runtime WASM paths (must be before any session creation)
if (typeof ort !== 'undefined') {
    ort.env.wasm.wasmPaths = baseUrl + 'assets/js/plugin/ort/';
    ort.env.wasm.numThreads = 1;
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
        $('#countModelProgress').text('encoder...');
        samEncoder = await ort.InferenceSession.create(
            baseUrl + 'assets/models/mobilesam.encoder.onnx',
            { executionProviders: ['wasm'] }
        );

        $('#countModelProgress').text('decoder...');
        samDecoder = await ort.InferenceSession.create(
            baseUrl + 'assets/models/mobilesam.decoder.quant.onnx',
            { executionProviders: ['wasm'] }
        );

        samReady = true;
        console.log('MobileSAM loaded. Encoder inputs:', samEncoder.inputNames, 'Decoder inputs:', samDecoder.inputNames);
    } catch (err) {
        console.error('SAM load failed:', err);
    }
    $('#countModelLoading').hide();
    return samReady;
}
