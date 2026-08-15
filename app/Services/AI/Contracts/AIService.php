<?php

namespace App\Services\AI\Contracts;

interface AIService
{
    public function providerId(): int;

    public function sendMessage(string $message): string;
}