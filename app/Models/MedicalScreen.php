<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalScreen extends Model
{
    protected $table = 'medscreen';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'medical_id', 'poli_id','medkind_id','medform_id','val_desc','staff_id'
    ];
}
