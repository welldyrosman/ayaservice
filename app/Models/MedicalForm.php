<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalForm extends Model
{
    protected $table = 'medform';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'formkind_id', 'medkind_id','dokter_only'
    ];
}
