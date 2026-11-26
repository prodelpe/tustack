<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\User;
use App\Notifications\GeminiUnavailable;
use App\Support\Gemini;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class EnrichCompanyWithGeminiAction
{
    private const EMPLOYEE_RANGES = ['1-10', '11-50', '51-200', '201-500', '501-1000', '1001-5000', '5000+'];

    /** The longest real description is under 500 characters: anything far beyond is the model looping. */
    private const DESCRIPTION_MAX_LENGTH = 1000;

    private const WEBSITE_MAX_LENGTH = 255;

    /**
     * @throws Throwable
     */
    public function handle(Company $company, TranslateTextAction $translator = new TranslateTextAction): bool
    {
        if (! Gemini::isEnabled()) {
            Log::warning(Gemini::whyItIsOff(), ['company' => $company->name]);

            return false;
        }

        $techStack = $company->jobOffers
            ->flatMap
            ->technologies
            ->unique('id')
            ->pluck('name')
            ->join(', ');

        $location = collect([
            $company->city,
            $company->province?->name
        ])
            ->filter()
            ->unique()
            ->join(', ');

        $locationStr  = $location ?: 'Spain';
        $techStackStr = $techStack ?: 'unknown';
        $companyName  = $company->name;
        $sectorKeys   = implode(', ', array_keys(config('sectors')));

        $prompt = view('prompts.enrich-company', compact('companyName', 'locationStr', 'techStackStr', 'sectorKeys'))->render();

        try {
            $response = Gemini::generateContent(
                [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema'   => [
                            'type'       => 'object',
                            'properties' => [
                                'description' => ['type' => 'string', 'nullable' => true],
                                'sector' => [
                                    'type'     => 'string',
                                    'nullable' => true,
                                    'enum'     => array_keys(config('sectors')),
                                ],
                                'employees' => [
                                    'type'     => 'string',
                                    'nullable' => true,
                                    'enum'     => self::EMPLOYEE_RANGES,
                                ],
                                'website'   => ['type' => 'string', 'nullable' => true],
                                'latitude'  => ['type' => 'number', 'nullable' => true],
                                'longitude' => ['type' => 'number', 'nullable' => true],
                            ],
                        ],
                    ],
                ],
                20
            );

            $response->throw();

            $raw  = $response->json('candidates.0.content.parts.0.text', '{}');
            $data = json_decode($raw, true) ?? [];

            $descriptionEn = $this->description($data['description'] ?? null);
            $description   = null;

            if ($descriptionEn) {
                $description = [
                    'en' => $descriptionEn,
                    'es' => $translator->handle($descriptionEn, 'es'),
                    'ca' => $translator->handle($descriptionEn, 'ca'),
                ];
            }

            // Gemini is asked for a main office, which for a large employer is
            // rarely where its offers are, and it answers null when unsure. Both
            // used to overwrite coordinates the job boards had already given, so
            // they now only fill a company that has none.
            $keepCoordinates = $company->latitude !== null && $company->longitude !== null;

            $company->update([
                'description'     => $description,
                'sector'          => $this->sector($data['sector'] ?? null),
                'employees'       => $this->employees($data['employees'] ?? null),
                'website'         => $this->website($data['website'] ?? null),
                'latitude'        => $keepCoordinates ? $company->latitude : $this->coordinate($data['latitude'] ?? null, 90),
                'longitude'       => $keepCoordinates ? $company->longitude : $this->coordinate($data['longitude'] ?? null, 180),
                'gemini_enriched' => true,
            ]);

            return true;

        } catch (Throwable $e) {
            Log::warning('Gemini enrichment failed', [
                'company' => $company->name,
                'error'   => $e->getMessage(),
            ]);

            // Quota, billing or network problems say nothing about this company,
            // so it stays pending and gets retried on the next run.
            if ($this->isTemporaryFailure($e)) {
                $this->alertAdmins($e);

                return false;
            }

            $company->update(['gemini_enriched' => true]);

            return false;
        }
    }

    /*
     * The schema asks for clean values and Gemini mostly obliges, but not
     * always: a website of 400 characters did not fit its column, the save
     * threw, and the company stayed pending to be paid for again on every run.
     * What does not look right is dropped and the rest is kept.
     */
    private function description(mixed $value): ?string
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        return mb_strlen($value) <= self::DESCRIPTION_MAX_LENGTH ? $value : null;
    }

    private function sector(mixed $value): ?string
    {
        return is_string($value) && array_key_exists($value, config('sectors')) ? $value : null;
    }

    private function employees(mixed $value): ?string
    {
        return in_array($value, self::EMPLOYEE_RANGES, true) ? $value : null;
    }

    private function website(mixed $value): ?string
    {
        if (! is_string($value) || mb_strlen($value) > self::WEBSITE_MAX_LENGTH) {
            return null;
        }

        return preg_match('/^\S+\.\S+$/', $value) ? $value : null;
    }

    private function coordinate(mixed $value, int $limit): ?float
    {
        return is_numeric($value) && abs((float) $value) <= $limit ? (float) $value : null;
    }

    private function isTemporaryFailure(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        if ($e instanceof RequestException) {
            $status = $e->response->status();

            return $status === 429 || $status === 402 || $status >= 500;
        }

        return false;
    }

    private function alertAdmins(Throwable $e): void
    {
        // One alert per outage instead of one per company in the batch.
        if (! Cache::add('gemini-unavailable-alert', true, now()->addHours(6))) {
            return;
        }

        // An alert that cannot be delivered is logged, never thrown: the
        // company has to stay pending whatever the mailer is doing.
        try {
            Notification::send(
                User::query()->where('is_admin', true)->get(),
                new GeminiUnavailable($e->getMessage())
            );
        } catch (Throwable $notDelivered) {
            Log::error('The Gemini alert could not be delivered', ['error' => $notDelivered->getMessage()]);
        }
    }
}
