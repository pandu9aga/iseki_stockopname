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
        'notes',
    ];
}

