<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;

class ProfilController extends Controller
{
    public function index()
    {
        return view('landing.profil', [
            'landingSections' => collect(SystemSetting::landingSections()),
        ]);
    }
}
