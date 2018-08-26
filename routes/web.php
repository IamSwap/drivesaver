<?php

use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

// Dashboard
Route::get('/dashboard/{vue_router?}', 'DashboardController@index')->where('vue_router', '[\/\w\.-]*');

// Authorize with Google
Route::get('/auth', 'GoogleController@redirectToProvider');
Route::get('/callback', 'GoogleController@handleProviderCallback');

// Handle default auth routes
Route::get('/login', function () {
    return redirect('/auth');
});

Route::get('/register', function () {
    return redirect('/auth');
});

Route::post('/login', function () {
    return abort(404);
});

Route::post('/register', function () {
    return abort(404);
});

Route::get('/password/reset', function () {
    return abort(404);
});

Route::get('/password/reset/{token}', function () {
    return abort(404);
});


// dev route
Route::get('/dev', function () {
    //
});
