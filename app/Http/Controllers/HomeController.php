<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Facades\App\Services\GoogleDrive;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // GoogleDrive::upload('01 - Chogada (128 Kbps) - DownloadMing.SE.mp3', 'http://2017.downloadming2018.com/temp/Loveratri%20(2018)/01%20-%20Chogada%20(128%20Kbps)%20-%20DownloadMing.SE.mp3');
        return view('home');
    }
}
