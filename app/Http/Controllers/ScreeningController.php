<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Medical;
use App\Models\MedicalScreen;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\Reservasi;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;
use stdClass;
use Tymon\JWTAuth\JWTAuth;
class ScreeningController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }
    private function getform($id){
        $data=DB::select("SELECT f.*,k.nama,k.datatype,f.id as medform_id FROM medform f
        join medkind k on f.medkind_id=k.id where f.formkind_id=$id
    ;");
        return $data;
    }
    public function screening($id){
        try{
           $data=$this->getform($id);
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
                "formkind_id"=>"required",
                "screenitems.*.medkind_id"=>"required",
                "screenitems.*.medform_id"=>"required",
                "screenitems.*.val_desc"=>"required",
            ]);
            $reservasi=Reservasi::find($data['reservasi_id']);
            if(!$reservasi){
                throw new Exception($reservasi);
            }
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $poliid=$reservasi->poli_id;
            $pasienid=$reservasi->pasien_id;
            $medicaldata=[
                "poli_id"=>$poliid,
                "pasien_id"=>$pasienid,
                "status"=>"1",
                "staff_id"=>$user->id,
                "formkind_id"=>$data["formkind_id"]
            ];
            $medical=Medical::create($medicaldata);
            $medicalid=$medical->id;
            $screenitems=$data['screenitems'];
            foreach($screenitems as $items){
                $items["medical_id"]=$medicalid;
                $items["poli_id"]=$poliid;
                Tools::CheckMedkindinForm($items["medkind_id"],$poliid,$items["medform_id"]);
                $medcr=MedicalScreen::where('medical_id',$medicalid)->where('medkind_id',$items["medkind_id"])->first();
                if($medcr!=null){
                    throw new Exception("Cannot Input Same Item in one Form");
                }
                $items["formkind_id"]=$data["formkind_id"];
                $items["staff_id"]=$user->id;
                MedicalScreen::create($items);
            }
            if($reservasi->medical_id!=null){
                throw new Exception("This Pasien Has Been Screening before");
            }
            $antrian=Antrian::create([
                "reg_time"=>$reservasi->created_at,
                "poli_id"=>$poliid,
                "status"=>1,
                "staff_id"=>null,
                "queue_date"=>$reservasi->tgl_book,
                "medical_id"=>$medicalid,
                "pasien_id"=>$pasienid
            ]);
            $reservasi->fill([
                "medical_id"=>$medicalid,
                "antrian_id"=> $antrian->id,
                "status"=>3
            ]);
            $reservasi->save();
            DB::commit();
            return Tools::MyResponse(true,"OK",$data,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function doktergetscreen(Request $request,$id){
        DB::beginTransaction();
        try{
            $antrian=Antrian::find($id);
            if(!$antrian){
                throw new Exception("Cannot Found Antrian");
            }
            $medical=Medical::find($antrian->medical_id);
            $getform=$this->getform($medical->formkind_id);
            $pasien=Pasien::find($antrian->pasien_id);
            $screendata=MedicalScreen::where('medical_id',$antrian->medical_id)->get();
            $antrian->fill([
                "status"=>"2"
            ]);
            $antrian->save();
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $dokter=Dokter::where('email',$user->email)->first();
            $medical->fill([
                "dokter_id"=>$dokter->id //hardcode temporary
            ]);
            $medical->save();
            DB::commit();
            $ret=new stdClass();
            $ret->formtemplate=$getform;
            $ret->pasien=$pasien;
            $ret->screendata=$screendata;
            $ret->medical=$medical;
            return Tools::MyResponse(true,"OK",$ret,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
