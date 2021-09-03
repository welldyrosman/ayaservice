<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Antrian;
use App\Models\Barang;
use App\Models\CompositeItem;
use App\Models\DetailResep;
use App\Models\Dokter;
use App\Models\ItemOut;
use App\Models\Labs;
use App\Models\Medical;
use App\Models\MedicalScreen;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\Resep;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use stdClass;
use Tymon\JWTAuth\Facades\JWTAuth;

class MedicalController extends Controller{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }
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
            $resep=Resep::where('medical_id',$id);
            $detail_resep=$request->input("detail_resep");
            $resepid=$resep->id;
            DetailResep::where('resep_id',$resepid)->delete();
            foreach($detail_resep as $row){
                Validator::make($row,[
                    "barang_id"=>"required",
                    "qty"=>"required",
                ]);
                $barangid=$row->barang_id;
                $barang=Barang::find($barangid);
                $row->resep_id=$resepid;
                $row->harga=$barang->harga;
                $row->isComposite=$barang->isComposite;
                if($barang->isComposite){
                    $itemcomposite=CompositeItem::where('parent_id',$barang->id)->get();
                    foreach($itemcomposite as $item){
                        ItemOut::create([
                            "resep_id"=>$resepid,
                            "barang_id"=>$item['id'],
                            "qty"=>$row['qty'],
                            "compositeitem"=>true
                        ]);
                    }
                }else{
                    ItemOut::create([
                        "resep_id"=>$resepid,
                        "barang_id"=>$barangid,
                        "qty"=>$row['qty'],
                        "compositeitem"=>false
                    ]);
                }
                DetailResep::create(
                    [
                        "resep_id"=>$resepid,
                        "barang_id"=>$barangid,
                        "iscomposite"=>$barang->isComposite,
                        "qty"=>$row['qty'],
                        "unit"=>$barang->harga
                    ]
                );
            }
            $data=$request->all();
            $medical->fill($data);
            $medical->save();
            return Tools::MyResponse(true,"Medical Data Has Been Saved",$medical,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    private function detailmed($id){
        $medical=new stdClass();
        $medicalscren=DB::select("select s.*,k.nama as label_kind,k.datatype from medical m
        left join medscreen s on m.id=s.medical_id
        join medkind k on s.medkind_id=k.id
        where m.id=$id");
        $medicalform=DB::select("select m.*,p.poli,d.nama as dokter,u.nama as pasien,
        (select CONCAT('REG',LPAD(id,6,'0')) from reservasi where medical_id=m.id) as code_reg
        from medical m
        join poli p on m.poli_id=p.id
        left join dokter d on m.dokter_id=d.id
        join pasiens u on m.pasien_id=u.id
        where m.id=$id");
        $resep=DB::select("select r.*,d.iscomposite,d.qty,d.unit,d.harga,b.nama from resep r
        left join resep_detail d on r.id=d.resep_id
        left join barang b on d.barang_id=b.id and kind=1
        where r.medical_id=$id");
        $labs=Labs::where('medical_id',$id)->first();
        $medical->form=$medicalform;
        $medical->screen=$medicalscren;
        $medical->labs=$labs;
        $medical->resep=$resep;
        return $medical;
    }
    public function getmeddet($id){
        try{
            $medical=$this->detailmed($id);
            return Tools::MyResponse(true,"OK",$medical,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function doktergetscreen(Request $request,$id){
        DB::beginTransaction();
        try{
            $antrian=Antrian::find($id);
            if(!$antrian){
                throw new Exception("Cannot Found Antrian");
            }
            $pasien=Pasien::find($antrian->pasien_id);
            $screendata=MedicalScreen::where('medical_id',$antrian->medical_id)->get();
            $medical=Medical::find($antrian->medical_id);
            $antrian->fill([
                "status"=>"2"
            ]);
            $antrian->save();
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $dokter=Dokter::where('email',$user->email)->first();
            $medical->fill([
                "dokter_id"=>$dokter->id //hardcode temporary
            ]);
            $medical->save();
            DB::commit();
            $ret=$this->detailmed($antrian->medical_id);
            return Tools::MyResponse(true,"OK",$ret,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
