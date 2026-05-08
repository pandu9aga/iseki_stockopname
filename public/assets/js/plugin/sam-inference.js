/**
 * MobileSAM Inference - Encode images and decode point prompts
 * Depends on: sam-loader.js (samEncoder, samDecoder, imageEmbedding)
 */

/**
 * Encode an image using the SAM encoder.
 * Input format: [H, W, 3] float32 with values 0-255 (RGB)
 * Caches result in `imageEmbedding` global.
 * @param {HTMLCanvasElement} canvas - canvas with the image drawn on it
 * @returns {Promise<boolean>}
 */
async function encodeSAMImage(canvas) {
    if (!samEncoder) return false;

    const w = canvas.width;
    const h = canvas.height;
    const ctx = canvas.getContext('2d');
    const imgData = ctx.getImageData(0, 0, w, h);
    const px = imgData.data; // RGBA uint8

    // Convert RGBA → RGB float32 [H, W, 3]
    const rgb = new Float32Array(h * w * 3);
    for (let i = 0; i < h * w; i++) {
        rgb[i * 3]     = px[i * 4];     // R
        rgb[i * 3 + 1] = px[i * 4 + 1]; // G
        rgb[i * 3 + 2] = px[i * 4 + 2]; // B
    }

    const inputTensor = new ort.Tensor('float32', rgb, [h, w, 3]);

    try {
        const t0 = Date.now();
        const results = await samEncoder.run({ 'input_image': inputTensor });
        imageEmbedding = results.image_embeddings;
        samImageWidth = w;
        samImageHeight = h;
        console.log('SAM encode done in', ((Date.now() - t0) / 1000).toFixed(1), 's');
        return true;
    } catch (err) {
        console.error('SAM encode error:', err);
        return false;
    }
}

/**
 * Run SAM decoder at a point to get segmentation mask.
 * @param {number} clickX - x coordinate in canvas pixel space
 * @param {number} clickY - y coordinate in canvas pixel space
 * @returns {Promise<Object|null>} { binaryMask, maskW, maskH, bbox } or null
 */
async function segmentAtPoint(clickX, clickY) {
    if (!samDecoder || !imageEmbedding) return null;

    // Point prompt: [click_point, padding_point]
    const pointCoords = new ort.Tensor('float32',
        new Float32Array([clickX, clickY, 0, 0]), [1, 2, 2]);
    const pointLabels = new ort.Tensor('float32',
        new Float32Array([1, -1]), [1, 2]); // 1=foreground, -1=pad
    const maskInput = new ort.Tensor('float32',
        new Float32Array(256 * 256), [1, 1, 256, 256]);
    const hasMask = new ort.Tensor('float32',
        new Float32Array([0]), [1]);
    const origSize = new ort.Tensor('float32',
        new Float32Array([samImageHeight, samImageWidth]), [2]);

    try {
        const results = await samDecoder.run({
            'image_embeddings': imageEmbedding,
            'point_coords': pointCoords,
            'point_labels': pointLabels,
            'mask_input': maskInput,
            'has_mask_input': hasMask,
            'orig_im_size': origSize
        });

        const maskData = results.masks.data;
        const dims = results.masks.dims;
        const maskH = dims[dims.length - 2];
        const maskW = dims[dims.length - 1];
        const maskSize = maskH * maskW;

        // Threshold logits > 0 → binary mask
        const binaryMask = new Uint8Array(maskSize);
        let minX = maskW, minY = maskH, maxX = 0, maxY = 0;
        let count = 0;

        for (let i = 0; i < maskSize; i++) {
            if (maskData[i] > 0) {
                binaryMask[i] = 255;
                const x = i % maskW;
                const y = Math.floor(i / maskW);
                if (x < minX) minX = x;
                if (y < minY) minY = y;
                if (x > maxX) maxX = x;
                if (y > maxY) maxY = y;
                count++;
            }
        }

        if (count < 10) {
            console.warn('SAM mask too small:', count, 'pixels');
            return null;
        }

        const bbox = { x: minX, y: minY, w: maxX - minX + 1, h: maxY - minY + 1 };
        console.log('SAM segment: bbox', bbox, 'pixels:', count);
        return { binaryMask, maskW, maskH, bbox, pixelCount: count };
    } catch (err) {
        console.error('SAM decode error:', err);
        return null;
    }
}
