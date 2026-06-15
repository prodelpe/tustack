<?php

namespace App\Jobs;

use App\Mail\SearchAlertMail;
use App\Models\Company;
use App\Models\SavedSearch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

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

        Mail::to($savedSearch->user)->send(
            new SearchAlertMail($savedSearch->user, $savedSearch, $companies)
        );

        $savedSearch->update(['last_notified_at' => now()]);
    }
}
