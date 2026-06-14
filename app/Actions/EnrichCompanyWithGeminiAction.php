<?php

namespace App\Actions;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnrichCompanyWithGeminiAction
{
    private const MODEL = 'gemini-2.5-flash';

    /**
     * @throws Throwable
     */
    public function handle(Company $company): bool
    {
        $apiKey = config('services.gemini.api_key');

        if (blank($apiKey)) {
            Log::warning('Gemini API key not configured.');
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
                                'description' => [
                                    'type'       => 'object',
                                    'nullable'   => true,
                                    'properties' => [
                                        'es' => ['type' => 'string', 'nullable' => true],
                                        'ca' => ['type' => 'string', 'nullable' => true],
                                        'eu' => ['type' => 'string', 'nullable' => true],
                                        'gl' => ['type' => 'string', 'nullable' => true],
                                        'en' => ['type' => 'string', 'nullable' => true],
                                    ],
                                ],
                                'sector' => [
                                    'type'     => 'string',
                                    'nullable' => true,
                                    'enum'     => array_keys(config('sectors')),
                                ],
                                'employees' => ['type' => 'string', 'nullable' => true],
                                'website'  => ['type' => 'string', 'nullable' => true],
                            ],
                        ],
                    ],
                ]
            );

            $response->throw();

            $raw  = $response->json('candidates.0.content.parts.0.text', '{}');
            $data = json_decode($raw, true) ?? [];

            $company->update([
                'description'     => $data['description'] ?? null,
                'sector'          => $data['sector'] ?? null,
                'employees'       => $data['employees'] ?? null,
                'website'         => $data['website'] ?? null,
                'gemini_enriched' => true,
            ]);

            return true;

        } catch (Throwable $e) {
            Log::warning('Gemini enrichment failed', [
                'company' => $company->name,
                'error'   => $e->getMessage(),
            ]);

            $company->update(['gemini_enriched' => true]);

            return false;
        }
    }
}
