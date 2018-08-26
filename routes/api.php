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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/files', 'FileController@index');
    Route::post('/files', 'FileController@store');
    Route::delete('/files/{file}', 'FileController@destroy');
    Route::post('/files/{file}/cancel', 'FileController@cancel');
    Route::post('/files/{file}/retry', 'FileController@retry');
    Route::post('/files/{file}/redownload', 'FileController@redownload');
});
