<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIService;

class GeminiService implements AIService
{
    public function providerId(): int
    {
        return 2;
    }

    public function sendMessage(string $message): string
    {
        // TODO: Connect to Gemini API.

        return 'Gemini Response: ' . $message;
    }
}