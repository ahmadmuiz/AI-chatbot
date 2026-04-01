<?php

namespace App\Services;

use App\Models\ChatAttachment;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;


class GeminiService
{
    private HttpClient $http;
    private string $model;
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    private const MAX_RETRIES = 3;
    private const RETRYABLE_CODES = [429, 500, 503];

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-3-flash-preview');

        if (!$this->apiKey) {
            throw new \RuntimeException('Gemini API key is not configured. Set GEMINI_API_KEY in .env');
        }

        $this->http = new HttpClient([
            'base_uri' => $this->baseUrl,
            'timeout'  => 25,
            'headers'  => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ]);
    }

    /**
     * Send a conversation to Gemini API and return text response.
     *
     * @param array $messages Array of ['role' => 'user'|'assistant', 'content' => '...', 'attachments' => Collection|array]
     * @param string|null $systemPrompt Optional per-session system prompt / memory
     * @return string
     */
    public function chat(array $messages, ?string $systemPrompt = null): string
    {
        $geminiMessages = $this->processMessagesWithAttachments($messages);

        $hasAttachments = collect($messages)->contains(fn ($m) => !empty($m['attachments']));

        $endpoint = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        $defaultSystem = 'You are Gemini, a helpful, friendly, and knowledgeable AI assistant made by Google. Provide clear, concise, and accurate responses.';

        $payload = [
            'contents'         => $geminiMessages,
            'generationConfig' => [
                'maxOutputTokens' => 4096,
                'temperature'     => 0.7,
                'topP'            => 0.95,
            ],
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt ?: $defaultSystem],
                ],
            ],
        ];

        // google_search grounding is incompatible with multimodal (inlineData) requests
        if (!$hasAttachments) {
            $payload['tools'] = [
                ['google_search' => (object) []],
            ];
        }

        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = $this->http->post($endpoint, ['json' => $payload]);
                $body = json_decode((string) $response->getBody(), true);

                return $this->extractText($body);

            } catch (RequestException $e) {
                $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
                $lastException = $e;

                if (\in_array($statusCode, self::RETRYABLE_CODES) && $attempt < self::MAX_RETRIES) {
                    $delay = pow(2, $attempt); // 2s, 4s
                    Log::warning("Gemini API {$statusCode} on attempt {$attempt}, retrying in {$delay}s", [
                        'model' => $this->model,
                    ]);
                    sleep($delay);
                    continue;
                }

                break;

            } catch (GuzzleException $e) {
                $lastException = $e;
                break;
            }
        }

        $statusCode = ($lastException instanceof RequestException && $lastException->getResponse())
            ? $lastException->getResponse()->getStatusCode()
            : 0;

        Log::error('Gemini API error', [
            'status'  => $statusCode,
            'message' => $lastException ? $lastException->getMessage() : 'Unknown error',
            'model'   => $this->model,
        ]);

        throw new \RuntimeException('Failed to reach Gemini service after ' . self::MAX_RETRIES . ' attempts. Please try again later.');
    }

    /**
     * Convert message history into Gemini's multimodal contents format.
     * Each user message builds a parts array: text + any file attachments.
     */
    private function processMessagesWithAttachments(array $messages): array
    {
        return collect($messages)->map(function ($msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';

            $parts = [];

            // Always include text content first
            if (!empty($msg['content'])) {
                $parts[] = ['text' => $msg['content']];
            }

            // Append file parts for user messages only (model messages never have attachments)
            if ($role === 'user' && !empty($msg['attachments'])) {
                $attachments = collect($msg['attachments']);
                foreach ($attachments as $attachment) {
                    $attachment = $attachment instanceof ChatAttachment
                        ? $attachment
                        : (object) $attachment;

                    $parts[] = $this->buildFilePart($attachment);
                }
            }

            if (empty($parts)) {
                $parts = [['text' => '']];
            }

            return ['role' => $role, 'parts' => $parts];
        })->toArray();
    }

    /**
     * Build a Gemini API part for a single file attachment.
     *
     * - Images and PDFs → inlineData (Gemini reads them natively)
     * - Everything else → extracted text content
     */
    private function buildFilePart(object $attachment): array
    {
        $mimeType    = $attachment->mime_type;
        $fileContent = Storage::disk('local')->get($attachment->storage_path);

        if ($fileContent === null) {
            return ['text' => "[File not found: {$attachment->original_filename}]"];
        }

        // Images: Gemini supports vision natively via inlineData
        if (str_starts_with($mimeType, 'image/')) {
            return [
                'inlineData' => [
                    'mimeType' => $mimeType,
                    'data'     => base64_encode($fileContent),
                ],
            ];
        }

        // PDF: Gemini reads PDFs natively via inlineData
        if ($mimeType === 'application/pdf') {
            return [
                'inlineData' => [
                    'mimeType' => 'application/pdf',
                    'data'     => base64_encode($fileContent),
                ],
            ];
        }

        // Excel: extract with PhpSpreadsheet
        if (
            str_contains($mimeType, 'spreadsheet') ||
            \in_array($mimeType, [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
        ) {
            return ['text' => $this->extractExcelContent($attachment, $fileContent)];
        }

        // Word DOCX: extract from ZIP XML
        if ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            return ['text' => $this->extractDocxContent($attachment, $fileContent)];
        }

        // Legacy .doc — cannot extract without external tools
        if ($mimeType === 'application/msword') {
            return [
                'text' => "[Word Document: {$attachment->original_filename} — Legacy .doc format cannot be parsed. Please convert to .docx or .txt and re-upload.]",
            ];
        }

        // CSV: parse into readable table
        if (str_contains($mimeType, 'csv') || str_ends_with($attachment->original_filename, '.csv')) {
            return ['text' => $this->extractCsvContent($attachment, $fileContent)];
        }

        // JSON: pretty print
        if (str_contains($mimeType, 'json') || str_ends_with($attachment->original_filename, '.json')) {
            $decoded = json_decode($fileContent, true);
            $pretty  = $decoded !== null
                ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : $fileContent;

            return ['text' => "JSON File: {$attachment->original_filename}\n\n" . substr($pretty, 0, 8000)];
        }

        // Plain text and other text/* types
        if (str_starts_with($mimeType, 'text/')) {
            return [
                'text' => "File: {$attachment->original_filename}\n\n" . substr($fileContent, 0, 8000),
            ];
        }

        // Unknown binary
        return [
            'text' => "[Unsupported file: {$attachment->original_filename} ({$mimeType}) — Cannot read this file type.]",
        ];
    }

    /**
     * Extract text content from an Excel file (.xlsx / .xls).
     */
    private function extractExcelContent(object $attachment, string $fileContent): string
    {
        try {
            $tmpPath = tempnam(sys_get_temp_dir(), 'excel_') . '_' . $attachment->stored_filename;
            file_put_contents($tmpPath, $fileContent);
            $spreadsheet = SpreadsheetIOFactory::load($tmpPath);
            @unlink($tmpPath);

            $text = "Excel File: {$attachment->original_filename}\n";
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $text .= "\n--- Sheet: {$sheet->getTitle()} ---\n";
                $rowCount = 0;
                foreach ($sheet->toArray(null, true, true, true) as $row) {
                    $text .= implode(' | ', array_map(fn ($v) => (string) ($v ?? ''), $row)) . "\n";
                    if (++$rowCount >= 200) {
                        $text .= "[... truncated after 200 rows ...]\n";
                        break;
                    }
                }
            }

            return substr($text, 0, 15000);
        } catch (\Throwable $e) {
            Log::warning('Gemini: Excel extraction failed', ['file' => $attachment->original_filename, 'error' => $e->getMessage()]);
            return "[Excel File: {$attachment->original_filename} — Could not extract content: {$e->getMessage()}]";
        }
    }

    /**
     * Extract text content from a DOCX file using ZipArchive + XML parsing.
     */
    private function extractDocxContent(object $attachment, string $fileContent): string
    {
        try {
            $tmpPath = tempnam(sys_get_temp_dir(), 'docx_') . '.docx';
            file_put_contents($tmpPath, $fileContent);

            $zip = new \ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                throw new \RuntimeException('Could not open DOCX as ZIP archive.');
            }

            $xmlContent = $zip->getFromName('word/document.xml');
            $zip->close();
            @unlink($tmpPath);

            if ($xmlContent === false) {
                throw new \RuntimeException('word/document.xml not found inside DOCX.');
            }

            $text = strip_tags(str_replace(['</w:p>', '</w:tr>'], ["\n", "\n"], $xmlContent));
            $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $text = preg_replace('/[ \t]+/', ' ', $text);
            $text = preg_replace('/\n{3,}/', "\n\n", trim($text));

            return "Word Document: {$attachment->original_filename}\n\n" . substr($text ?: '[No readable text found.]', 0, 15000);
        } catch (\Throwable $e) {
            Log::warning('Gemini: DOCX extraction failed', ['file' => $attachment->original_filename, 'error' => $e->getMessage()]);
            return "[Word Document: {$attachment->original_filename} — Could not extract content: {$e->getMessage()}]";
        }
    }

    /**
     * Parse a CSV file into a readable table string.
     */
    private function extractCsvContent(object $attachment, string $fileContent): string
    {
        $rows = array_filter(str_getcsv($fileContent, "\n"));
        $text = "CSV File: {$attachment->original_filename}\n\n";
        $count = 0;
        foreach ($rows as $row) {
            $text .= implode(' | ', str_getcsv($row)) . "\n";
            if (++$count >= 100) {
                $text .= "\n... and " . \count($rows) - 100 . " more rows";
                break;
            }
        }
        return $text;
    }

    /**
     * Extract text from Gemini response, handling both plain and grounded responses.
     * Grounded responses (google_search tool) may return multiple parts.
     */
    private function extractText(array $body): string
    {
        $parts = $body['candidates'][0]['content']['parts'] ?? [];

        $text = '';
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
        }

        return $text !== '' ? $text : 'Sorry, I could not generate a response.';
    }
}
