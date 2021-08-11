<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Poli;
use Illuminate\Support\Facades\DB;
use Exception;
class PoliController extends Controller
{
    public function getall(){
        $poli=Poli::all();
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
}
