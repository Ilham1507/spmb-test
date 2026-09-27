<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;

class KontakController extends Controller
{
    public function index()
    {
        return view('landing.kontak');
    }
}
