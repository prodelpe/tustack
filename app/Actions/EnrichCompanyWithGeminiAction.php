<?php

namespace App\Actions;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrichCompanyWithGeminiAction
{
    private const MODEL = 'gemini-2.5-flash';

    public function handle(Company $company): bool
    {
        $apiKey = config('services.gemini.api_key');

        if (blank($apiKey)) {
            Log::warning('Gemini API key not configured.');
            return false;
        }

        $techStack = $company->jobOffers
            ->flatMap->technologies
            ->unique('id')
            ->pluck('name')
            ->join(', ');

        $location = collect([$company->city, $company->province?->name])
            ->filter()
            ->unique()
            ->join(', ');

        $locationStr = $location ?: 'Spain';
        $techStackStr = $techStack ?: 'unknown';
        $companyName = $company->name;

        $prompt = <<<PROMPT
You are a business intelligence assistant. A user is researching tech companies.

Company name: {$companyName}
Location: {$locationStr}
Known tech stack (from job offers): {$techStackStr}

Return a JSON object with exactly these fields:
- "description": an object with keys "es", "ca", "eu", "gl", "en". Each value is 2-3 factual sentences about what this company does, written in that language. Return null for the whole field if you are not confident about the company.
- "sector": one short label like "Fintech", "E-commerce", "SaaS", "Healthcare", "Consulting", "Gaming", etc. Return null if unsure.
- "employees": LinkedIn-style headcount range. One of: "1-10", "11-50", "51-200", "201-500", "501-1000", "1001-5000", "5000+". Return null if unsure.
- "website": the company's official website URL. Return null if unsure.

Important: if you are not confident about a field, return null. Do not invent or guess information.
PROMPT;

        try {
            $response = Http::timeout(20)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL . ":generateContent?key={$apiKey}",
                [
                    'contents'         => [
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
                                'sector'   => ['type' => 'string', 'nullable' => true],
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

        } catch (\Throwable $e) {
            Log::warning('Gemini enrichment failed', [
                'company' => $company->name,
                'error'   => $e->getMessage(),
            ]);

            $company->update(['gemini_enriched' => true]);

            return false;
        }
    }
}
