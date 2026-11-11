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
        'is_courier_return_merged',
        'description',
        'created_by',
    ];

    protected $casts = [
        'start_date'               => 'date',
        'end_date'                 => 'date',
        'is_courier_return_merged' => 'boolean',
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

    /**
     * Dapatkan nama folder penyimpanan upload untuk event SO ini.
     * Contoh: "SO-2026-001" atau "EVENT_1" atau sanitized event_name.
     */
    public function getUploadFolder(): string
    {
        $code = $this->event_code ?: ('EVENT_' . $this->id);
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $code);
        return trim($safe, '._-') ?: ('EVENT_' . $this->id);
    }

    /**
     * Dapatkan nama folder event aktif saat ini (atau 'general' jika belum ada event aktif).
     */
    public static function getActiveUploadFolder(): string
    {
        $active = self::getActiveEvent();
        if ($active) {
            return $active->getUploadFolder();
        }
        return 'general';
    }
}

