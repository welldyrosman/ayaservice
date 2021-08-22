<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Reservasi extends Model
{
    protected $table = 'reservasi';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pasien_id', 'poli_id','tgl_book','status','cancel_reason','role_id','staff_id','checkin_time','cancel_time',"antrian_id"
    ];
    public function poli()
    {
        return $this->belongsToMany(Poli::class,'poli_id','id');
    }
}
