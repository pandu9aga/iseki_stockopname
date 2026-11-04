<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoScaleRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'so_item_id',
        'round_no',
        'stage',
        'weight_no_sample',
        'weight_with_sample',
        'sample_qty',
        'error_margin_percent',
        'result_count',
        'status',
        'photo_path',
        'notes',
    ];

    public function session()
    {
        return $this->belongsTo(SoScaleSession::class, 'session_id');
    }

    public function soItem()
    {
        return $this->belongsTo(SoItem::class, 'so_item_id');
    }
}
