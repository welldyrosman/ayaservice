<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Barang;
use App\Models\BarangIn;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\JWTAuth;

class BarangInController extends Controller
{
     public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;

    }
    public function barangin(Request $request){
        DB::beginTransaction();
        try{
            $this->validate($request,[
                "items.*.barang_id"=>"required",
                "items.*.qty"=>"required",
                "items.*.harga"=>"required",
            ]);
            $items=$request->input("items");
            if(count($items)<1){
                throw new Exception("No Data Send");
            }
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            foreach($items as $item){
                $item['staff_id']=$user->id;
                $barang=Tools::CheckObat($item['barang_id']);
                if($barang->iscomposite){
                    throw new Exception("cannot add qty for composite item");
                }
                BarangIn::create($item);
            }
            DB::commit();
            return Tools::MyResponse(true,"OK",$items,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }

}
