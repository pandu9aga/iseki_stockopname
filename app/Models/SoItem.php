<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoItem extends Model
{
    use HasFactory;

    protected $fillable = [
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
}

