<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;
use App\Helpers\Tools;

class AuthController extends Controller
{
    /**
     * @var TymonJWTAuthJWTAuth
     */
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
    }

    public function loginPost(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required|email|max:255',
            'password' => 'required',
        ]);

        try {
            if (!$token = $this->jwt->attempt($request->only('email', 'password'))) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (TokenExpiredException $e) {
            return Tools::MyResponse(false,$e,null,401);
          //  return response()->json(['token_expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return Tools::MyResponse(false,$e,null,401);
            //return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (JWTException $e) {
            return Tools::MyResponse(false,$e,null,401);
            //return response()->json(['token_absent' => $e->getMessage()], $e->getStatusCode());
        }
        return Tools::MyResponse(true,'OK',compact('token'),200);
    }
}
