/**
 * scale-calc.js
 * Kalkulator timbangan & margin of error untuk Modul Timbangan (Fase 2B).
 * Semua input dalam satuan PCS (bukan gram/kg).
 *
 * Rumus:
 *   targetWithSample = totalNoSample + sampleQty
 *   selisih          = Math.abs(totalWithSample - targetWithSample)
 *   errorMargin (%)  = (selisih / totalWithSample) * 100
 *   estimatedCount   = Math.round(totalWithSample) (Total Final dengan Sample)
 *
 * Artinya:
 *   Nilai target adalah (tanpa sample + sample). Margin error dihitung dari selisih
 *   terhadap nilai total dengan sample.
 *   Contoh: tanpa sample 101 pcs, sample 50 pcs -> target 151 pcs.
 *   Jika timbang final terbaca 152 pcs -> selisih 1 pcs.
 *   Margin = (1 / 152) * 100% = 0.66% (< 2% -> OK).
 *
 * Status: OK jika errorMargin < 2%, NG jika >= 2%
 */

const ScaleCalc = (() => {
    /**
     * Hitung hasil timbangan.
     * @param {number} totalNoSample    - Total tanpa sample (pcs)
     * @param {number} totalWithSample  - Total dengan sample (pcs)
     * @param {number} sampleQty        - Jumlah sample (pcs)
     * @returns {{ valid: boolean, error?: string, target?: number, selisih?: number, delta?: number, estimatedCount?: number, errorMarginPercent?: number, status?: 'OK'|'NG' }}
     */
    function calculate(totalNoSample, totalWithSample, sampleQty) {
        const wNo   = parseFloat(totalNoSample);
        const wWith = parseFloat(totalWithSample);
        const qty   = parseInt(sampleQty, 10);

        if (isNaN(wNo) || isNaN(wWith) || isNaN(qty)) {
            return { valid: false, error: 'Semua field wajib diisi dengan angka.' };
        }
        if (wNo <= 0) {
            return { valid: false, error: 'Total tanpa sample harus lebih dari 0 pcs.' };
        }
        if (qty < 1) {
            return { valid: false, error: 'Jumlah sample minimal 1 pcs.' };
        }
        if (wWith <= 0) {
            return { valid: false, error: 'Total dengan sample harus lebih dari 0 pcs.' };
        }
        if (wWith <= wNo) {
            return { valid: false, error: 'Total dengan sample harus lebih besar dari total tanpa sample (' + wNo + ' pcs).' };
        }

        const target           = wNo + qty;
        const selisih          = Math.abs(wWith - target);
        const delta            = wWith - wNo;
        const estimatedCount   = Math.round(wWith); // Total final dengan sample (misal 152)
        const errorMargin      = (selisih / wWith) * 100;
        const errorRounded     = parseFloat(errorMargin.toFixed(2));
        const status           = errorRounded >= 2.0 ? 'NG' : 'OK';

        return {
            valid:              true,
            target:             target,
            selisih:            selisih,
            delta:              delta,
            estimatedCount:     estimatedCount,
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
            return `<span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>OK &mdash; Margin: ${margin}%</span>`;
        }
        return `<span class="badge bg-danger fs-6"><i class="fas fa-times-circle me-1"></i>NG &mdash; Margin: ${margin}% (&ge; 2%)</span>`;
    }

    /**
     * Update live preview UI elemen-elemen timbangan.
     * @param {object} elements - { wNoInput, wWithInput, qtyInput, previewBox }
     */
    function liveUpdate(elements) {
        const { wNoInput, wWithInput, qtyInput, previewBox } = elements;
        if (!wNoInput || !wWithInput || !qtyInput || !previewBox) return;

        const result = calculate(wNoInput.value, wWithInput.value, qtyInput.value);

        if (!result.valid) {
            previewBox.innerHTML = `<span class="text-muted"><i class="fas fa-calculator me-1"></i>${result.error || 'Isi semua field untuk melihat preview.'}</span>`;
            return;
        }

        const badge = renderStatusBadge(result.status, result.errorMarginPercent);

        previewBox.innerHTML = `
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="text-muted small">Target Total</div>
                    <div class="fw-bold">${result.target.toLocaleString('id-ID')} pcs</div>
                    <div class="text-muted" style="font-size:0.7rem;">selisih: ${result.selisih} pcs</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Total QTY Final</div>
                    <div class="fw-bold fs-4 text-primary">${result.estimatedCount.toLocaleString('id-ID')} pcs</div>
                </div>
                <div class="col-md-4">
                    ${badge}
                </div>
            </div>
        `;
    }

    return { calculate, renderStatusBadge, liveUpdate };
})();
