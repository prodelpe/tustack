<?php

namespace App\Actions;

use App\Support\Gemini;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The only paid step of the merge. Gemini is never asked what to do, only
 * whether two names are the same employer; the answer is stored, so a pair is
 * paid for once and never again.
 */
class AskGeminiAboutCompanyNamesAction
{
    private const MODEL = 'gemini-2.5-flash';

    /**
     * @param  array<int, array{0: string, 1: string}>  $pairs
     * @return array<int, bool>|null  Verdict per pair, or null when Gemini could not answer.
     */
    public function judgePairs(array $pairs): ?array
    {
        $prompt = view('prompts.judge-company-pairs', ['pairs' => array_values($pairs)])->render();

        $schema = [
            'type'  => 'array',
            'items' => [
                'type'       => 'object',
                'properties' => [
                    'index' => ['type' => 'integer'],
                    'same'  => ['type' => 'boolean'],
                ],
                'required' => ['index', 'same'],
            ],
        ];

        $answers = $this->ask($prompt, $schema);

        if ($answers === null) {
            return null;
        }

        $verdicts = [];

        foreach ($answers as $answer) {
            $index = $answer['index'] ?? null;

            if (is_int($index) && array_key_exists($index, $pairs)) {
                $verdicts[$index] = (bool) ($answer['same'] ?? false);
            }
        }

        return $verdicts;
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, array{0: int, 1: int}>|null  Index pairs Gemini believes are one employer.
     */
    public function findPairsIn(array $names): ?array
    {
        $prompt = view('prompts.sweep-company-names', ['names' => array_values($names)])->render();

        $schema = [
            'type'  => 'array',
            'items' => [
                'type'       => 'object',
                'properties' => [
                    'a' => ['type' => 'integer'],
                    'b' => ['type' => 'integer'],
                ],
                'required' => ['a', 'b'],
            ],
        ];

        $answers = $this->ask($prompt, $schema);

        if ($answers === null) {
            return null;
        }

        $pairs = [];

        foreach ($answers as $answer) {
            $first  = $answer['a'] ?? null;
            $second = $answer['b'] ?? null;

            if (is_int($first) && is_int($second) && $first !== $second
                && array_key_exists($first, $names) && array_key_exists($second, $names)) {
                $pairs[] = [$first, $second];
            }
        }

        return $pairs;
    }

    private function ask(string $prompt, array $schema): ?array
    {
        if (! Gemini::isEnabled()) {
            Log::warning(Gemini::whyItIsOff());

            return null;
        }

        $apiKey = config('services.gemini.api_key');

        try {
            $response = Http::timeout(60)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL . ":generateContent?key={$apiKey}",
                [
                    'contents'         => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema'   => $schema,
                    ],
                ]
            );

            $response->throw();

            $raw = $response->json('candidates.0.content.parts.0.text', '[]');

            return json_decode($raw, true) ?: [];

        } catch (Throwable $e) {
            // Nothing is recorded on failure: an unanswered pair stays unknown
            // and is asked about again on the next run.
            Log::warning('Gemini could not judge company names', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
