<?php

namespace App\Services\AI\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\AI\Contracts\AIService;

class GroqService implements AIService
{
    public function providerId(): int
    {
        return 1;
    }

    public function sendMessage(string $message): string
    {
        $apiKey = config('services.groq.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new Exception('Groq API key is missing.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->contentType('application/json')
            ->timeout(30)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'groq/compound-mini',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $message,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new Exception(
                'Groq API error [' . $response->status() . ']: ' . $response->body()
            );
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new Exception('Invalid or empty response received from Groq.');
        }

        return trim($content);
    }
}