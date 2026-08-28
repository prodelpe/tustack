<?php

namespace App\Actions;

use App\DTOs\NormalizedJobOfferDTO;
use App\Models\Company;
use App\Models\JobOffer;
use App\Support\JobTitle;
use App\Support\PlausibleSalary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class UpsertJobOfferAction
{
    public function handle(NormalizedJobOfferDTO $dto, ?Company $company): JobOffer
    {
        $existing = $this->findByUrl($dto->url)
            ?? $this->findRepublished($dto, $company)
            ?? $this->findOnAnotherSource($dto, $company);

        if ($existing) {
            return $this->merge($existing, $dto);
        }

        return $this->create($dto, $company);
    }

    private function findByUrl(string $url): ?JobOffer
    {
        return JobOffer::query()->where('url', $url)->first();
    }

    private function findRepublished(NormalizedJobOfferDTO $dto, ?Company $company): ?JobOffer
    {
        return $this->sameVacancy($dto, $company, config('jobs.duplicate_window_days'))
            ?->where('source', $dto->source)
            ->first();
    }

    private function findOnAnotherSource(NormalizedJobOfferDTO $dto, ?Company $company): ?JobOffer
    {
        return $this->sameVacancy($dto, $company, config('jobs.cross_source_window_days'))
            ?->where('source', '!=', $dto->source)
            ->first();
    }

    private function sameVacancy(NormalizedJobOfferDTO $dto, ?Company $company, int $days): ?Builder
    {
        $title = JobTitle::normalize($dto->title);

        if ($company === null || blank($title)) {
            return null;
        }

        return JobOffer::query()
            ->where('company_id', $company->id)
            ->where('title_normalized', $title)
            ->where('published_at', '>=', now()->subDays($days));
    }

    private function merge(JobOffer $offer, NormalizedJobOfferDTO $dto): JobOffer
    {
        $salary   = PlausibleSalary::filter($dto->salaryMin, $dto->salaryMax);
        $incoming = $this->prefers($dto->source, $offer->source);

        $offer->update([
            'title'               => $incoming ? $dto->title : $offer->title,
            'url'                 => $incoming ? $dto->url : $offer->url,
            'source'              => $incoming ? $dto->source : $offer->source,
            'description'         => $this->longest($dto->description, $offer->description),
            'published_at'        => $this->latestDate($offer->published_at, $dto->publishedAt),
            'salary_min'          => $salary['min'] ?? $offer->salary_min,
            'salary_max'          => $salary['max'] ?? $offer->salary_max,
            'salary_is_predicted' => $dto->salaryIsPredicted ?? $offer->salary_is_predicted,
        ]);

        return $offer;
    }

    private function prefers(string $incoming, ?string $stored): bool
    {
        return $this->priority($incoming) <= $this->priority($stored);
    }

    private function priority(?string $source): int
    {
        $order = config('jobs.title_source_priority');
        $index = array_search($source, $order, true);

        return $index === false ? count($order) : $index;
    }

    private function longest(?string $incoming, ?string $stored): ?string
    {
        if (blank($incoming)) {
            return $stored;
        }

        if (blank($stored)) {
            return $incoming;
        }

        return mb_strlen($incoming) > mb_strlen($stored) ? $incoming : $stored;
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
