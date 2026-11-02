<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'so_item_id',
        'history_id',
        'pos_type',
        'photo_path',
    ];

    public function soItem()
    {
        return $this->belongsTo(SoItem::class, 'so_item_id');
    }

    public function history()
    {
        return $this->belongsTo(SoCountHistory::class, 'history_id');
    }
}

