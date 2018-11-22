<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/* Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
}); */

//Route::resource('mov','Mov\MovController', ['only' => ['store']]);
//Route::resource('cliente','Client\ClientController',['only' => ['store']]);

Route::post('store_xaction', 'Mov\MovController@store');
Route::post('invoice_request','Mov\MovController@makeInvoice');
Route::get('test', function () {
    return response()->json('Hola mundo', 200);
});