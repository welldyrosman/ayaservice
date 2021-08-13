<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;
use App\Helpers\Tools;
use Exception;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * @var TymonJWTAuthJWTAuth
     */
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {

        $this->jwt = $jwt;
      //  $this->middleware('auth:staff', ['except' => ['loginPost']]);
    }
    public  function loginstaff(Request $request){
        try{
            $this->validate($request, [
                'email'    => 'required|email|max:255',
                'password' => 'required',
            ]);
            if (!$token = auth('staff')->attempt($request->only('email', 'password'))){
                throw new Exception("User Not Found");
            };
        } catch (TokenExpiredException $e) {
            return Tools::MyResponse(false,$e,null,401);
          //  return response()->json(['token_expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return Tools::MyResponse(false,$e,null,401);
            //return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (JWTException $e) {
            return Tools::MyResponse(false,$e,null,401);
            //return response()->json(['token_absent' => $e->getMessage()], $e->getStatusCode());
        } catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
        return Tools::MyResponse(true,'OK',compact('token'),200);
    }
    public function loginPost(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required|email|max:255',
            'password' => 'required',
        ]);

        try {
           // return response()->json($this->jwt->user());
          //  \Config::set('auth.providers.users.model', \App\Trainer::class);
            if (!$token = $this->jwt->attempt($request->only('email', 'password'))) {
               throw new Exception("User Not Found");
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
        } catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
        return Tools::MyResponse(true,'OK',compact('token'),200);
    }
}
