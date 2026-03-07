<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Choose AI Model &mdash; AI Chatbot</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #111118;
            --glass: rgba(255,255,255,0.04);
            --glass-border: rgba(255,255,255,0.08);
            --accent: #7c3aed;
            --accent-glow: rgba(124,58,237,0.35);
            --accent-light: #a78bfa;
            --text-primary: #f1f0ff;
            --text-secondary: #9994b3;
            --text-muted: #5f5a78;
            --user-bubble: linear-gradient(135deg, #7c3aed, #5b21b6);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            height: 100vh;
            overflow: hidden;
            background-image:
                radial-gradient(ellipse 80% 60% at 50% -20%, rgba(124,58,237,0.12) 0%, transparent 70%);
        }

        .container {
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .selector-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 40px;
            max-width: 800px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: var(--user-bubble);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            box-shadow: 0 0 40px var(--accent-glow);
            margin: 0 auto 24px;
        }

        .header-title {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 12px;
        }

        .header-subtitle {
            font-size: 16px;
            color: var(--text-secondary);
            max-width: 500px;
        }

        .models-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            width: 100%;
        }

        .model-card {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 24px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .model-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(124,58,237,0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .model-card:hover {
            background: rgba(124,58,237,0.08);
            border-color: rgba(124,58,237,0.3);
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(124,58,237,0.15);
        }

        .model-card:hover::before {
            opacity: 1;
        }

        .model-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(124,58,237,0.3), rgba(79,70,229,0.3));
            border: 1px solid rgba(124,58,237,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .model-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .model-description {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.6;
            flex: 1;
        }

        .model-features {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .feature::before {
            content: '✓';
            color: #10b981;
            font-weight: 700;
        }

        .model-button {
            padding: 10px 16px;
            background: var(--user-bubble);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 0 15px var(--accent-glow);
        }

        .model-button:hover {
            transform: scale(1.02);
            box-shadow: 0 0 25px var(--accent-glow);
        }

        .model-button:active {
            transform: scale(0.98);
        }

        @media (max-width: 768px) {
            .models-grid {
                grid-template-columns: 1fr;
            }

            .header-title {
                font-size: 24px;
            }

            .header-subtitle {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="selector-card">
        <div class="header">
            <div class="header-icon">✦</div>
            <div class="header-title">Choose Your AI Model</div>
            <div class="header-subtitle">
                Select which AI provider you'd like to use for this chat session. You can switch between them for different sessions.
            </div>
        </div>

        <div class="models-grid">
            <!-- Claude Card -->
            <div class="model-card" onclick="selectProvider('claude')">
                <div class="model-icon">🧠</div>
                <div class="model-name">Claude</div>
                <div class="model-description">
                    Anthropic's advanced AI model, excellent at analysis, reasoning, and creative tasks.
                </div>
                <div class="model-features">
                    <div class="feature">Fast and efficient responses</div>
                    <div class="feature">AWS Bedrock powered</div>
                    <div class="feature">File upload support</div>
                    <div class="feature">200K context window</div>
                </div>
                <button type="button" class="model-button">Start with Claude →</button>
            </div>

            <!-- Gemini Card -->
            <div class="model-card" onclick="selectProvider('gemini')">
                <div class="model-icon">🌟</div>
                <div class="model-name">Gemini</div>
                <div class="model-description">
                    Google's latest AI model, great for multimodal tasks and large-scale data analysis.
                </div>
                <div class="model-features">
                    <div class="feature">Advanced vision capabilities</div>
                    <div class="feature">Google API powered</div>
                    <div class="feature">File upload support</div>
                    <div class="feature">1M context window (2.0)</div>
                </div>
                <button type="button" class="model-button">Start with Gemini →</button>
            </div>
        </div>
    </div>
</div>

<form id="provider-form" method="POST" action="{{ route('chat.store') }}" style="display: none;">
    @csrf
    <input type="hidden" name="ai_provider" id="provider-input" value="">
</form>

<script>
    function selectProvider(provider) {
        document.getElementById('provider-input').value = provider;
        document.getElementById('provider-form').submit();
    }
</script>
</body>
</html>
