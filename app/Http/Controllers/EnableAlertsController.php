<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class EnableAlertsController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        auth()->user()->update(['alerts_enabled' => true]);

        return redirect()->route('dashboard');
    }
}
