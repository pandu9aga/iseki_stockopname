<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoCountHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'so_item_id',
        'count_type',
        'member_nik',
        'partner_nik',
        'scale_id',
        'input_1',
        'input_2',
        'weight_no_sample',
        'weight_with_sample',
        'sample_qty',
        'error_margin_percent',
        'result_count',
        'status',
        'notes',
    ];

    public function soItem()
    {
        return $this->belongsTo(SoItem::class, 'so_item_id');
    }

    public function scale()
    {
        return $this->belongsTo(Scale::class, 'scale_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_nik', 'nik');
    }

    public function partner()
    {
        return $this->belongsTo(Member::class, 'partner_nik', 'nik');
    }
}

