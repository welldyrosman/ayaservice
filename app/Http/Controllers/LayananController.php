<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Layanan;
use Illuminate\Support\Facades\DB;
use Exception;
class LayananController extends Controller
{
    public function getall(){
        $Layanan=Layanan::all();
        return Tools::MyResponse(true,"OK",$Layanan,200);
    }
    public function getid($id){
       try{
            $Layanan=Layanan::find($id);
            if (!$Layanan) {
                throw new Exception("Layanan tidak ditemukan");
            }
            return Tools::MyResponse(true,"OK",$Layanan,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function create(Request $request){
        $data = $request->all();
        try{
            $this->validate($request,[
                'service_name' => 'required',
                'service_desc' => 'required']
                ,['required'=>':attribute cannot Empty']);
            $Layanan = Layanan::create($data);
            return Tools::MyResponse(true,"OK",$Layanan,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function delete($id){
        try{
            $Layanan = Layanan::find($id);
            if (!$Layanan) {
                throw new Exception("Layanan tidak ditemukan");
            }
            $Layanan->delete();
            return Tools::MyResponse(true,"Layanan Was Deleted",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function update(Request $request,$id){
        try{
            $Layanan= Layanan::find($id);
            if(!$Layanan){
                throw new Exception("Layanan Tidak Ditemukan");
            }
            $data=$request->all();
            $Layanan->fill($data);
            $Layanan->save();
            return Tools::MyResponse(true,"Layanan Was Updated",$Layanan,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }


}
