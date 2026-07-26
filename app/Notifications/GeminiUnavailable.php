<?php

namespace App\Notifications;

use App\Notifications\Channels\TelegramChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeminiUnavailable extends Notification
{
    public function __construct(private readonly string $reason) {}

    public function via(object $notifiable): array
    {
        return ['mail', TelegramChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('TuStack: company enrichment stopped')
            ->line('Gemini could not be reached, so company enrichment is on hold.')
            ->line('Reason: ' . $this->reason)
            ->line('Check the API quota and billing, then run companies:enrich again. The companies left out keep their pending state and will be retried.');
    }

    public function toTelegram(object $notifiable): string
    {
        return "<b>TuStack</b>\nCompany enrichment stopped: Gemini is not responding.\n\n<code>"
            . e($this->reason)
            . "</code>\n\nCheck the quota and run <code>companies:enrich</code> again.";
    }
}
