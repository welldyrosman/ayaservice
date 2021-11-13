<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    protected $table = 'dokter';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama', 'tempat','tgl_lahir','pendidikan','poli_id',
        'email','desc','photo','staff_id'
    ];
    public function poli() {
        return $this->belongsTo(Poli::class);
    }
}
