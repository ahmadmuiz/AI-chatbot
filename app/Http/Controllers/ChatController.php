<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\ClaudeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(protected ClaudeService $claude) {}

    /**
     * Show the chat page (redirect to latest session or create new one).
     */
    public function index(): RedirectResponse
    {
        $session = auth()->user()
            ->chatSessions()
            ->latest()
            ->first();

        if (! $session) {
            $session = $this->createSession('New Chat');
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
     * Create a new chat session and redirect to it.
     */
    public function store(): RedirectResponse
    {
        $session = $this->createSession('New Chat');

        return redirect()->route('chat.show', $session);
    }

    /**
     * Send a message and return the AI response as JSON.
     */
    public function sendMessage(Request $request, ChatSession $chatSession): JsonResponse
    {
        abort_unless($chatSession->user_id === auth()->id(), 403);

        $request->validate(['message' => 'required|string|max:10000']);

        // Persist user message
        $chatSession->messages()->create([
            'role'    => 'user',
            'content' => $request->message,
        ]);

        // Auto-title the session from the first message
        if ($chatSession->title === 'New Chat' && $chatSession->messages()->count() === 1) {
            $title = mb_substr($request->message, 0, 60);
            $chatSession->update(['title' => $title]);
        }

        // Build message history for Claude
        $history = $chatSession->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        // Call Claude
        $assistantText = $this->claude->chat($history);

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

    private function createSession(string $title): ChatSession
    {
        return auth()->user()->chatSessions()->create(['title' => $title]);
    }
}
