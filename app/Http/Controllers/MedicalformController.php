<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\MedicalForm;
use App\Models\MedicalKind;
use App\Models\MedicalScreen;
use Illuminate\Support\Facades\DB;
use Exception;
class MedicalformController extends Controller
{
    public function getall($id){
        $Medicalform=DB::table('medform')->join('medkind','medkind.id','=','medform.medkind_id')->where('medform.formkind_id',$id)->get();
        return Tools::MyResponse(true,"OK",$Medicalform,200);
    }
    public function getid($id){
       try{
            $Medicalform=DB::table('medform')->join('medkind','medkind.id','=','medform.medkind_id')->where('medform.formkind_id',$id)->get();
            if (!$Medicalform) {
                throw new Exception("Medical kind tidak ditemukan");
            }
            return Tools::MyResponse(true,"OK",$Medicalform,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function create(Request $request){
        $data = $request->all();
        try{
            $this->validate($request,[
            'formkind_id' => 'required',
            'medkind_id' => 'required',
            ],['required'=>':attribute cannot Empty']);
            $medkind=Medicalkind::find($data['medkind_id']);
            Tools::Checkformkind($data['formkind_id']);
            if(!$medkind){
                throw new Exception("Cannot Found Medical Kind");
            }
            $Medicalform = Medicalform::create($data);

            return Tools::MyResponse(true,"OK",$Medicalform,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function delete($id){
        try{
            $Medicalform = Medicalform::find($id);
            if (!$Medicalform) {
                throw new Exception("Medical kind tidak ditemukan");
            }
            $medscreen=MedicalScreen::where('formkind_id',$id)->get();
            if(count($medscreen)>0){
                throw new Exception("this form has been used, cannot delete");
            }
            $Medicalform->delete();
            return Tools::MyResponse(true,"Medicalform Was Deleted",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function update(Request $request,$id){
        try{
            $Medicalform=Medicalform::find($id);
            if(!$Medicalform){
                throw new Exception("Medicalform Tidak Ditemukan");
            }
            $this->validate($request,[
                'Medicalform' => 'required',
                'ruangan' => 'required'],['required'=>':attribute cannot Empty']);
            $data=$request->all();
            $Medicalform->fill($data);
            $Medicalform->save();
            return Tools::MyResponse(true,"Medicalform Was Updated",$Medicalform,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
