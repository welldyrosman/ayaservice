<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimoni extends Model
{
    protected $table = 'testimoni';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pasien_id','staff_id','testimoni','star','reservasi_id','fromstaff','publish'
    ];
    public function pasien() {
        return $this->belongsTo(Pasien::class);
    }
    public function staff() {
        return $this->belongsTo(Staff::class);
    }
}
