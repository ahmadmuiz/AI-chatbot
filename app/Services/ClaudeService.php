<?php

namespace App\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    private HttpClient $http;
    private string $model;
    private string $region;
    private string $baseUrl;

    public function __construct()
    {
        $this->region  = config('services.aws.region', 'ap-southeast-3');
        $this->model   = config('services.aws.bedrock_model', 'global.anthropic.claude-haiku-4-5-20251001-v1:0');
        $this->baseUrl = "https://bedrock-runtime.{$this->region}.amazonaws.com";

        // Bearer token read directly from env at request time — never stored in memory longer than needed
        $this->http = new HttpClient([
            'base_uri' => $this->baseUrl,
            'timeout'  => 60,
            'headers'  => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                // AWS Bedrock bearer token auth (IAM Identity Center / temporary token)
                'Authorization' => 'Bearer ' . config('services.aws.bearer_token'),
            ],
        ]);
    }

    /**
     * Send a conversation to Claude via AWS Bedrock (bearer token) and return text.
     *
     * @param  array  $messages  Array of ['role' => 'user'|'assistant', 'content' => '...']
     * @return string
     */
    public function chat(array $messages): string
    {
        // Bedrock Messages API path: /model/{modelId}/invoke
        $path = '/model/' . rawurlencode($this->model) . '/invoke';

        $payload = [
            'anthropic_version' => 'bedrock-2023-05-31',
            'max_tokens'        => 4096,
            'system'            => 'You are a helpful, friendly, and knowledgeable AI assistant called Claude. Provide clear, concise, and accurate responses.',
            'messages'          => $messages,
        ];

        try {
            $response = $this->http->post($path, ['json' => $payload]);
            $body     = json_decode((string) $response->getBody(), true);

            return $body['content'][0]['text'] ?? 'Sorry, I could not generate a response.';

        } catch (GuzzleException $e) {
            // Log error but NEVER log the bearer token or request body content
            Log::error('Bedrock API error', [
                'status'  => method_exists($e, 'getCode') ? $e->getCode() : 0,
                'message' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to reach AI service. Please try again later.');
        }
    }
}
