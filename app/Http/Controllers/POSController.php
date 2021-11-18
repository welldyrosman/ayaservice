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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;
use Tymon\JWTAuth\JWTAuth;

class POSController extends Controller
{
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }
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
            $token = $this->jwt->getToken();
            $user= Auth::guard('staff')->user($token);
            $dataresep=[
                "status"=>"3",
                "staff_id"=>$user->id,
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
    public function getresepbyid($id){
        try
       {
        $resep=DB::select("
        select r.*,CONCAT('TRX',LPAD(r.id,6,'0')) as kode_trans,
        case when r.medical_id is not null then p.nama
        when r.medical_id is null and r.pasien_id is not null then p2.nama
        else r.cust_nm end as nama,
        case when r.transtype=1 then p.no_telp
        when r.transtype=2 then p2.no_telp
        else r.phone_no end as no_telp,
        CONCAT('AK',LPAD(p.id,4,'0')) as kode_pasien,m.fee,s.nama as nama_staff,
        rd.grand_total
        from resep r
        left join medical m on r.medical_id=m.id
        left join pasiens p on m.pasien_id=p.id
        left join pasiens p2 on r.pasien_id=p2.id
        left join staff s on r.staff_id=s.id
        left join (
            select sum(qty*harga) as grand_total,resep_id from resep_detail group by resep_id
            )rd on rd.resep_id=r.id
        where r.id=$id");
          // $resep=Resep::where('id',$id)->with(["detailresep.barang"])->first();
           $deatail=DB::select("select rd.*,b.nama,t.takaran from resep_detail rd
           join barang b on rd.barang_id=b.id
           left join takaran t on rd.takaran_id=t.id
           where rd.resep_id=$id
           ");
           $data=new stdClass();
           $data->form=$resep;
           $data->resep=$deatail;
            return Tools::MyResponse(true,"OK",$data,200);
        } catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function getresep(){
        $resep=Resep::with(["detailresep"])->get();
        return Tools::MyResponse(true,"OK",$resep,200);
    }
}
