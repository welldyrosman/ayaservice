<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\Reservasi;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWTAuth;

class ReservasiController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $token = $this->jwt->getToken();
        $this->user = $this->jwt->toUser($token);
        $this->pasien=Pasien::where('email', $this->user['email'])->first();
    }
    private function gettodayreservasi(){

    }
    private function changestatus($id,$newstatus,$reason){
        try{
            $reservasi=Reservasi::find($id);
            if(!$reservasi){
                throw new Exception("Cannot Found Reservation");
            }
            if($newstatus=='3'&&$reservasi->status!=1){
                throw new Exception("Cannot Check IN, Please Check Current Status");
            }
            $data=array(
                'status'=>$newstatus,'cancel_reason'=>$reason
            );
            if($newstatus==3){
                $data['checkin_time']=Carbon::now();
            }else{
                $data['cancel_time']=Carbon::now();
            }
            $reservasi->fill($data);
            $reservasi->save();
            return Tools::MyResponse(true,"Reservation Was Updated",$reservasi,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function checkin($id){
       return $this->changestatus($id,'3',null);
    }
    public function cancelreservasi(Request $request,$id){
        $this->validate($request,['cancel_reason'=>'required']);
        $reason=$request->input('cancel_reason');
        return $this->changestatus($id,'2',$reason);
    }
    public function myreservation(){
        try{
            $reservasi=DB::table('reservasi as r')
            ->join('poli as p','r.poli_id','=','p.id')->get();
            return Tools::MyResponse(true,"Query Reservation success",$reservasi,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function bookonline(Request $request){
        try{
            $this->validate($request,[
                'poli_id'=>'required',
                'tgl_book'=>'required',
            ],['required'=>':attibute cannot empty']);

            $data=$request->all();
            $poliid=$request->input('poli_id');
            $tglbook=$request->input('tgl_book');
            $pasienid=$this->pasien->id;
            $poli=Poli::find($poliid);
            if(!$poli){
                throw new Exception("Cannot Found Poli");
            }
            $resevasicek=Reservasi::where('pasien_id',$pasienid)
            ->where('tgl_book',$tglbook)
            ->where('status','!=','2')->first();
            if($resevasicek){
                throw new Exception("Cannot make more than 1 Reservation");
            }
            $data['pasien_id']=$this->pasien->id;
            $data['status']=1;
            $reservasi=Reservasi::create($data);
            return Tools::MyResponse(true,"OK",$reservasi,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
