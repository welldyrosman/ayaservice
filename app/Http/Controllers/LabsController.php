<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Labs;
use App\Models\LabsInCharge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
class LabsController extends Controller
{
    public function getall(){
        $Labs=Labs::all();
        return Tools::MyResponse(true,"OK",$Labs,200);
    }
    public function getid($id){
       try{
            $Labs=Labs::find($id);
            if (!$Labs) {
                throw new Exception("Hasil Lab tidak ditemukan");
            }
            return Tools::MyResponse(true,"OK",$Labs,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function create(Request $request){
        $data = $request->all();
        try{
            $this->validate($request,[
                'medical_id' => 'required'],['required'=>':attribute cannot Empty']);

            $cek=Labs::where('medical_id',$data['medical_id'])->first();
            if($cek!=null){
                throw new Exception("Labs was exist");
            }
            $Labs = Labs::create($data);
            return Tools::MyResponse(true,"OK",$Labs,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function delete($id){
        try{
            $Labs = Labs::find($id);
            if (!$Labs) {
                throw new Exception("Pasien tidak ditemukan");
            }
            $Labs->delete();
            return Tools::MyResponse(true,"Labs Was Deleted",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function update(Request $request,$id){
        try{
            $Labs= Labs::where('medical_id',$id)->first();
            if(!$Labs){
                throw new Exception("Labs Tidak Ditemukan");
            }
            $data=$request->all();
            $Labs->fill($data);
            $Labs->save();
            return Tools::MyResponse(true,"Labs Was Updated",$Labs,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }


}
