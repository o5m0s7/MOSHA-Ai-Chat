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
        $response = Http::withToken(config('services.groq.api_key'))
            ->acceptJson()
            ->contentType('application/json')
            ->timeout(30)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.3-70b-versatile',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $message,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new Exception($response->body());
        }

        $content = $response->json('choices.0.message.content');

        if (!$content) {
            throw new Exception('Invalid response received from Groq.');
        }

        return trim($content);
    }
}