# Gemini API Integration Guide

This application now supports both **Claude (AWS Bedrock)** and **Google Gemini** as AI providers.

## Quick Start

### Switch to Gemini API

1. **Get your Gemini API Key:**
   - Go to [Google AI Studio](https://aistudio.google.com/app/apikey)
   - Create a new API key
   - Copy the key

2. **Update `.env` file:**
   ```env
   # Set AI provider to Gemini
   AI_PROVIDER=gemini

   # Add Gemini API key
   GEMINI_API_KEY=your-api-key-here

   # (Optional) Choose model
   GEMINI_MODEL=gemini-1.5-flash
   ```

3. **Restart your application:**
   ```bash
   php artisan serve
   ```

That's it! The chat will now use Gemini API instead of Claude.

### Switch Back to Claude

Update `.env`:
```env
AI_PROVIDER=claude
```

Then restart the application.

---

## Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `AI_PROVIDER` | `claude` | Set to `claude` or `gemini` |
| `GEMINI_API_KEY` | - | Your Google Gemini API key |
| `GEMINI_MODEL` | `gemini-1.5-flash` | Gemini model to use (see available models below) |

### Available Gemini Models

- `gemini-1.5-flash` - Fast, efficient model (recommended for chat)
- `gemini-1.5-pro` - More capable, slower model
- `gemini-2.0-flash-exp` - Latest experimental model

### Available Claude Models (Bedrock)

- `global.anthropic.claude-haiku-4-5-20251001-v1:0` - Fast, efficient
- `global.anthropic.claude-sonnet-4-6-20250514-v1:0` - Balanced
- `global.anthropic.claude-opus-4-6-20250514-v1:0` - Most capable

---

## How It Works

### Service Architecture

```
AIServiceFactory
├── Claude → ClaudeService (AWS Bedrock)
└── Gemini → GeminiService (Google API)
```

### Request Flow

1. **ChatController** calls `AIServiceFactory::make()` to get the configured service
2. **AIServiceFactory** returns either `ClaudeService` or `GeminiService` based on config
3. Service processes the message and returns a response
4. Response is stored and displayed

### Message Format Conversion

- **Claude**: Uses Anthropic message format
- **Gemini**: Automatically converts to Google's format
  - Role conversion: `assistant` → `model`
  - Content wrapping in `parts` array

---

## Comparison

| Feature | Claude (Bedrock) | Gemini |
|---------|------------------|--------|
| **Authentication** | AWS Bearer Token | API Key |
| **Pricing** | Per-token pricing | Free tier + paid |
| **Models** | Opus, Sonnet, Haiku | 2.0 Flash, 1.5 Pro/Flash |
| **File Upload** | Images as base64 | Images as base64 |
| **Context Window** | Up to 200K tokens | Up to 1M tokens (2.0) |
| **Speed** | Very fast | Fast |

---

## File Upload Support

Both services support file uploads:

- **Images** (jpg, png, gif, webp) - Sent as base64
- **Text files** (txt, md, json, csv) - Sent as text content
- **Documents** (pdf, docx) - Metadata reference (limited support)

### Gemini-Specific Notes

- More effective at analyzing images than Claude
- Handles CSV and JSON very well
- JSON responses are automatically formatted

---

## Troubleshooting

### "Gemini API key is not configured"

**Solution:** Make sure you have set `GEMINI_API_KEY` in your `.env` file.

```env
GEMINI_API_KEY=your-actual-api-key
```

### "Failed to reach Gemini service"

**Possible causes:**
- Invalid API key
- API quota exceeded
- Network connectivity issue
- Model not available in your region

**Solution:**
1. Verify API key is correct
2. Check [Google Cloud Console](https://console.cloud.google.com) for quota/billing
3. Verify internet connection
4. Try a different model

### Slow Responses

**Solution:**
- Use `gemini-1.5-flash` instead of `gemini-1.5-pro`
- Reduce message history length if possible
- Check your network speed

---

## Switching Strategies

### Development

Use different providers for different purposes:
- **Claude** - When you need highest accuracy
- **Gemini** - When testing or if Claude quota is exceeded

### Production

- Monitor both services for uptime
- Consider load balancing between them
- Keep API keys secure in environment variables

### Cost Optimization

- **Free Tier**: Use `gemini-1.5-flash` (generous free tier)
- **Paid**: Compare costs per 1M tokens
  - Gemini: Often cheaper
  - Claude: Enterprise support available

---

## API Reference

### GeminiService

```php
$gemini = new GeminiService();

// Send messages
$response = $gemini->chat([
    ['role' => 'user', 'content' => 'Hello'],
    ['role' => 'assistant', 'content' => 'Hi there!'],
    ['role' => 'user', 'content' => 'How are you?'],
]);
```

### AIServiceFactory

```php
use App\Services\AIServiceFactory;

// Get configured service
$service = AIServiceFactory::make(); // Returns Claude or Gemini

// Check available providers
$providers = AIServiceFactory::availableProviders(); // ['claude', 'gemini']

// Check if provider available
if (AIServiceFactory::isProviderAvailable('gemini')) {
    // ...
}
```

---

## Environment Setup Example

### Using Claude (Default)
```env
AI_PROVIDER=claude
AWS_BEARER_TOKEN_BEDROCK=your-token
GEMINI_API_KEY=  # Leave empty or not set
```

### Using Gemini
```env
AI_PROVIDER=gemini
GEMINI_API_KEY=your-api-key
AWS_BEARER_TOKEN_BEDROCK=  # Leave empty or not set
```

### Using Both (Switch at Runtime)
```env
# Start with Claude
AI_PROVIDER=claude

# Keep both configured
AWS_BEARER_TOKEN_BEDROCK=your-token
GEMINI_API_KEY=your-api-key

# Change AI_PROVIDER in .env and restart to switch
```

---

## Testing

Run tests to verify both services work:

```bash
# Test factory logic
php artisan test tests/Feature/AIServiceFactoryTest.php

# Test all chat features
php artisan test tests/Feature/FileUploadTest.php
```

---

## Support

For issues or questions:
- **Claude/Bedrock**: Check AWS documentation
- **Gemini**: Visit [Google AI Studio](https://aistudio.google.com)
- **Application**: Check logs in `storage/logs/laravel.log`
