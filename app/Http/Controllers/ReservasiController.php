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
use stdClass;
use Tymon\JWTAuth\JWTAuth;

class ReservasiController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;

    }
    public function getallreservation(Request $request){
        $this->validate($request,[
            "rowsPerPage"=>"required",
            "page"=>"required"
        ]);
        $page=Tools::GenPagingQueryStr($request);
        $filter=$request->input('filter');
        $sort=$request->input('sort');
        $cmd=Tools::GenFilterQueryStr($filter);
        $orderby=Tools::GenSortQueryStr($sort);
        try{
            $pasien=DB::select("with t as
            (
                select CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien,p.nama,CONCAT('REG',LPAD(r.id,6,'0')) as kode_reg,
                r.*,l.poli
                from reservasi r
                join pasiens p on r.pasien_id=p.id
                join poli l on r.poli_id=l.id
            )
            select * from t where 1=1 $cmd $orderby $page");
            $data=new stdClass();
            $data->rows=$pasien;
            $data->count=Reservasi::all()->count();
            return Tools::MyResponse(true,"OK",$data,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function offreservasi(){
        $reservasi=$this->gettodayreservasi(2);
        return Tools::MyResponse(true,"OK",$reservasi,200);
    }
    public function onreservasi(){
        $reservasi=$this->gettodayreservasi(1);
        return Tools::MyResponse(true,"OK",$reservasi,200);
    }
    public function allreservasi(){
        $reservasi=$this->gettodayreservasi(null);
        return Tools::MyResponse(true,"OK",$reservasi,200);
    }
    public function notyetcheckin(){

        $reservasi=DB::select("SELECT a.*,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien,l.poli,p.nama,p.ktpno,CONCAT('REG',LPAD(a.id,6,'0')) as code_reg  FROM u5621751_ayaklinik.reservasi a
            join u5621751_ayaklinik.pasiens p on a.pasien_id=p.id
            join poli l on a.poli_id=l.id
            where a.tgl_book=current_date() and a.role_id='1' and a.status='1'
        ;");
          return Tools::MyResponse(true,"OK",$reservasi,200);
    }
    private function gettodayreservasi($roleid){
        $cmd=$roleid==null?"":"and a.role_id=$roleid";
        $reservasi=DB::select("SELECT ls.id as lab_id,a.*,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien,l.poli,p.nama,p.ktpno,CONCAT('REG',LPAD(a.id,6,'0')) as code_reg  FROM u5621751_ayaklinik.reservasi a
        join u5621751_ayaklinik.pasiens p on a.pasien_id=p.id
        join poli l on a.poli_id=l.id
        left join labs ls on a.medical_id=ls.medical_id
        where a.tgl_book=current_date() $cmd
        ;");
         return $reservasi;
    }
    public function dashboard(){
        $data=new stdClass();
        $data->graph=$this->graphicreservasi();
        $data->onregqty=count(DB::select("select * from reservasi where tgl_book=current_date() and role_id=1"));
        $data->offregqty=count(DB::select("select * from reservasi where tgl_book=current_date() and role_id=2"));
        $data->oncheckqty=count(DB::select("select * from reservasi where tgl_book=current_date() and role_id=1 and status=1"));
        $data->queue=count(DB::select("select * from antrian where queue_date=current_date() and status=1 "));

        return Tools::MyResponse(true,"OK",$data,200);
    }
    private function graphicreservasi(){
        $graph=DB::select("select  MONTHNAME(tgl_book) monthbook,count(id) as qty from reservasi group by MONTH(tgl_book),monthbook order by  MONTH(tgl_book) desc");
        $arrdata=array();
        $arrcat=array();
        foreach($graph as $item){
            array_push($arrcat,$item->monthbook);
            array_push($arrdata,$item->qty);
        }

        return ["data"=>$arrdata,"category"=>$arrcat];
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
    public function cancelreservasi(Request $request,$id){
        $this->validate($request,['cancel_reason'=>'required']);
        $reason=$request->input('cancel_reason');
        return $this->changestatus($id,'2',$reason);
    }
    public function myreservation(){
        try{

            $token = $this->jwt->getToken();
            $user= Auth::guard('api')->user($token);
            $pasien=Pasien::where('email', $user['email'])->first();
            $reservasi=DB::select("
            SELECT r.*,CONCAT('REG',LPAD(r.id,6,'0')) as kode_reg,p.*,m.dokter_id,d.nama as nama_dokter FROM u5621751_ayaklinik.reservasi r
            left join u5621751_ayaklinik.poli p on r.poli_id=p.id
            left join u5621751_ayaklinik.medical m on r.medical_id=m.id
            left join u5621751_ayaklinik.dokter d on m.dokter_id=d.id
            where r.status IN (1,3) and r.tgl_book>=curdate() and r.pasien_id='$pasien->id'
            ;");

            return Tools::MyResponse(true,"Query Reservation success",$reservasi,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function myreservationhist(Request $request){
        try{
            $this->validate($request,[
                "rowsPerPage"=>"required",
                "page"=>"required"
            ]);
            $page=Tools::GenPagingQueryStr($request);
            $filter=$request->input('filter');
            $sort=$request->input('sort');
            $cmd=Tools::GenFilterQueryStr($filter);
            $orderby=Tools::GenSortQueryStr($sort);
            $token = $this->jwt->getToken();
            $user= Auth::guard('api')->user($token);
            $pasien=Pasien::where('email', $user['email'])->first();

            $sql=" with t as( SELECT r.*,CONCAT('REG',LPAD(r.id,6,'0')) as kode_reg,p.poli,m.dokter_id,d.nama as nama_dokter FROM u5621751_ayaklinik.reservasi r
            left join u5621751_ayaklinik.poli p on r.poli_id=p.id
            left join u5621751_ayaklinik.medical m on r.medical_id=m.id
            left join u5621751_ayaklinik.dokter d on m.dokter_id=d.id
            where r.status=2 or r.tgl_book<curdate() and r.pasien_id='$pasien->id'
            )
            select * from t ";
            $reservasipast=DB::select("$sql $cmd $orderby $page");

            $data=new stdClass();
            $data->rows=$reservasipast;
            $resercount=DB::select("$sql");
            $data->count=count($resercount);
            return Tools::MyResponse(true,"Query Reservation success",$data,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    private function reservasiaction($poliid,$pasienid,$tglbook,$kind){
        $data=[];
        if($kind==2){
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $data['staff_id']=$user->id;
        }
        Tools::CheckPoli($poliid);
        $resevasicek=Reservasi::where('pasien_id',$pasienid)
        ->where('tgl_book',$tglbook)
        ->where('status','!=','2')->first();
        if($resevasicek){
            throw new Exception("Cannot make more than 1 Reservation in a Day");
        }
        $data['pasien_id']=$pasienid;
        $data['status']=1;
        $data['role_id']=$kind;
        $data['tgl_book']=$tglbook;
        $data['poli_id']=$poliid;
        $reservasi=Reservasi::create($data);
        return $reservasi;
    }
    public function checkinoffline(Request $request){
        DB::beginTransaction();
        try{
            $this->validate($request,[
                'poli_id'=>'required',
                'pasien_id'=>'required',
                'tgl_book'=>'required'
            ]);
            $poliid=$request->input('poli_id');
            $pasienid=$request->input('pasien_id');
            $tglbook=$request->input('tgl_book');
            Tools::Checkpasien($pasienid);

            $reservation=$this->reservasiaction($poliid,$pasienid,$tglbook,2);
            // $chekindata=[
            //     "reg_time"=>Carbon::now(),
            //     "poli_id"=>$poliid,
            //     "status"=>1,
            //     "staff_id"=>$user->id,
            //     "pasien_id"=>$data['pasien_id']
            // ];
            // $antrian=Antrian::create($chekindata);
            DB::commit();
            return Tools::MyResponse(true,"Queue Has Been Created",$reservation,200);
        }
        catch(Exception $e){
            DB::rollback();
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
            $now=Carbon::now()->toDateString();
            if($tglbook<$now){
                throw new Exception("[".$now.$tglbook."]Cannot Book at Past Time");
            }
            $token = $this->jwt->getToken();
            $user= Auth::guard('api')->user($token);
            $pasien=Pasien::where('email',$user['email'])->first();
            $pasienid=$pasien->id;
            $reservasi=$this->reservasiaction($poliid,$pasienid,$tglbook,1);
            return Tools::MyResponse(true,"OK",$reservasi,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function getreservasibyid($id){
        $reservasi=DB::select("SELECT a.*,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien,l.poli,p.nama,p.ktpno,CONCAT('REG',LPAD(a.id,6,'0')) as code_reg  FROM u5621751_ayaklinik.reservasi a
        join u5621751_ayaklinik.pasiens p on a.pasien_id=p.id
        join poli l on a.poli_id=l.id
        where a.id=$id
            ;");
            if(!isset($reservasi[0])){
                return Tools::MyResponse(false,"Data Reservasi Tidak Ditemukan",null,428);
            }else{
                return Tools::MyResponse(true,"OK",$reservasi[0],200);
            }
       // return Tools::MyResponse(true,"OK",$reservasi,200);
    }

}
