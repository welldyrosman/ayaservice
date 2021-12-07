<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formformat extends Model
{
    protected $table = 'formformat';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title','formformat_id', 'formkind_id'
    ];

    public function subtitle() {
        return $this->hasMany(Formformat::class,"formformat_id","id")->with('subtitle','input');
    }
    public function input() {
        return $this->hasMany(MedicalForm::class,'formformat_id')->with('medkind');
    }
}
