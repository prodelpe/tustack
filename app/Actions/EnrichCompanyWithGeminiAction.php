<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\User;
use App\Notifications\GeminiUnavailable;
use App\Support\Gemini;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class EnrichCompanyWithGeminiAction
{
    private const MODEL = 'gemini-2.5-flash';

    /**
     * @throws Throwable
     */
    public function handle(Company $company, TranslateTextAction $translator = new TranslateTextAction): bool
    {
        if (! Gemini::isEnabled()) {
            Log::warning(Gemini::whyItIsOff(), ['company' => $company->name]);

            return false;
        }

        $apiKey = config('services.gemini.api_key');

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
            $response = Http::timeout(20)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL . ":generateContent?key={$apiKey}",
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
                                'employees' => ['type' => 'string', 'nullable' => true],
                                'website'   => ['type' => 'string', 'nullable' => true],
                                'latitude'  => ['type' => 'number', 'nullable' => true],
                                'longitude' => ['type' => 'number', 'nullable' => true],
                            ],
                        ],
                    ],
                ]
            );

            $response->throw();

            $raw  = $response->json('candidates.0.content.parts.0.text', '{}');
            $data = json_decode($raw, true) ?? [];

            $descriptionEn = $data['description'] ?? null;
            $description   = null;

            if ($descriptionEn) {
                $description = [
                    'en' => $descriptionEn,
                    'es' => $translator->handle($descriptionEn, 'es'),
                    'ca' => $translator->handle($descriptionEn, 'ca'),
                ];
            }

            $company->update([
                'description'     => $description,
                'sector'          => $data['sector'] ?? null,
                'employees'       => $data['employees'] ?? null,
                'website'         => $data['website'] ?? null,
                'latitude'        => $data['latitude'] ?? null,
                'longitude'       => $data['longitude'] ?? null,
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

        Notification::send(
            User::query()->where('is_admin', true)->get(),
            new GeminiUnavailable($e->getMessage())
        );
    }
}
