<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoliInCharge extends Model
{
    protected $table = 'poli_incharge';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'poli_id', 'praktek_date','dokter_id','staff_id'
    ];
}
