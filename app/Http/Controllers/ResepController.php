<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Resep;
use Illuminate\Support\Facades\DB;
use Exception;
class ResepController extends Controller
{
    public function getall(Request $request){
       // $resep=Resep::with('medical','detailresep','staff')->get();
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
            $resepstr="
            with sumd as(
                select sum(qty*harga) as total,resep_id from resep_detail group by resep_id
            )
            select r.*,m.fee,CONCAT('TRX',LPAD(r.id,6,'0')) as trans_kode,
            s.nama as nama_staff
            from resep r
            left join sumd dr on r.id=dr.resep_id
            left join medical m on r.medical_id=m.id
            left join staff s on s.id=r.staff_id where 1=1 $cmd $orderby $page";
            $resep=DB::select($resepstr);
            return Tools::MyResponse(true,"OK",$resep,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }

}
