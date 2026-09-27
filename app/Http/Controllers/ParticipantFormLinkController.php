<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class ParticipantFormLinkController extends Controller
{
    /** A short-lived link from the payment notification. Authentication is still required. */
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('login')->with('status', 'Silakan masuk untuk melanjutkan pengisian formulir.');
    }
}
