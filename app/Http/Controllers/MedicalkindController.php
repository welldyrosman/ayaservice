<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Medicalkind;
use App\Models\MedicalScreen;
use Illuminate\Support\Facades\DB;
use Exception;
class MedicalkindController extends Controller
{
    public function getall(){
        $Medicalkind=Medicalkind::all();
        return Tools::MyResponse(true,"OK",$Medicalkind,200);
    }
    public function getid($id){
       try{
            $Medicalkind=Medicalkind::find($id);
            if (!$Medicalkind) {
                throw new Exception("Medical kind tidak ditemukan");
            }
            return Tools::MyResponse(true,"OK",$Medicalkind,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function create(Request $request){
        $data = $request->all();
        try{
            $this->validate($request,[
            'nama' => 'required',
            'datatype' => 'required',
            ],['required'=>':attribute cannot Empty']);
            $Medicalkind = Medicalkind::create($data);
            return Tools::MyResponse(true,"OK",$Medicalkind,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function delete($id){
        try{
            $Medicalkind = Medicalkind::find($id);
            if (!$Medicalkind) {
                throw new Exception("Medical kind tidak ditemukan");
            }
            $medscreen=MedicalScreen::where('medkind_id',$id)->first();
            if($medscreen){
                throw new Exception("tidak bisa hapus medical kind yang sedang digunakan");
            }
            $Medicalkind->delete();
            return Tools::MyResponse(true,"Medicalkind Was Deleted",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function update(Request $request,$id){
        try{
            $Medicalkind=Medicalkind::find($id);
            if(!$Medicalkind){
                throw new Exception("Medicalkind Tidak Ditemukan");
            }
            $this->validate($request,[
                'Medicalkind' => 'required',
                'ruangan' => 'required'],['required'=>':attribute cannot Empty']);
            $data=$request->all();
            $Medicalkind->fill($data);
            $Medicalkind->save();
            return Tools::MyResponse(true,"Medicalkind Was Updated",$Medicalkind,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
