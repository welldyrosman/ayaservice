<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicalform extends Model
{
    protected $table = 'medform';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'formformat_id', 'medkind_id','dokter_only'
    ];

    public function medkind(){
        return $this->belongsTo(Medicalkind::class,'medkind_id');
    }

}
