<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangIn extends Model
{
    protected $table = 'barang_in';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'barang_id','qty','harga','supplier'
    ];
}
