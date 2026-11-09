<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'active_role',
        'area_id',
        'area_letter',
        'scale_partner_nik',
        'scale_id',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'nik', 'nik');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function partner()
    {
        return $this->belongsTo(Member::class, 'scale_partner_nik', 'nik');
    }

    public function scale()
    {
        return $this->belongsTo(Scale::class, 'scale_id');
    }
}

