<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIService;

class OpenRouterService implements AIService
{
    public function providerId(): int
    {
        return 3;
    }

    public function sendMessage(string $message): string
    {
        // TODO: Connect to OpenRouter API.

        return 'OpenRouter Response: ' . $message;
    }
}