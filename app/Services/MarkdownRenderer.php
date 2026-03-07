<?php

namespace App\Services;

use Illuminate\Support\Str;

class MarkdownRenderer
{
    /**
     * Render markdown content with proper HTML formatting.
     * Uses Laravel's built-in markdown parser.
     */
    public static function render(string $content): string
    {
        // Use Laravel's Str::markdown() if available
        if (method_exists(Str::class, 'markdown')) {
            return Str::markdown($content, [
                'html' => true,
                'breaks' => true,
            ]);
        }

        // Fallback: simple markdown parsing
        return self::parseMarkdown($content);
    }

    /**
     * Simple markdown parser for common patterns.
     */
    private static function parseMarkdown(string $text): string
    {
        // Headings
        $text = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $text);
        $text = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $text);

        // Bold
        $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/__( .*?)__/s', '<strong>$1</strong>', $text);

        // Italic
        $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
        $text = preg_replace('/_( .*?)_/s', '<em>$1</em>', $text);

        // Code blocks
        $text = preg_replace('/```(.*?)```/s', '<pre><code>$1</code></pre>', $text);

        // Inline code
        $text = preg_replace('/`(.*?)`/s', '<code>$1</code>', $text);

        // Links
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $text);

        // Line breaks
        $text = nl2br($text);

        return $text;
    }

    /**
     * Extract plain text from markdown (strip formatting).
     */
    public static function toPlainText(string $markdown): string
    {
        // Remove markdown formatting
        $text = preg_replace('/[#*_`\[\]()]/m', '', $markdown);
        return trim($text);
    }

    /**
     * Escape markdown content to prevent parsing.
     */
    public static function escape(string $text): string
    {
        $special = ['\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '#', '+', '-', '.', '!'];
        foreach ($special as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }
        return $text;
    }
}
