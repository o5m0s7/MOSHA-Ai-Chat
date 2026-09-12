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

    public function sendMessage(array $messages): string
    {
        $apiKey = config('services.groq.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new Exception('Groq API key is missing.');
        }

        /*
         * Remove MOSHA's internal provider_id field
         * before sending the messages to Groq.
         */
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
            ->connectTimeout(10)
            ->timeout(30)
            ->withOptions([
                'force_ip_resolve' => 'v4',
            ])
            ->post(
                'https://api.groq.com/openai/v1/chat/completions',
                [
                    'model' => 'groq/compound-mini',
                    'messages' => $apiMessages,
                ]
            );

        if (! $response->successful()) {
            throw new Exception(
                'Groq API error [' .
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
                'Invalid or empty response received from Groq.'
            );
        }

        return trim($content);
    }
}
