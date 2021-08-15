<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\Reservasi;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWTAuth;

class ReservasiController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;

    }
    private function gettodayreservasi(){

    }
    private function changestatus($id,$newstatus,$reason){
        DB::beginTransaction();
        try{
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
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
                $now=Carbon::now()->format('Y-m-d');
                if($reservasi->tgl_book!=$now){
                    throw new Exception("Cannot Check in With Different Day ".$reservasi->tgl_book." now :".$now);
                }

                $chekindata=[
                    "reg_time"=>$reservasi->created_at,
                    "poli_id"=>$reservasi->poli_id,
                    "status"=>1,
                    "staff_id"=>$user->id,
                    "pasien_id"=>$reservasi->pasien_id
                ];
                $antrian=Antrian::create($chekindata);
                $data["antrian_id"]=$antrian->id;
                $data['checkin_time']=Carbon::now();
            }else{
                $data['cancel_time']=Carbon::now();
            }
            $reservasi->fill($data);
            $reservasi->save();
            DB::commit();
            return Tools::MyResponse(true,"Reservation Was Updated",$reservasi,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function checkinoffline(Request $request){
        DB::beginTransaction();
        try{
            $this->validate($request,[
                'poli_id'=>'required',
                'pasien_id'=>'required'
            ]);
            $poliid=$request->input('poli_id');
            $pasienid=$request->input('pasien_id');
            $now=Carbon::now()->format('Y-m-d');
            $cekantrian=DB::select("SELECT * FROM antrian
            where pasien_id=$pasienid
            and poli_id=$poliid
            and cast(reg_time as date)='$now'");
            if(count($cekantrian)>0){
                throw new Exception("Cannot Regist More Than 1");
            }
            Tools::CheckPoli($poliid);
            $data=$request->all();
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $chekindata=[
                "reg_time"=>Carbon::now(),
                "poli_id"=>$poliid,
                "status"=>1,
                "staff_id"=>$user->id,
                "pasien_id"=>$data['pasien_id']
            ];
            $antrian=Antrian::create($chekindata);
            DB::commit();
            return Tools::MyResponse(true,"Queue Has Been Created",$antrian,200);
        }
        catch(Exception $e){
            DB::rollback();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function checkin($id){
        try{
            return $this->changestatus($id,'3',null);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
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
            $token = $this->jwt->getToken();
            $user= Auth::guard('api')->user($token);
            $pasien=Pasien::where('email',$user['email'])->first();
            $pasienid=$pasien->id;
            Tools::CheckPoli($poliid);
            $resevasicek=Reservasi::where('pasien_id',$pasienid)
            ->where('tgl_book',$tglbook)
            ->where('status','!=','2')->first();
            if($resevasicek){
                throw new Exception("Cannot make more than 1 Reservation ina Day");
            }
            $data['pasien_id']=$pasienid;
            $data['status']=1;
            $reservasi=Reservasi::create($data);
            return Tools::MyResponse(true,"OK",$reservasi,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
