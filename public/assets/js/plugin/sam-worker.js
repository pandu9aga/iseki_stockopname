importScripts('ort/ort.min.js');

// Configure ONNX Runtime WASM paths relative to the worker
ort.env.wasm.wasmPaths = 'ort/';
ort.env.wasm.numThreads = Math.min(navigator.hardwareConcurrency || 8, 8);

let samEncoder = null;

self.onmessage = async function(e) {
    const data = e.data;
    
    if (data.type === 'init') {
        try {
            // Load standard encoder with WebGL/WASM fallback
            samEncoder = await ort.InferenceSession.create(
                '../../models/mobilesam.encoder.onnx',
                { executionProviders: ['webgl', 'wasm'] }
            );
            self.postMessage({ type: 'init_done', success: true });
        } catch (err) {
            self.postMessage({ type: 'init_done', success: false, error: err.message });
        }
    } 
    else if (data.type === 'encode') {
        if (!samEncoder) {
            self.postMessage({ type: 'encode_done', success: false, error: 'Encoder not loaded' });
            return;
        }
        
        try {
            const { rgbArray, h, w } = data.payload;
            const inputTensor = new ort.Tensor('float32', rgbArray, [h, w, 3]);
            const results = await samEncoder.run({ 'input_image': inputTensor });
            
            // Pass back the embedding tensor data
            const embeddingData = results.image_embeddings.data;
            const embeddingDims = results.image_embeddings.dims;
            
            self.postMessage({ 
                type: 'encode_done', 
                success: true, 
                embeddingData: embeddingData,
                embeddingDims: embeddingDims
            }, [embeddingData.buffer]); // Transfer buffer to avoid copy overhead
            
        } catch (err) {
            self.postMessage({ type: 'encode_done', success: false, error: err.message });
        }
    }
};
