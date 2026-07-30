<?php

namespace App\Actions;

use App\DTOs\NormalizedJobOfferDTO;
use App\Models\Company;
use App\Models\JobOffer;
use App\Support\JobTitle;
use App\Support\PlausibleSalary;
use Illuminate\Support\Carbon;

class UpsertJobOfferAction
{
    public function handle(NormalizedJobOfferDTO $dto, ?Company $company): JobOffer
    {
        $existing = $this->findByUrl($dto->url) ?? $this->findRepublished($dto, $company);

        if ($existing) {
            return $this->refresh($existing, $dto);
        }

        return $this->create($dto, $company);
    }

    private function findByUrl(string $url): ?JobOffer
    {
        return JobOffer::query()->where('url', $url)->first();
    }

    private function findRepublished(NormalizedJobOfferDTO $dto, ?Company $company): ?JobOffer
    {
        $title = JobTitle::normalize($dto->title);

        if ($company === null || blank($title)) {
            return null;
        }

        return JobOffer::query()
            ->where('company_id', $company->id)
            ->where('source', $dto->source)
            ->where('title_normalized', $title)
            ->where('published_at', '>=', now()->subDays(config('jobs.duplicate_window_days')))
            ->first();
    }

    private function refresh(JobOffer $offer, NormalizedJobOfferDTO $dto): JobOffer
    {
        $salary = PlausibleSalary::filter($dto->salaryMin, $dto->salaryMax);

        $offer->update([
            'url'                 => $dto->url,
            'published_at'        => $this->latestDate($offer->published_at, $dto->publishedAt),
            'description'         => $dto->description ?: $offer->description,
            'salary_min'          => $salary['min'] ?? $offer->salary_min,
            'salary_max'          => $salary['max'] ?? $offer->salary_max,
            'salary_is_predicted' => $dto->salaryIsPredicted ?? $offer->salary_is_predicted,
        ]);

        return $offer;
    }

    private function latestDate(?Carbon $stored, ?string $incoming): ?Carbon
    {
        if ($incoming === null) {
            return $stored;
        }

        $incoming = Carbon::parse($incoming);

        if ($stored === null) {
            return $incoming;
        }

        return $stored->gt($incoming) ? $stored : $incoming;
    }

    private function create(NormalizedJobOfferDTO $dto, ?Company $company): JobOffer
    {
        $salary = PlausibleSalary::filter($dto->salaryMin, $dto->salaryMax);

        return JobOffer::query()->create([
            'url'                 => $dto->url,
            'company_id'          => $company?->id,
            'title'               => $dto->title,
            'description'         => $dto->description,
            'source'              => $dto->source,
            'published_at'        => $dto->publishedAt,
            'salary_min'          => $salary['min'],
            'salary_max'          => $salary['max'],
            'salary_is_predicted' => $dto->salaryIsPredicted,
        ]);
    }
}
