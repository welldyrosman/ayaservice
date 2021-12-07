<?php

namespace App\Http\Controllers;

use App\Helpers\Tools;
use App\Models\Formformat;
use App\Models\MedicalForm;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormformatController extends Controller
{
    public function getfolderbyformkind($id){
        $formformat=Formformat::with('subtitle','input')->where('formkind_id',$id)->where('formformat_id',0)->get();
        return Tools::MyResponse(true,"OK",$formformat,200);
    }
    public function createfolder(Request $request){
        DB::beginTransaction();
        try{
            $data=$this->validate($request,[
                "title"=>"required",
                "formformat_id"=>"required",
                "formkind_id"=>"required"
            ]);
            $formformat=Formformat::create($data);
            DB::commit();
            return Tools::MyResponse(true,"OK",$formformat,200);
        }catch(Exception $e){
            DB::rollback();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function deletefolder($id){
        try{
            $formformat=Formformat::find($id);
            $checkingchild=Formformat::where('formformat_id',$id)->get();
            $MedicalForm = MedicalForm::where('formformat_id',$id)->get();
            if (count($MedicalForm)||count($checkingchild)>0) {
                throw new Exception("Hapus Isi Group terlebih dahulu");
            }
            $formformat->delete();
            return Tools::MyResponse(true,"Group Was Deleted",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function deletefill($id){
        try{
            $MedicalForm = MedicalForm::find($id);
            if (!$MedicalForm) {
                throw new Exception("Cannot Found Med form");
            }
            $MedicalForm->delete();
            return Tools::MyResponse(true,"Medform Was Deleted",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function fillfolder(Request $request){
        DB::beginTransaction();
        try{
            $data=$this->validate($request,[
                "formformat_id"=>"required",
                "medkind_id"=>"required"
            ]);
            $medicalform=MedicalForm::create($data);
            DB::commit();
            return Tools::MyResponse(true,"OK",$medicalform,200);
        }catch(Exception $e){
            DB::rollback();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
