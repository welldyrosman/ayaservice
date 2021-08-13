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
use \Illuminate\Http\Request;
use App\Models\User;
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
    $router->put('/api/v1/registrasi/{id}','PasienController@updatepasienoffline');
    $router->delete('/api/v1/registrasi/{id}','PasienController@deletepasien');
    $router->put('/api/v1/registrasi/banned/{id}','PasienController@bannedpasien');
    $router->put('/api/v1/registrasi/enable/{id}','PasienController@disabled');
    $router->put('/api/v1/registrasi/disable/{id}','PasienController@enabled');
  //  $router->get('/api/v1/pasien','PasienController@getallpasien');
    $router->get('/api/v1/pasien/{id}','PasienController@getpasienbyid');

    $router->put('/api/v1/checkin/{id}','ReservasiController@checkin');
    $router->post('/api/v1/staff/{id}','StaffController@create');
});

$router->group(['middleware' => 'auth:api'], function () use ($router){
    $router->post('/api/v1/reservation','ReservasiController@bookonline');
    $router->get('/api/v1/myreservasi','ReservasiController@myreservation');
    $router->put('/api/v1/cancelreservasi/{id}','ReservasiController@cancelreservasi');

});

$router->post('/auth/v1/login', 'AuthController@loginPost');
$router->post('/auth/v1/login2', 'AuthController@loginstaff');
$router->post('/api/v1/pasien','PasienController@addpasienonline');
$router->get('/api/v1/pasien','PasienController@getallpasien');

$router->get('/api/v1/articleimg/{id}','ArticleController@get_image');
$router->get('/api/v1/article','ArticleController@getall');
$router->get('/api/v1/article/{id}','ArticleController@getid');
$router->post('/api/v1/article','ArticleController@create');
$router->delete('/api/v1/article/{id}','ArticleController@delete');
$router->post('/api/v1/article/{id}','ArticleController@update');

// $router->get('/login', function (Request $request) {
//     $token = app('auth')->attempt($request->only('email', 'password'));
//     return response()->json(compact('token'));
// });




$router->get('/key', function() {
    return \Illuminate\Support\Str::random(32);
});
