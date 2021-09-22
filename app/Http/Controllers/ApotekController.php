<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\DetailResep;
use App\Models\MedicalScreen;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;
use Tymon\JWTAuth\JWTAuth;

class ApotekController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $token = $this->jwt->getToken();
        $this->user= Auth::guard('staff')->user($token);
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
                $resepdetail=DetailResep::where('resep_id',$id)->where('barang_id',$item['barang_id'])->first();
                $resepdetail->fill([
                    "ispreorder"=>1
                ]);
                $resepdetail->save();
            }
            Tools::MedChangeStatus($id,4,4,3,6);
            DB::commit();
            return Tools::MyResponse(true,"OK",null,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
       }
    }
    private function stockquery(){
        $sql="
        with sumin as(
            select barang_id,sum(qty) as in_qty from barang_in group by barang_id),
        sumout as(
            select barang_id,sum(qty) as out_qty from barang_out group by barang_id
        ),
        inventories as(
            select b.*,IFNULL(i.in_qty,0) as in_qty,IFNULL(o.out_qty,0) as out_qty,IFNULL(i.in_qty,0)-IFNULL(o.out_qty,0) as stock from barang b
            left join sumin i on b.id=i.barang_id
            left join sumout o on b.id=o.barang_id)
        select * from inventories";
        return $sql;
    }
    private function getemptyitem(){
        $sql=$this->stockquery().' where stock<1;';
        $data=DB::select($sql);
        return $data;
    }
    private function getwarnitem(){
        $sql=$this->stockquery().' where stock<10 and stock>1;';
        $data=DB::select($sql);
        return $data;
    }
    public function showemptyitem(){
        try{
            $data=$this->getemptyitem();
            return Tools::MyResponse(true,"OK",$data,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function showwarnitem(){
        try{
            $data=$this->getwarnitem();
            return Tools::MyResponse(true,"OK",$data,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function dashboard(){
        try{
            $data=new stdClass();
            $data->empty=count($this->getemptyitem());
            $data->preorder=10;
            $data->emptywarning=count($this->getwarnitem());
            $data->needprepare=10;
            return Tools::MyResponse(true,"OK",$data,200);
        }catch(Exception $e){
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
