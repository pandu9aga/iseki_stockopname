<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_code',
        'event_name',
        'start_date',
        'end_date',
        'status',
        'total_parts_snapshot',
        'description',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function parts()
    {
        return $this->hasMany(SoEventPart::class, 'so_event_id');
    }

    public function soItems()
    {
        return $this->hasMany(SoItem::class, 'so_event_id');
    }

    public function auditorRecords()
    {
        return $this->hasMany(AuditorRecord::class, 'so_event_id');
    }

    /**
     * Dapatkan event yang sedang aktif saat ini
     */
    public static function getActiveEvent()
    {
        return self::where('status', 'ACTIVE')->orderByDesc('id')->first();
    }
}

