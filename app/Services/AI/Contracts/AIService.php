<?php

namespace App\Services\AI\Contracts;

interface AIService
{
    public function sendMessage(array $messages): string;

    public function providerId(): int;
}