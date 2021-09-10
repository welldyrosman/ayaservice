<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\DetailResep;
use App\Models\MedicalScreen;
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
                "resep_id"=>"required",
                "preorder.*.barang_id"=>"required",
            ]);
            $data=$request->all();
            $id=$data["resep_id"];
            $items=$request->input("preorder");
            foreach($items as $item){
                $resepdetail=DetailResep::where('resep_id')->where('barang_id',$item['barang_id'])->first();
                $resepdetail->fill([
                    "ispreorder"=>1
                ]);
                $resepdetail->save();
            }
            Tools::MedChangeStatus($id,3,3,2,5);
            DB::commit();
            return Tools::MyResponse(true,"OK",null,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
       }
    }
    public function gettodaylist(){
        $med=DB::select("select r.*,CONCAT('MED-',LPAD(r.id,6,'0')) as kode_trans,p.nama,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien from resep r
        left join medical m on r.medical_id=m.id
        left join pasiens p on m.pasien_id=p.id where cast(r.created_at as date)='$this->now' and r.status=2");
        return Tools::MyResponse(true,"OK",$med,200);
    }
}
