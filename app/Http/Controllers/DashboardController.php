<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        return view('dashboard', [
            'savedSearches'     => $user->savedSearches()->latest()->get(),
            'alertsEnabled'     => $user->alerts_enabled,
            'telegramConnected' => (bool) $user->telegram_chat_id,
        ]);
    }
}
