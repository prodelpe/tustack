<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramWebhookController extends Controller
{
    /**
     * @throws TelegramSDKException
     */
    public function __invoke(Request $request): void
    {
        $this->abortUnlessSecretMatches($request);

        $text   = $request->input('message.text', '');
        $chatId = $request->input('message.chat.id');

        if (! $chatId || ! str_starts_with($text, '/start ')) {
            return;
        }

        $token = trim(str_replace('/start ', '', $text));

        $user = User::query()
            ->where('telegram_connect_token', $token)
            ->first();

        if (! $user) {
            return;
        }

        $user->update([
            'telegram_chat_id'        => $chatId,
            'telegram_connect_token'  => null,
        ]);

        $telegram = new Api(config('telegram.bots.tustack_bot.token'));

        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text'    => "Connected! You'll now receive job alerts via Telegram.",
        ]);
    }

    private function abortUnlessSecretMatches(Request $request): void
    {
        $secret = config('telegram.bots.tustack_bot.webhook_secret');

        if (blank($secret)) {
            return;
        }

        $received = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');

        abort_unless(hash_equals($secret, $received), 403);
    }
}
