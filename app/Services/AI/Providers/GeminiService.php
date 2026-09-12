<?php

namespace App\Services\AI\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\AI\Contracts\AIService;
use App\Services\AI\AIPrompt;

class GeminiService implements AIService
{
    public function providerId(): int
    {
        return 2;
    }

    public function sendMessage(array $messages): string
    {
        $apiKey = config('services.gemini.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new Exception('Gemini API key is missing.');
        }

        /*
         * Gemini keeps the system instruction separate
         * from the conversation contents.
         */
        $contents = [];

        foreach ($messages as $message) {

            // Ignore MOSHA's internal system message here.
            if (($message['role'] ?? '') === 'system') {
                continue;
            }

            $contents[] = [
                'role' => ($message['role'] ?? 'user') === 'assistant'
                    ? 'model'
                    : 'user',

                'parts' => [
                    [
                        'text' => $message['content'] ?? '',
                    ],
                ],
            ];
        }

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->acceptJson()
            ->timeout(30)
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent',
                [
                    'systemInstruction' => [
                        'parts' => [
                            [
                                'text' => AIPrompt::system(),
                            ],
                        ],
                    ],

                    'contents' => $contents,
                ]
            );

        if (! $response->successful()) {
            throw new Exception(
                'Gemini API error [' .
                $response->status() .
                ']: ' .
                $response->body()
            );
        }

        $content = $response->json(
            'candidates.0.content.parts.0.text'
        );

        if (! is_string($content) || trim($content) === '') {
            throw new Exception(
                'Invalid or empty response received from Gemini.'
            );
        }

        return trim($content);
    }
}
