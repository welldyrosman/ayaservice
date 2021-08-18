<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOut extends Model
{
    protected $table = 'barang_out';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'resep_id', 'barang_id'
    ];
}
