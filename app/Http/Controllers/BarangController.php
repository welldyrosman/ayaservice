<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Barang;
use App\Models\BarangIn;
use App\Models\CompositeItem;
use App\Models\Poli;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Validator;
use stdClass;

class BarangController extends Controller
{

    public function getnoncompositeobat(){
        return $this->getnoncomposite(1);
    }
    public function getnoncompositecosmetic(){
        return $this->getnoncomposite(2);
    }
    private function getnoncomposite($kind){
        $barang=Barang::where('kind',$kind)->where('iscomposite',false)->get();
        return Tools::MyResponse(true,"OK",$barang,200);
    }
    private function getall(Request $request, $kind){
        try{
            $this->validate($request,[
                "rowsPerPage"=>"required",
                "page"=>"required"
            ]);
            $page=Tools::GenPagingQueryStr($request);
            $filter=$request->input('filter');
            $sort=$request->input('sort');
            $cmd=Tools::GenFilterQueryStr($filter);
            $orderby=Tools::GenSortQueryStr($sort);
            $all=Barang::where('kind',$kind)->get();
            $barang=DB::select("select * from barang  where kind='$kind' $cmd $orderby $page");
            $data=new stdClass();
            $data->rows=$barang;
            $data->count=count($all);
            return Tools::MyResponse(true,"OK",$data,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function getallobat(Request $request){
        return $this->getall($request,1);
    }
    public function getallcosmetic(Request $request){
        return $this->getall($request,2);
    }
    public function getidobat($id){
        return $this->getid($id,1);
    }
    public function getidcosmetik($id){
        return $this->getid($id,2);
    }
    private function getid($id,$kind){
       try{
            $barang=Barang::where('id',$id)->where('kind',$kind)->first();
            if (!$barang) {
                throw new Exception("Barang tidak ditemukan");
            }
            $compositeitem=[];
            if($barang->iscomposite==true){
                $compositeitem=DB::select("select b.*,c.qty from composite_item c
                join barang b on c.barang_id=b.id
                where c.parent_id=$id
                ");
            }
            $barang['composite_item']=$compositeitem;
            return Tools::MyResponse(true,"OK",$barang,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function createobat(Request $request){
        return $this->create($request,1);
    }
    public function createcosmetik(Request $request){
        return $this->create($request,2);
    }
    private function create(Request $request,$kind){
        $data = $request->all();
        DB::beginTransaction();
        try{
            $this->validate($request,[
            'barcode' => 'required',
            'nama' => 'required',
            'unit' => 'required',
            'harga' => 'required',
            'iscomposite' => 'required',
            ]
            ,['required'=>':attribute cannot Empty']);
            $data['kind']=$kind;
            $cek=Barang::where('barcode',$data['barcode'])->where('kind',$kind)->first();
            if($cek!=null){
                throw new Exception("barcode was used by other");
            }
            $barang = Barang::create($data);
            if($data['iscomposite']==true){
                $this->validate($request,[
                    'itemcomposite' => 'required',
                    ]
                    ,['required'=>':attribute cannot Empty']);
                $compositeite=$request->input('itemcomposite');
                foreach($compositeite as $item){
                    Validator::make($item,[
                       "barang_id"=>'required',
                       "qty"=>"required"
                    ],['required'=>':attribute cannot Empty'])->validate();
                    $item["parent_id"]=$barang->id;
                    CompositeItem::create($item);
                }
            }
            DB::commit();
            return Tools::MyResponse(true,"OK",$barang,200);
        }catch(Exception $e){
            DB::rollback();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function deleteobat($id){
        return $this->delete($id,1);
    }
    public function deletecosmetic($id){
        return $this->delete($id,2);
    }
    private function delete($id,$kind){
        DB::beginTransaction();
        try{
            $barang = Barang::where('id',$id)->where('kind',$kind)->first();

            if (!$barang) {
                throw new Exception("Barang tidak ditemukan");
            }
            if($barang->iscomposite=="0"){
                $haveparent=CompositeItem::where('barang_id',$id)->get();
                if(count($haveparent)>0){
                    throw new Exception("this item is used by other item as composite item");
                }
            }
            $barangin=BarangIn::where('barang_id',$id)->get();
            if(count($barangin)>1){
                throw new Exception("this item used by other data");
            }
            $barang->delete();
            DB::commit();
            return Tools::MyResponse(true,"Item Was Deleted",null,200);
        }
        catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }

    }
    public function updateobat(Request $request,$id){
        return $this->update($request,$id,1);
    }
    public function updatecosmetic(Request $request,$id){
        return $this->update($request,$id,1);
    }
    public function update(Request $request,$id,$kind){
        DB::beginTransaction();
        try{
            $barang=Barang::where('id',$id)->where('kind',$kind)->first();
            if(!$barang){
                throw new Exception("Barang Tidak Ditemukan");
            }
            $this->validate($request,[
                'barcode' => 'required',
                'nama' => 'required',
                'unit' => 'required',
                'harga' => 'required',
                'iscomposite' => 'required',
            ]);

            $data=$request->all();
            $barang->fill($data);
            $barang->save();
            if($data['iscomposite']==true){
                $this->validate($request,[
                    'itemcomposite' => 'required',
                    ]
                    ,['required'=>':attribute cannot Empty']);
                $compositeite=$request->input('itemcomposite');
                CompositeItem::where('parent_id',$id)->delete();
                foreach($compositeite as $item){
                    Validator::make($item,[
                       "barang_id"=>'required',
                       "qty"=>"required"
                    ],['required'=>':attribute cannot Empty'])->validate();
                    $item["parent_id"]=$barang->id;
                    CompositeItem::create($item);
                }
            }
            DB::commit();
            return Tools::MyResponse(true,"Poli Was Updated",$barang,200);
        }catch(Exception $e){
            DB::rollBack();
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
