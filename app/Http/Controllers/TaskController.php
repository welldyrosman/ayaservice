<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\Task;
class TaskController extends Controller
{
    public function getAllTask(){
        $provinsi=Task::all();
        return Tools::MyResponse(true,"OK",$provinsi,200);
    }
    public function gettodolist(){
        $todo=Task::where('solve_mk',0)->get();
        return Tools::MyResponse(true,"OK",$todo,200);
    }
    public function createtask(Request $request){
        $task=Task::create(["task"=>$request->input("task")]);
        return Tools::MyResponse(true,"OK",$task,200);
    }
    public function solvetask(Request $request){
        $data=$request->all();
        $this->validate($request,[
            "id"=>'required',
            "solve_desc"=>'required',
        ]);
        $task=Task::find($data["id"]);
        $task->fill(["solve_desc"=>$data["solve_desc"],"solve_mk"=>1]);
        $task->save();
        return Tools::MyResponse(true,"OK",$task,200);
    }
    public function getsolved(){
        $todo=Task::where('solve_mk',1)->get();
        return Tools::MyResponse(true,"OK",$todo,200);
    }

}
