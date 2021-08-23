<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kota;
use App\Models\Poli;
use App\Models\Provinsi;
use Illuminate\Support\Facades\DB;
use Exception;
class AddressController extends Controller
{
    public function getprovinsi(){
        $provinsi=Provinsi::all();
        return Tools::MyResponse(true,"OK",$provinsi,200);
    }
    public function getcity(Request $request,$id){
        $city=Kota::where('id_prov',$id)->get();
        return Tools::MyResponse(true,"OK",$city,200);
    }
    public function getkec($idprov,$idkota){
        $kec=Kecamatan::where('id_prov',$idprov)->where('id_kota',$idkota)->get();
        return Tools::MyResponse(true,"OK",$kec,200);
    }
    public function getdes($idprov,$idkota,$idkec){
        $des=Desa::where('id_prov',$idprov)->where('id_kota',$idkota)->where('id_kec',$idkec)->get();
        return Tools::MyResponse(true,"OK",$des,200);
    }

}
