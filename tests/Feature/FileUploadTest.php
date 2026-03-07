<?php

namespace Tests\Feature;

use App\Models\ChatAttachment;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ChatSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->user = User::factory()->create();
        $this->session = $this->user->chatSessions()->create(['title' => 'Test Chat']);
    }

    public function test_can_upload_single_file_with_message(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->user)->postJson(
            route('chat.message', $this->session),
            [
                'message' => 'Check this image',
                'attachments' => [$file],
            ]
        );

        $response->assertOk();
        $response->assertJsonStructure(['message', 'session_title']);

        // Verify message was created
        $message = $this->session->messages()->where('role', 'user')->first();
        $this->assertNotNull($message);
        $this->assertEquals('Check this image', $message->content);

        // Verify attachment was created
        $this->assertCount(1, $message->attachments);
        $attachment = $message->attachments->first();
        $this->assertEquals('test.jpg', $attachment->original_filename);
        $this->assertEquals('image/jpeg', $attachment->mime_type);
    }

    public function test_can_upload_multiple_files(): void
    {
        $files = [
            UploadedFile::fake()->image('image1.jpg'),
            UploadedFile::fake()->image('image2.png'),
            UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($this->user)->postJson(
            route('chat.message', $this->session),
            [
                'message' => 'Multiple files',
                'attachments' => $files,
            ]
        );

        $response->assertOk();

        $message = $this->session->messages()->where('role', 'user')->first();
        $this->assertCount(3, $message->attachments);
    }

    public function test_rejects_more_than_five_files(): void
    {
        $files = array_map(
            fn ($i) => UploadedFile::fake()->image("image$i.jpg"),
            range(1, 6)
        );

        $response = $this->actingAs($this->user)->postJson(
            route('chat.message', $this->session),
            [
                'message' => 'Too many files',
                'attachments' => $files,
            ]
        );

        $response->assertUnprocessable();
    }

    public function test_rejects_unsupported_file_type(): void
    {
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($this->user)->postJson(
            route('chat.message', $this->session),
            [
                'message' => 'Bad file',
                'attachments' => [$file],
            ]
        );

        $response->assertUnprocessable();
    }

    public function test_rejects_oversized_file(): void
    {
        // Create a mock file that appears to be 60MB
        $file = UploadedFile::fake()->create('huge.pdf', 60000, 'application/pdf');

        $response = $this->actingAs($this->user)->postJson(
            route('chat.message', $this->session),
            [
                'message' => 'Too big',
                'attachments' => [$file],
            ]
        );

        $response->assertUnprocessable();
    }

    public function test_attachment_is_persisted_in_storage(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $this->actingAs($this->user)->postJson(
            route('chat.message', $this->session),
            [
                'message' => 'Check this image',
                'attachments' => [$file],
            ]
        );

        $attachment = ChatAttachment::first();
        $this->assertNotNull($attachment);

        // Verify file exists in storage
        Storage::disk('local')->assertExists($attachment->storage_path);
    }

    public function test_can_send_message_without_files(): void
    {
        $response = $this->actingAs($this->user)->postJson(
            route('chat.message', $this->session),
            ['message' => 'Just text, no files']
        );

        $response->assertOk();

        $message = $this->session->messages()->where('role', 'user')->first();
        $this->assertEquals('Just text, no files', $message->content);
        $this->assertCount(0, $message->attachments);
    }

    public function test_attachment_includes_correct_metadata(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 500);

        $this->actingAs($this->user)->postJson(
            route('chat.message', $this->session),
            [
                'message' => 'Photo',
                'attachments' => [$file],
            ]
        );

        $attachment = ChatAttachment::first();
        $this->assertEquals('photo.jpg', $attachment->original_filename);
        $this->assertEquals('image/jpeg', $attachment->mime_type);
        $this->assertGreaterThan(0, $attachment->file_size);
        $this->assertStringContainsString('private/uploads/', $attachment->storage_path);
        $this->assertNull($attachment->claude_file_id);
    }

    public function test_message_loads_with_attachments(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $this->actingAs($this->user)->postJson(
            route('chat.message', $this->session),
            [
                'message' => 'Image attached',
                'attachments' => [$file],
            ]
        );

        $message = ChatMessage::with('attachments')->first();
        $this->assertCount(1, $message->attachments);
        $this->assertEquals('test.jpg', $message->attachments[0]->original_filename);
    }
}
