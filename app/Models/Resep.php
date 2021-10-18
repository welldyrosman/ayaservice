<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resep extends Model
{
    protected $table = 'resep';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'resep_time', 'staff_id','status','medical_id','discount','transtype','cust_nm','phone_no','pasien_id'
    ];
    public function detailresep() {
        return $this->hasMany(DetailResep::class);
    }
    public function medical() {
        return $this->belongsTo(Medical::class);
    }
    public function staff() {
        return $this->belongsTo(Staff::class);
    }
}
