<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoScaleSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'so_item_id',
        'member_nik',
        'partner_nik',
        'scale_id',
        'status',
        'round_count',
        'sample_qty',
        'total_without_sample',
        'total_with_sample',
        'final_count',
        'final_margin',
        'label_photo_path',
    ];

    protected $casts = [
        'sample_qty'           => 'integer',
        'total_without_sample' => 'decimal:4',
        'total_with_sample'    => 'decimal:4',
        'final_margin'         => 'decimal:2',
    ];

    public function soItem()
    {
        return $this->belongsTo(SoItem::class, 'so_item_id');
    }

    public function scale()
    {
        return $this->belongsTo(Scale::class, 'scale_id');
    }

    public function rounds()
    {
        return $this->hasMany(SoScaleRound::class, 'session_id')->orderBy('round_no');
    }

    public function sampleRounds()
    {
        return $this->hasMany(SoScaleRound::class, 'session_id')->where('stage', 'SAMPLE')->orderBy('round_no');
    }

    public function finalRounds()
    {
        return $this->hasMany(SoScaleRound::class, 'session_id')->where('stage', 'FINAL')->orderBy('round_no');
    }
}
