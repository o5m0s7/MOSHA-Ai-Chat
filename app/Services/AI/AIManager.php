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
    /**
     * @var AIService[]
     */
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

    public function send(string $message): array
    {
        $responses = [];

        foreach ($this->services as $service) {

            $providerId = $service->providerId();

            try {

                $responses[] = [
                    'provider_id' => $providerId,
                    'content'     => $service->sendMessage($message),
                ];

            } catch (Throwable $e) {

                Log::error(
                    "AI Provider [{$providerId}] failed.",
                    [
                        'exception' => $e,
                    ]
                );

                $responses[] = [
                    'provider_id' => $providerId,
                    'content'     => 'This provider is currently unavailable.',
                ];
            }
        }

        return $responses;
    }
}