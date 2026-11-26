<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DisconnectTelegramController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        auth()->user()->update(['telegram_chat_id' => null, 'telegram_connect_token' => null]);

        return redirect()->route('dashboard');
    }
}
