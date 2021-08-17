<?php

namespace App\Helpers;

use App\Models\Pasien;
use App\Models\Poli;
use Exception;

class Tools{

    public static function sayhello()
    {
        return "Hello Friends";
    }
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
    public static function Checkpasien($id){
        $pasien=Pasien::find($id);
        if(!$pasien){
            throw new Exception("Cannot Found Pasien");
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
}
?>
