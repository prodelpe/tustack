<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TranslateTextAction
{
    public function handle(string $text, string $targetLocale, string $sourceLocale = 'en'): ?string
    {
        $apiKey = config('services.google_translate.api_key');

        if (blank($apiKey)) {
            Log::warning('Google Translate API key not configured.');
            return null;
        }

        try {
            $response = Http::timeout(10)->post(
                "https://translation.googleapis.com/language/translate/v2?key={$apiKey}",
                [
                    'q'      => $text,
                    'source' => $sourceLocale,
                    'target' => $targetLocale,
                    'format' => 'text',
                ]
            );

            $response->throw();

            return $response->json('data.translations.0.translatedText');
        } catch (Throwable $e) {
            Log::warning('Google Translate failed', [
                'target' => $targetLocale,
                'error'  => $e->getMessage(),
            ]);

            return null;
        }
    }
}
