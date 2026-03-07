<?php

namespace App\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private HttpClient $http;
    private string $model;
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');

        if (!$this->apiKey) {
            throw new \RuntimeException('Gemini API key is not configured. Set GEMINI_API_KEY in .env');
        }

        $this->http = new HttpClient([
            'base_uri' => $this->baseUrl,
            'timeout'  => 60,
            'headers'  => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ]);
    }

    /**
     * Send a conversation to Gemini API and return text response.
     *
     * @param array $messages Array of ['role' => 'user'|'model', 'content' => '...']
     * @return string
     */
    public function chat(array $messages): string
    {
        // Convert role names: 'assistant' -> 'model' for Gemini
        $geminiMessages = array_map(function ($msg) {
            return [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }, $messages);

        $endpoint = "{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents'      => $geminiMessages,
            'generationConfig' => [
                'maxOutputTokens'   => 4096,
                'temperature'       => 0.7,
                'topP'              => 0.95,
            ],
            'systemInstruction' => [
                'parts' => [
                    ['text' => 'You are a helpful, friendly, and knowledgeable AI assistant called Claude. Provide clear, concise, and accurate responses.'],
                ],
            ],
        ];

        try {
            $response = $this->http->post($endpoint, ['json' => $payload]);
            $body = json_decode((string) $response->getBody(), true);

            // Extract text from Gemini response structure
            if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
                return $body['candidates'][0]['content']['parts'][0]['text'];
            }

            return 'Sorry, I could not generate a response.';

        } catch (GuzzleException $e) {
            Log::error('Gemini API error', [
                'status'  => method_exists($e, 'getCode') ? $e->getCode() : 0,
                'message' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to reach Gemini service. Please try again later.');
        }
    }
}
