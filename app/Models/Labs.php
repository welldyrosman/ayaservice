<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Labs extends Model
{
    protected $table = 'labs';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'medical_id','hb','hbsag','hiv','sifilis','asam_urat','kolesterol','gol_dar','pp_test','protein_urine','ph','glukosa','staff_id'
    ];
}
