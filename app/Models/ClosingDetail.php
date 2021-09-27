<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClosingDetail extends Model
{
    protected $table = 'closing_detail';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'closing_id','resep_id','sum_amt'
    ];
}
