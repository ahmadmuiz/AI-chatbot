<?php

namespace App\Services;

use App\Models\ChatAttachment;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        // Bearer token is read per-request (not stored in constructor) to support token refresh
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
     * Send a conversation to Claude via AWS Bedrock (bearer token) and return text.
     *
     * @param  array  $messages  Array of ['role' => 'user'|'assistant', 'content' => '...', 'attachments' => Collection]
     * @return string
     */
    public function chat(array $messages): string
    {
        // Process messages to include file content
        $processedMessages = $this->processMessagesWithAttachments($messages);

        // Bedrock Messages API path: /model/{modelId}/invoke
        $path = '/model/' . rawurlencode($this->model) . '/invoke';

        $payload = [
            'anthropic_version' => 'bedrock-2023-05-31',
            'max_tokens'        => 4096,
            'system'            => 'You are a helpful, friendly, and knowledgeable AI assistant called Claude. Provide clear, concise, and accurate responses.',
            'messages'          => $processedMessages,
        ];

        try {
            $response = $this->http->post($path, [
                'json'    => $payload,
                // Bearer token read fresh per-request to support IAM Identity Center token refresh
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.aws.bearer_token'),
                ],
            ]);
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

    /**
     * Process messages to include file content in the appropriate format.
     */
    private function processMessagesWithAttachments(array $messages): array
    {
        return collect($messages)->map(function ($msg) {
            // If no attachments, return message as-is
            if (empty($msg['attachments'])) {
                return [
                    'role'    => $msg['role'],
                    'content' => $msg['content'],
                ];
            }

            // Process attachments
            $attachments = $msg['attachments'] instanceof Collection
                ? $msg['attachments']
                : collect($msg['attachments'] ?? []);

            if ($attachments->isEmpty()) {
                return [
                    'role'    => $msg['role'],
                    'content' => $msg['content'],
                ];
            }

            // Build content array with text and file references
            $content = [
                [
                    'type' => 'text',
                    'text' => $msg['content'],
                ],
            ];

            // Add file content blocks
            foreach ($attachments as $attachment) {
                $attachment = $attachment instanceof ChatAttachment ? $attachment : (object) $attachment;

                $content[] = $this->buildFileContentBlock($attachment);
            }

            return [
                'role'    => $msg['role'],
                'content' => $content,
            ];
        })->toArray();
    }

    /**
     * Build a content block for a file attachment based on MIME type.
     */
    private function buildFileContentBlock(ChatAttachment $attachment): array
    {
        $mimeType = $attachment->mime_type;
        $storagePath = $attachment->storage_path;
        $fileContent = Storage::disk('local')->get($storagePath);

        // Images: Use vision capability
        if (str_starts_with($mimeType, 'image/')) {
            return [
                'type'      => 'image',
                'source'    => [
                    'type'       => 'base64',
                    'media_type' => $mimeType,
                    'data'       => base64_encode($fileContent),
                ],
            ];
        }

        // Text-based files: Send actual content
        if ($this->isTextFile($mimeType)) {
            return [
                'type' => 'text',
                'text' => sprintf(
                    "File: %s (%s)\n\n%s",
                    $attachment->original_filename,
                    $mimeType,
                    $fileContent
                ),
            ];
        }

        // CSV files: Parse and display as structured text
        if (str_contains($mimeType, 'csv')) {
            $rows = array_filter(str_getcsv($fileContent, "\n"));
            $text = "CSV File: {$attachment->original_filename}\n\n";
            foreach (array_slice($rows, 0, 100) as $row) {
                $text .= implode(" | ", str_getcsv($row)) . "\n";
            }
            if (count($rows) > 100) {
                $text .= "\n... and " . (count($rows) - 100) . " more rows";
            }

            return [
                'type' => 'text',
                'text' => $text,
            ];
        }

        // JSON files: Pretty print
        if (str_contains($mimeType, 'json')) {
            $decoded = json_decode($fileContent, true);
            $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            return [
                'type' => 'text',
                'text' => sprintf(
                    "JSON File: %s\n\n%s",
                    $attachment->original_filename,
                    substr($pretty, 0, 5000)
                ),
            ];
        }

        // Binary files (PDF, DOCX, etc): Encode as base64 with metadata
        // Note: Claude's vision can analyze PDF images, but for full PDF content
        // you would need to use Claude's Files API (requires different approach)
        return [
            'type' => 'text',
            'text' => sprintf(
                "[Binary File: %s (%s, %s) - Unable to fully extract content. For detailed analysis, please convert to text/PDF format or use Claude's Files API]",
                $attachment->original_filename,
                $mimeType,
                $this->formatFileSize($attachment->file_size)
            ),
        ];
    }

    /**
     * Check if file is text-based and can be directly sent to Claude.
     */
    private function isTextFile(string $mimeType): bool
    {
        $textTypes = [
            'text/',
            'application/json',
            'application/xml',
            'application/javascript',
            'application/x-yaml',
        ];

        foreach ($textTypes as $type) {
            if (str_starts_with($mimeType, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format file size for display.
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        }
    }
}
