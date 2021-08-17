<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Medical;
use App\Models\Poli;
use App\Models\Resep;
use Exception;
use Illuminate\Support\Facades\DB;
use stdClass;

class MedicalController extends Controller{
    public function medicalsave(Request $request,$id){
        DB::beginTransaction();
        try{
            $this->validate($request,[
                "poli_id"=>"required",
                "dokter_id"=>"required",
                "pasien_id"=>"required",
                "diagnosa"=>"required",
                "treatment_kind"=>"required",
                "detail_resep"=>"array"
            ]);

            $medical=Medical::find($id);
            if(!$medical){
                throw new Exception("Cannot Found Medical");
            }
            $resepid=Resep::where('medical_id',$id);
            $detail_resep=$request->input("detail_resep");
            foreach($detail_resep as $row){
                $this->validate($row,[
                    "barang_id"=>"required",
                    "qty"=>"required",
                ]);

            }
            $data=$request->all();
            $medical->fill($data);
            $medical->save();
            return Tools::MyResponse(true,"Medical Data Has Been Saved",$medical,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
