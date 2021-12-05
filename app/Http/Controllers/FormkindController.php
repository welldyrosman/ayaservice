<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Formkind;
use App\Models\Medicalform;
use App\Models\Medicalkind;
use App\Models\Poli;
use Exception;
use Illuminate\Support\Str;
class FormkindController extends Controller
{
    public function getall(){
        $formkind=Formkind::with('poli')->get();
        return Tools::MyResponse(true,"OK",$formkind,200);
    }
    public function getid($id){
        try{
             $Formkind=Formkind::with('poli')->find($id);
             if (!$Formkind) {
                 throw new Exception("Formkind tidak ditemukan");
             }
             return Tools::MyResponse(true,"OK",$Formkind,200);
         }
         catch(Exception $e){
             return Tools::MyResponse(false,$e,null,401);
         }
     }
     public function create(Request $request){

        try{
            $this->validate($request,[
            'poli_id' => 'required',
            'kind_nm' => 'required'],['required'=>':attribute cannot Empty']);
            $data = $request->all();
            $cek=Poli::find($data['poli_id']);
            if($cek==null){
                throw new Exception("poli doent exist");
            }
            $Formkind = Formkind::create($data);
            return Tools::MyResponse(true,"OK",$Formkind,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }

    public function delete($id){
        try{
            $Formkind = Formkind::find($id);
            if (!$Formkind) {
                throw new Exception("Formkind tidak ditemukan");
            }
            $medform=Medicalform::where('formkind_id',$id)->get();
            if(count($medform)>0){
                throw new Exception("Formkind cannot delete");
            }
            $Formkind->delete();
            return Tools::MyResponse(true,"Formkind Was Deleted",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function getunselect($id){
        try{
            $Medicalkind = Medicalkind::whereNotIn('id',Medicalform::select('medkind_id')->where('formkind_id',$id))->get();
            if (!$Medicalkind) {
                throw new Exception("Medicalkind tidak ditemukan");
            }
            return Tools::MyResponse(true,"OK",$Medicalkind,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function update(Request $request,$id){
        try{
            $Formkind=Formkind::find($id);
            if(!$Formkind){
                throw new Exception("Poli Tidak Ditemukan");
            }
            $this->validate($request,[
                'poli_id' => 'required',
                'kind_nm' => 'required'],['required'=>':attribute cannot Empty']);
            $data=$request->all();
            $Formkind->fill($data);
            $Formkind->save();
            return Tools::MyResponse(true,"Poli Was Updated",$Formkind,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
