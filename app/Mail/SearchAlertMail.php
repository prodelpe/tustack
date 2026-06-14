<?php

namespace App\Mail;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

class SearchAlertMail extends Mailable
{
    public string $searchUrl;
    public string $unsubscribeUrl;

    public function __construct(
        public User $user,
        public SavedSearch $savedSearch,
        public Collection $companies,
    ) {
        $this->searchUrl      = $this->buildSearchUrl();
        $this->unsubscribeUrl = URL::signedRoute('alerts.unsubscribe', ['user' => $user->id]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New companies matching your search · Find Your Dev Stack',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.search-alert',
        );
    }

    private function buildSearchUrl(): string
    {
        $filters = $this->savedSearch->filters;
        $params  = [];

        if (! empty($filters['technologies'])) {
            $params['technologies'] = implode(',', $filters['technologies']);
        }

        if (! empty($filters['provinces'])) {
            $params['provinces'] = implode(',', $filters['provinces']);
        }

        if (! empty($filters['query'])) {
            $params['q'] = $filters['query'];
        }

        return url('/') . ($params ? '?' . http_build_query($params) : '');
    }
}
