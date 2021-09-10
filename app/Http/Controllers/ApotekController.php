<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWTAuth;

class ApotekController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->now=Carbon::now()->toDateString();
    }
    public function submitcheck(Request $request){
       DB::beginTransaction();
       try{
            $this->validate($request,[
                "medical_id"=>"required",
            ]);
            $data=$request->all();
            $id=$data["medical_id"];
            Tools::MedChangeStatus($id,3,3,2,5);
            DB::commit();
            return Tools::MyResponse(true,"OK",null,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
       }
    }
    public function gettodaylist(){
        $med=DB::select("select m.*,CONCAT('MED-',m.poli_id,'-',LPAD(m.id,4,'0')) as kode_pasien,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien,p.nama from medical m
        join pasiens p on m.pasien_id=p.id where cast(m.created_at as date)='$this->now' and m.status=4");
        return Tools::MyResponse(true,"OK",$med,200);
    }
}
