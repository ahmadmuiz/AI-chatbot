# AI Chatbot

A full-featured AI chat application built with Laravel, supporting multiple AI providers per session with a modern dark-themed UI.

## Features

### AI Providers
- **Claude (AWS Bedrock)** — Anthropic's Claude model accessed via AWS Bedrock bearer token authentication. Supports vision (image analysis) and large context windows.
- **Google Gemini** — Google's Gemini model via the Generative Language REST API. Configurable model (default: `gemini-2.0-flash`).
- **Per-session provider selection** — choose Claude or Gemini when creating each new chat session.
- **Live model switching** — switch the AI provider for the current session at any time using the pill toggle below the chat input, without reloading the page.

### Chat Interface
- Multi-line expandable chat input (grows up to 240px, Enter to send, Shift+Enter for newline).
- WYSIWYG markdown rendering for AI responses — headings, bold, italic, tables, code blocks with syntax highlighting (highlight.js).
- Typing indicator while the AI is generating a response.
- Auto-titles sessions from the first message.
- Session history listed in the sidebar with per-session AI provider badges.
- New chat modal for selecting the AI provider before starting a session.

### File Uploads
- Attach up to **5 files per message**, up to **50 MB each**.
- Supported formats: `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx`, `txt`, `csv`, `xlsx`, `json`, `pptx`, `odt`.
- Drag-and-drop support.
- Images sent to Claude as base64 vision blocks; text/JSON/CSV files sent as inline content.
- Thumbnails shown inline for image attachments; document icon + filename for other files.
- Files stored persistently at `storage/app/private/uploads/`.

### Themes
Four built-in themes, switchable via the palette swatches at the top of the sidebar. Selection is saved in `localStorage` and persists across sessions.

| Theme | Description |
|-------|-------------|
| **Dark** | Deep purple-black (default) |
| **Light** | Soft lavender/white — full light mode |
| **Midnight** | Pure black with bright purple accents |
| **Ocean** | Deep navy with sky-blue accents |

### Authentication
- User registration, login, email verification, and password reset via Laravel Breeze.
- All chat sessions are scoped to the authenticated user.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend framework** | Laravel 12 (PHP 8.2+) |
| **Authentication** | Laravel Breeze |
| **Database** | SQLite (default) — swappable to MySQL/PostgreSQL |
| **AI — Claude** | AWS Bedrock REST API (bearer token auth) |
| **AI — Gemini** | Google Generative Language REST API v1beta |
| **HTTP client** | Guzzle HTTP |
| **Frontend** | Blade templates, vanilla JavaScript, Vite |
| **CSS** | Custom CSS with CSS variables (no framework) |
| **Markdown rendering** | `League\CommonMark` via `Str::markdown()` + marked.js (client-side) |
| **Syntax highlighting** | highlight.js 11 |
| **File storage** | Laravel local disk (`storage/app/private/`) |
| **Queue** | Laravel database queue |
| **Testing** | PHPUnit 11 |
| **Dev tooling** | Laravel Pint (linting), Laravel Pail (log tailing), Laravel Sail (Docker) |

---

## Requirements

- PHP 8.2+
- Composer
- Node.js & npm
- An AWS Bedrock bearer token **or** a Google Gemini API key (or both)

---

## Installation

```bash
# 1. Clone the repo
git clone https://github.com/ahmadmuiz/AI-chatbot.git
cd AI-chatbot

# 2. Run the full setup (installs deps, generates app key, runs migrations, builds assets)
composer setup
```

---

## Configuration

Copy `.env.example` to `.env` and fill in the relevant credentials:

```env
# ── AI Provider ─────────────────────────────────────────────────────────
# Default provider when creating a new session: 'claude' or 'gemini'
AI_PROVIDER=claude

# ── Claude via AWS Bedrock ───────────────────────────────────────────────
AWS_BEARER_TOKEN_BEDROCK=        # IAM Identity Center temporary bearer token
AWS_BEDROCK_MODEL_ID=global.anthropic.claude-haiku-4-5-20251001-v1:0
AWS_REGION=ap-southeast-3

# ── Google Gemini ────────────────────────────────────────────────────────
GEMINI_API_KEY=                  # From https://aistudio.google.com/app/apikey
GEMINI_MODEL=gemini-2.0-flash    # Or gemini-2.5-pro, gemini-2.0-flash-lite, etc.
```

---

## Running Locally

```bash
# Start all dev services (server, queue, log tail, Vite)
composer dev
```

The app will be available at `http://localhost:8000`.

---

## Running Tests

```bash
composer test

# Run a single test
php artisan test --filter=TestClassName
```

---

## Project Structure

```
app/
  Http/
    Controllers/
      ChatController.php       # Chat sessions, messages, provider switching
    Requests/
      ChatMessageRequest.php   # Message + file upload validation
  Models/
    ChatSession.php            # Belongs to User, has many ChatMessage
    ChatMessage.php            # Has many ChatAttachment
    ChatAttachment.php         # Stored file metadata
  Services/
    ClaudeService.php          # AWS Bedrock API integration
    GeminiService.php          # Google Gemini API integration
    AIServiceFactory.php       # Provider resolver
    FileUploadService.php      # Multipart file storage
    MarkdownRenderer.php       # Server-side markdown → HTML
resources/
  views/
    chat/
      index.blade.php          # Main chat UI (sidebar, messages, input, themes)
      select-provider.blade.php # Provider selection screen (first session)
```
