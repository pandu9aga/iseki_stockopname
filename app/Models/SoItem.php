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
        'pos1_at',
        'pos2_count',
        'pos2_at',
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
        'courier_at'         => 'datetime',
        'pos1_at'            => 'datetime',
        'pos2_at'            => 'datetime',
        'count_completed_at' => 'datetime',
        'returned_at'        => 'datetime',
    ];

    public function histories()
    {
        return $this->hasMany(SoCountHistory::class, 'so_item_id');
    }

    public function event()
    {
        return $this->belongsTo(SoEvent::class, 'so_event_id');
    }

    /**
     * Dapatkan folder event SO untuk item ini
     */
    public function getEventUploadFolder(): string
    {
        if ($this->event) {
            return $this->event->getUploadFolder();
        }

        if ($this->so_event_id) {
            $event = SoEvent::find($this->so_event_id);
            if ($event) {
                return $event->getUploadFolder();
            }
        }

        return SoEvent::getActiveUploadFolder();
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
     * Cek apakah member dengan NIK tertentu diizinkan melakukan peran ($targetRole)
     * pada part ini berdasarkan aturan pencegahan multi-peran.
     *
     * @param string $memberNik NIK member yang hendak bertindak
     * @param string $targetRole 'courier' | 'pos1' | 'pos2' | 'scale' | 'returner'
     * @param SoEvent|null $event
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function canMemberAct(string $memberNik, string $targetRole, ?SoEvent $event = null): array
    {
        if (!$event) {
            $event = $this->event ?: ($this->so_event_id ? SoEvent::find($this->so_event_id) : SoEvent::getActiveEvent());
        }

        $isMerged = $event ? (bool)$event->is_courier_return_merged : false;
        $memberNik = trim($memberNik);

        // Cari riwayat partisipasi member ini pada item terkait
        $isCourier = ($this->courier_nik && trim($this->courier_nik) === $memberNik);
        $isReturner = ($this->return_nik && trim($this->return_nik) === $memberNik);
        $isScale = (($this->scale_user_nik && trim($this->scale_user_nik) === $memberNik) ||
                    ($this->scale_partner_nik && trim($this->scale_partner_nik) === $memberNik));

        // Cek histori hitung Pos 1 & Pos 2
        $hasPos1 = $this->histories()
            ->where('count_type', 'POS1')
            ->where('member_nik', $memberNik)
            ->exists();

        $hasPos2 = $this->histories()
            ->where('count_type', 'POS2')
            ->where('member_nik', $memberNik)
            ->exists();

        $hasScaleHistory = $this->histories()
            ->where('count_type', 'SCALE')
            ->where('member_nik', $memberNik)
            ->exists();

        switch ($targetRole) {
            case 'pos1':
                // Pos 1 tidak boleh diisi oleh orang yang jadi Kurir untuk part ini
                if ($isCourier) {
                    return [
                        'allowed' => false,
                        'reason'  => 'Anda adalah Kurir yang mengambil part ini dari rak. Demi independensi perhitungan, Anda tidak boleh menghitung di Pos 1 untuk part ini.'
                    ];
                }
                break;

            case 'pos2':
                // Pos 2 tidak boleh diisi oleh Kurir atau oleh orang yang menghitung di Pos 1 untuk part ini
                if ($isCourier) {
                    return [
                        'allowed' => false,
                        'reason'  => 'Anda adalah Kurir yang mengambil part ini dari rak. Anda tidak boleh menghitung di Pos 2 untuk part ini.'
                    ];
                }
                if ($hasPos1) {
                    return [
                        'allowed' => false,
                        'reason'  => 'Anda telah melakukan perhitungan di Pos 1 untuk part ini. Verifikasi Pos 2 harus dikerjakan oleh orang yang berbeda.'
                    ];
                }
                break;

            case 'scale':
                // Timbangan tidak boleh diisi oleh Kurir
                if ($isCourier) {
                    return [
                        'allowed' => false,
                        'reason'  => 'Anda adalah Kurir yang mengambil part ini dari rak. Anda tidak boleh menjadi PIC atau Partner timbangan untuk part ini.'
                    ];
                }
                if ($hasPos1 || $hasPos2) {
                    return [
                        'allowed' => false,
                        'reason'  => 'Anda telah menghitung part ini di Pos Manual. Tidak boleh menghitung ulang di timbangan dengan user yang sama.'
                    ];
                }
                break;

            case 'returner':
                // Pengembalian:
                // Dilarang jika pernah menghitung di Pos 1, Pos 2, atau Timbangan
                if ($hasPos1 || $hasPos2 || $isScale || $hasScaleHistory) {
                    return [
                        'allowed' => false,
                        'reason'  => 'Anda telah terlibat dalam perhitungan fisik part ini (Pos 1/Pos 2/Timbangan). Pengembalian part ke rak harus dilakukan oleh personel lain.'
                    ];
                }

                // Cek relasi dengan Kurir:
                // Jika is_courier_return_merged == true, Kurir part ini BOLEH mengembalikan part ini.
                // Jika is_courier_return_merged == false, Kurir part ini TIDAK BOLEH mengembalikan part ini.
                if ($isCourier && !$isMerged) {
                    return [
                        'allowed' => false,
                        'reason'  => 'Pada jadwal SO ini, peran Kurir dan Pengembali diatur TERPISAH. Anda adalah kurir pembawa part ini, sehingga pengembalian harus dilakukan oleh member lain.'
                    ];
                }
                break;
        }

        return ['allowed' => true, 'reason' => null];
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

