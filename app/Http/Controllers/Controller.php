<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function redirectAfterParticipantSave(Request $request, string $nextRoute, string $message, string $nextTour): RedirectResponse
    {
        $returnToReview = $request->input('return_to') === 'review';
        $response = redirect()->route($returnToReview ? 'peserta.review' : $nextRoute)
            ->with('success', $message);

        if (! $returnToReview) {
            $response->with('participant_tour', $nextTour);
        }

        return $response;
    }
}
