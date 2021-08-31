<?php

namespace App\Helpers;

use App\Models\Dokter;
use App\Models\MedicalForm;
use App\Models\Medicalkind;
use App\Models\Pasien;
use App\Models\Poli;
use Exception;
use Illuminate\Http\Request;

class Tools{


    public static function MyResponse($issuccess,$err,$data,$responsecode){
        $msg="";
        $errcode=$responsecode;
        if($responsecode!=200){
           // print_r($err);
            if($responsecode==428){
                $msg=$err;
            }else{
                if(property_exists($err,'validator')){
                    $msg=$err->validator->errors();
                }else{
                    if(method_exists($err,'getMessage')){
                        $msg=$err->getMessage();
                    }else{
                        $msg=$err;
                    }
                }
                $errcode=property_exists($err,'validator')?427:$responsecode;
            }

        }else{
            $msg=$err;
        }
        return response()->json([
            "success"=>$issuccess,
            "messages"=>$msg,
            "data"=>$data
        ],$errcode);
    }
    public static function CheckPoli($id){
        $poli=Poli::find($id);
        if(!$poli){
            throw new Exception("Cannot Found Poli");
        }else{
            return $poli;
        }
    }
    public static function CheckDokter($id){
        $dokter=Dokter::find($id);
        if(!$dokter){
            throw new Exception("Cannot Found Dokter");
        }else{
            return $dokter;
        }
    }
    public static function Checkpasien($id){
        $pasien=Pasien::find($id);
        if(!$pasien){
            throw new Exception("err:[".$id."]Cannot Found Pasien");
        }else{
            return $pasien;
        }
    }
    public static function Checkemail($email){
        $poli=Pasien::where('email',$email)->first();
        if($poli){
            throw new Exception("Email Was Used By Other");
        }else{
            return null;
        }
    }
    public static function CheckMedkindinForm($medkindid,$poli,$medformid){
        $medkind=Medicalkind::find($medkindid);
        if(!$medkind){
            throw new Exception("cannot found Medical Kind");
        }
        $medform=MedicalForm::where('medkind_id',$medkindid)->where('poli_id',$poli)->first();
        if(!$medform){
            throw new Exception("cannot found form in ".$poli.$medkindid);
        }
        if($medform->id!=$medformid){
            throw new Exception("Form ID not match with poli and medkind");
        }
        return $medform;
    }
    public static function GenFilterQueryStr($filter){
        $cmd="";
        if($filter){
            foreach($filter as $key=>$value){
                $cmd.=" AND $key LIKE '%$value%' ";
            }
        }
        return $cmd;
    }
    public static function GenSortQueryStr($sort){
        $orderby="";
        if($sort||$sort!=""){
            $pieces = explode(",", $sort);
            $col=$pieces[0];
            $orderby.=" order by $col $pieces[1]";
        }
        return $orderby;
    }
    public static function GenPagingQueryStr(Request $request){
        $offset=$request->input('page')-1;
        $rowsPerPage=$request->input('rowsPerPage');
        return "LIMIT $rowsPerPage OFFSET $offset";
    }
}
?>
