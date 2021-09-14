<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\Tools;
use Illuminate\Support\Facades\DB;
use Exception;
use Tymon\JWTAuth\JWTAuth;

class BarangInController extends Controller
{
     public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;

    }

}
