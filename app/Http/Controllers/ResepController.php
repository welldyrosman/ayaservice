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
            case when r.medical_id is not null then p.nama
            when r.medical_id is null and r.pasien_id is not null then p2.nama
            else r.cust_nm end as nama,
            CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien,

            case when r.transtype=1 then p.no_telp
            when r.transtype=2 then p2.no_telp
            else r.phone_no end as no_telp,
            s.nama as nama_staff,dr.total as grand_total
            from resep r
            left join sumd dr on r.id=dr.resep_id
            left join medical m on r.medical_id=m.id
            left join pasiens p on m.pasien_id=p.id
            left join pasiens p2 on r.pasien_id=p2.id
            left join staff s on s.id=r.staff_id where 1=1 $cmd $orderby $page";
            $resep=DB::select($resepstr);
            return Tools::MyResponse(true,"OK",$resep,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }

}
