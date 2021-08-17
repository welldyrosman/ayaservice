<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Pasien;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Str;
class PasienController extends Controller
{
    protected $path='app\photo_pasien';
    protected $publicpath='storage\photo_pasien';
    private function insertdatapasien($request,$data){
        $ktp=$request->input('ktpno');
        $cek=Pasien::where('ktpno',$ktp)->first();
        if($cek!=null){
            throw new Exception("No KTP ini telah terdaftar, silahkan lakukan Login");
        }
        $pass=$request->input("password");
        $pass2=$request->input("password2");
        $myphone=$request->input("no_telp");
        $partnerphone=$request->input("partner_tel");


        if($myphone==$partnerphone){
            throw new Exception("Nomor Telpon Kerabat Dekat tidak boleh sama");
        }
        if($pass!=$pass2){
            throw new Exception("Kombinasi Password Salah");
        }
        if($data['photo_pasien']!=null){
            $thumbnail = Str::random(34);
            $ext=$request->file('photo_pasien')->getClientOriginalExtension();
            $this->filename=$thumbnail.'.'.$ext;
            $request->file('photo_pasien')->move(storage_path($this->path), $this->filename);
            $data['photo_pasien']=$this->filename;
        }
        $pasien = Pasien::create($data);
        return $pasien;
    }
    public function addpasienoffline(Request $request){
        DB::beginTransaction();
        try{
            $data = $request->all();
            $this->validate($request,[
                'ktpno' => 'required',
                'nama' => 'required',
                'tempat_lahir' => 'required',
                'tgl_lahir' => 'required',
                'jk' => 'required',
                'status_nikah' => 'required',
                'alamat' => 'required',
                'kec' => 'required',
                'kota' => 'required',
                'pekerjaan' => 'required',
                'no_telp' => 'required',
                'partner' => 'required',
                'partner_tel' => 'required',
                'partner_status' => 'required',
                'add_user'=>'required'
            ],['required'=>':attribute cannot Empty']);
            $data['reg_rule']=2;
            $data['status_akun']=1;

            if(isset($data["email"])){
                Tools::Checkemail($data["email"]);
            }
            $pasien=$this->insertdatapasien($request,$data);
            DB::commit();
            return Tools::MyResponse(true,"OK",$pasien,200);
        }catch(Exception $e){
            DB::rollback();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function addpasienonline(Request $request){
        DB::beginTransaction();
        try{
            $data = $request->all();
            $this->validate($request,[
                'ktpno' => 'required',
                'nama' => 'required',
                'tempat_lahir' => 'required',
                'tgl_lahir' => 'required',
                'jk' => 'required',
                'status_nikah' => 'required',
                'alamat' => 'required',
                'kec' => 'required',
                'kota' => 'required',
                'pekerjaan' => 'required',
                'no_telp' => 'required',
                'email' => 'required',
                'partner' => 'required',
                'partner_tel' => 'required',
                'partner_status' => 'required',
                'password' => 'required',
                'password2' => 'required',
            ],['required'=>':attribute cannot Empty']);
            $data['reg_rule']=1;
            $data['status_akun']=2;
            Tools::Checkemail($data["email"]);
            $pasien=$this->insertdatapasien($request,$data);
            $akun=[
                "name"=>$data["nama"],
                "email"=>$data["email"],
                "password"=>app('hash')->make($data["password"])
            ];
            User::create($akun);
            DB::commit();
            return Tools::MyResponse(true,"OK",$pasien,200);
        }catch(Exception $e){
            DB::rollback();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function updatepasienoffline(Request $request,$id){
        DB::beginTransaction();
        try{
            $pasien=Pasien::find($id);
            if(!$pasien){
                throw new Exception("Pasien tidak ditemukan");
            }
            if($pasien->reg_rule==1){
                $this->validate($request,$messages=[
                    'email'=>'bail|required'
                ],['email.required'=>'Email Cannot Empty in Online Registration']);
            }
            $this->validate($request,[
                'ktpno' => 'required',
                'nama' => 'required',
                'tempat_lahir' => 'required',
                'tgl_lahir' => 'required',
                'jk' => 'required',
                'status_nikah' => 'required',
                'alamat' => 'required',
                'kec' => 'required',
                'kota' => 'required',
                'pekerjaan' => 'required',
                'no_telp' => 'required',
                'partner' => 'required',
                'partner_tel' => 'required',
                'partner_status' => 'required',
            ],['required'=>':attribute cannot Empty']);
            $data = $request->all();
            if($data['photo_pasien']!=null){
            $current_avatar_path = storage_path($this->publicpath) . '/' .$pasien->photo;
                if (file_exists($current_avatar_path)) {
                unlink($current_avatar_path);
                }
                $thumbnail = Str::random(34);
                $ext=$request->file('photo_pasien')->getClientOriginalExtension();
                $this->filename=$thumbnail.'.'.$ext;
                $request->file('photo_pasien')->move(storage_path($this->path), $this->filename);
            }
            $pasien->fill($data);
            $pasien->save();
            DB::commit();
            return Tools::MyResponse(true,"Berhasil Update",$pasien,200);
        }
        catch(Exception $e){
            DB::rollback();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function deletepasien($id){
        try{
            $patients = Pasien::find($id);
            if (!$patients) {
                throw new Exception("Pasien tidak ditemukan");
            }
            $patients->delete();
            return Tools::MyResponse(true,"OK",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }

    }
    public function bannedpasien($id){
        $pasien=Pasien::find($id);
        if($pasien==null){
            return Tools::MyResponse(false,"Data Pasien Tidak Ditemukan",null,428);
        }else{
            $data=['status_akun'=>3];
            $pasien->fill($data);
            $pasien->save();
            return Tools::MyResponse(true,"Pasien Di Banned",$pasien,200);
        }

    }
    public function disabled($id){
        $pasien=Pasien::find($id);
        if($pasien==null){
            return Tools::MyResponse(false,"Data Pasien Tidak Ditemukan",null,428);
        }else{
            $data=['status_akun'=>2];
            $pasien->fill($data);
            $pasien->save();
            return Tools::MyResponse(true,"Pasien Di Non Aktivkan",$pasien,200);
        }
    }
    public function enabled($id){
        $pasien=Pasien::find($id);
        if($pasien==null){
            return Tools::MyResponse(false,"Data Pasien Tidak Ditemukan",null,428);
        }else{
            $data=['status_akun'=>1];
            $pasien->fill($data);
            $pasien->save();
            return Tools::MyResponse(true,"Pasien Di Aktivkan",$pasien,200);
        }
    }
    public function setactive($id){
        $pasien=Pasien::find($id);
        if($pasien==null){
            return Tools::MyResponse(false,"Data Pasien Tidak Ditemukan",null,428);
        }else{
            $data=['status_akun'=>3];
            $pasien->fill($data);
            $pasien->save();
            return Tools::MyResponse(true,"Pasien Di Banned",$pasien,200);
        }

    }
    public function getpasienbyid($id){
        $data=Pasien::find($id);
        if($data==null){
            return Tools::MyResponse(false,"Data Pasien Tidak Ditemukan",null,428);
        }else{
            return Tools::MyResponse(true,"OK",$data,200);
        }
    }
    public function getallpasien(){
        $data=Pasien::all();
        return Tools::MyResponse(true,"OK",$data,200);
    }

    //
}
