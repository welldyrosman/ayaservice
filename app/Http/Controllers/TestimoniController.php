<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Testimoni;
use App\Models\Pasien;
use App\Models\Reservasi;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Tymon\JWTAuth\JWTAuth;
class TestimoniController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        //throw new Exception("sdfsd");
        $this->jwt = $jwt;
        $this->now=Carbon::now()->toDateString();
    }
    public function getalltesti(){
        $testi=Testimoni::with('pasien','staff')->get();
        return Tools::MyResponse(true,"OK",$testi,200);
    }
    public function gettestipublish(){
        $testi=Testimoni::with('pasien','staff')->where("publish",1)->get();
        return Tools::MyResponse(true,"OK",$testi,200);
    }
    public function gettetibyid($id){
        $testi=Testimoni::find($id)->with('pasien','staff')->get();
        return Tools::MyResponse(true,"OK",$testi,200);
    }
    public function publish($id){
        return $this->publishsrv($id,1);
    }
    private function publishsrv($id,$publish){
        try{
            $testi=Testimoni::findOrFail($id);
            $testi->fill([
                "publish"=>$publish
            ]);
            $testi->save();
            return Tools::MyResponse(true,"OK",$testi,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function unpublish($id){
        return $this->publishsrv($id,0);
    }
    public function delete($id){
        try{
            $testi=Testimoni::findOrFail($id);
            $testi->delete();
            return Tools::MyResponse(true,"delete OK",null,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function deletepasien($id){
        try{
        $token = $this->jwt->getToken();
        $user= Auth::guard('api')->user($token);
        $pasein=Pasien::where('email', $user['email'])->first();
        $testi=Testimoni::findorFail($id)->where("pasien_id",$pasein->id);
        return $this->delete($id);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function create(Request $request){
        DB::beginTransaction();
        try{
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $data=$this->validate($request,[
                "pasien_id"=>"required",
                "testimoni"=>"required",
                "star"=>"required",
            ]);
            $data["staff_id"]=$user->id;
            $pasien=Pasien::findorFail($data["pasien_id"]);
            $testi=Testimoni::create($data);
            DB::commit();
            return Tools::MyResponse(true,"OK",$testi,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
       }
    }
    public function givetesti(Request $request){
        DB::beginTransaction();
        try{

            $token = $this->jwt->getToken();
            $user= Auth::guard('api')->user($token);
            $pasein=Pasien::where('email', $user['email'])->first();
            $data=$this->validate($request,[
                "reservasi_id"=>"required",
                "testimoni"=>"required",
                "star"=>"required",
            ]);
            $reservasi=Reservasi::find($data["reservasi_id"]);
            if(!$reservasi){
                throw new Exception("Reservasi tidak ditemukan");
            }
            if($reservasi->status!=7){
                throw new Exception("Tidak dapat membuat testimoni jika tidak menyelesaikan kunjungan");
            }
            $data["pasien_id"]=$pasein->id;
            $cek=Testimoni::where("reservasi_id",$data["reservasi_id"])->where("pasien_id",$data["pasien_id"])->first();
            if($cek){
                throw new Exception("Hanya Dapat membuat 1 testimoni dalam 1 kunjungan");
            }
            $testi=Testimoni::create($data);
            DB::commit();
            return Tools::MyResponse(true,"OK",$testi,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
       }
    }
}
