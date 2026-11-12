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
     * @param {number} totalNoSample    - Total seluruh hitungan tanpa sample (pcs), misal: 4500 + 1190 = 5690
     * @param {number} inputWithSample  - Angka yang diinput pada timbang final (ronde terakhir + sample), misal: 1390 (atau 5890 jika ditimbang sekaligus)
     * @param {number} sampleQty        - Jumlah sample (pcs), misal: 200
     * @param {number} lastRoundWeight  - Berat/qty hitungan tanpa sample pada ronde terakhir saja (pcs), misal: 1190
     * @returns {{ valid: boolean, error?: string, target?: number, selisih?: number, delta?: number, estimatedCount?: number, errorMarginPercent?: number, status?: 'OK'|'NG', isLastRoundOnly?: boolean, totalFinalWithSample?: number }}
     */
    function calculate(totalNoSample, inputWithSample, sampleQty, lastRoundWeight = null) {
        const totalNo = parseFloat(totalNoSample);
        const wInput  = parseFloat(inputWithSample);
        const qty     = parseInt(sampleQty, 10);
        const lastNo  = lastRoundWeight !== null && lastRoundWeight !== undefined && lastRoundWeight !== ''
            ? parseFloat(lastRoundWeight)
            : totalNo;

        if (isNaN(totalNo) || isNaN(wInput) || isNaN(qty)) {
            return { valid: false, error: 'Semua field wajib diisi dengan angka.' };
        }
        if (totalNo <= 0) {
            return { valid: false, error: 'Total tanpa sample harus lebih dari 0 pcs.' };
        }
        if (qty < 1) {
            return { valid: false, error: 'Jumlah sample minimal 1 pcs.' };
        }
        if (wInput <= 0) {
            return { valid: false, error: 'Timbangan dengan sample harus lebih dari 0 pcs.' };
        }

        // Tentukan apakah operator menimbang:
        // A) Ronde terakhir saja + sample (misal: 1190 + 200 -> input ~1390)
        // B) Seluruh barang ditimbang sekaligus + sample (misal: 5690 + 200 -> input ~5890)
        let baseWeightWithout = totalNo;
        let isLastRoundOnly = false;

        if (lastNo > 0 && lastNo < totalNo && wInput < totalNo) {
            // Kasus input ronde terakhir saja: wInput mendekati (lastNo + qty)
            baseWeightWithout = lastNo;
            isLastRoundOnly = true;
        }

        if (wInput <= baseWeightWithout) {
            return {
                valid: false,
                error: isLastRoundOnly
                    ? `Hasil timbang dengan sample (${wInput} pcs) harus lebih besar dari hitungan ronde terakhir (${baseWeightWithout} pcs).`
                    : `Hasil timbang dengan sample (${wInput} pcs) harus lebih besar dari hitungan tanpa sample (${baseWeightWithout} pcs).`
            };
        }

        const targetRound           = baseWeightWithout + qty;
        const selisih               = Math.abs(wInput - targetRound);
        const delta                 = wInput - baseWeightWithout;

        // Total akumulasi final seluruh barang (termasuk sample)
        // Jika ronde terakhir: (totalNo - lastNo) + wInput = (5690 - 1190) + 1390 = 5890
        // Jika ditimbang sekaligus: wInput = 5890
        const totalFinalWithSample  = isLastRoundOnly ? Math.round((totalNo - lastNo) + wInput) : Math.round(wInput);
        const estimatedCount        = totalFinalWithSample;

        // Hitung Margin of Error terhadap sample
        // MoE (%) = (selisih / sampleQty) * 100% atau terhadap totalWithSample
        // Sesuai rumus standar: persentase selisih terhadap timbangan dengan sample ronde tersebut
        const errorMargin           = (selisih / wInput) * 100;
        const errorRounded          = parseFloat(errorMargin.toFixed(2));
        const status                = errorRounded >= 2.0 ? 'NG' : 'OK';

        return {
            valid:                true,
            target:               targetRound,
            selisih:              selisih,
            delta:                delta,
            estimatedCount:       estimatedCount,
            totalFinalWithSample: totalFinalWithSample,
            errorMarginPercent:   errorRounded,
            status:               status,
            isLastRoundOnly:      isLastRoundOnly,
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
