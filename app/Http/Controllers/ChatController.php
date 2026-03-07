<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatMessageRequest;
use App\Models\ChatSession;
use App\Services\AIServiceFactory;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
            // No sessions exist, show provider selection
            return redirect()->route('chat.select-provider');
        }

        return redirect()->route('chat.show', $session);
    }

    /**
     * Display a specific chat session.
     */
    public function show(ChatSession $chatSession): View
    {
        // Authorise – users can only view their own sessions
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $sessions  = auth()->user()->chatSessions()->latest()->get();
        $messages  = $chatSession->messages;

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

        $session = $this->createSession('New Chat', $provider);

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

        // Build message history for Claude (with attachments)
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

        // Call AI service based on session's selected provider
        $aiService = $this->getAIServiceForSession($chatSession);
        $assistantText = $aiService->chat($history);

        // Persist assistant message
        $chatSession->messages()->create([
            'role'    => 'assistant',
            'content' => $assistantText,
        ]);

        return response()->json([
            'message' => $assistantText,
            'session_title' => $chatSession->fresh()->title,
        ]);
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
            default => app(\App\Services\ClaudeService::class),
        };
    }

    /**
     * Create a new chat session with specified AI provider.
     */
    private function createSession(string $title, string $aiProvider = 'claude'): ChatSession
    {
        return auth()->user()->chatSessions()->create([
            'title' => $title,
            'ai_provider' => $aiProvider,
        ]);
    }
}
