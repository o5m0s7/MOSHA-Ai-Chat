<?php

namespace App\Services\AI\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\AI\Contracts\AIService;

class GeminiService implements AIService
{
    public function providerId(): int
    {
        return 2;
    }

    public function sendMessage(string $message): string
    {
        $apiKey = config('services.gemini.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new Exception('Gemini API key is missing.');
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
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => $message,
                                ],
                            ],
                        ],
                    ],
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
