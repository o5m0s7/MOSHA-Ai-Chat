<?php

namespace App\Services\AI\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Services\AI\Contracts\AIService;

class OpenRouterService implements AIService
{
    public function providerId(): int
    {
        return 3;
    }

    public function sendMessage(array $messages): string
    {
        $apiKey = config('services.openrouter.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new Exception('OpenRouter API key is missing.');
        }

        // Send only the fields OpenRouter expects.
        $apiMessages = array_map(
            function (array $message) {
                return [
                    'role' => $message['role'],
                    'content' => $message['content'],
                ];
            },
            $messages
        );

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->contentType('application/json')
            ->timeout(30)
            ->post(
                'https://openrouter.ai/api/v1/chat/completions',
                [
                    'model' => 'openrouter/free',
                    'messages' => $apiMessages,
                ]
            );

        if (! $response->successful()) {
            throw new Exception(
                'OpenRouter API error [' .
                $response->status() .
                ']: ' .
                $response->body()
            );
        }

        $content = $response->json(
            'choices.0.message.content'
        );

        if (! is_string($content) || trim($content) === '') {
            throw new Exception(
                'Invalid or empty response received from OpenRouter.'
            );
        }

        return trim($content);
    }
}
