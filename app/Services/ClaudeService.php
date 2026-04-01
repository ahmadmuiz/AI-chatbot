<?php

namespace App\Services;

use App\Models\ChatAttachment;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

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
     * @param  array  $messages     Array of ['role' => 'user'|'assistant', 'content' => '...', 'attachments' => Collection]
     * @param  string|null  $systemPrompt  Optional per-session system prompt / memory
     * @return string
     */
    public function chat(array $messages, ?string $systemPrompt = null): string
    {
        // Process messages to include file content
        $processedMessages = $this->processMessagesWithAttachments($messages);

        // Bedrock Messages API path: /model/{modelId}/invoke
        $path = '/model/' . rawurlencode($this->model) . '/invoke';

        $defaultSystem = 'You are a helpful, friendly, and knowledgeable AI assistant called Claude. Provide clear, concise, and accurate responses.';

        $payload = [
            'anthropic_version' => 'bedrock-2023-05-31',
            'max_tokens'        => 4096,
            'system'            => $systemPrompt ?: $defaultSystem,
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

        // PDF: extract text with smalot/pdfparser
        if ($mimeType === 'application/pdf') {
            return $this->extractPdfContent($attachment, $fileContent);
        }

        // Excel: extract with PhpSpreadsheet
        if (
            str_contains($mimeType, 'spreadsheet') ||
            in_array($mimeType, [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
        ) {
            return $this->extractExcelContent($attachment, $fileContent);
        }

        // Word DOCX: extract from ZIP XML
        if ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            return $this->extractDocxContent($attachment, $fileContent);
        }

        // Legacy .doc binary — cannot extract without external tools
        if ($mimeType === 'application/msword') {
            return [
                'type' => 'text',
                'text' => sprintf(
                    "[Word Document: %s (%s) — Legacy .doc format cannot be parsed. Please convert to .docx or .txt and re-upload.]",
                    $attachment->original_filename,
                    $this->formatFileSize($attachment->file_size)
                ),
            ];
        }

        // Unknown binary fallback
        return [
            'type' => 'text',
            'text' => sprintf(
                "[Unsupported File: %s (%s, %s) — This file type cannot be read. Please convert to a supported format (text, PDF, DOCX, XLSX, image).]",
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
     * Extract text from a PDF file using smalot/pdfparser.
     */
    private function extractPdfContent(ChatAttachment $attachment, string $fileContent): array
    {
        try {
            $parser = new PdfParser();
            $pdf    = $parser->parseContent($fileContent);
            $text   = $pdf->getText();

            if (empty(trim($text))) {
                $text = '[PDF appears to contain only images or scanned content — no extractable text found.]';
            } else {
                // Trim to avoid token explosion on very large PDFs
                $text = substr($text, 0, 15000);
                if (strlen($pdf->getText()) > 15000) {
                    $text .= "\n\n[... content truncated to first 15,000 characters ...]";
                }
            }

            return [
                'type' => 'text',
                'text' => "PDF File: {$attachment->original_filename}\n\n{$text}",
            ];
        } catch (\Throwable $e) {
            Log::warning('PDF extraction failed', ['file' => $attachment->original_filename, 'error' => $e->getMessage()]);
            return [
                'type' => 'text',
                'text' => "[PDF File: {$attachment->original_filename} — Could not extract text: {$e->getMessage()}]",
            ];
        }
    }

    /**
     * Extract text from an Excel file (.xlsx / .xls) using PhpSpreadsheet.
     */
    private function extractExcelContent(ChatAttachment $attachment, string $fileContent): array
    {
        try {
            // Write to a temp file because PhpSpreadsheet needs a file path
            $tmpPath = tempnam(sys_get_temp_dir(), 'excel_') . '_' . $attachment->stored_filename;
            file_put_contents($tmpPath, $fileContent);

            $spreadsheet = SpreadsheetIOFactory::load($tmpPath);
            @unlink($tmpPath);

            $text = "Excel File: {$attachment->original_filename}\n";
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $text .= "\n--- Sheet: {$sheet->getTitle()} ---\n";
                $rows = $sheet->toArray(null, true, true, true);
                $rowCount = 0;
                foreach ($rows as $row) {
                    $text .= implode(" | ", array_map(fn ($v) => (string) ($v ?? ''), $row)) . "\n";
                    if (++$rowCount >= 200) {
                        $text .= "[... truncated after 200 rows ...]\n";
                        break;
                    }
                }
            }

            return ['type' => 'text', 'text' => substr($text, 0, 15000)];
        } catch (\Throwable $e) {
            Log::warning('Excel extraction failed', ['file' => $attachment->original_filename, 'error' => $e->getMessage()]);
            return [
                'type' => 'text',
                'text' => "[Excel File: {$attachment->original_filename} — Could not extract content: {$e->getMessage()}]",
            ];
        }
    }

    /**
     * Extract text from a DOCX file using ZipArchive + XML parsing (no binary deps).
     */
    private function extractDocxContent(ChatAttachment $attachment, string $fileContent): array
    {
        try {
            // DOCX is a ZIP; write to temp file to use ZipArchive
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

            // Strip XML tags, decode entities, collapse whitespace
            $text = strip_tags(str_replace(
                ['</w:p>', '</w:tr>'],
                ["\n", "\n"],
                $xmlContent
            ));
            $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $text = preg_replace('/[ \t]+/', ' ', $text);
            $text = preg_replace('/\n{3,}/', "\n\n", trim($text));

            if (empty($text)) {
                $text = '[DOCX appears to contain no readable text.]';
            } else {
                $text = substr($text, 0, 15000);
            }

            return [
                'type' => 'text',
                'text' => "Word Document: {$attachment->original_filename}\n\n{$text}",
            ];
        } catch (\Throwable $e) {
            Log::warning('DOCX extraction failed', ['file' => $attachment->original_filename, 'error' => $e->getMessage()]);
            return [
                'type' => 'text',
                'text' => "[Word Document: {$attachment->original_filename} — Could not extract content: {$e->getMessage()}]",
            ];
        }
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
