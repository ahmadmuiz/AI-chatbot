<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatMessageRequest;
use App\Models\ChatAttachment;
use App\Models\ChatSession;
use App\Services\AIServiceFactory;
use App\Services\AuditService;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService,
    ) {}

    /**
     * Show the chat page (redirect to latest session or provider selection).
     */
    public function index(): RedirectResponse
    {
        $session = auth()->user()
            ->chatSessions()
            ->latest()
            ->first();

        if (! $session) {
            return redirect()->route('chat.select-provider');
        }

        return redirect()->route('chat.show', $session);
    }

    /**
     * Display a specific chat session.
     */
    public function show(ChatSession $chatSession): View
    {
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $sessions = auth()->user()->chatSessions()->latest()->get();
        $messages = $chatSession->messages()->with('attachments')->get();

        return view('chat.index', compact('chatSession', 'sessions', 'messages'));
    }

    /**
     * Show AI provider selection screen.
     */
    public function selectProvider(): View
    {
        return view('chat.select-provider');
    }

    /**
     * Create a new chat session with selected AI provider and redirect to it.
     */
    public function store(): RedirectResponse
    {
        $provider = request()->input('ai_provider', 'claude');

        if (!in_array($provider, ['claude', 'gemini'])) {
            $provider = 'claude';
        }

        // Load default memory from memory.md
        $defaultMemory = $this->loadDefaultMemory();

        $session = auth()->user()->chatSessions()->create([
            'title'         => 'New Chat',
            'ai_provider'   => $provider,
            'system_prompt' => $defaultMemory,
        ]);

        return redirect()->route('chat.show', $session);
    }

    /**
     * Send a message and return the AI response as JSON.
     */
    public function sendMessage(ChatMessageRequest $request, ChatSession $chatSession): JsonResponse
    {
        abort_unless($chatSession->user_id === auth()->id(), 403);

        // Persist user message
        $userMessage = $chatSession->messages()->create([
            'role'    => 'user',
            'content' => $request->message,
        ]);

        // Handle file attachments if present
        $attachments = null;
        if ($request->hasFile('attachments')) {
            $attachments = $this->fileUploadService->storeFiles(
                $request->file('attachments'),
                $userMessage
            );
        }

        // Auto-title the session from the first message
        if ($chatSession->title === 'New Chat' && $chatSession->messages()->count() === 1) {
            $title = mb_substr($request->message, 0, 60);
            $chatSession->update(['title' => $title]);
        }

        // Build message history for AI (with attachments)
        $history = $chatSession->messages()
            ->with('attachments')
            ->orderBy('created_at')
            ->get()
            ->map(function ($m) {
                $msg = ['role' => $m->role, 'content' => $m->content];
                if ($m->attachments->isNotEmpty()) {
                    $msg['attachments'] = $m->attachments;
                }
                return $msg;
            })
            ->toArray();

        // Call AI service, passing session's system prompt
        $aiService     = $this->getAIServiceForSession($chatSession);
        $assistantText = $aiService->chat($history, $chatSession->system_prompt);

        // Persist assistant message
        $chatSession->messages()->create([
            'role'    => 'assistant',
            'content' => $assistantText,
        ]);

        // Audit log
        AuditService::log(
            'chat.message',
            'User sent a chat message',
            auth()->user(),
            [
                'session_id'    => $chatSession->id,
                'provider'      => $chatSession->ai_provider,
                'message_chars' => strlen($request->message),
            ],
        );

        // Return attached file metadata so JS can render download chips
        $attachmentData = $attachments
            ? $attachments->map(fn ($a) => [
                'id'                => $a->id,
                'original_filename' => $a->original_filename,
                'mime_type'         => $a->mime_type,
                'file_size'         => $a->file_size,
                'download_url'      => route('chat.attachment.download', $a->id),
            ])->values()->toArray()
            : [];

        return response()->json([
            'message'       => $assistantText,
            'session_title' => $chatSession->fresh()->title,
            'attachments'   => $attachmentData,
        ]);
    }

    /**
     * Download a chat attachment (private storage, ownership-checked).
     */
    public function downloadAttachment(ChatAttachment $chatAttachment): Response
    {
        // Verify ownership via the message → session chain
        $session = $chatAttachment->message->chatSession;
        abort_unless($session->user_id === auth()->id(), 403);

        abort_unless(Storage::disk('local')->exists($chatAttachment->storage_path), 404);

        $contents = Storage::disk('local')->get($chatAttachment->storage_path);

        return response($contents, 200, [
            'Content-Type'        => $chatAttachment->mime_type,
            'Content-Disposition' => 'attachment; filename="' . $chatAttachment->original_filename . '"',
            'Content-Length'      => strlen($contents),
        ]);
    }

    /**
     * Save (update) the system prompt / memory for a session.
     */
    public function updateMemory(ChatSession $chatSession): JsonResponse
    {
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $prompt = request()->input('system_prompt', '');
        $chatSession->update(['system_prompt' => $prompt ?: null]);

        return response()->json(['status' => 'ok', 'system_prompt' => $chatSession->system_prompt]);
    }

    /**
     * Update the AI provider for an existing chat session.
     */
    public function updateProvider(ChatSession $chatSession): JsonResponse
    {
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $provider = request()->input('ai_provider');

        if (!in_array($provider, ['claude', 'gemini'])) {
            return response()->json(['error' => 'Invalid provider.'], 422);
        }

        $chatSession->update(['ai_provider' => $provider]);

        return response()->json(['ai_provider' => $provider]);
    }

    // -------------------------------------------------------------------------

    /**
     * Get AI service instance based on session's provider selection.
     */
    private function getAIServiceForSession(ChatSession $chatSession)
    {
        $provider = $chatSession->ai_provider ?? 'claude';

        return match ($provider) {
            'claude' => app(\App\Services\ClaudeService::class),
            'gemini' => app(\App\Services\GeminiService::class),
            default  => app(\App\Services\ClaudeService::class),
        };
    }

    /**
     * Load the default system prompt from memory.md (if it exists).
     */
    private function loadDefaultMemory(): ?string
    {
        $path = base_path('memory.md');
        return file_exists($path) ? trim(file_get_contents($path)) : null;
    }
}
