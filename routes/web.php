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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/api/v1/pasien','PasienController@getallpasien');
$router->get('/api/v1/pasien/{id}','PasienController@getpasienbyid');
$router->post('/api/v1/pasien','PasienController@addpasienonline');
$router->post('/api/v1/registrasi','PasienController@addpasienoffline');
$router->put('/api/v1/registrasi/{id}','PasienController@updatepasienoffline');
$router->delete('/api/v1/registrasi/{id}','PasienController@deletepasien');
$router->put('/api/v1/registrasi/banned/{id}','PasienController@bannedpasien');
$router->put('/api/v1/registrasi/enable/{id}','PasienController@disabled');
$router->put('/api/v1/registrasi/disable/{id}','PasienController@enabled');


$router->get('/api/v1/poli','PoliController@getall');
$router->get('/api/v1/poli/{id}','PoliController@getid');
$router->put('/api/v1/poli/{id}','PoliController@update');
$router->delete('/api/v1/poli/{id}','PoliController@delete');
$router->post('/api/v1/poli','PoliController@create');
