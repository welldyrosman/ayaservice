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
    public function getscreen2(){
        $now=Carbon::now()->toDateString();
        $med=KasirController::querypayment($now);
        $apk=ApotekController::queryapotek($now);
        $ret=[
            "payment_queue"=>$med,
            "apotik_queue"=>$apk
        ];
        return Tools::MyResponse(true,"Data Antrian",$ret,200);
    }
    public function getscreen(){
        try{
            $poli=Poli::where('stop_mk',0)->get();
            $data=new stdClass();
            $onprocess=new stdClass();
            $now=Carbon::now()->toDateString();
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $dokter=Dokter::where('email',$user->email)->first();
            if($dokter->isdokter=='2'){
                $dtrpoli='in(6,7)';
            }
            else{
                $dtrpoli='=$p->id';
            }
            foreach($poli as $p){
                $query="SELECT a.*,p.nama,p.tgl_lahir,p.jk,CONCAT('AK',LPAD(p.id,4,'0')) as kode_pasien,
                i.poli FROM antrian a
                join pasiens p on a.pasien_id=p.id
                join poli i on a.poli_id=i.id
                where a.queue_date='$now' and a.status=1 and a.poli_id= ".$dtrpoli."
                and i.stop_mk=0
                order by a.reg_time asc";
                $query2="SELECT a.*,p.nama,p.tgl_lahir,p.jk,CONCAT('AK',LPAD(p.id,4,'0')) as kode_pasien,
                i.poli,d.nama as dokter FROM antrian a
                join pasiens p on a.pasien_id=p.id
                join poli i on a.poli_id=i.id
                join poli_incharge ic on a.poli_id=ic.poli_id and ic.praktek_date='$now'
                join dokter d on ic.dokter_id=d.id
                 where a.queue_date='$now' and a.status=2 and a.poli_id= ".$dtrpoli."
                 and i.stop_mk=0
                 order by a.reg_time asc";
                $antrislq=DB::select($query);
                $process=DB::select($query2);
                $data->{$p->id}=$antrislq;
                if(count($process)<1){
                    $schedule=DB::select("select ic.*,d.nama from poli_incharge ic join dokter d on ic.dokter_id=d.id
                    where ic.poli_id= ".$dtrpoli." and ic.praktek_date='$now'
                    ");
                    $p->dokter=count($schedule)<1?"Closed":$schedule[0]->nama;
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
