<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function __construct()
    {
        return $this->middleware('auth');
    }


    public function index()
    {
        return view('dashboard');
    }
}
