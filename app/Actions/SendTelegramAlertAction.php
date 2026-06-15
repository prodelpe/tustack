<?php

namespace App\Actions;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Support\Collection;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

readonly class SendTelegramAlertAction
{
    public function handle(User $user, SavedSearch $savedSearch, Collection $companies): void
    {
        $lines = ["<b>New companies matching «{$savedSearch->describe()}»</b>\n"];

        foreach ($companies->take(10) as $company) {
            $lines[] = '• <a href="' . route('companies.show', $company) . '">' . $company->name . '</a>';
        }

        if ($companies->count() > 10) {
            $lines[] = "\n<i>And " . ($companies->count() - 10) . ' more...</i>';
        }

        $telegram = new Api(config('telegram.bots.findyourdevstack_bot.token'));
        $telegram->sendMessage([
            'chat_id'    => $user->telegram_chat_id,
            'text'       => implode("\n", $lines),
            'parse_mode' => 'HTML',
        ]);
    }
}
