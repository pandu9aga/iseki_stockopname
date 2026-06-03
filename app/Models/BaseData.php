<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseData extends Model
{
    protected $table = 'base_datas';
    protected $primaryKey = 'Id_Base_Data';
    public $timestamps = false;

    protected $fillable = [
        'Code_Part',
        'Name_Part',
        'Code_Rack',
        'Area',
        'Location'
    ];
}
