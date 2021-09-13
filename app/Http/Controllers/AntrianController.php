<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Medical;
use App\Models\Poli;
use App\Models\Resep;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;
use Tymon\JWTAuth\JWTAuth;

class AntrianController extends Controller{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;

    }
    public function getscreen(){
        try{
            $poli=Poli::all();
            $data=new stdClass();
            $onprocess=new stdClass();
            $now=Carbon::now()->toDateString();
            foreach($poli as $p){
                $query="SELECT a.*,p.nama,p.tgl_lahir,p.jk,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien,
                i.poli FROM antrian a
                join pasiens p on a.pasien_id=p.id
                join poli i on a.poli_id=i.id
                where a.queue_date='$now' and a.status=1 and a.poli_id=$p->id
                order by a.reg_time asc";
                $query2="SELECT a.*,p.nama,p.tgl_lahir,p.jk,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien,
                i.poli,d.nama as dokter FROM antrian a
                join pasiens p on a.pasien_id=p.id
                join poli i on a.poli_id=i.id
                join poli_incharge ic on a.poli_id=ic.poli_id and ic.praktek_date='$now'
                join dokter d on ic.dokter_id=d.id
                 where a.queue_date='$now' and a.status=2 and a.poli_id=$p->id
                 order by a.reg_time asc";
                $antrislq=DB::select($query);
                $process=DB::select($query2);
                $data->{$p->id}=$antrislq;
                if(count($process)<1){
                    $schedule=DB::select("select ic.*,d.nama from poli_incharge ic join dokter d on ic.dokter_id=d.id
                    where ic.poli_id='$p->id' and ic.praktek_date='$now'
                    ");
                    $p->dokter=count($schedule)<1?"Belum Diatur":$schedule[0]->nama;
                }
                $onprocess->{$p->id}=count($process)<1?$p:$process[0];
            }
            $ret=new stdClass();
            $ret->queue=$data;
            $ret->onprocess=$onprocess;
            return Tools::MyResponse(true,"Data Antrian",$ret,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }

}
