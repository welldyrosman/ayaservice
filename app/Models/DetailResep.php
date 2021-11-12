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
        'resep_id', 'barang_id','isComposite','qty','unit','harga','ispreorder','preodr_staff','eat_qty','day_qty','takaran_id'
    ];
    public function barang() {
        return $this->belongsTo('App\Models\Barang');
    }
    public function reseps() {
        return $this->belongsTo('App\Models\Resep');
    }
}
