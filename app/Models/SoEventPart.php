<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoEventPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'so_event_id',
        'label_part_id',
        'rack_no',
        'location',
        'cell',
        'loc_cell_display',
        'item_code',
        'part_name',
        'supplier',
        'satuan',
        'tracking_code',
        'pair_index',
        'total_pairs',
    ];

    public function event()
    {
        return $this->belongsTo(SoEvent::class, 'so_event_id');
    }
}

