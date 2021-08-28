<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use App\Models\MailErr;
use App\Models\Poli;
use Illuminate\Support\Facades\DB;
use Exception;
class MailController extends Controller
{

    public function getmailerr(){
        return MailErr::all();
    }

}
