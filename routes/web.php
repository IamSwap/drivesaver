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

Route::get('/auth', 'GoogleController@redirectToProvider');
Route::get('/callback', 'GoogleController@handleProviderCallback');


Route::get('/dev', function () {
    // Facades\App\Services\GoogleDrive::upload(
    //     '01 - Chogada (320 Kbps) - DownloadMing.SE.mp3',
    //     'http://2017.downloadming2018.com/temp/Loveratri%20(2018)/01%20-%20Chogada%20(320%20Kbps)%20-%20DownloadMing.SE.mp3'
    // );
});
