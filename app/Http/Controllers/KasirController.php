<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Medical;
use App\Models\Resep;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class KasirController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->now=Carbon::now()->toDateString();
    }
    public function submittrans(Request $request){
       DB::beginTransaction();
       try{
            $this->validate($request,[
                "resep_id"=>"required",
                "pay_amt"=>"required"
            ]);
            $data=$request->all();
            $id=$data["resep_id"];
            $money=$data["pay_amt"];
            $medicalfee=0;
            $resep=Resep::find($id);
            if($resep->medical_id){
                $medid=Medical::find($resep->medical_id);
                $medicalfee=$medid->fee;
            }
            $totalobat=DB::select("select sum(harga*qty) total from resep_detail r where r.resep_id=$id");
            $bruto=$totalobat[0]['total'];
            $totalnet=$bruto+$medicalfee-$resep->dicount;
            if($money<$totalnet){
                throw new Exception("Payment Failed");
            }
            $resep->fill([
                "pay_amt"=>$money
            ]);
            $resep->obat();

            Tools::MedChangeStatus($id,4,4,3,6);
            DB::commit();
            return Tools::MyResponse(true,"OK",["rest"=>$money-$totalnet],200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
       }
    }
    public function gettodaylist(){
        $med=DB::select("select r.*,CONCAT('MED-',LPAD(r.id,6,'0')) as kode_trans,p.nama,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien from resep r
        left join medical m on r.medical_id=m.id
        left join pasiens p on m.pasien_id=p.id where cast(r.created_at as date)='$this->now' and r.status=3");
        return Tools::MyResponse(true,"OK",$med,200);
    }
}
