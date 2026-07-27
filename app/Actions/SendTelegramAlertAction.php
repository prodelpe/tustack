<?php

namespace App\Actions;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

readonly class SendTelegramAlertAction
{
    public function handle(User $user, SavedSearch $savedSearch, Collection $companies): void
    {
        $lines = ["<b>New companies matching «{$savedSearch->describe()}»</b>\n"];

        foreach ($companies->take(10) as $company) {
            $lines[] = '• <a href="' . route('companies.show', $company) . '">' . e($company->display_name) . '</a>';
        }

        if ($companies->count() > 10) {
            $lines[] = "\n<i>And " . ($companies->count() - 10) . ' more...</i>';
        }

        $telegram = new Api(config('telegram.bots.tustack_bot.token'));

        try {
            $telegram->sendMessage([
                'chat_id'    => $user->telegram_chat_id,
                'text'       => implode("\n", $lines),
                'parse_mode' => 'HTML',
            ]);
        } catch (TelegramSDKException $e) {
            Log::error('Telegram SDK error', ['chat_id' => $user->telegram_chat_id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
