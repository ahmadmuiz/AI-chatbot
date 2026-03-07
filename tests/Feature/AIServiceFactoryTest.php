<?php

namespace Tests\Feature;

use App\Services\AIServiceFactory;
use App\Services\ClaudeService;
use App\Services\GeminiService;
use Tests\TestCase;

class AIServiceFactoryTest extends TestCase
{
    public function test_factory_returns_claude_service_when_provider_is_claude(): void
    {
        $this->app['config']->set('services.ai_provider', 'claude');

        $service = AIServiceFactory::make();

        $this->assertInstanceOf(ClaudeService::class, $service);
    }

    public function test_factory_returns_gemini_service_when_provider_is_gemini(): void
    {
        $this->app['config']->set('services.ai_provider', 'gemini');
        $this->app['config']->set('services.gemini.api_key', 'test-key');

        $service = AIServiceFactory::make();

        $this->assertInstanceOf(GeminiService::class, $service);
    }

    public function test_factory_throws_exception_for_unknown_provider(): void
    {
        $this->app['config']->set('services.ai_provider', 'unknown');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown AI provider: unknown');

        AIServiceFactory::make();
    }

    public function test_available_providers_returns_list(): void
    {
        $providers = AIServiceFactory::availableProviders();

        $this->assertContains('claude', $providers);
        $this->assertContains('gemini', $providers);
    }

    public function test_is_provider_available_returns_true_for_valid_provider(): void
    {
        $this->assertTrue(AIServiceFactory::isProviderAvailable('claude'));
        $this->assertTrue(AIServiceFactory::isProviderAvailable('gemini'));
    }

    public function test_is_provider_available_returns_false_for_invalid_provider(): void
    {
        $this->assertFalse(AIServiceFactory::isProviderAvailable('unknown'));
    }
}
