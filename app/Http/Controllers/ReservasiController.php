<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\Reservasi;
use Exception;
use Tymon\JWTAuth\JWTAuth;

class ReservasiController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }
    public function bookonline(Request $request){
        try{
            $token = $this->jwt->getToken();
            $user = $this->jwt->toUser($token);
            $this->validate($request,[
                'poli_id'=>'required',
                'tgl_book'=>'required',
            ],['required'=>':attibute cannot empty']);
            $pasien=Pasien::where('email',$user['email'])->first();
            $data=$request->all();
            $poliid=$request->input('poli_id');
            $tglbook=$request->input('tgl_book');
            $pasienid=$pasien->id;
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
            $data['pasien_id']=$pasien->id;
            $data['status']=1;
            $reservasi=Reservasi::create($data);
            return Tools::MyResponse(true,"OK",$reservasi,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
