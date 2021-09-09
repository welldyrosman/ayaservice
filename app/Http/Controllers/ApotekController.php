<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use Exception;
use Illuminate\Support\Facades\DB;

class ApotekController extends Controller
{
    public function submitcheck(Request $request){
       DB::beginTransaction();
       try{
            $this->validate($request,[
                "medical_id"=>"required",
            ]);
            $data=$request->all();
            $id=$data["medical_id"];
            Tools::MedChangeStatus($id,3,3,2,5);
            DB::commit();
        }catch(Exception $e){
            DB::rollBack();
       }
    }
}
