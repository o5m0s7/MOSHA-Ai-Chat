<?php

namespace App\Services\AI;

use Throwable;
use Illuminate\Support\Facades\Log;
use App\Services\AI\Contracts\AIService;
use App\Services\AI\Providers\GroqService;
use App\Services\AI\Providers\GeminiService;
use App\Services\AI\Providers\OpenRouterService;

class AIManager
{
    private array $services;

    public function __construct(
        GroqService $groq,
        GeminiService $gemini,
        OpenRouterService $openRouter
    ) {
        $this->services = [
            $groq,
            $gemini,
            $openRouter,
        ];
    }

    public function send(array $messages): array
    {
        $responses = [];

        foreach ($this->services as $service) {
            $responses[] = $this->sendToService($service, $messages);
        }

        return $responses;
    }

    public function sendToProvider(int $providerId, array $messages): array
    {
        foreach ($this->services as $service) {
            if ($service->providerId() === $providerId) {
                return $this->sendToService($service, $messages);
            }
        }

        return [
            'provider_id' => $providerId,
            'success' => false,
            'content' => null,
            'error' => 'Provider not found.',
        ];
    }

    private function sendToService(
        AIService $service,
        array $messages
    ): array {
        $providerId = $service->providerId();

        $providerMessages = array_values(array_filter(
            $messages,
            function (array $message) use ($providerId) {
                if ($message['role'] === 'user') {
                    return trim((string) ($message['content'] ?? '')) !== '';
                }
        
                return $message['role'] === 'assistant'
                    && (int) ($message['provider_id'] ?? 0) === $providerId
                    && trim((string) ($message['content'] ?? '')) !== '';
            }
        ));

        $providerMessages = array_merge(
            [
                [
                    'role' => 'system',
                    'content' => AIPrompt::system(),
                    'provider_id' => null,
                ],
            ],
            $providerMessages
        );

        try {
            return [
                'provider_id' => $providerId,
                'success' => true,
                'content' => $service->sendMessage($providerMessages),
                'error' => null,
            ];
        } catch (Throwable $e) {
            Log::error(
                "AI Provider [{$providerId}] failed.",
                [
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]
            );

            return [
                'provider_id' => $providerId,
                'success' => false,
                'content' => null,
                'error' => 'This provider is currently unavailable.',
            ];
        }
    }
}
