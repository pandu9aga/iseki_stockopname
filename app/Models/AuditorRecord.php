<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditorRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'so_event_id',
        'rack_no',
        'code_part',
        'auditor_name',
        'audited_count',
        'method',
        'input_1',
        'input_2',
        'scale_id',
        'scale_number',
        'sample_qty',
        'weight_without_sample',
        'weight_with_sample',
        'weight_calculated',
        'weight_diff',
        'error_margin_percent',
        'member_diff',
        'member_error_margin_percent',
        'scale_status',
        'audit_status',
        'photo_paths',
        'notes',
    ];

    protected $casts = [
        'photo_paths' => 'array',
    ];
}

