<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompositeItem extends Model
{
    protected $table = 'composite_item';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id', 'barang_id','qty'
    ];
}
