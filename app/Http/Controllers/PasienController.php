<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Mail\RegisterVer;
use App\Models\Medical;
use App\Models\Pasien;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image as Image;
use Milon\Barcode\DNS1D;
use Milon\Barcode\DNS2D;
use stdClass;
use Tymon\JWTAuth\JWTAuth;

class PasienController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }
    protected $path='app/photo_pasien';
    protected $publicpath='storage/app/photo_pasien';
    public function getbarcode($id){
        $d = new DNS1D();
        $d->setStorPath(__DIR__.'/cache/');
        echo $d->getBarcodeHTML($id, 'EAN13');
        //return response($d->getBarcodeHTML($id, 'EAN13'), 200);//->header('Content-Type', 'image/jpeg');
    }
    public function get_image(){
        $avatar_path = storage_path('app/logo.png');
            if (file_exists($avatar_path)) {
                $file = file_get_contents($avatar_path);
                return response($file, 200)->header('Content-Type', 'image/jpeg');
            }
        return Tools::MyResponse(false,"Image Not Found",null,401);
    }
    public function membercard($id){
        $pasien=Pasien::find($id);
        try{
            if(!$pasien){
                throw new Exception("Cannot Found Pasien");
            }
            $pdf = App::make('dompdf.wrapper');
            $customPaper = array(0,0,243,155);
            $d = new DNS1D();
            $d->setStorPath(__DIR__.'/cache/');
            $ss=$d->getBarcodeHTML($id, 'EAN13',1,21,'#276071',false);
            $pasien->nopasien='AKP'.$pasien->id;
            $data = ['barcode' => $ss,'pasien'=>$pasien];
            $pdf->loadView('Kartupasien',$data);
            $pdf->setPaper($customPaper);
            return $pdf->stream('kuntul.pdf');
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
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
        if(key_exists('photo_pasien',$data) && $data['photo_pasien']!=null){
            $thumbnail = Str::random(34);
            $ext=$request->file('photo_pasien')->getClientOriginalExtension();
            $this->filename=$thumbnail.'.'.$ext;

            $request->file('photo_pasien')->move(storage_path($this->publicpath), $this->filename);
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
                'kel' => 'required',
                'prov' => 'required',
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
                'kel' => 'required',
                'prov' => 'required',
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
            $this->registrationMail($pasien);
            DB::commit();
            return Tools::MyResponse(true,"OK",$pasien,200);
        }catch(Exception $e){
            DB::rollback();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function registrationMail($pasien){
        Mail::to([$pasien->email])->send(new RegisterVer($pasien));
        return new RegisterVer($pasien);
    }
    protected $filename;
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
                'kel' => 'required',
                'prov' => 'required',
                'pekerjaan' => 'required',
                'no_telp' => 'required',
                'partner' => 'required',
                'partner_tel' => 'required',
                'partner_status' => 'required',
            ],['required'=>':attribute cannot Empty']);
            $data = $request->all();
            if(key_exists('photo_pasien',$data)){
               // throw new Exception(storage_path());
                if($pasien->photo_pasien!=null){
                    $current_avatar_path = storage_path($this->path. '/' .$pasien->photo_pasien) ;
                    if (file_exists($current_avatar_path)) {
                        unlink($current_avatar_path);
                    }
                }
                $thumbnail = Str::random(34);
                $ext=$request->file('photo_pasien')->getClientOriginalExtension();
                $this->filename=$thumbnail.'.'.$ext;
            //    throw new Exception(storage_path($this->publicpath));
                $request->file('photo_pasien')->move(storage_path($this->path), $this->filename);
                $data['photo_pasien']=$this->filename;
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
    public function getbio(){
        try{
            $token = $this->jwt->getToken();
            $user=Auth::user($token);
            $pasein=Pasien::where('email', $user['email'])->first();
            if(!$pasein){
                throw new Exception("Cannot Found Pasien");
            }
            return $this->getpasienbyid($pasein->id);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function getpasienbyid($id){
        $data=DB::select("select *,CONCAT('AKP',LPAD(id,4,'0')) as kode_pasien
        ,(select max(created_at) as last_time from medical where pasien_id=pasiens.id) as last_time
        from pasiens where id=$id");
        if(!isset($data[0])){
            return Tools::MyResponse(false,"Data Pasien Tidak Ditemukan",null,428);
        }else{
            return Tools::MyResponse(true,"OK",$data[0],200);
        }
    }
    public function getallpasien(Request $request){
        $this->validate($request,[
            "rowsPerPage"=>"required",
            "page"=>"required"
        ]);
        $offset=$request->input('page')-1;
        $rowsPerPage=$request->input('rowsPerPage');
        $filter=$request->input('filter');
        $sort=$request->input('sort');
        $cmd="";
        if($filter){
            foreach($filter as $key=>$value){
                // if($key=="kode_pasien"){
                //     // $digit=str_replace("AKP","", strtoupper($value));
                //     // $value=is_numeric($digit)?intval($digit):"z";
                //     $key="id";
                // }
                $cmd.=" AND $key LIKE '%$value%' ";
            }
        }
        $orderby="";
        if($sort||$sort!=""){
            $pieces = explode(",", $sort);
            $col=$pieces[0]=="kode_pasien"?"id":$pieces[0];
            $orderby.=" order by $col $pieces[1]";
        }
        try{
            $pasien=DB::select("with t as(
                select p.id,p.nama,p.created_at,p.no_telp,p.email,p.jk,CONCAT(kt.nama,'-',pv.nama)  as kota
                ,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien
                from pasiens p
                join t_propinsi pv on p.prov=pv.id
                join t_kota kt on p.kota=kt.id_kota and p.prov=kt.id_prov
                )select * from t where 1=1 $cmd $orderby LIMIT $rowsPerPage OFFSET $offset");
            $data=new stdClass();
            $data->rows=$pasien;
            $data->count=Pasien::all()->count();
            return Tools::MyResponse(true,"OK",$data,200);
            // $pasien=Pasien::all();
            // return Tools::MyResponse(true,"OK",$pasien,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }

    public function getmedhist($id){
        try{
            $medical=DB::select("select m.*,p.poli,d.nama as dokter,
            (select CONCAT('REG',LPAD(id,6,'0')) from reservasi where medical_id=m.id) as code_reg
            from medical m
            join poli p on m.poli_id=p.id
            left join dokter d on m.dokter_id=d.id
            where m.pasien_id=$id");
            return Tools::MyResponse(true,"OK",$medical,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    //
}
