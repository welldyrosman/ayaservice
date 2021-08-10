<?php

namespace App\Helpers;

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
                $msg=property_exists($err,'validator')?$err->validator->errors():$err->getMessage();
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
}
?>
