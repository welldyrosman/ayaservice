<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Article;
use Exception;
use Illuminate\Support\Str;
class ArticleController extends Controller
{
    protected $path='app/thumbnail_img';
    public function getall(){
        $Article=Article::all();
        return Tools::MyResponse(true,"OK",$Article,200);
    }
    public function get_image($id){
        $avatar_path = storage_path($this->path) . '/' . $id;
            if (file_exists($avatar_path)) {
                $file = file_get_contents($avatar_path);
                return response($file, 200)->header('Content-Type', 'image/jpeg');
            }
        return Tools::MyResponse(false,"Image Not Found",null,401);
    }
    public function getid($id){
       try{
            $Article=Article::find($id);
            if (!$Article) {
                throw new Exception("Article tidak ditemukan");
            }
            return Tools::MyResponse(true,"OK",$Article,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }

    protected $filename;
    public function create(Request $request){
        $data = $request->all();
        try{
            $this->validate($request,[
                'subject' => 'required',
                'content' => 'required',
                'thumbnail_img'=>'required|image',
            ],['required'=>':attribute cannot Empty']);

            $thumbnail = Str::random(34);
            $ext=$request->file('thumbnail_img')->getClientOriginalExtension();
            $this->filename=$thumbnail.'.'.$ext;
            $request->file('thumbnail_img')->move(storage_path($this->path), $this->filename);
            $data['thumbnail_img']=$this->filename;
            $data['staff_id']=1;
            $Article = Article::create($data);
            return Tools::MyResponse(true,"OK",$Article,200);
        }catch(Exception $e){
            $current_avatar_path = storage_path($this->path) . '/' .$this->filename;
            if (file_exists($current_avatar_path)) {
              unlink($current_avatar_path);
            }
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function delete($id){
        try{
            $Article = Article::find($id);
            if (!$Article) {
                throw new Exception("Article tidak ditemukan");
            }
            $current_avatar_path = storage_path($this->path) . '/' .$Article->thumbnail_img;
            if (file_exists($current_avatar_path)) {
              unlink($current_avatar_path);
            }
            $Article->delete();
            return Tools::MyResponse(true,"Article Was Deleted",null,200);
        }
        catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
    public function update(Request $request,$id){
        try{
            $Article=Article::find($id);
            if(!$Article){
                throw new Exception("Article Tidak Ditemukan");
            }
            $this->validate($request,[
                'subject' => 'required',
                'content' => 'required',

            ],['required'=>':attribute cannot Empty']);

            $data=$request->all();
            $current_avatar_path = storage_path($this->path) . '/' .$Article->thumbnail_img;
            if(key_exists('thumbnail_img',$data)){
                if (file_exists($current_avatar_path)) {
                unlink($current_avatar_path);
                }

                $thumbnail = Str::random(34);
                $ext=$request->file('thumbnail_img')->getClientOriginalExtension();
                $this->filename=$thumbnail.'.'.$ext;
                $request->file('thumbnail_img')->move(storage_path($this->path), $this->filename);

                $data['thumbnail_img']=$this->filename;
            }
            $Article->fill($data);
            $Article->save();
            return Tools::MyResponse(true,"Article Was Updated",$Article,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
