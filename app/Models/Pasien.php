<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    protected $table = 'pasiens';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ktpno', 'nama','tempat_lahir','tgl_lahir','jk','status_nikah',
        'alamat','kec','kota','pekerjaan','no_telp','email','no_kk','password',
        'reg_rule','status_akun','add_user'
    ];
    protected $hidden = [
        'password',
    ];
}
