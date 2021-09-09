<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use Exception;
use Illuminate\Support\Facades\DB;

class KasirController extends Controller
{
    public function submittrans(Request $request){
       DB::beginTransaction();
       try{
            $this->validate($request,[
                "medical_id"=>"required",
            ]);
            $data=$request->all();
            $id=$data["medical_id"];
            Tools::MedChangeStatus($id,4,4,3,6);
            DB::commit();
        }catch(Exception $e){
            DB::rollBack();
       }
    }
}
