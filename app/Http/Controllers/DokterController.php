<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Dokter;
use App\Models\Medical;
use App\Models\Staff;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
class DokterController extends Controller
{
    protected $path='dokter_photo';
    protected $publicpath='dokter_photo';
    public function getall(){
        $Dokter=Dokter::with('poli')->get();
        return Tools::MyResponse(true,"OK",$Dokter,200);
    }
    public function getid($id){
       try{
            $Dokter=Dokter::find($id);
            if (!$Dokter) {
                throw new Exception("Dokter tidak ditemukan");
            }
            return Tools::MyResponse(true,"OK",$Dokter,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }

    protected $filename;
    public function create(Request $request){
        $data = $request->all();
        DB::beginTransaction();
        try{
            $this->validate($request,[
                'nama' => 'required',
                'tempat' => 'required',
                'tgl_lahir' => 'required',
                'pendidikan'=>'required',
                'poli_id'=>'required',
                'email'=>'required',
                'desc'=>'required',
                'photo'=>'required|image',
            ],['required'=>':attribute cannot Empty']);
            $cekdokter=Dokter::where('email',$data['email'])->first();
            if($cekdokter){
                throw new Exception("Email was used by other dokter");
            }
            $thumbnail = Str::random(34);
            $staff=Staff::where('email',$data['email'])->first();
            if($staff){
                throw new Exception("Email was used by other staff");
            }
            $ext=$request->file('photo')->getClientOriginalExtension();
            $this->filename=$thumbnail.'.'.$ext;
            $request->file('photo')->move(storage_path($this->publicpath), $this->filename);

            $akundata=[
                "nama"=>$data['nama'],
                "email"=>$data["email"],
                "password"=>app('hash')->make("Dokter@#123"),
                "role_id"=>2
            ];
            $staff=Staff::create($akundata);
            $data['photo']=$this->filename;
            $data['isdokter']=1;
            $data['staff_id']=$staff->id;
            $Dokter = Dokter::create($data);
            DB::commit();
            return Tools::MyResponse(true,"OK",$Dokter,200);
        }catch(Exception $e){
            $current_avatar_path = storage_path($this->publicpath) . '/' .$this->filename;
            if (file_exists($current_avatar_path)) {
              unlink($current_avatar_path);
            }
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function delete($id){
        try{
            $Dokter = Dokter::find($id);

            if (!$Dokter) {
                throw new Exception("Dokter tidak ditemukan");
            }
            $staff = Staff::where('email',$Dokter->email)->first();
            $med=Medical::where("dokter_id",$Dokter->id)->first();
            if($med){
                throw new Exception("Tidak bisa Delete, Dokter Sudah Punya Riwayat Melayani");
            }
            if($Dokter->isdokter==1){
                $current_avatar_path = storage_path($this->path) . '/' .$Dokter->photo;
                if (file_exists($current_avatar_path)) {
                    unlink($current_avatar_path);
                }
            }
            $staff->delete();
            $Dokter->delete();
            return Tools::MyResponse(true,"Dokter Was Deleted",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }

    public function update(Request $request,$id){
        try{
            $Dokter=Dokter::find($id);
            if(!$Dokter){
                throw new Exception("Dokter Tidak Ditemukan");
            }
            $this->validate($request,[
                'nama' => 'required',
                'tempat' => 'required',
                'tgl_lahir' => 'required',
                'pendidikan'=>'required',
                'poli_id'=>'required',
                'email'=>'required',
                'desc'=>'required',
            ],['required'=>':attribute cannot Empty']);
            $data=$request->all();
            if(key_exists('photo',$data)){
                $current_avatar_path = storage_path($this->path) . '/' .$Dokter->photo;
                if (file_exists($current_avatar_path)) {
                    unlink($current_avatar_path);
                }

                $thumbnail = Str::random(34);
                $ext=$request->file('photo')->getClientOriginalExtension();
                $this->filename=$thumbnail.'.'.$ext;
                $request->file('photo')->move(storage_path($this->path), $this->filename);

                $data['photo']=$this->filename;
            }
            $Dokter->fill($data);
            $Dokter->save();
            return Tools::MyResponse(true,"Dokter Was Updated",$Dokter,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
