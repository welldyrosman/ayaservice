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
            foreach($poli as $p){
                $query="SELECT a.*,p.nama FROM u5621751_ayaklinik.antrian a
                join u5621751_ayaklinik.pasiens p on a.pasien_id=p.id
                where a.queue_date=current_date() and a.status in(1,2) and a.poli_id=2
                order by a.reg_time asc";
                $antrislq=DB::select($query);
                $data->{$p->id}=$antrislq;
            }
            return Tools::MyResponse(true,"Data Antrian",$data,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function tomedical($id){
        DB::beginTransaction();
        try{
            $antrian=Antrian::find($id);
            if(!$antrian){
                throw new Exception("Cannot Found Antrian");
            }
            $antrian->fill(["status"=>"2"]);
            $antrian->save();
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $dokter=Dokter::where('email',$user->email)->first();
            $medic=[
                "pasien_id"=>$antrian->pasien_id,
                "poli_id"=>$antrian->poli_id,
                "dokter_id"=>$dokter->id,
                "status"=>"1"
            ];

            $medical=Medical::create($medic);
            $resep=Resep::create([
                "medical_id"=>$medical->id,
                "status"=>"1"
            ]);
            $data=new stdClass();
            $data->medic=$medical;
            $data->resep=$resep;
            DB::commit();
            return Tools::MyResponse(true,"Execution OK",$data,200);
        }
        catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
