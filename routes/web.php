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
    $router->get('/api/v1/pasien/medhist/{id}','PasienController@getmedhist');



    $router->put('/api/v1/checkin/{id}','ReservasiController@checkin');

    $router->post('/api/v1/staff/{id}','StaffController@create');
    $router->get('/api/v1/staff','StaffController@getall');
    $router->put('/api/v1/staff/changepass','StaffController@changepass');


    $router->post('/api/v1/checkin','ReservasiController@checkinoffline');

    $router->post('/api/v1/reservasioff','ReservasiController@checkinoffline');
    $router->get('/api/v1/reservasiall','ReservasiController@getallreservation');
    $router->get('/api/v1/reservasioff','ReservasiController@offreservasi');
    $router->get('/api/v1/reservasitoday','ReservasiController@allreservasi');
    $router->get('/api/v1/reservasiuncoming','ReservasiController@notyetcheckin');


    $router->get('/api/v1/reservasion','ReservasiController@onreservasi');
    $router->get('/api/v1/regdashboard','ReservasiController@dashboard');

    $router->post('/api/v1/article','ArticleController@create');
    $router->delete('/api/v1/article/{id}','ArticleController@delete');
    $router->post('/api/v1/article/{id}','ArticleController@update');

    $router->get('/api/v1/dokter/{id}','DokterController@getid');
    $router->post('/api/v1/dokter','DokterController@create');
    $router->delete('/api/v1/dokter/{id}','DokterController@delete');
    $router->post('/api/v1/dokter/{id}','DokterController@update');

    $router->get('/api/v1/cosmetic','BarangController@getallcosmetic');
    $router->post('/api/v1/cosmetic','BarangController@createcosmetic');
    $router->put('/api/v1/cosmetic/{id}','BarangController@updatecosmetic');
    $router->delete('/api/v1/cosmetic/{id}','BarangController@deletecosmetic');
    $router->put('/api/v1/cancelreservasi/{id}','ReservasiController@cancelreservasi');


    $router->post('/api/v1/schedule','PoliController@createincharge');
    $router->get('/api/v1/scheduletoday','PoliController@gettodayincharge');
    $router->get('/api/v1/schedule/{date}','PoliController@getchargebydate');

    $router->get('/api/v1/screeningform/{id}','ScreeningController@screening');
    $router->post('/api/v1/submitscreen','ScreeningController@submitscreening');

    $router->get('/api/v1/formkind/{id}','ScreeningController@getFormkind');

    $router->get('/api/v1/formkindid/{id}','FormkindController@getid');
    $router->get('/api/v1/formkind','FormkindController@getall');
    $router->get('/api/v1/formkind/unselect/{id}','FormkindController@getunselect');
    $router->post('/api/v1/formkind','FormkindController@create');
    $router->delete('/api/v1/formkind/{id}','FormkindController@delete');
    $router->put('/api/v1/formkind/{id}','FormkindController@update');

    $router->get('/api/v1/medicalform/{id}','MedicalformController@getall');
    $router->post('/api/v1/medicalform','MedicalformController@create');
    $router->delete('/api/v1/medicalform/{id}','MedicalformController@delete');

    $router->get('/api/v1/medical/{id}','MedicalController@getmeddet');
    $router->get('/api/v1/dokgetscreening/{id}','MedicalController@doktergetscreen');
    $router->get('/api/v1/dokdashboard','MedicalController@dashboard');
    $router->put('/api/v1/medicalsave/{id}','MedicalController@medicalsave');
    $router->put('/api/v1/medicalsubmit/{id}','MedicalController@medicalsubmit');
    $router->put('/api/v1/medicalcancel/{id}','MedicalController@medicalcancel');
    $router->get('/api/v1/medicaldone','MedicalController@done');
    $router->get('/api/v1/medicalallres','MedicalController@allreserve');


    $router->get('/api/v1/transaksi','ResepController@getall');

    $router->get('/api/v1/admdashboard','AdminController@dashboard');


    $router->post('/api/v1/labs','LabsController@create');
    $router->get('/api/v1/labs/{id}','LabsController@getid');
    $router->put('/api/v1/labs/{id}','LabsController@update');

    $router->get('/api/v1/obatnc','BarangController@getnoncompositeobat');
    $router->get('/api/v1/obat','BarangController@getallobat');
    $router->get('/api/v1/obat/{id}','BarangController@getidobat');
    $router->delete('/api/v1/obat/{id}','BarangController@deleteobat');
    $router->post('/api/v1/obat','BarangController@createobat');
    $router->put('/api/v1/obat/{id}','BarangController@updateobat');
    $router->delete('/api/v1/obat/{id}','BarangController@deleteobat');

    $router->post('/api/v1/apotekcheck','ApotekController@submitcheck');
    $router->get('/api/v1/listapotek','ApotekController@gettodaylist');
    $router->get('/api/v1/apotekdashboard','ApotekController@dashboard');
    $router->get('/api/v1/getemptyitem','ApotekController@showemptyitem');
    $router->get('/api/v1/getwarnitem','ApotekController@showwarnitem');
    $router->get('/api/v1/stockitem','ApotekController@stockofname');
    $router->get('/api/v1/getpreorder','ApotekController@getpreorder');
    $router->put('/api/v1/takemissitem/{id}','ApotekController@takepreorderitem');


    $router->post('/api/v1/paymentsubmit','KasirController@submittrans');
    $router->get('/api/v1/paymentlist','KasirController@gettodaylist');
    $router->get('/api/v1/paymentitem/{id}','KasirController@getpayitem');

    $router->post('/api/v1/obat-in','BarangInController@barangin');





    $router->get('/api/v1/layanan/{id}','LayananController@getid');
    $router->post('/api/v1/layanan','LayananController@create');
    $router->put('/api/v1/layanan/{id}','LayananController@update');
    $router->delete('/api/v1/layanan/{id}','LayananController@delete');


    $router->get('/api/v1/report/dailyincome','ReportController@dailyincomereport');

    $router->post('/api/v1/handover','ClosingController@createhandover');
    $router->get('/api/v1/handover','ClosingController@handoverlist');
    $router->put('/api/v1/handover/{id}','ClosingController@gethandover');
    $router->get('/api/v1/handover/{id}','ClosingController@detailGenerate');
    $router->get('/api/v1/handover/detail','ClosingController@detailGenerateUnclose');

    $router->get('/api/v1/currenthandover','ClosingController@calcclosing');
    $router->get('/api/v1/needclosinglist','ClosingController@needclosinglist');

    $router->get('/api/v1/resepall','POSController@getresep');
    $router->post('/api/v1/reseppos','POSController@savepos');

    $router->put('/api/v1/testimoni/publish/{id}','TestimoniController@publish');
    $router->put('/api/v1/testimoni/unpublish/{id}','TestimoniController@unpublish');
    $router->get('/api/v1/testimoni','TestimoniController@getalltesti');
    $router->post('/api/v1/testimoni/staff','TestimoniController@create');
    $router->delete('/api/v1/testimoni/staff/{id}','TestimoniController@delete');
});



$router->group(['middleware' => 'auth:api'], function () use ($router){
    $router->post('/api/v1/reservation','ReservasiController@bookonline');
    $router->get('/api/v1/myreservasi','ReservasiController@myreservation');
    $router->get('/api/v1/myreservasihist','ReservasiController@myreservationhist');
    $router->get('/api/v1/mybio','PasienController@getbio');
    $router->post('/api/v1/pasien/{id}','PasienController@updatepasienoffline');

    $router->post('/api/v1/testimoni/pasien','TestimoniController@givetesti');
    $router->delete('/api/v1/testimoni/pasien/{id}','TestimoniController@deletepasien');

    $router->put('/api/v1/pasien/changepass','PasienController@changepass');
});
$router->get('/api/v1/dokter','DokterController@getall');
$router->get('/api/v1/layanan','LayananController@getall');
$router->get('/api/v1/reservation/{id}','ReservasiController@getreservasibyid');


$router->get('/api/v1/testimoni/{id}','TestimoniController@gettetibyid');
$router->get('/api/v1/testimonipublish','TestimoniController@gettestipublish');


$router->get('/tools/alltask','TaskController@getAllTask');
$router->get('/tools/todolist','TaskController@gettodolist');
$router->get('/tools/tododone','TaskController@getsolved');
$router->post('/tools/solvetask','TaskController@solvetask');
$router->post('/tools/newtask','TaskController@createtask');

$router->get('/tools/mailerr','MailController@getmailerr');
$router->get('/api/v1/poli','PoliController@getall');
$router->get('/tools/provinsi','AddressController@getprovinsi');
$router->get('/tools/city/{id}','AddressController@getcity');
$router->get('/tools/kec/{idprov}/{idkota}','AddressController@getkec');
$router->get('/tools/desa/{idprov}/{idkota}/{idkec}','AddressController@getdes');





$router->get('/api/v1/medicalkind','MedicalkindController@getall');
$router->post('/api/v1/medicalkind','MedicalkindController@create');
$router->delete('/api/v1/medicalkind/{id}','MedicalkindController@delete');


$router->get('/api/v1/screen','AntrianController@getscreen');


$router->post('/auth/v1/login', 'AuthController@loginPost');
$router->post('/auth/v1/login2', 'AuthController@loginstaff');
$router->get('/api/v1/verify/{id}', 'AuthController@verify');
$router->post('/api/v1/pasien','PasienController@addpasienonline');




$router->get('/api/v1/articleimg/{id}','ArticleController@get_image');
$router->get('/api/v1/article','ArticleController@getall');
$router->get('/api/v1/article/{id}','ArticleController@getid');



$router->get('/api/v1/cetakkartu/{id}','PasienController@membercard');

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
  //  Mail::to(['welldyrosman@gmail.com'])->send(new RegisterVer);

   // return new RegisterVer;
});


// $router->get('/login', function (Request $request) {
//     $token = app('auth')->attempt($request->only('email', 'password'));
//     return response()->json(compact('token'));
// });





$router->get('/key', function() {
    return \Illuminate\Support\Str::random(32);
});
