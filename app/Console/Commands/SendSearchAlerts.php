<?php

namespace App\Console\Commands;

use App\Mail\SearchAlertMail;
use App\Models\Company;
use App\Models\SavedSearch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSearchAlerts extends Command
{
    protected $signature = 'searches:notify';

    protected $description = 'Send email alerts for saved searches with new matching companies';

    public function handle(): int
    {
        $searches = SavedSearch::with('user')
            ->whereHas('user', fn ($q) => $q->where('alerts_enabled', true))
            ->get();

        if ($searches->isEmpty()) {
            $this->info('No active saved searches.');
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($searches as $savedSearch) {
            $since   = $savedSearch->last_notified_at ?? $savedSearch->created_at;
            $filters = $savedSearch->filters;

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
                continue;
            }

            Mail::to($savedSearch->user)->send(
                new SearchAlertMail($savedSearch->user, $savedSearch, $companies)
            );

            $savedSearch->update(['last_notified_at' => now()]);
            $sent++;
        }

        $this->info("Done. Sent {$sent} alert(s) out of {$searches->count()} saved searches.");

        return self::SUCCESS;
    }
}
