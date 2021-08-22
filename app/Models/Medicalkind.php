<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicalkind extends Model
{
    protected $table = 'medkind';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama', 'datatype','unit'
    ];
}
