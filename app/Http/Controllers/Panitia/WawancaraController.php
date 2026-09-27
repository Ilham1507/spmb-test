<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WawancaraController extends Controller
{
    public function index()
    {
        return view('panitia.wawancara.index');
    }
}
