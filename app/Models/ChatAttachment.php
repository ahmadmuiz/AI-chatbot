<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ChatAttachment extends Model
{
    protected $fillable = [
        'chat_message_id',
        'original_filename',
        'stored_filename',
        'mime_type',
        'file_size',
        'storage_path',
        'claude_file_id',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    /**
     * Get the full storage path for this attachment.
     */
    public function getFullPathAttribute(): string
    {
        return Storage::disk('local')->path($this->storage_path);
    }

    /**
     * Get the file contents as base64 (for Claude API).
     */
    public function getBase64Content(): string
    {
        return base64_encode(Storage::disk('local')->get($this->storage_path));
    }

    /**
     * Get the download URL for this attachment (if needed).
     */
    public function getDownloadUrlAttribute(): string
    {
        // If you want to serve files, implement a download route
        return route('attachment.download', $this);
    }
}
