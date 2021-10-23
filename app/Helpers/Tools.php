<?php

namespace App\Helpers;

use App\Models\Antrian;
use App\Models\Barang;
use App\Models\Dokter;
use App\Models\Formkind;
use App\Models\Medical;
use App\Models\MedicalForm;
use App\Models\Medicalkind;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\Resep;
use App\Models\Reservasi;
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
    public static function Checkformkind($id){
        $Formkind=Formkind::find($id);
        if(!$Formkind){
            throw new Exception("Cannot Found Medical form");
        }else{
            return $Formkind;
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
    public static function CheckObat($id){
        $barang=Barang::find($id);
        if(!$barang){
            throw new Exception("Cannot Found Product");
        }else{
            if($barang->kind!=1){
                throw new Exception("Hanya Boleh Memasukan Jenis Obat");
            }
            return $barang;
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
    public static function CheckMedkindinForm($medkindid,$formkind_id,$medformid){
        $medkind=Medicalkind::find($medkindid);
        if(!$medkind){
            throw new Exception("cannot found Medical Kind");
        }
        $medform=MedicalForm::where('medkind_id',$medkindid)->where('formkind_id',$formkind_id)->first();
        if(!$medform){
            throw new Exception("cannot found form in ".$formkind_id.$medkindid);
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
        $offset=$request->input('page');
        $rowsPerPage=$request->input('rowsPerPage');
        $page=($offset*$rowsPerPage)-$rowsPerPage;
        return "LIMIT  $rowsPerPage OFFSET $page";
    }
    public static function MedChangeStatus($rid,$medical_status,$antrian_status,$resep_status,$reservasi_status){
            $resep=Resep::find($rid);
            if($resep->medical_id){
                $id=$resep->medical_id;
                $medical=Medical::find($id);
                $medical->fill(['status'=>$medical_status]);
                $medical->save();
                $antrian=Antrian::where('medical_id',$id)->first();
                $antrian->fill(['status'=>$antrian_status]);
                $antrian->save();
                $reservasi=Reservasi::where('medical_id',$id)->first();
                $reservasi->fill(['status'=>$reservasi_status]);
                $reservasi->save();
            }
            $resep->fill(['status'=>$resep_status]);
            $resep->save();

    }
}
?>
