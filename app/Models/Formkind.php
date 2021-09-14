<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formkind extends Model
{
    protected $table = 'formkind';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'poli_id', 'kind_nm'
    ];
}
