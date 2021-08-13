<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;
use App\Helpers\Tools;
use App\Models\Pasien;
use App\Models\Staff;
use Exception;
use Illuminate\Support\Facades\Auth;
use stdClass;

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
        } catch (TokenInvalidException $e) {
            return Tools::MyResponse(false,$e,null,401);
        } catch (JWTException $e) {
            return Tools::MyResponse(false,$e,null,401);
        } catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
        $token=compact('token')['token'];
        $user= Auth::guard('staff')->user($token);
        $data=[
            "data"=>$user,
            "token"=>$token
        ];
        return Tools::MyResponse(true,'OK',$data,200);
    }
    public function loginPost(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required|email|max:255',
            'password' => 'required',
        ]);

        try {
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
        $token=compact('token')['token'];
        $user=Auth::user($token);
        $pasein=Pasien::where('email', $user['email'])->first();
        $data=[
            "data"=>$pasein,
            "token"=>$token
        ];
        return Tools::MyResponse(true,'OK',$data,200);
    }
}
