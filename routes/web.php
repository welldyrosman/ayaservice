<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use App\Mail\RegisterVer;
use \Illuminate\Http\Request;
use App\Models\User;
use Faker\Provider\File;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as Image;
use Milon\Barcode\DNS1D;

$router->get('/', function () use ($router) {
    return $router->app->version();
});
$router->group(['middleware' => 'auth:staff'], function () use ($router){
    $router->get('/api/v1/poli','PoliController@getall');
    $router->get('/api/v1/poli/{id}','PoliController@getid');
    $router->put('/api/v1/poli/{id}','PoliController@update');
    $router->delete('/api/v1/poli/{id}','PoliController@delete');
    $router->post('/api/v1/poli','PoliController@create');

    $router->post('/api/v1/registrasi','PasienController@addpasienoffline');
    $router->post('/api/v1/registrasi/{id}','PasienController@updatepasienoffline');
    $router->delete('/api/v1/registrasi/{id}','PasienController@deletepasien');
    $router->put('/api/v1/registrasi/banned/{id}','PasienController@bannedpasien');
    $router->put('/api/v1/registrasi/enable/{id}','PasienController@disabled');
    $router->put('/api/v1/registrasi/disable/{id}','PasienController@enabled');
    $router->get('/api/v1/pasien','PasienController@getallpasien');
    $router->get('/api/v1/pasien/{id}','PasienController@getpasienbyid');

    $router->put('/api/v1/checkin/{id}','ReservasiController@checkin');
    $router->post('/api/v1/staff/{id}','StaffController@create');
    $router->post('/api/v1/checkin','ReservasiController@checkinoffline');
    $router->get('/api/v1/reservasi','ReservasiController@gettodayreservasi');


    $router->post('/api/v1/article','ArticleController@create');
    $router->delete('/api/v1/article/{id}','ArticleController@delete');
    $router->post('/api/v1/article/{id}','ArticleController@update');

    $router->get('/api/v1/dokter','DokterController@getall');
    $router->get('/api/v1/dokter/{id}','DokterController@getid');
    $router->post('/api/v1/dokter','DokterController@create');
    $router->delete('/api/v1/dokter/{id}','DokterController@delete');
    $router->post('/api/v1/dokter/{id}','DokterController@update');

    $router->get('/api/v1/cosmetic','BarangController@getallcosmetic');
    $router->post('/api/v1/cosmetic','BarangController@createcosmetic');
    $router->put('/api/v1/cosmetic/{id}','BarangController@updatecosmetic');
    $router->delete('/api/v1/cosmetic/{id}','BarangController@deletecosmetic');


});

$router->group(['middleware' => 'auth:api'], function () use ($router){
    $router->post('/api/v1/reservation','ReservasiController@bookonline');
    $router->get('/api/v1/myreservasi','ReservasiController@myreservation');
    $router->put('/api/v1/cancelreservasi/{id}','ReservasiController@cancelreservasi');

});
$router->get('/api/v1/medicalform','MedicalformController@getall');
$router->post('/api/v1/medicalform','MedicalformController@create');

$router->get('/api/v1/medicalkind','MedicalkindController@getall');
$router->post('/api/v1/medicalkind','MedicalkindController@create');

$router->get('/api/v1/screen','AntrianController@getscreen');


$router->post('/auth/v1/login', 'AuthController@loginPost');
$router->post('/auth/v1/login2', 'AuthController@loginstaff');
$router->post('/api/v1/pasien','PasienController@addpasienonline');




$router->get('/api/v1/articleimg/{id}','ArticleController@get_image');
$router->get('/api/v1/article','ArticleController@getall');
$router->get('/api/v1/article/{id}','ArticleController@getid');

$router->get('/api/v1/obatnc','BarangController@getnoncompositeobat');
$router->get('/api/v1/obat','BarangController@getallobat');
$router->get('/api/v1/obat/{id}','BarangController@getidobat');
$router->delete('/api/v1/obat/{id}','BarangController@deleteobat');
$router->post('/api/v1/obat','BarangController@createobat');
$router->put('/api/v1/obat/{id}','BarangController@updateobat');
$router->delete('/api/v1/obat/{id}','BarangController@deleteobat');

$router->get('pdftest/{id}','PasienController@membercard');

$router->get('ayaklinik/logo','PasienController@get_image');
$router->get('pasien/barcode/{id}','PasienController@getbarcode');

$router->get('pdftestview',function(Request $request){
    $d = new DNS1D();
    $d->setStorPath(__DIR__.'/cache/');
    $ss=$d->getBarcodeHTML('232323', 'EAN13',1,21,'#276071',false);
    $data = ['barcode' => $ss];
    return view('Kartupasien',$data);
});

$router->get('/mail', function() {
    Mail::to(['welldyrosman@gmail.com'])->send(new RegisterVer);

    return new RegisterVer;
});


// $router->get('/login', function (Request $request) {
//     $token = app('auth')->attempt($request->only('email', 'password'));
//     return response()->json(compact('token'));
// });




$router->get('/key', function() {
    return \Illuminate\Support\Str::random(32);
});
