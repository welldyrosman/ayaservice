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
use App\Models\Reservasi;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use stdClass;
use Tymon\JWTAuth\JWTAuth;

class MedicalController extends Controller{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }
    public function medicalcancel($id){
        DB::beginTransaction();
        try{
            $medical=Medical::find($id);
            $medical->fill(['status'=>1]);
            $medical->save();
            $antrian=Antrian::where('medical_id',$id)->first();
            $antrian->fill(['status'=>1]);
            $antrian->save();
            $resep=Resep::where('medical_id',$id)->first();
            $resep->fill(['status'=>1]);
            $resep->save();
            $reservasi=Reservasi::where('medical_id',$id)->first();
            $reservasi->fill(['status'=>3]);
            $reservasi->save();
            DB::commit();
            return Tools::MyResponse(true,"Medical Data Has Been Cancelled",null,200);

        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function medicalsubmit(Request $request,$id){
        DB::beginTransaction();
        try{
            $this->saveaction($request,$id);
            Tools::MedChangeStatus($id,3,3,2,5);
            DB::commit();
            return Tools::MyResponse(true,"Medical Data Has Been Saved",null,200);

        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    private function saveaction(Request $request,$id){
        $this->validate($request,[
            "treatment_kind"=>"required",
            "fee"=>"required",
            "screenitems.*.id"=>"required",
            "screenitems.*.medkind_id"=>"required",
            "screenitems.*.medform_id"=>"required",
            "screenitems.*.val_desc"=>"required",
            "detail_resep.*.barang_id"=>"required",
            "detail_resep.*.qty"=>"required",
        ]);
        $medical=Medical::find($id);
        if(!$medical){
            throw new Exception("Cannot Found Medical");
        }
        $resep=Resep::where('medical_id',$id)->first();
        if(!$resep){
            $resep=Resep::create([
                "medical_id"=>$id,
                "status"=>"1"
            ]
            );
        }
        $detail_resep=$request->input("detail_resep");
        $resepid=$resep->id;
        DetailResep::where('resep_id',$resepid)->delete();
        ItemOut::where('resep_id',$resepid)->delete();
        foreach($detail_resep as $row){
            $barangid=$row['barang_id'];
            $barang=Barang::find($barangid);
            if(!$barang){
                throw new Exception("Cannot Found Obat");
            }
            $row['resep_id']=$resepid;
            $row['harga']=$barang->harga;
            $row['iscomposite']=$barang->iscomposite;
            if($barang->iscomposite!=0){
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
                    "unit"=>$barang->unit,
                    "harga"=>$barang->harga
                ]
            );
        }
        $data=$request->all();
        $screenitems=$data['screenitems'];
        foreach($screenitems as $items){
            $medcr=MedicalScreen::find($items['id']);
            $medcr->fill($items);
            $medcr->save();
        }
        $medical->fill($data);
        $medical->save();
    }
    public function medicalsave(Request $request,$id){
        DB::beginTransaction();
        try{
            $this->saveaction($request,$id);
            DB::commit();
            return Tools::MyResponse(true,"Medical Data Has Been Saved",null,200);

        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    private function detailmed($id){
        $medical=new stdClass();
        $medicalscren=DB::select("select f.*,s.val_desc,k.nama as label_kind,k.datatype from medical m
        join medform f on m.poli_id=f.poli_id
        left join medscreen s on f.id=s.medform_id and s.medical_id=m.id
        join medkind k on f.medkind_id=k.id
        where m.id=$id");
        $medicalform=DB::select("select m.*,p.poli,d.nama as dokter,u.nama as pasien,
        (select CONCAT('REG',LPAD(id,6,'0')) from reservasi where medical_id=m.id) as code_reg
        from medical m
        join poli p on m.poli_id=p.id
        left join dokter d on m.dokter_id=d.id
        join pasiens u on m.pasien_id=u.id
        where m.id=$id");
        $resep=DB::select("select b.id,d.iscomposite,d.qty,d.unit,b.harga,b.nama from resep r
        join resep_detail d on r.id=d.resep_id
        join barang b on d.barang_id=b.id and kind=1
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
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $dokter=Dokter::where('email',$user->email)->first();
            $now=Carbon::now()->toDateString();
            $cekantian=Antrian::where('poli_id',$dokter->poli_id)->where('status','2')->where('queue_date',$now)->first();
            if($cekantian&&$cekantian->id!=$id){
                throw new Exception("Cannot Process More Than 1 Pasien");
            }
            $pasien=Pasien::find($antrian->pasien_id);
            $screendata=MedicalScreen::where('medical_id',$antrian->medical_id)->get();
            $medical=Medical::find($antrian->medical_id);
            $antrian->fill([
                "status"=>"2"
            ]);
            $resep=Resep::where('medical_id',$antrian->medical_id)->first();
            if(!$resep){
                $resep=Resep::create([
                    "medical_id"=>$antrian->medical_id,
                    "status"=>"1"
                 ]);
            }
            $antrian->save();
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
    private function graphicreservasi($poliid){
        $graph=DB::select("
        select  MONTHNAME(tgl_book) monthbook,count(id) as qty from reservasi
        where poli_id=$poliid
        group by MONTH(tgl_book),monthbook order by  MONTH(tgl_book) desc");
        $arrdata=array();
        $arrcat=array();
        foreach($graph as $item){
            array_push($arrcat,$item->monthbook);
            array_push($arrdata,$item->qty);
        }

        return ["data"=>$arrdata,"category"=>$arrcat];
    }
    public function allreserve(){
        try{
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $dokter=Dokter::where('email',$user->email)->first();
            $poliid=$dokter->poli_id;
            $now=Carbon::now()->toDateString();
            $data=DB::select("SELECT a.*,p.nama,p.tgl_lahir,p.jk,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien,
            i.poli FROM antrian a
            join pasiens p on a.pasien_id=p.id
            join poli i on a.poli_id=i.id
            where a.queue_date='$now' and a.poli_id='$poliid' and a.status!=0
            order by a.reg_time asc
      ");
            return Tools::MyResponse(true,"OK",$data,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function done(){
        try{
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $dokter=Dokter::where('email',$user->email)->first();
            $poliid=$dokter->poli_id;
            $now=Carbon::now()->toDateString();
            $data=DB::select("SELECT a.*,p.nama,p.tgl_lahir,p.jk,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien,
            i.poli FROM antrian a
            join pasiens p on a.pasien_id=p.id
            join poli i on a.poli_id=i.id
            where a.queue_date='$now' and a.poli_id='$poliid' and a.status in(3,4,5)
            order by a.reg_time asc
      ");
            return Tools::MyResponse(true,"OK",$data,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function dashboard(){
        try{
        $token = $this->jwt->getToken();
        $user= Auth::guard('staff')->user($token);
        $dokter=Dokter::where('email',$user->email)->first();
        $poliid=$dokter->poli_id;
        $data=new stdClass();
        $now=Carbon::now()->toDateString();
        $currentproc=DB::select("select a.*,pl.poli as poli,p.nama,p.tgl_lahir,CONCAT('AKP',LPAD(p.id,4,'0')) as kode_pasien from antrian a
        left join pasiens p on a.pasien_id=p.id
        left join poli pl on a.poli_id=pl.id
        where a.queue_date='$now' and a.poli_id=$poliid and a.status=2");
        // if(count($currentproc)<1){
        //     throw new Exception($now.$poliid);
        // }
        $data->graph=$this->graphicreservasi($poliid);
        $data->regqty=count(DB::select("select * from reservasi where tgl_book='$now' and poli_id='$poliid'"));
        $data->waiting=count(DB::select("select * from antrian where queue_date='$now' and poli_id='$poliid' and status=1"));
        $data->process=count($currentproc)<1?null:$currentproc[0];
        $data->done=count(DB::select("select * from antrian where queue_date='$now' and poli_id='$poliid' and status in(3,4,5)"));

        $data->now=$now;
        return Tools::MyResponse(true,"OK",$data,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
