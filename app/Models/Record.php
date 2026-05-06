<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    protected $table = 'records';
    protected $primaryKey = 'Id_Record';
    public $timestamps = false;

    protected $fillable = [
        'Code_Part',
        'Name_Part',
        'Code_Rack',
        'No_Sequence',
        'Area',
        'No_Card',
        'Location',
        'NIK',
        'Time_Record',
        'Count_Record',
        'Photo_Record',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'NIK', 'nik');
    }
}
