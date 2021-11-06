<?php

namespace App\Http\Controllers;

use App\Helpers\Tools;
use App\Models\Closing;
use App\Models\ClosingDetail;
use App\Models\Staff;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWTAuth;

class ClosingController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $token = $this->jwt->getToken();
        $this->user= Auth::guard('staff')->user($token);
        $this->now=Carbon::now()->toDateString();
    }
    private function sqlresep($status){
        $cmd="";
        if($status==4){
            $cmd.=" where r.status=4";
        }
        return "with sumresep as (
            select
            case when r.special=1 then r.payamt
            else ifnull(sum(rd.qty*rd.harga),0) end as total
            ,r.id,r.medical_id,r.special from resep r
            left join resep_detail rd on r.id=rd.resep_id
            $cmd
            group by r.id,r.medical_id,r.special,r.payamt
        ),".$this->sql;
    }
    private $sql="
        sumall as(
            select s.*,m.fee,
            case when s.special=1 then s.total
            else s.total+m.fee end as grand_total from sumresep s
            left join medical m on s.medical_id=m.id
        ),
        in_out as(
            select
                (select sum(grand_total) as in_amt from sumall) as in_amt,
                (select ifnull(sum(ifnull(closing_amt,0)),0) as total_in from closing) as over_amt,
                (select ifnull(sum(ifnull(closing_amt,0)),0) as total_in from closing where status=1) as recap_over_amt,
                (select ifnull(sum(ifnull(closing_amt,0)),0) as total_in from closing where status=2) as hand_over_amt
                from dual
        ),
        recap as (
            select * from sumall s where s.id not in(
            select resep_id from closing_detail)
        )";
    public function calcclosing(Request $request){
        try{
            $inoutsql=" select *,in_amt-over_amt as wait_hand_over from in_out";
            $sql=DB::select($this->sqlresep(4).$inoutsql);
            return Tools::MyResponse(true,"OK",$sql,200);

        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function createhandover(){
        DB::beginTransaction();
        try{
            $sumoversql="select sum(grand_total) as total from recap";
            $sumdata=DB::select($this->sqlresep(4).$sumoversql);
            if(!$sumdata[0]->total){
                throw new Exception("No Data Need Caclculate");
            }
            $closing=Closing::whereNotIn("status",[2,0])->first();
            if($closing){
                $closing->fill([
                    "closing_amt"=>$sumdata[0]->total,
                    "closing_date"=>Carbon::now(),
                    "staff_id"=>$this->user->id,
                    "status"=>"1"
                ]);
                $closing->save();
            }else{
                $closing=Closing::create([
                    "closing_amt"=>$sumdata[0]->total,
                    "closing_date"=>Carbon::now(),
                    "staff_id"=>$this->user->id,
                    "status"=>"1"
                ]);
            }
            ClosingDetail::where("closing_id",$closing->id)->delete();
            $sumdetail="select * from recap";
            $detdata=DB::select($this->sqlresep(4).$sumdetail);
            foreach($detdata as $data){
                ClosingDetail::create([
                    "closing_id"=>$closing->id,
                    "resep_id"=>$data->id,
                    "sum_amt"=>$data->grand_total
                ]);
            }
            DB::commit();
            return Tools::MyResponse(true,"OK",$closing,200);
        }
        catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function needclosinglist(Request $request){
        $sql="select *,CONCAT('TRX',LPAD(id,6,'0')) as kode_trans from sumall
        where id not in(
            select resep_id from closing_detail)";
        $detdata=DB::select($this->sqlresep(4).$sql);
        return Tools::MyResponse(true,"OK",$detdata,200);
    }
    public function handoverlist(Request $request){
        $closing=Closing::with(["staff"])->get();
        return Tools::MyResponse(true,"OK",$closing,200);
    }
    public function detailGenerate($id){
        try{
            $detailtrans=DB::select("
                with sumresep as(
                select sum(rd.qty*rd.harga) as total,r.id,r.medical_id from resep r
                    join resep_detail rd on r.id=rd.resep_id
                    group by r.id,r.medical_id
                ) select *,CONCAT('TRX',LPAD(cd.id,6,'0')) as trans_kode from closing_detail cd
                join sumresep sr on cd.resep_id=sr.id where cd.closing_id=$id");
            if(count($detailtrans)<1){
                throw new Exception("No Closing data");
            }
            if($detailtrans[0]->total==null){
                throw new Exception("Cannot Founf Closing Data");
            }
            return Tools::MyResponse(true,"OK",$detailtrans,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function detailGenerateUnclose(){
        try{
            $detailtrans=DB::select("
select cd.*,CONCAT('TRX',LPAD(cd.resep_id,6,'0')) as trans_kode from closing c
join closing_detail cd on c.id=cd.closing_id
where c.status=1");
            if(count($detailtrans)<1){
                throw new Exception("No Closing data");
            }
            return Tools::MyResponse(true,"OK",$detailtrans,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function gethandover($id){
        DB::beginTransaction();
        try{
            $closing=Closing::find($id);
            if(!$closing){
                throw new Exception("Cannot Found Rekap");
            }
            if($closing->status!="1"){
                throw new Exception("Cannot Receive Before Rekap");
            }
            $closing->fill(["status"=>"2","receive_time"=>Carbon::now()]);
            $closing->save();
            DB::commit();
            return Tools::MyResponse(true,"OK",$closing,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
