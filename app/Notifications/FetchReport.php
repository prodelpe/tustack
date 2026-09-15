<?php

namespace App\Notifications;

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
        return $this->log->status !== 'success' || $this->stat('new_offers') === 0;
    }

    public function toTelegram(object $notifiable): string
    {
        $lines = [
            '📊 <b>TuStack · fetch del ' . $this->log->started_at->format('d/m') . '</b>',
            $this->statusIcon() . ' ' . $this->stat('total_queries') . ' cerques, '
                . $this->stat('failed') . ' fallides · ' . ($this->log->duration ?? '—'),
            '+' . $this->number('new_offers') . ' ofertes noves (total ' . $this->number('total_offers') . ')',
            '+' . $this->number('new_companies') . ' empreses noves (total ' . $this->number('total_companies') . ')',
            'Per font: ' . $this->bySource(),
        ];

        if ($failures = $this->sourceFailures()) {
            $lines[] = '⚠️ Errors: ' . $failures;
        }

        if ($offers = $this->offerFailures()) {
            $lines[] = '⚠️ Ofertes no guardades: ' . $offers;
        }

        if ($this->stat('new_offers') === 0) {
            $lines[] = '⚠️ No ha entrat cap oferta nova';
        }

        if (filled($this->log->error_message)) {
            $lines[] = '<code>' . e($this->log->error_message) . '</code>';
        }

        return implode("\n", $lines);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('TuStack: el fetch del ' . $this->log->started_at->format('d/m') . ' necessita atenció')
            ->line('Estat: ' . $this->log->status . ' · ' . ($this->log->duration ?? 'sense acabar'))
            ->line($this->stat('total_queries') . ' cerques, ' . $this->stat('failed') . ' fallides.')
            ->line('Ofertes noves: ' . $this->number('new_offers') . ' (per font: ' . $this->bySource() . ').')
            ->line('Empreses noves: ' . $this->number('new_companies') . '.');

        if ($failures = $this->sourceFailures()) {
            $mail->line('Errors per font: ' . $failures . '. Pot ser una clau caducada, una quota esgotada o una web que ha canviat.');
        }

        if ($offers = $this->offerFailures()) {
            $mail->line('Ofertes que no s\'han pogut guardar: ' . $offers . '. La resta de la cerca sí que s\'ha guardat; el motiu és al log de Laravel.');
        }

        if (filled($this->log->error_message)) {
            $mail->line('Error: ' . $this->log->error_message);
        }

        return $mail->line('El detall és a Filament, a Command logs, i al log de Laravel.');
    }

    private function statusIcon(): string
    {
        return match (true) {
            $this->log->status === 'failed' => '❌',
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

        foreach (FetchRun::SOURCES as $source) {
            $parts[] = ucfirst($source) . ' ' . number_format((int) ($counts[$source] ?? 0), 0, ',', '.');
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
            $parts[] = ucfirst($source) . ' en ' . $count . ' cerques';
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
