<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Poli;
use App\Models\PoliInCharge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
class PoliController extends Controller
{
    public function getall(){
        $poli=Poli::all();
        return Tools::MyResponse(true,"OK",$poli,200);
    }
    public function getactive(){
        $poli=Poli::where('stop_mk',0)->get();
        return Tools::MyResponse(true,"OK",$poli,200);
    }
    public function getid($id){
       try{
            $poli=Poli::find($id);
            if (!$poli) {
                throw new Exception("Pasien tidak ditemukan");
            }
            return Tools::MyResponse(true,"OK",$poli,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function create(Request $request){
        $data = $request->all();
        try{

            $cek=Poli::where('poli',$data['poli'])->first();
            if($cek!=null){
                throw new Exception("poli was exist");
            }
            $this->validate($request,[
            'poli' => 'required',
            'ruangan' => 'required'],['required'=>':attribute cannot Empty']);
            $poli = Poli::create($data);
            return Tools::MyResponse(true,"OK",$poli,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function enable($id){
        try{
            $poli = Poli::find($id);
            if (!$poli) {
                throw new Exception("Poli tidak ditemukan");
            }
            $poli->fill(["stop_mk"=>0]);
            $poli->save();
            return Tools::MyResponse(true,"Poli Was Update",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function disable($id){
        try{
            $poli = Poli::find($id);
            if (!$poli) {
                throw new Exception("Poli tidak ditemukan");
            }
            $poli->fill(["stop_mk"=>1]);
            $poli->save();
            return Tools::MyResponse(true,"Poli Was Update",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function delete($id){
        try{
            $poli = Poli::find($id);
            if (!$poli) {
                throw new Exception("Pasien tidak ditemukan");
            }
            $poli->delete();
            return Tools::MyResponse(true,"Poli Was Deleted",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function update(Request $request,$id){
        try{
            $poli=Poli::find($id);
            if(!$poli){
                throw new Exception("Poli Tidak Ditemukan");
            }
            $this->validate($request,[
                'poli' => 'required',
                'ruangan' => 'required'],['required'=>':attribute cannot Empty']);
            $data=$request->all();
            $poli->fill($data);
            $poli->save();
            return Tools::MyResponse(true,"Poli Was Updated",$poli,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function createincharge(Request $request){
        DB::beginTransaction();
        try{
            $this->validate($request,[
                "poli_id"=>"required",
                "praktek_date"=>"required",
                "dokter_id"=>"required",
            ]);
            $data=$request->all();
            Tools::CheckPoli($data["poli_id"]);
            Tools::CheckDokter($data["dokter_id"]);
            $now=Carbon::now()->toDateString();
            if($data["praktek_date"]<$now){
                throw new Exception("Cannot Set Dokter Before Today");
            }
            $policheck=PoliInCharge::where('poli_id',$data['poli_id'])->where('praktek_date',$data['praktek_date'])->first();
            if($policheck){
                throw new Exception("This Date Has Scheduled");
            }
            $poliincharge=PoliInCharge::create($data);
            DB::commit();
            return Tools::MyResponse(true,"Schedule Setted",$poliincharge,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function getchargebydate($date){
        $poliin=PoliInCharge::where('praktek_date',$date)->get();
        return Tools::MyResponse(true,"Query Ok",$poliin,200);
    }
    public function gettodayincharge(){
        $now=Carbon::now()->toDateString();
        $poliin=PoliInCharge::where('praktek_date',$now)->get();
        return Tools::MyResponse(true,"Query Ok",$poliin,200);
    }
    public function getincharge(){
        $poliin=PoliInCharge::with('dokter','poli')->get();
        return Tools::MyResponse(true,"Query Ok",$poliin,200);
    }
}
