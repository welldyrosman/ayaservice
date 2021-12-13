<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Antrian;
use App\Models\Barang;
use App\Models\CompositeItem;
use App\Models\DetailResep;
use App\Models\Dokter;
use App\Models\Formformat;
use App\Models\ItemOut;
use App\Models\Labs;
use App\Models\Medical;
use App\Models\MedicalForm;
use App\Models\Medicalkind;
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
            $resep=Resep::where('medical_id',$id)->first();
            Tools::MedChangeStatus($resep->id,3,3,2,5);
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
            "special"=>"required",
            // "screenitems.*.id"=>"required",
            "screenitems.*.medkind_id"=>"required",
            "screenitems.*.medform_id"=>"required",
           // "screenitems.*.val_desc"=>"required",
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
        $resep=Resep::where('medical_id',$id)->first();
        if(!$resep){
            throw new Exception("Cannot Found Resep");
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
            if($barang->iscomposite){
                $itemcomposite=CompositeItem::where('parent_id',$barang->id)->get();
                if(count($itemcomposite)<1){
                    throw new Exception("Error Barang 1");
                }
                foreach($itemcomposite as $item){
                    // if(!$item['id']){
                    //     throw new Exception(json_encode($item));
                    // }
                    ItemOut::create([
                        "resep_id"=>$resepid,
                        "barang_id"=>$item['barang_id'],
                        "qty"=>$item['qty']*$row['qty'],
                        "compositeitem"=>true
                    ]);
                }
            }else{
                if(!$barangid){
                    throw new Exception("Error Barang 2");
                }
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
                    "eat_qty"=>array_key_exists('eat_qty',$row)?$row['eat_qty']:0,
                    "day_qty"=>array_key_exists('day_qty',$row)?$row['day_qty']:0,
                    "takaran_id"=>array_key_exists('takaran_id',$row)?$row['takaran_id']:null,
                    "unit"=>$barang->unit,
                    "harga"=>$barang->harga
                ]
            );
        }
        $data=$request->all();
        if($data["special"]==1){
            try{
                $this->validate($request,["payamt"=>"required"]);
                $resep->fill(
                    ["special"=>$data["special"],
                        "payamt"=>$data["payamt"]
                    ]
                );
                $resep->save();
            }catch(Exception $e){
                throw new Exception("Input Special Case Amount");
            }
        }
        $screenitems=$data['screenitems'];
        foreach($screenitems as $items){
            if($items['id']==null){
                $items['medical_id']=$id;
                $medcrcek=MedicalScreen::where('medform_id',$items['medform_id'])->where('medical_id',$items['medical_id'])->first();
                if($medcrcek==null){
                    MedicalScreen::create($items);
                }else{
                    $medcrcek->fill($items);
                    $medcrcek->save();
                }
            }else{
                $medcr=MedicalScreen::find($items['id']);
                $medcr->fill($items);
                $medcr->save();
            }
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

    public function ceknode($formformat,$medid,$med){
        foreach($formformat as $form){
            $nodes=Formformat::where('formkind_id',$medid)->where('formformat_id',$form->id)->get();
            $inputs=DB::select("
            select mf.*,ms.val_desc from medform mf
            left join medscreen ms on mf.id=ms.medform_id and ms.medical_id=$med
            where mf.formformat_id=$form->id  ");
            foreach($inputs as $input){
                $input->medkind=Medicalkind::find($input->medkind_id);
            }
            if(count($nodes)>0){
                $this->ceknode($nodes,$medid,$med);
                $form->subtitle=$nodes;
            }
            $form->input=$inputs;
            if(count($nodes)>0){
                $form->subtitle=$nodes;
            }
        }
    }

    public function getnewform($id){
        $medformnew=Medical::find($id);
        $formformat=Formformat::where('formkind_id',$medformnew->formkind_id)->where('formformat_id',0)->get();
        foreach($formformat as $form){
            $nodes=Formformat::where('formkind_id',$medformnew->formkind_id)->where('formformat_id',$form->id)->get();
            $inputs=DB::select("
            select mf.*,ms.val_desc from medform mf
            left join medscreen ms on mf.id=ms.medform_id and ms.medical_id=$id
            where mf.formformat_id=$form->id  ");

            foreach($inputs as $input){
                $input->medkind=Medicalkind::find($input->medkind_id);
            }
            $form->input=$inputs;
            if(count($nodes)>0){
                $form->subtitle=$nodes;
                $this->ceknode($nodes,$medformnew->formkind_id,$id);
            }
        }
        return $formformat;
    }

    private function detailmed($id){
        $medical=new stdClass();
        $medicalscren=$this->getnewform($id);
        $medicalform=DB::select("
        select m.*,p.poli,d.nama as dokter,u.nama as pasien,
        (select CONCAT('TRX',LPAD(id,6,'0')) from resep where medical_id=m.id) as code_trans
        from medical m
        join poli p on m.poli_id=p.id
        left join dokter d on m.dokter_id=d.id
        join pasiens u on m.pasien_id=u.id
        where m.id=$id");
        $resep=DB::select("select b.id,b.iscomposite,d.qty,d.unit,b.harga,b.nama,r.id as resep_id,d.ispreorder,r.payamt,r.special
        ,k.takaran
        from resep r
        left join resep_detail d on r.id=d.resep_id
        join barang b on d.barang_id=b.id and kind=1
        left join takaran k on d.takaran_id=k.id
        where r.medical_id=$id");
        $labs=Labs::where('medical_id',$id)->first();
        $medical->form=$medicalform;
        $medical->screen=$medicalscren;
        $medical->labs=$labs;
        $medical->transaksi=Resep::where('medical_id',$id)->first();
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
            $medical=Medical::find($antrian->medical_id);
            $resep=Resep::where('medical_id',$antrian->medical_id)->first();
            if(!$resep){
                $resep=Resep::create([
                    "medical_id"=>$antrian->medical_id,
                    "status"=>"1"
                 ]);
            }
            $medical->fill([
                "dokter_id"=>$dokter->id //hardcode temporary
            ]);
            $medical->save();
            Tools::MedChangeStatus($resep->id,2,2,1,4);
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
            $data=DB::select("SELECT a.*,p.nama,p.tgl_lahir,p.jk,CONCAT('AK',LPAD(p.id,4,'0')) as kode_pasien,
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
            $data=DB::select("SELECT a.*,p.nama,p.tgl_lahir,p.jk,CONCAT('AK',LPAD(p.id,4,'0')) as kode_pasien,
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
        $currentproc=DB::select("select a.*,pl.poli as poli,p.nama,p.tgl_lahir,CONCAT('AK',LPAD(p.id,4,'0')) as kode_pasien from antrian a
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
