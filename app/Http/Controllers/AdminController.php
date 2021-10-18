<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Resep;
use Exception;
use Illuminate\Support\Facades\DB;
class AdminController extends Controller
{
    private function getgraphincome(){
        $data=DB::select("
            with sumd as(
                select sum(qty*harga) as total,resep_id from resep_detail group by resep_id
            ),
            sumtot as(
            select r.*,m.fee,CONCAT('TRX',LPAD(r.id,6,'0')) as trans_kode,m.fee+dr.total as grand_tot
            from resep r
            left join sumd dr on r.id=dr.resep_id
            left join medical m on r.medical_id=m.id
            left join staff s on s.id=r.staff_id)
            select  sum(grand_tot) as data,EXTRACT( YEAR_MONTH FROM created_at) as catagories
            from sumtot
            group by EXTRACT( YEAR_MONTH FROM created_at)
            order by EXTRACT( YEAR_MONTH FROM created_at) desc
            limit 8");
        return $data;
    }
    private function getpie(){
        $data=DB::select("
            with umur as (
                select TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) AS umur
                from pasiens),
            range_umur as(
            select *,
                case
                    when umur<10 then '... - 10'
                    when umur BETWEEN 10 and 20 THEN '10 - 20'
                    when umur BETWEEN 20 and 30 THEN '20 - 30'
                    when umur BETWEEN 30 and 40 THEN '30 - 40'
                    when umur >40 THEN '40 - ...'
                end as range_umur
            from umur
            )
            select count(range_umur) as label,range_umur from range_umur group by range_umur
        ");
        return $data;
    }
    //{data: [1.4, 2, 2.5, 1.5, 2.5, 2.8, 3.8, 4.6], categories: [2009, 2010, 2011, 2012, 2013, 2014, 2015, 2016]}
    //{series: [44, 55, 13, 33], labels: ["Apple", "Mango", "Orange", "Watermelon"]}
    public function dashboard(Request $request){
       try{
           $grapharr=$this->getgraphincome();
           $catagaories=array();
           $datagraph=array();
           foreach($grapharr as $i){
                array_push($catagaories,$i->catagories);
                array_push($datagraph,$i->data);
           }

           $pieharr=$this->getpie();
           $labels=array();
           $series=array();

            foreach($pieharr as $j){
                array_push($labels,$j->range_umur);
                array_push($series,$j->label);
            }
            $pasien=DB::select("
            with topone as(
                select count(pasien_id)qty, pasien_id
                from medical
                group by pasien_id
                order by count(pasien_id) desc limit 1)
               select * from topone t
               join pasiens p on t.pasien_id=p.id
            ");
           $data=[
                "graph"=>["catagories"=>$catagaories,"data"=>$datagraph],
                "pie"=>["series"=>$series,"labels"=>$labels],
                "pasien"=>$pasien,
                "amt"=>DB::select("select ifnull(sum(closing_amt),0)amt from closing where cast(created_at as date)=current_date() and status=2")
            ];
            return Tools::MyResponse(true,"OK",$data,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }

}
