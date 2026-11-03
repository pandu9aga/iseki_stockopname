/**
 * scale-calc.js
 * Kalkulator timbangan & margin of error untuk Modul Timbangan (Fase 2B).
 *
 * Rumus:
 *   weightPerPiece  = (beratDgnSample - beratTanpaSample) / jumlahSample
 *   estimatedCount  = round(beratTanpaSample / weightPerPiece)
 *   exactCount      = beratTanpaSample / weightPerPiece
 *   errorMargin (%) = |exactCount - estimatedCount| / max(exactCount, 1) * 100
 *
 * Status: OK jika errorMargin < 2%, NG jika >= 2%
 */

const ScaleCalc = (() => {
    /**
     * Hitung hasil timbangan.
     * @param {number} weightNoSample   - Berat tanpa sample (gram/kg)
     * @param {number} weightWithSample - Berat dengan sample (gram/kg)
     * @param {number} sampleQty        - Jumlah sample (pcs)
     * @returns {{ valid: boolean, error?: string, weightPerPiece?: number, estimatedCount?: number, exactCount?: number, errorMarginPercent?: number, status?: 'OK'|'NG' }}
     */
    function calculate(weightNoSample, weightWithSample, sampleQty) {
        const wNo   = parseFloat(weightNoSample);
        const wWith = parseFloat(weightWithSample);
        const qty   = parseInt(sampleQty, 10);

        if (isNaN(wNo) || isNaN(wWith) || isNaN(qty)) {
            return { valid: false, error: 'Semua field wajib diisi dengan angka.' };
        }
        if (wNo <= 0) {
            return { valid: false, error: 'Berat tanpa sample harus lebih dari 0.' };
        }
        if (wWith <= wNo) {
            return { valid: false, error: 'Berat dengan sample harus lebih besar dari berat tanpa sample.' };
        }
        if (qty < 1) {
            return { valid: false, error: 'Jumlah sample minimal 1.' };
        }

        const sampleWeight   = wWith - wNo;
        const weightPerPiece = sampleWeight / qty;
        const exactCount     = wNo / weightPerPiece;
        const estimatedCount = Math.round(exactCount);
        const errorMargin    = Math.abs(exactCount - estimatedCount) / Math.max(exactCount, 1) * 100;
        const errorRounded   = parseFloat(errorMargin.toFixed(2));
        const status         = errorRounded >= 2.0 ? 'NG' : 'OK';

        return {
            valid:              true,
            weightPerPiece:     parseFloat(weightPerPiece.toFixed(6)),
            estimatedCount:     estimatedCount,
            exactCount:         parseFloat(exactCount.toFixed(4)),
            errorMarginPercent: errorRounded,
            status:             status,
        };
    }

    /**
     * Render badge HTML berdasarkan status.
     * @param {'OK'|'NG'} status
     * @param {number} margin
     * @returns {string} HTML string
     */
    function renderStatusBadge(status, margin) {
        if (status === 'OK') {
            return `<span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>OK — Margin: ${margin}%</span>`;
        }
        return `<span class="badge bg-danger fs-6"><i class="fas fa-times-circle me-1"></i>NG — Margin: ${margin}% (&ge; 2%)</span>`;
    }

    /**
     * Update live preview UI elemen-elemen timbangan.
     * @param {object} elements - { wNoInput, wWithInput, qtyInput, previewBox, submitBtn }
     */
    function liveUpdate(elements) {
        const { wNoInput, wWithInput, qtyInput, previewBox, submitBtn } = elements;
        const result = calculate(wNoInput.value, wWithInput.value, qtyInput.value);

        if (!result.valid) {
            previewBox.innerHTML = `<span class="text-muted"><i class="fas fa-calculator me-1"></i>${result.error || 'Isi semua field untuk melihat preview.'}</span>`;
            if (submitBtn) submitBtn.disabled = true;
            return;
        }

        const badge = renderStatusBadge(result.status, result.errorMarginPercent);

        previewBox.innerHTML = `
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="text-muted small">Berat / pcs</div>
                    <div class="fw-bold">${result.weightPerPiece.toFixed(4)}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Estimasi Qty</div>
                    <div class="fw-bold fs-4 text-primary">${result.estimatedCount.toLocaleString('id-ID')} pcs</div>
                </div>
                <div class="col-md-4">
                    ${badge}
                </div>
            </div>
        `;

        if (submitBtn) {
            submitBtn.disabled = result.status === 'NG';
        }
    }

    return { calculate, renderStatusBadge, liveUpdate };
})();
