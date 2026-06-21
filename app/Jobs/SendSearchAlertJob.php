<?php

namespace App\Jobs;

use App\Actions\SendTelegramAlertAction;
use App\Mail\SearchAlertMail;
use App\Models\Company;
use App\Models\SavedSearch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendSearchAlertJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private SavedSearch $savedSearch) {}

    public function handle(): void
    {
        $savedSearch = $this->savedSearch;
        $since       = $savedSearch->last_notified_at ?? $savedSearch->created_at;
        $filters     = $savedSearch->filters;

        $query = Company::with(['province', 'jobOffers.technologies'])
            ->where('created_at', '>', $since);

        if (! empty($filters['technologies'])) {
            $query->whereHas('jobOffers.technologies', fn ($q) =>
                $q->whereIn('name', $filters['technologies'])
            );
        }

        if (! empty($filters['provinces'])) {
            $query->whereHas('province', fn ($q) =>
                $q->whereIn('name', $filters['provinces'])
            );
        }

        $companies = $query->get();

        if ($companies->isEmpty()) {
            return;
        }

        $user = $savedSearch->user;

        $context = ['user_id' => $user->id, 'email' => $user->email, 'saved_search_id' => $savedSearch->id, 'companies' => $companies->count()];

        if ($user->telegram_chat_id) {
            try {
                app(SendTelegramAlertAction::class)->handle($user, $savedSearch, $companies);
                Log::info('Telegram alert sent', $context);
            } catch (Throwable $e) {
                Log::error('Telegram alert failed', $context + ['error' => $e->getMessage()]);
            }
        }

        if ($user->alerts_enabled) {
            try {
                Mail::to($user)->send(new SearchAlertMail($user, $savedSearch, $companies));
                Log::info('Search alert mail sent', $context);
            } catch (Throwable $e) {
                Log::error('Search alert mail failed', $context + ['error' => $e->getMessage()]);
            }
        }

        $savedSearch->update(['last_notified_at' => now()]);
    }
}
