<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'so_event_id',
        'tracking_code',
        'rack_label_id',
        'rack_no',
        'code_rack',
        'area',
        'cell',
        'code_part',
        'name_part',
        'process_type',
        'current_step',
        'courier_nik',
        'courier_photo_part',
        'courier_photo_rack',
        'courier_at',
        'final_count',
        'pos1_count',
        'pos2_count',
        'scale_id',
        'scale_number',
        'scale_user_nik',
        'scale_partner_nik',
        'count_completed_at',
        'return_nik',
        'return_rack_scanned',
        'return_photo',
        'returned_at',
    ];

    protected $casts = [
        'courier_at' => 'datetime',
        'count_completed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function histories()
    {
        return $this->hasMany(SoCountHistory::class, 'so_item_id');
    }

    public function photos()
    {
        return $this->hasMany(SoPhoto::class, 'so_item_id');
    }

    public function scaleSession()
    {
        return $this->hasOne(SoScaleSession::class, 'so_item_id');
    }

    public function scaleRounds()
    {
        return $this->hasMany(SoScaleRound::class, 'so_item_id');
    }

    public function scale()
    {
        return $this->belongsTo(Scale::class, 'scale_id');
    }

    public function courier()
    {
        return $this->belongsTo(Member::class, 'courier_nik', 'nik');
    }

    public function returner()
    {
        return $this->belongsTo(Member::class, 'return_nik', 'nik');
    }

    /**
     * Tahap hitung manual terakhir untuk notifikasi lintas pos.
     * NONE | POS1_ONLY | MISMATCH | COMPLETED
     */
    public function manualCountStage(): string
    {
        $pos1History = $this->histories()
            ->where('count_type', 'POS1')
            ->orderByDesc('id')
            ->first();

        if (!$pos1History) {
            return 'NONE';
        }

        // Pos 1 sudah diisi dan sedang menunggu verifikasi Pos 2.
        if ($this->current_step === 'POS1_DONE') {
            return 'POS1_ONLY';
        }

        $pos2History = $this->histories()
            ->where('count_type', 'POS2')
            ->orderByDesc('id')
            ->first();

        // Baru sampai Pos 1 (Pos 2 belum pernah diisi).
        if (!$pos2History) {
            return 'POS1_ONLY';
        }

        // Pos 2 sudah diisi tetapi hasilnya belum cocok.
        if ($this->current_step === 'MISMATCH_NG') {
            return 'MISMATCH';
        }

        if ($this->pos1_count !== null && $this->pos2_count !== null
            && (int) $this->pos1_count === (int) $this->pos2_count) {
            return 'COMPLETED';
        }

        return 'MISMATCH';
    }

    /**
     * Item sudah lengkap dihitung manual (Pos 1 & Pos 2 menghasilkan angka sama).
     */
    public function hasCompletedManualCount(): bool
    {
        if ($this->pos1_count === null || $this->pos2_count === null) {
            return false;
        }

        if ((int) $this->pos1_count !== (int) $this->pos2_count) {
            return false;
        }

        return $this->histories()
                ->where('count_type', 'POS1')
                ->exists()
            && $this->histories()
                ->where('count_type', 'POS2')
                ->exists();
    }

    /**
     * Item sudah selesai dihitung di pos timbangan (histori resmi SCALE
     * atau sesi timbangan berstatus COMPLETED).
     */
    public function hasCompletedScaleCount(): bool
    {
        return $this->histories()
                ->where('count_type', 'SCALE')
                ->where('status', 'OK')
                ->exists()
            || SoScaleSession::where('so_item_id', $this->id)
                ->whereIn('status', ['FINAL_WEIGHED', 'PRINTED', 'COMPLETED'])
                ->exists();
    }
}

