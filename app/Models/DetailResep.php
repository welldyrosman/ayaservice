<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailResep extends Model
{
    protected $table = 'resep_detail';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'resep_id', 'barang_id','isComposite','qty','unit','harga','ispreorder'
    ];
}
