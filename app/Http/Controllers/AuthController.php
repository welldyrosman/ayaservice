<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;
use App\Helpers\Tools;
use App\Models\MailErr;
use App\Models\Pasien;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    public function verify($id){
        DB::beginTransaction();
        try{
            $pasien=Pasien::find($id);
            Tools::Checkpasien($id);
            $user=User::where('email',$pasien->email)->first();
            if(!$user){
                throw new Exception("Cannot Found User".$pasien->email.$id);
            }
            if($user->email_verified_at!=null){
                throw new Exception("Invalid Verify");
            }
            $pasien->fill(['status_akun'=>'1']);
            $pasien->save();
            $user->fill(['email_verified_at'=>Carbon::now()]);
            $user->save();
            DB::commit();
            return redirect()->to('http://test.ayaklinik.id/verified');
          //  return Tools::MyResponse(true,"Email Verified",$pasien,200);
        }catch(Exception $e){
            DB::rollback();
            MailErr::create(["err_msg"=>$e->getMessage()]);
            return redirect()->to('http://test.ayaklinik.id/not-verified');
            // return Tools::MyResponse(false,$e,null,401);
        }
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
            if (!$token = auth('api')->attempt($request->only('email', 'password'))) {
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
        try{
            $token=compact('token')['token'];
            $user=Auth::guard('api')->user($token);
            $pasein=Pasien::where('email', $user['email'])->first();
            if(!$pasein){
                throw new Exception("Cannot found pasien".$user);
            }
            if($pasein->status_akun!=1){
                throw new Exception("Check your mail and Verify Your Account Please".$pasein->status_akun);
            }
            $pasein->kode_pasien='AKP'.str_pad($pasein->id,4,"0");
            $data=[
                "data"=>$pasein,
                "token"=>$token
            ];
            return Tools::MyResponse(true,'OK',$data,200);
        }catch(Exception $e){
            return Tools::MyResponse(false,$e,null,401);
        }
    }
}
