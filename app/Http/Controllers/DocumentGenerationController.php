<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\MarkdownRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html as WordHtml;

class DocumentGenerationController extends Controller
{
    /** Supported export formats. */
    private const FORMATS = ['txt', 'docx', 'pdf', 'xlsx'];

    /**
     * Generate and stream a document from an AI message.
     *
     * @param  ChatMessage  $message
     * @param  string       $format  One of: txt, docx, pdf, xlsx
     */
    public function generate(ChatMessage $message, string $format): Response
    {
        // Ownership check: only the session owner can export
        abort_unless(
            in_array($format, self::FORMATS, true),
            400,
            'Unsupported format.'
        );

        $session = $message->chatSession;
        abort_unless($session && $session->user_id === auth()->id(), 403);
        abort_unless($message->role === 'assistant', 400, 'Only AI responses can be exported.');

        $content  = $message->content;
        $basename = 'AI-Response-' . $message->id;

        return match ($format) {
            'txt'  => $this->generateTxt($content, $basename),
            'docx' => $this->generateDocx($content, $basename),
            'pdf'  => $this->generatePdf($content, $basename),
            'xlsx' => $this->generateXlsx($content, $basename),
        };
    }

    // -------------------------------------------------------------------------

    /** Plain text — strip all markdown. */
    private function generateTxt(string $content, string $basename): Response
    {
        // Remove common markdown syntax for clean plain text
        $text = preg_replace('/#{1,6}\s+/', '', $content);           // headings
        $text = preg_replace('/\*{1,2}([^*]+)\*{1,2}/', '$1', $text); // bold/italic
        $text = preg_replace('/`{1,3}[^`]*`{1,3}/', '$1', $text);    // inline/block code
        $text = preg_replace('/!\[.*?\]\(.*?\)/', '', $text);         // images
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text); // links
        $text = preg_replace('/^\s*[-*+]\s+/m', '• ', $text);        // bullets
        $text = preg_replace('/^\s*\d+\.\s+/m', '', $text);           // numbered lists
        $text = trim($text);

        return response($text, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $basename . '.txt"',
        ]);
    }

    /** Word DOCX — convert markdown HTML into a Word document. */
    private function generateDocx(string $content, string $basename): Response
    {
        $phpWord  = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        // Style definitions
        $phpWord->addParagraphStyle('Normal', ['spaceAfter' => 160]);
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 18, 'color' => '2E4057']);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => 15, 'color' => '2E4057']);
        $phpWord->addTitleStyle(3, ['bold' => true, 'size' => 13, 'color' => '44576D']);

        $section = $phpWord->addSection([
            'marginTop'    => 1440,
            'marginBottom' => 1440,
            'marginLeft'   => 1440,
            'marginRight'  => 1440,
        ]);

        // Convert markdown → HTML → inject into PhpWord
        $html = MarkdownRenderer::render($content);

        // PhpWord's HTML parser needs a clean fragment
        $html = '<html><body>' . $html . '</body></html>';

        try {
            WordHtml::addHtml($section, $html, false, false);
        } catch (\Throwable) {
            // Fallback: add as plain text
            $section->addText(strip_tags($content));
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'docx_export_') . '.docx';
        $writer  = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        $contents = file_get_contents($tmpPath);
        @unlink($tmpPath);

        return response($contents, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $basename . '.docx"',
            'Content-Length'      => strlen($contents),
        ]);
    }

    /** PDF — render markdown as styled HTML then convert to PDF. */
    private function generatePdf(string $content, string $basename): Response
    {
        $html = MarkdownRenderer::render($content);

        $styledHtml = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; line-height: 1.7; color: #1a1a2e; padding: 20px; }
                h1 { font-size: 20pt; color: #2E4057; border-bottom: 2px solid #7c3aed; padding-bottom: 6px; margin-bottom: 14px; }
                h2 { font-size: 15pt; color: #2E4057; margin-top: 22px; }
                h3 { font-size: 12pt; color: #44576D; }
                p  { margin-bottom: 10px; }
                ul, ol { margin-left: 20px; margin-bottom: 10px; }
                li { margin-bottom: 4px; }
                code { background: #f4f0ff; border: 1px solid #d8b4fe; border-radius: 3px; padding: 1px 5px; font-size: 10pt; font-family: 'Courier New', monospace; }
                pre  { background: #1e1b2e; color: #e2e8f0; border-radius: 6px; padding: 12px; font-size: 9.5pt; margin: 12px 0; overflow-wrap: break-word; }
                pre code { background: none; border: none; color: inherit; padding: 0; }
                blockquote { border-left: 4px solid #7c3aed; padding-left: 14px; color: #555; font-style: italic; margin: 12px 0; }
                table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 10pt; }
                th { background: #f3f0ff; color: #2E4057; font-weight: bold; padding: 8px 10px; border: 1px solid #c4b5fd; text-align: left; }
                td { padding: 7px 10px; border: 1px solid #e2e8f0; }
                tr:nth-child(even) { background: #faf5ff; }
                strong { color: #5b21b6; }
                a { color: #7c3aed; }
                hr { border: none; border-top: 1px solid #e2e8f0; margin: 18px 0; }
            </style>
        </head>
        <body>{$html}</body>
        </html>
        HTML;

        $pdf = Pdf::loadHTML($styledHtml)->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $basename . '.pdf"',
        ]);
    }

    /** Excel XLSX — parse content into rows and write a spreadsheet. */
    private function generateXlsx(string $content, string $basename): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('AI Response');

        // Style the header row
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FF7C3AED']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F0FF']],
            'alignment' => ['wrapText' => true],
        ];

        // Convert column index (1-based) to Excel letter (A, B, C ...)
        $col = fn(int $n): string => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($n);

        // Try to detect and parse a markdown table from the content
        $tableData = $this->extractMarkdownTable($content);

        if (!empty($tableData)) {
            // Write table headers
            foreach ($tableData[0] as $c => $header) {
                $coord = $col($c + 1) . '1';
                $sheet->getCell($coord)->setValue($header);
                $sheet->getStyle($coord)->applyFromArray($headerStyle);
            }
            // Write data rows
            foreach (array_slice($tableData, 1) as $rowIdx => $row) {
                foreach ($row as $c => $value) {
                    $sheet->getCell($col($c + 1) . ($rowIdx + 2))->setValue($value);
                }
            }
        } else {
            // No table found — write content split into lines
            $sheet->getCell('A1')->setValue('Line');
            $sheet->getCell('B1')->setValue('Content');
            $sheet->getStyle('A1')->applyFromArray($headerStyle);
            $sheet->getStyle('B1')->applyFromArray($headerStyle);

            $lines = array_filter(explode("\n", strip_tags(MarkdownRenderer::render($content))));
            $row   = 2;
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $sheet->getCell('A' . $row)->setValue($row - 1);
                $sheet->getCell('B' . $row)->setValue($line);
                $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
                $row++;
            }
        }

        // Auto-size columns A and B
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        // Auto-size any additional columns from table data
        if (!empty($tableData) && !empty($tableData[0])) {
            foreach (range(1, count($tableData[0])) as $ci) {
                $sheet->getColumnDimension($col($ci))->setAutoSize(true);
            }
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_export_') . '.xlsx';
        $writer  = new XlsxWriter($spreadsheet);
        $writer->save($tmpPath);

        $contents = file_get_contents($tmpPath);
        @unlink($tmpPath);

        return response($contents, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $basename . '.xlsx"',
            'Content-Length'      => strlen($contents),
        ]);
    }

    /**
     * Extract the first markdown table from content and return as a 2D array.
     * Returns empty array if no table found.
     */
    private function extractMarkdownTable(string $content): array
    {
        if (!preg_match('/(\|.+\|[\r\n]+\|[-| :]+\|[\r\n]+(?:\|.+\|[\r\n]*)+)/m', $content, $m)) {
            return [];
        }

        $lines = array_filter(explode("\n", trim($m[1])));
        $rows  = [];

        foreach ($lines as $line) {
            $line = trim($line, " \t\r\n|");
            if (preg_match('/^[-| :]+$/', $line)) continue; // separator row

            $cells = array_map('trim', explode('|', $line));
            if (!empty(array_filter($cells))) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }
}
