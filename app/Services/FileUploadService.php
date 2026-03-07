<?php

namespace App\Services;

use App\Models\ChatAttachment;
use App\Models\ChatMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadService
{
    private const UPLOAD_DIR = 'private/uploads';

    /**
     * Store uploaded files and create attachment records.
     *
     * @param array<UploadedFile> $files
     * @return Collection<ChatAttachment>
     */
    public function storeFiles(array $files, ChatMessage $message): Collection
    {
        $attachments = collect();

        foreach ($files as $file) {
            // Generate unique filename to avoid collisions
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $storedName = Str::uuid() . '.' . $extension;

            // Store file in private/uploads directory
            $storagePath = self::UPLOAD_DIR . '/' . $storedName;
            Storage::disk('local')->put($storagePath, file_get_contents($file));

            // Create attachment record
            $attachment = ChatAttachment::create([
                'chat_message_id'   => $message->id,
                'original_filename' => $originalName,
                'stored_filename'   => $storedName,
                'mime_type'         => $file->getMimeType(),
                'file_size'         => $file->getSize(),
                'storage_path'      => $storagePath,
                'claude_file_id'    => null, // Will be set when uploaded to Claude
            ]);

            $attachments->push($attachment);
        }

        return $attachments;
    }

    /**
     * Delete an attachment file and record.
     */
    public function deleteAttachment(ChatAttachment $attachment): void
    {
        // Delete from storage
        if (Storage::disk('local')->exists($attachment->storage_path)) {
            Storage::disk('local')->delete($attachment->storage_path);
        }

        // Delete database record
        $attachment->delete();
    }

    /**
     * Get MIME type category for Claude API processing.
     */
    public static function getMimeTypeCategory(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_contains($mimeType, ['application/pdf', 'application/msword', 'text/plain'])) {
            return 'document';
        } elseif (str_contains($mimeType, ['csv', 'spreadsheet', 'json'])) {
            return 'data';
        }

        return 'unknown';
    }
}
