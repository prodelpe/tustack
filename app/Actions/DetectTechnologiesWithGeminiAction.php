<?php

namespace App\Actions;

use App\DTOs\NormalizedJobOfferDTO;
use App\Models\Technology;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DetectTechnologiesWithGeminiAction
{
    private const MODEL = 'gemini-2.5-flash';

    public function handle(NormalizedJobOfferDTO $dto, Collection $technologies): Collection
    {
        $apiKey = config('services.gemini.api_key');

        if (blank($apiKey)) {
            Log::warning('Gemini API key not configured — skipping AI tech detection.');
            return collect();
        }

        $knownNames = $technologies->pluck('name')->join(', ');
        $text       = trim(($dto->title ?? '') . "\n" . ($dto->description ?? ''));

        $prompt = view('prompts.detect-technologies', compact('knownNames', 'text'))->render();

        try {
            $response = Http::timeout(15)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/" . self::MODEL . ":generateContent?key={$apiKey}",
                [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => ['responseMimeType' => 'application/json'],
                ]
            );

            $response->throw();

            $raw      = $response->json('candidates.0.content.parts.0.text', '[]');
            $detected = json_decode($raw, true) ?? [];

            return $technologies->filter(function (Technology $tech) use ($detected) {
                return in_array($tech->name, $detected, strict: true);
            });
        } catch (\Throwable $e) {
            Log::warning('Gemini detection failed', ['error' => $e->getMessage()]);

            return collect();
        }
    }
}
