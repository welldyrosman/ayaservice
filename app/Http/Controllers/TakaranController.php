<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Takaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
class TakaranController extends Controller
{
    public function getall(){
        $takaran=Takaran::all();
        return Tools::MyResponse(true,"OK",$takaran,200);
    }
    public function getid($id){
       try{
            $takaran=Takaran::find($id);
            if (!$takaran) {
                throw new Exception("Takaran tidak ditemukan");
            }
            return Tools::MyResponse(true,"OK",$takaran,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function create(Request $request){
        $data = $request->all();
        try{
            $this->validate($request,[
            'takaran' => 'required'],['required'=>':attribute cannot Empty']);
            $takaran = Takaran::create($data);
            return Tools::MyResponse(true,"OK",$takaran,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function delete($id){
        try{
            $takaran = Takaran::find($id);
            if (!$takaran) {
                throw new Exception("Takaran tidak ditemukan");
            }
            $takaran->delete();
            return Tools::MyResponse(true,"Takaran Was Deleted",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function update(Request $request,$id){
        try{
            $takaran=Takaran::find($id);
            if(!$takaran){
                throw new Exception("Takaran Tidak Ditemukan");
            }
            $this->validate($request,[
                'takaran' => 'required'],['required'=>':attribute cannot Empty']);
            $data=$request->all();
            $takaran->fill($data);
            $takaran->save();
            return Tools::MyResponse(true,"Takaran Was Updated",$takaran,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }

}
