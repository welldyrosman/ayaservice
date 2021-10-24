<?php

namespace App\Http\Controllers;

use App\Helpers\Tools;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dailyincomereport(Request $request){
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
            $sql=DB::select("
            with sumresep as(
                    select sum(rd.qty*rd.harga)as total,cast(r.created_at as date) as days from resep r
                    join resep_detail rd on r.id=rd.resep_id
                    group by cast(r.created_at as date))
                ,summed as(
                    select sum(m.fee) as feeamt,cast(r.created_at as date) as med_days,count(r.id) as visit_qty
                    from resep r left join
                    medical m on r.medical_id=m.id group by cast(r.created_at as date)
                )
                select r.total,m.feeamt,r.days,r.total+m.feeamt as grand_total,m.visit_qty from sumresep r
                left join summed m on r.days=m.med_days where 1=1 $cmd $orderby $page");
            return Tools::MyResponse(true,"OK",$sql,200);

        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }

}
