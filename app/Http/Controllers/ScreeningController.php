<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Medical;
use App\Models\MedicalScreen;
use App\Models\Poli;
use App\Models\Reservasi;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\JWTAuth;
class ScreeningController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }
    public function screening($id){
        try{
            $data=DB::select("SELECT f.*,k.nama,k.datatype,f.id as medform_id FROM medform f
                join medkind k on f.medkind_id=k.id where f.poli_id=$id
            ;");
            if(count($data)<1){
                throw new Exception("Form Poli Belum di Setting");
            }
            return Tools::MyResponse(true,"OK",$data,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function submitscreening(Request $request){
        $data=$request->all();
        DB::beginTransaction();
        try{
            $this->validate($request,[
                "reservasi_id"=>"required",
                "screenitems.*.medkind_id"=>"required",
                "screenitems.*.medform_id"=>"required",
                "screenitems.*.val_desc"=>"required",
            ]);
            $reservasi=Reservasi::find($data['reservasi_id']);
            if(!$reservasi){
                throw new Exception($reservasi);
            }
            $token = $this->jwt->getToken();
            //$user= Auth::guard('staff')->user($token);
            $medicaldata=[
                "poli_id"=>$reservasi->poli_id,
                "pasien_id"=>$reservasi->pasien_id,
                "status"=>"1",
              //  "staff_id"=>$user->id
            ];
            $medical=Medical::create($medicaldata);
            $medicalid=$medical->id;
            $screenitems=$data['screenitems'];
            foreach($screenitems as $items){
                $items["medical_id"]=$medicalid;
                $items["poli_id"]=$reservasi->poli_id;
                Tools::CheckMedkindinForm($items["medkind_id"],$reservasi->poli_id,$items["medform_id"]);
                $medcr=MedicalScreen::where('medical_id',$medicalid)->where('medkind_id',$items["medkind_id"])->first();
                if($medcr!=null){
                    throw new Exception("Cannot Input Same Item in one Form");
                }
              //  $items["staff_id"]=$user->id;
                MedicalScreen::create($items);
            }
            DB::commit();
            return Tools::MyResponse(true,"OK",$data,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
