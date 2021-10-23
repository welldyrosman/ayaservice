<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Barang;
use App\Models\CompositeItem;
use App\Models\DetailResep;
use App\Models\ItemOut;
use App\Models\Resep;
use Exception;
use Illuminate\Support\Facades\DB;

class POSController extends Controller
{
    public function savepos(Request $request){
        DB::beginTransaction();
        try{
            $this->validate($request,[
                "transtype"=>"required",
                "detail_resep"=>"required|present|array|min:1",
                "detail_resep.*.barang_id"=>"required",
                "detail_resep.*.qty"=>"required",
            ]);
            $data=$request->all();
            $transtype=$data["transtype"];

            $dataresep=[
                "status"=>"3",
                "transtype"=>$transtype];
            if($transtype=="2"){
                $this->validate($request,[
                    "pasien_id"=>"required"
                ]);
                $dataresep["pasien_id"]=$data["pasien_id"];
            }else{
                $this->validate($request,[
                    "cust_nm"=>"required",
                    "phone_no"=>"required"
                ]);
                $dataresep["cust_nm"]=$data["cust_nm"];
                $dataresep["phone_no"]=$data["phone_no"];
            }
            $resep=Resep::create($dataresep);
            $resepid=$resep->id;
            DetailResep::where('resep_id',$resepid)->delete();
            ItemOut::where('resep_id',$resepid)->delete();
            $detail_resep=$request->input("detail_resep");
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
                        throw new Exception($barang->id);
                    }
                    foreach($itemcomposite as $item){
                        ItemOut::create([
                            "resep_id"=>$resepid,
                            "barang_id"=>$item['barang_id'],
                            "qty"=>$item['qty']*$row['qty'],
                            "compositeitem"=>true
                        ]);
                    }
                }else{
                    if(!$barangid){
                        throw new Exception($barang->id);
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
                        "unit"=>$barang->unit,
                        "harga"=>$barang->harga
                    ]
                );
            }
            DB::commit();
            return Tools::MyResponse(true,"Medical Data Has Been Saved",null,200);
        }
        catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function getresep(){
        $resep=Resep::with(["detailresep"])->get();
        return Tools::MyResponse(true,"OK",$resep,200);
    }
}
