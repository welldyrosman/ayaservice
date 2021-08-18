<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Barang;
use App\Models\CompositeItem;
use App\Models\DetailResep;
use App\Models\ItemOut;
use App\Models\Medical;
use App\Models\Poli;
use App\Models\Resep;
use Exception;
use Illuminate\Support\Facades\DB;
use stdClass;

class MedicalController extends Controller{
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
                $this->validate($row,[
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
}
