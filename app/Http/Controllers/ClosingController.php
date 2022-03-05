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
            select r.id,r.medical_id,m.fee,sum(rd.qty*rd.harga) as subtot,
            case when r.special=1 then r.payamt else ifnull(m.fee,0)+sum(rd.qty*rd.harga) end as total from resep r
            left join medical m on r.medical_id=m.id
            left join resep_detail rd on r.id=rd.resep_id
            $cmd
            group by r.id,r.medical_id,m.fee
        ),".$this->sql;
        // return "with sumresep as (
        //     select
        //     case when r.special=1 then r.payamt
        //     else ifnull(sum(rd.qty*rd.harga),0) end as total
        //     ,r.id,r.medical_id,r.special from resep r
        //     left join resep_detail rd on r.id=rd.resep_id
        //     $cmd
        //     group by r.id,r.medical_id,r.special,r.payamt
        // ),".$this->sql;
    }
    private $sql="
        sumall as(
            select s.*,m.fee,
            case when s.special=1 then s.total
            else s.total+m.fee end as grand_total from sumresep s
            left join medical m on s.medical_id=m.id
        ),
        clossing_w as(
            select * from sumresep where id not in (select resep_id from closing_detail cd join closing c on cd.closing_id=c.id)
        ),
        in_out as(
            select
                (select sum(total) as in_amt from sumresep) as in_amt,
                (select ifnull(sum(ifnull(total,0)),0) as total_in from clossing_w) as wait_hand_over,
                (select ifnull(sum(ifnull(closing_amt,0)),0) as total_in from closing where status=1) as recap_over_amt,
                (select ifnull(sum(ifnull(closing_amt,0)),0) as total_in from closing where status=2) as hand_over_amt
            from dual
        ),
        recap as (
            select * from sumall s where s.id not in(
            select resep_id from closing_detail)
        )";
//         select
//         (select sum(grand_total) as in_amt from sumall) as in_amt,
//         (select ifnull(sum(ifnull(closing_amt,0)),0) as total_in from closing) as over_amt,
//         (select ifnull(sum(ifnull(closing_amt,0)),0) as total_in from closing where status=1) as recap_over_amt,
//         (select ifnull(sum(ifnull(closing_amt,0)),0) as total_in from closing where status=2) as hand_over_amt
//         from dual
// ),
    public function calcclosing(Request $request){
        try{
            $inoutsql=" select *,in_amt, wait_hand_over from in_out";
            throw new Exception($this->sqlresep(4).$inoutsql);
            $sql=DB::select($this->sqlresep(4).$inoutsql);
            return Tools::MyResponse(true,"OK",$sql,200);

        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function createhandover(){
        DB::beginTransaction();
        try{
            $sumoversql="select sum(total) as total from clossing_w";
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
            $sumdetail="select * from clossing_w";
            $detdata=DB::select($this->sqlresep(4).$sumdetail);
            foreach($detdata as $data){
                ClosingDetail::create([
                    "closing_id"=>$closing->id,
                    "resep_id"=>$data->id,
                    "sum_amt"=>$data->total
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
        $sql="select *,CONCAT('TRX',LPAD(id,6,'0')) as kode_trans from clossing_w";
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
                select IFNULL(sum(rd.qty*rd.harga),0) as total,r.id,r.medical_id,
					m.fee
                    from resep r
                    left join resep_detail rd on r.id=rd.resep_id
					join medical m on m.id=r.medical_id
                    group by r.id,r.medical_id,m.fee
                )
                select sr.id,sr.medical_id,sr.total+sr.fee as total,CONCAT('TRX',LPAD(cd.id,6,'0')) as trans_kode from closing_detail cd
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
    public function updatedetail(){
        DB::beginTransaction();
        try{
            $sql="select r.id,cast(r.created_at as date) as tdate,
            r.special,
            m.fee,
            ifnull(k.subtotal,0) as subtotal,
            case when r.special=1 then r.payamt
                        else ifnull(k.subtotal,0)+m.fee end as grand_total
            from resep r
            left join medical m on r.medical_id=m.id
            left join (
                select ifnull(sum(qty*harga),0) as subtotal,resep_id from resep_detail group by resep_id
            )k on k.resep_id=r.id
            where cast(r.created_at as date) in ('2022-02-07')
            ";
            $detdata=DB::select($sql);
            foreach($detdata as $data){
                $closing=Closing::where('closing_date',$data->tdate)->first();
                if(!$closing){
                    throw new Exception("asd");
                }
                ClosingDetail::create([
                    "closing_id"=>$closing->id,
                    "resep_id"=>$data->id,
                    "sum_amt"=>$data->grand_total
                ]);
            }
            DB::commit();
        }catch(Exception $e){
        Db::rollBack();
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
