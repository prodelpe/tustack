<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ConnectTelegramController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $token = Str::random(32);
        auth()->user()->update(['telegram_connect_token' => $token]);
        $botUsername = config('telegram.bots.tustack_bot.username');

        return redirect("https://t.me/{$botUsername}?start={$token}");
    }
}
