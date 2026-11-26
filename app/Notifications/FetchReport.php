<?php

namespace App\Notifications;

use App\Enums\CommandStatus;
use App\Enums\JobSource;
use App\Models\CommandLog;
use App\Notifications\Channels\TelegramChannel;
use App\Support\FetchRun;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The morning summary of the nightly fetch. A normal night is one short
 * Telegram message; mail is added only when something needs a look, so the
 * inbox stays for the days there is work to do.
 */
class FetchReport extends Notification
{
    public function __construct(private readonly CommandLog $log) {}

    public function via(object $notifiable): array
    {
        // Telegram first: channels go out in order, and a failing mailer must
        // not keep the message that is actually read from being sent.
        return $this->needsAttention()
            ? [TelegramChannel::class, 'mail']
            : [TelegramChannel::class];
    }

    /**
     * A night with nothing new is as suspicious as a night with errors: every
     * board failing quietly looks exactly like that from the outside.
     */
    public function needsAttention(): bool
    {
        return $this->log->status !== CommandStatus::Success || $this->stat('new_offers') === 0;
    }

    public function toTelegram(object $notifiable): string
    {
        $lines = [
            '📊 <b>TuStack · fetch of ' . $this->log->started_at->format('d/m') . '</b>',
            $this->statusIcon() . ' ' . $this->stat('total_queries') . ' queries, '
                . $this->stat('failed') . ' failed · ' . ($this->log->duration ?? '—'),
            '+' . $this->number('new_offers') . ' new offers (total ' . $this->number('total_offers') . ')',
            '+' . $this->number('new_companies') . ' new companies (total ' . $this->number('total_companies') . ')',
            'By source: ' . $this->bySource(),
        ];

        if ($failures = $this->sourceFailures()) {
            $lines[] = '⚠️ Errors: ' . $failures;
        }

        if ($offers = $this->offerFailures()) {
            $lines[] = '⚠️ Offers not saved: ' . $offers;
        }

        if ($this->stat('new_offers') === 0) {
            $lines[] = '⚠️ No new offers came in';
        }

        if (filled($this->log->error_message)) {
            $lines[] = '<code>' . e($this->log->error_message) . '</code>';
        }

        return implode("\n", $lines);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('TuStack: the fetch of ' . $this->log->started_at->format('d/m') . ' needs attention')
            ->line('Status: ' . $this->log->status->value . ' · ' . ($this->log->duration ?? 'not finished'))
            ->line($this->stat('total_queries') . ' queries, ' . $this->stat('failed') . ' failed.')
            ->line('New offers: ' . $this->number('new_offers') . ' (by source: ' . $this->bySource() . ').')
            ->line('New companies: ' . $this->number('new_companies') . '.');

        if ($failures = $this->sourceFailures()) {
            $mail->line('Errors by source: ' . $failures . '. It may be an expired key, an exhausted quota or a website that changed.');
        }

        if ($offers = $this->offerFailures()) {
            $mail->line('Offers that could not be saved: ' . $offers . '. The rest of the search was saved; the reason is in the Laravel log.');
        }

        if (filled($this->log->error_message)) {
            $mail->line('Error: ' . $this->log->error_message);
        }

        return $mail->line('The details are in Filament, under Command logs, and in the Laravel log.');
    }

    private function statusIcon(): string
    {
        return match (true) {
            $this->log->status === CommandStatus::Failed => '❌',
            $this->needsAttention()         => '⚠️',
            default                         => '✅',
        };
    }

    private function bySource(): string
    {
        $counts = $this->log->stats['new_offers_by_source'] ?? [];

        // Every board is listed, a zero included: a board that goes silent is
        // the thing worth seeing.
        $parts = [];

        foreach (JobSource::cases() as $source) {
            $parts[] = ucfirst($source->value) . ' ' . number_format((int) ($counts[$source->value] ?? 0), 0, ',', '.');
        }

        return implode(' · ', $parts);
    }

    private function sourceFailures(): ?string
    {
        $failures = $this->log->stats['source_failures'] ?? [];

        if (empty($failures)) {
            return null;
        }

        $parts = [];

        foreach ($failures as $source => $count) {
            $parts[] = ucfirst($source) . ' in ' . $count . ' queries';
        }

        return implode(' · ', $parts);
    }

    private function offerFailures(): ?string
    {
        $failures = $this->log->stats['offer_failures'] ?? [];

        if (empty($failures)) {
            return null;
        }

        $parts = [];

        foreach ($failures as $source => $count) {
            $parts[] = ucfirst($source) . ' ' . $count;
        }

        return implode(' · ', $parts);
    }

    private function stat(string $key): int
    {
        return (int) ($this->log->stats[$key] ?? 0);
    }

    private function number(string $key): string
    {
        return number_format($this->stat($key), 0, ',', '.');
    }
}
