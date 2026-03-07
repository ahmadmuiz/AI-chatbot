<?php

namespace App\Services;

use RuntimeException;

class AIServiceFactory
{
    /**
     * Get the appropriate AI service based on configuration.
     */
    public static function make(): ClaudeService|GeminiService
    {
        $provider = config('services.ai_provider', 'claude');

        return match ($provider) {
            'claude' => app(ClaudeService::class),
            'gemini' => app(GeminiService::class),
            default => throw new RuntimeException("Unknown AI provider: {$provider}"),
        };
    }

    /**
     * Get available providers.
     */
    public static function availableProviders(): array
    {
        return ['claude', 'gemini'];
    }

    /**
     * Check if a provider is available.
     */
    public static function isProviderAvailable(string $provider): bool
    {
        return in_array($provider, self::availableProviders());
    }
}
