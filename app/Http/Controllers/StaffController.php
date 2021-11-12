<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Dokter;
use App\Models\Poli;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\JWTAuth;

class StaffController extends Controller
{
     public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;

    }
    public function getall(){
        $poli=Staff::all();
        return Tools::MyResponse(true,"OK",$poli,200);
    }
    public function staffchangepass(Request $request){
        try{
            $this->validate($request,[]);
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
        }catch(Exception $e){

        }
    }
    public function getid($id){
       try{
            $poli=Staff::find($id);
            if (!$poli) {
                throw new Exception("Staff not Found");
            }
            return Tools::MyResponse(true,"OK",$poli,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function create(Request $request,$id){
        $data = $request->all();
        try{
            $this->validate($request,[
                'nama' => 'required',
                'email' => 'required',
            ],['required'=>':attribute cannot Empty']);

            $data['password']=app('hash')->make('pass@#123');
            $data['role_id']=$id;
            $cek=Staff::where('email',$data['email'])->first();

            if($cek!=null){
                throw new Exception("email was exist");
            }
            $staff = Staff::create($data);
            $dokter=Dokter::create([
                "staff_id"=>$staff->id,
                "nama"=>$staff->nama,
                "isdokter"=>2,
                "poli_id"=>2
            ]);
            return Tools::MyResponse(true,"OK",$staff,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function disabled($id){
        try{
            $staff = Staff::find($id);
            if (!$staff) {
                throw new Exception("Staff tidak ditemukan");
            }
            $staff->fill(["stop_mk"=>"Y"]);
            $staff->save();
            return Tools::MyResponse(true,"Staff Was Disabled",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function enabled($id){
        try{
            $staff = Staff::find($id);
            if (!$staff) {
                throw new Exception("Staff tidak ditemukan");
            }
            $staff->fill(["stop_mk"=>"N"]);
            $staff->save();
            return Tools::MyResponse(true,"Staff Was Enabled",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function update(Request $request,$id){
        try{
            $staff=Staff::find($id);
            if(!$staff){
                throw new Exception("Poli Tidak Ditemukan");
            }
            $this->validate($request,[
                'nama' => 'required',
                'email' => 'required',
                'role_id' => 'required']
                ,['required'=>':attribute cannot Empty']);
            $data=$request->all();
            $staff->fill($data);
            $staff->save();
            return Tools::MyResponse(true,"Staff Was Updated",$staff,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function resetpass(Request $request){
        try{
            $this->validate($request,["id"]);
        }catch(Exception $e){

        }

    }
    public function changepass(Request $request){
        DB::beginTransaction();
        try{
            $this->validate($request,[
                "old_password"=>"required",
                "new_password"=>"required",
                "retype_password"=>"required",
            ]);
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $staff=Staff::find($user->id);
            $data=$request->all();
            if(!Hash::check($data["old_password"],$staff->password)){
                throw new Exception("Check your old password");
            }else if(Hash::check($data["new_password"],$staff->password)){
                throw new Exception("New Password Cannot same with before");
            }else if($data["new_password"]!=$data["retype_password"]){
                throw new Exception("New Password doesnt match");
            }else{
                $staff->fill([
                    "password"=>app('hash')->make($data["new_password"])
                ]);
                $staff->save();
                DB::commit();
            }
            return Tools::MyResponse(true,"OK",$staff,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
