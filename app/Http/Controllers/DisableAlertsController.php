<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DisableAlertsController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        auth()->user()->update(['alerts_enabled' => false]);

        return redirect()->route('dashboard');
    }
}
