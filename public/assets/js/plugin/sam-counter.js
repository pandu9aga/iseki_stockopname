/**
 * SAM-based Object Counter
 * Uses SAM mask for precise template extraction, then OpenCV.js template matching
 * Depends on: sam-inference.js, OpenCV.js loaded
 */

/**
 * Non-Maximum Suppression
 */
function nms(boxes, overlapThresh) {
    if (boxes.length === 0) return [];
    boxes.sort(function(a, b) { return a.score - b.score; });
    var pick = [];
    var suppressed = {};
    for (var i = boxes.length - 1; i >= 0; i--) {
        if (suppressed[i]) continue;
        pick.push(boxes[i]);
        for (var j = i - 1; j >= 0; j--) {
            if (suppressed[j]) continue;
            var xx1 = Math.max(boxes[i].x, boxes[j].x);
            var yy1 = Math.max(boxes[i].y, boxes[j].y);
            var xx2 = Math.min(boxes[i].x + boxes[i].w, boxes[j].x + boxes[j].w);
            var yy2 = Math.min(boxes[i].y + boxes[i].h, boxes[j].y + boxes[j].h);
            var inter = Math.max(0, xx2 - xx1) * Math.max(0, yy2 - yy1);
            var union = boxes[i].w * boxes[i].h + boxes[j].w * boxes[j].h - inter;
            if (inter / union > overlapThresh) suppressed[j] = true;
        }
    }
    return pick;
}

/**
 * Extract HSV histogram from a region of interest
 * @param {cv.Mat} srcRGBA - source image (RGBA)
 * @param {Object} bbox - {x, y, w, h}
 * @returns {cv.Mat} histogram (caller must delete)
 */
function extractRegionHist(srcRGBA, bbox) {
    var roi = srcRGBA.roi(new cv.Rect(bbox.x, bbox.y, bbox.w, bbox.h));
    var rgb = new cv.Mat();
    cv.cvtColor(roi, rgb, cv.COLOR_RGBA2RGB);
    var hsv = new cv.Mat();
    cv.cvtColor(rgb, hsv, cv.COLOR_RGB2HSV);

    var hsvVec = new cv.MatVector();
    hsvVec.push_back(hsv);
    var hist = new cv.Mat();
    var channels = [0, 1]; // H and S channels
    var histSize = [16, 16];
    var ranges = [0, 180, 0, 256];
    cv.calcHist(hsvVec, channels, new cv.Mat(), hist, histSize, ranges);
    cv.normalize(hist, hist, 0, 1, cv.NORM_MINMAX);

    roi.delete(); rgb.delete(); hsv.delete(); hsvVec.delete();
    return hist;
}

/**
 * Find all objects similar to the exemplar using SAM mask + template matching
 * @param {Object} segResult - from segmentAtPoint()
 * @param {cv.Mat} srcImage - original image (RGBA cv.Mat)
 * @param {number} threshold - match threshold 0-1
 * @returns {Array} detections [{x,y,w,h,score}]
 */
function findSimilarObjects(segResult, srcImage, threshold) {
    if (!srcImage || !cvReady) return [];

    var bbox = segResult.bbox;
    var gray = new cv.Mat();
    cv.cvtColor(srcImage, gray, cv.COLOR_RGBA2GRAY);

    // Extract template from SAM bounding box (tight crop around the object)
    var tx = Math.max(0, bbox.x);
    var ty = Math.max(0, bbox.y);
    var tw = Math.min(bbox.w, gray.cols - tx);
    var th = Math.min(bbox.h, gray.rows - ty);

    if (tw < 15 || th < 15) { gray.delete(); return []; }

    var templateRect = new cv.Rect(tx, ty, tw, th);
    var template = gray.roi(templateRect);

    // Extract exemplar color histogram for filtering
    var exemplarHist = extractRegionHist(srcImage, { x: tx, y: ty, w: tw, h: th });

    var allBoxes = [];
    var scales = [0.7, 0.8, 0.9, 1.0, 1.1, 1.2, 1.3];
    var rotations = [0, 90, 180, 270];

    for (var si = 0; si < scales.length; si++) {
        var scale = scales[si];
        var newW = Math.max(10, Math.round(template.cols * scale));
        var newH = Math.max(10, Math.round(template.rows * scale));
        if (newW >= gray.cols || newH >= gray.rows) continue;

        var scaled = new cv.Mat();
        cv.resize(template, scaled, new cv.Size(newW, newH));

        for (var ri = 0; ri < rotations.length; ri++) {
            var angle = rotations[ri];
            var rotated = scaled;
            var needDel = false;

            if (angle !== 0) {
                rotated = new cv.Mat();
                needDel = true;
                if (angle === 90) cv.rotate(scaled, rotated, cv.ROTATE_90_CLOCKWISE);
                else if (angle === 180) cv.rotate(scaled, rotated, cv.ROTATE_180);
                else cv.rotate(scaled, rotated, cv.ROTATE_90_COUNTERCLOCKWISE);
            }

            if (rotated.cols >= gray.cols || rotated.rows >= gray.rows) {
                if (needDel) rotated.delete();
                continue;
            }

            var result = new cv.Mat();
            cv.matchTemplate(gray, rotated, result, cv.TM_CCOEFF_NORMED);

            for (var r = 0; r < result.rows; r++) {
                for (var c = 0; c < result.cols; c++) {
                    var val = result.floatPtr(r, c)[0];
                    if (val >= threshold) {
                        allBoxes.push({ x: c, y: r, w: rotated.cols, h: rotated.rows, score: val });
                    }
                }
            }
            result.delete();
            if (needDel) rotated.delete();
        }
        scaled.delete();
    }

    // NMS first pass
    var candidates = nms(allBoxes, 0.3);

    // Color histogram filtering - remove candidates with very different colors
    var filtered = [];
    for (var i = 0; i < candidates.length; i++) {
        var c = candidates[i];
        var cx = Math.max(0, Math.min(c.x, srcImage.cols - c.w));
        var cy = Math.max(0, Math.min(c.y, srcImage.rows - c.h));
        var cw = Math.min(c.w, srcImage.cols - cx);
        var ch = Math.min(c.h, srcImage.rows - cy);

        if (cw < 5 || ch < 5) continue;

        var candHist = extractRegionHist(srcImage, { x: cx, y: cy, w: cw, h: ch });
        var similarity = cv.compareHist(exemplarHist, candHist, cv.HISTCMP_CORREL);
        candHist.delete();

        // Accept if color similarity > 0.3 (loose threshold to avoid missing valid items)
        if (similarity > 0.3) {
            c.colorScore = similarity;
            filtered.push(c);
        }
    }

    template.delete();
    gray.delete();
    exemplarHist.delete();

    return filtered;
}
