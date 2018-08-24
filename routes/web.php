<?php

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

Route::get('/google', function() {
    $client = new Google_Client();
    $client->setAuthConfig(storage_path('client_secrets.json'));
    $client->setAccessType("offline");        // offline access
    $client->setIncludeGrantedScopes(true);   // incremental auth
    $client->addScope(Google_Service_Drive::DRIVE);
    $client->setRedirectUri(url('/callback'));



    return redirect($client->createAuthUrl());
});

Route::get('/callback', function() {
    $client = new Google_Client();
    $client->setAuthConfig(storage_path('client_secrets.json'));
    $client->setAccessType("offline");        // offline access
    $client->setIncludeGrantedScopes(true);   // incremental auth
    $client->addScope(Google_Service_Drive::DRIVE);

    $client->authenticate(request()->input('code'));

    $access_token = $client->getAccessToken();

    $client->setAccessToken($access_token);

    $drive = new Google_Service_Drive($client);
    $files = $drive->files->listFiles(array())->getItems();
    return json_encode($files);

    // $service = new Google_Service_Drive($client);


    // // $files = $drive->files->listFiles(array())->files;

    // $file = new Google_Service_Drive_DriveFile();
    // $file->setName("addonsu-15.1-arm-signed.zip");
    // $result = $service->files->create($file, array(
    //     'data' => file_get_contents('https://mirrorbits.lineageos.org/su/20180718/addonsu-15.1-arm-signed.zip'),
    //     'mimeType' => 'application/octet-stream',
    //     'uploadType' => 'media'
    // ));

    // //return $result;
});
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
