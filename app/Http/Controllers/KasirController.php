<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Medical;
use App\Models\Resep;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;
use Tymon\JWTAuth\JWTAuth;

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
            $bruto=$totalobat[0]->total;
            $totalnet=$bruto+$medicalfee-$resep->dicount;
            if($money<$totalnet){
                throw new Exception("Payment Failed");
            }
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $resep->fill([
                "pay_amt"=>$money,
                "staff_id"=>$user->id
            ]);
            $resep->save();
            Tools::MedChangeStatus($id,5,5,4,7);
            DB::commit();
            return Tools::MyResponse(true,"OK",["rest"=>$money-$totalnet],200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
       }
    }
    public function getdetailcomposite($barangid,$resepid){
        try{
            $dt=DB::select("select b.*,o.qty from barang_out o
            join barang b on o.barang_id=b.id where o.barang_id=$barangid and o.resep_id=$resepid");
            return Tools::MyResponse(true,"OK",$dt,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
       }
    }
    public function gettodaylist(){
        $med=DB::select("select r.*,CONCAT('TRX',LPAD(r.id,6,'0')) as kode_trans,p.nama,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien from resep r
        left join medical m on r.medical_id=m.id
        left join pasiens p on m.pasien_id=p.id where cast(r.created_at as date)='$this->now' and r.status=3");
        return Tools::MyResponse(true,"OK",$med,200);
    }
    public function getpayitem($id){
        $getresep=DB::select("select r.*,rd.barang_id,rd.qty,rd.unit,rd.harga,rd.harga*rd.qty as subtot,
        b.nama
        from resep r
        left join resep_detail rd on r.id=rd.resep_id
        join barang b on rd.barang_id=b.id
        where r.id=20
        ");
        $resep=Resep::find($id);
        $fee=0;
        if($resep->medical_id){
            $medical=Medical::find($resep->medical_id);
            $fee=$medical->fee;
        }
        $ret=new stdClass();
        $ret->resep=$resep;
        $ret->fee=$fee;
        $ret->detail_resep=$getresep;
        return Tools::MyResponse(true,"OK",$ret,200);
    }

}
