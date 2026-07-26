<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Throwable;

class TelegramChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $chatId = $notifiable->telegram_chat_id ?? null;

        if (blank($chatId) || ! method_exists($notification, 'toTelegram')) {
            return;
        }

        try {
            (new Api(config('telegram.bots.tustack_bot.token')))->sendMessage([
                'chat_id'    => $chatId,
                'text'       => $notification->toTelegram($notifiable),
                'parse_mode' => 'HTML',
            ]);
        } catch (Throwable $e) {
            // Never let a failing alert break whatever triggered it.
            Log::error('Telegram notification failed', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
