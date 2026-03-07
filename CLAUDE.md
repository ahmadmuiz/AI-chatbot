# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Full initial setup (install deps, generate key, migrate, build assets)
composer setup

# Start all dev services concurrently (server, queue, logs, vite)
composer dev

# Run tests
composer test

# Run a single test file
php artisan test --filter=TestClassName

# Run migrations
php artisan migrate

# Lint PHP with Pint
./vendor/bin/pint

# Build frontend assets
npm run build
```

## Architecture

This is a **Laravel 12** AI chatbot with multi-provider support, authentication (via Laravel Breeze), and file upload capability. The database defaults to SQLite.

### AI Provider Layer

Two AI services are available per chat session, selected at session creation:

- **`ClaudeService`** (`app/Services/ClaudeService.php`) — calls Claude via **AWS Bedrock** using a bearer token (not SDK). Sends requests directly via Guzzle to `bedrock-runtime.{region}.amazonaws.com/model/{modelId}/invoke`.
- **`GeminiService`** (`app/Services/GeminiService.php`) — calls Google Gemini REST API using an API key.
- **`AIServiceFactory`** (`app/Services/AIServiceFactory.php`) — static factory; `ChatController` resolves the correct service from `ChatSession::$ai_provider`.

The provider is stored on `chat_sessions.ai_provider` and resolved at message-send time in `ChatController::getAIServiceForSession()`.

### Data Models

- `User` — fields: `is_admin`, `is_active`, `must_change_password`, `disabled_at`; has many `ChatSession`, `AuditLog`
- `ChatSession` — stores `title`, `ai_provider`; has many `ChatMessage`
- `ChatMessage` — stores `role` (`user`|`assistant`), `content`; has many `ChatAttachment`
- `ChatAttachment` — stores file metadata; files persisted to `storage/app/private/uploads/`
- `AuditLog` — stores `event`, `description`, `ip_address`, `user_agent`, `metadata` (JSON); has `user_id` (subject) and `actor_id` (who acted)

### File Uploads

`FileUploadService` stores files with UUID names. `ClaudeService` handles attachments in `processMessagesWithAttachments()`: images are base64-encoded for vision, text/JSON/CSV files are sent as inline text, binary files get a placeholder message.

### Admin Panel

Routes prefixed `/admin`, protected by `admin` middleware alias (`EnsureUserIsAdmin`). Controllers live in `app/Http/Controllers/Admin/`.

- **`UserManagementController`** — list/search users, disable/enable accounts, reset passwords (generates a 12-char temp password flashed once to the session; sets `must_change_password = true`)
- **`AuditLogController`** — filterable audit log viewer (by user, event type, date range)

**`AuditService::log()`** is a static helper called throughout the app to record events. It never throws — failures are silently swallowed so audit logging never breaks the main flow.

### Middleware

Registered as aliases in `bootstrap/app.php`:
- `admin` (`EnsureUserIsAdmin`) — 403s non-admins
- `active` (`EnsureUserIsActive`) — appended to the `web` group globally; logs out and redirects disabled users to login

### Frontend

Single Blade view at `resources/views/chat/index.blade.php`. Admin views at `resources/views/admin/`. Messages use vanilla JS with `fetch` (FormData for multipart uploads). Markdown in AI responses is rendered server-side via `MarkdownRenderer` (wraps `Str::markdown()`).

## Environment Variables

```
# AWS Bedrock (Claude)
AWS_BEARER_TOKEN_BEDROCK=     # IAM Identity Center temporary token
AWS_BEDROCK_MODEL_ID=         # e.g. global.anthropic.claude-haiku-4-5-20251001-v1:0
AWS_REGION=                   # e.g. ap-southeast-3

# Google Gemini
GEMINI_API_KEY=
GEMINI_MODEL=gemini-1.5-flash  # optional, this is the default
```
