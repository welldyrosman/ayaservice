<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Resep;
use App\Models\Task;
use Exception;
use Illuminate\Support\Facades\DB;
use stdClass;

class SpecialpayController extends Controller
{
    public function specialpay(Request $request,$id){
        DB::beginTransaction();
        try{
            $data=$this->validate($request,["payamt"=>"required"]);
            $resep=Resep::findOrFail($id);
            $resep->fill(
                [
                    "payamt"=>$data["payamt"],
                    "status"=>2
                ]
            );
            $resep->save();
            DB::commit();
            return Tools::MyResponse(true,"OK",$resep,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function setspecial($id){
        DB::beginTransaction();
        try{
            $resep=Resep::findOrFail($id);
            $resep->fill(
                [
                    "special"=>1,
                    "status"=>5
                ]
            );
            $resep->save();
            DB::commit();
            return Tools::MyResponse(true,"OK",$resep,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function getspeciallist(){
        $resep=DB::select("
            select CONCAT('TRX',LPAD(r.id,6,'0'))  as code_trans,r.* from resep r where r.status=5
        ");
        return Tools::MyResponse(true,"OK",$resep,200);
    }
    public function getspcialbyid($id){
        $resep=DB::select("
            select CONCAT('TRX',LPAD(r.id,6,'0'))  as code_trans,r.*,m.fee,p.nama
            from resep r
            left join medical m on r.medical_id=m.id
            left join pasiens p on m.pasien_id=p.id
            where r.status=5 and r.id=$id
        ");
        $detail=DB::select("select rd.*,b.nama from resep_detail rd
        join barang b on rd.barang_id=b.id where rd.resep_id=$id
        ");
        $data=new stdClass();
        $data->resep=$resep;
        $data->detail=$detail;
        return Tools::MyResponse(true,"OK",$data,200);
    }
}
