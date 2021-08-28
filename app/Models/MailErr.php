<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailErr extends Model
{
    protected $table = 'mail_err';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'err_msg'
    ];
}
