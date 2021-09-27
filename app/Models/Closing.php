<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Closing extends Model
{
    protected $table = 'closing';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'closing_amt','closing_date','staff_id','status','receive_time'
    ];
    public function staff() {
        return $this->belongsTo('App\Models\Staff');
    }
}
