<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Dokter;
use App\Models\Staff;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class DokterController extends Controller
{
    protected $path='app/dokter_photo';
    protected $publicpath='storage/app/dokter_photo';
    public function getall(){
        $Dokter=Dokter::all();
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
            $request->file('photo')->move(storage_path($this->path), $this->filename);
            $data['photo']=$this->filename;
            $data['staff_id']=1;
            $Dokter = Dokter::create($data);
            $akundata=[
                "nama"=>$data['nama'],
                "email"=>$data["email"],
                "password"=>app('hash')->make("Dokter@#123"),
                "role_id"=>2
            ];
            Staff::create($akundata);
            DB::commit();
            return Tools::MyResponse(true,"OK",$Dokter,200);
        }catch(Exception $e){
            $current_avatar_path = storage_path($this->path) . '/' .$this->filename;
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
            $current_avatar_path = storage_path($this->path) . '/' .$Dokter->photo;
            if (file_exists($current_avatar_path)) {
              unlink($current_avatar_path);
            }
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
                'photo'=>'required|image',
            ],['required'=>':attribute cannot Empty']);

            $current_avatar_path = storage_path($this->path) . '/' .$Dokter->photo;
            if (file_exists($current_avatar_path)) {
              unlink($current_avatar_path);
            }
            $thumbnail = Str::random(34);
            $ext=$request->file('photo')->getClientOriginalExtension();
            $this->filename=$thumbnail.'.'.$ext;
            $request->file('photo')->move(storage_path($this->path), $this->filename);
            $data=$request->all();
            $data['photo']=$this->filename;
            $Dokter->fill($data);
            $Dokter->save();
            return Tools::MyResponse(true,"Dokter Was Updated",$Dokter,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
