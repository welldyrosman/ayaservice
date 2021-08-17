<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medical extends Model
{
    protected $table = 'medical';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'poli_id', 'dokter_id','pasien_id','diagnosa','status','treatment_kind'];
}
