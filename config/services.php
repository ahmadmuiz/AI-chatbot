<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AWS Bedrock — Claude via SigV4 authentication
    |--------------------------------------------------------------------------
    | AWS_ACCESS_KEY_ID        — IAM access key
    | AWS_SECRET_ACCESS_KEY    — IAM secret key
    | AWS_SESSION_TOKEN        — Temporary session token (leave blank for
    |                           long-term credentials / IAM user keys)
    | AWS_DEFAULT_REGION       — Bedrock region, e.g. us-east-1
    | AWS_BEDROCK_MODEL_ID     — Bedrock cross-region inference profile ID or
    |                           model ARN, e.g.:
    |                           us.anthropic.claude-opus-4-5-20251101-v1:0
    */
    'aws' => [
        'key'            => env('AWS_ACCESS_KEY_ID'),
        'secret'         => env('AWS_SECRET_ACCESS_KEY'),
        'token'          => env('AWS_SESSION_TOKEN'),
        'region'         => env('AWS_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        'bedrock_model'  => env('ANTHROPIC_MODEL', env('AWS_BEDROCK_MODEL_ID', 'global.anthropic.claude-haiku-4-5-20251001-v1:0')),
        // Bearer token is read via env() at runtime — never cached or logged
        'bearer_token'   => env('AWS_BEARER_TOKEN_BEDROCK'),
        'use_bedrock'    => env('CLAUDE_CODE_USE_BEDROCK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gemini API Configuration
    |--------------------------------------------------------------------------
    | Google's Gemini API for generative AI capabilities
    |
    | GEMINI_API_KEY       — API key from Google AI Studio
    | GEMINI_MODEL         — Model identifier (e.g., gemini-1.5-flash, gemini-1.5-pro)
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model'   => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Service Provider Selection
    |--------------------------------------------------------------------------
    | Choose which AI provider to use: 'claude' or 'gemini'
    |
    | AI_PROVIDER          — 'claude' (default) or 'gemini'
    */
    'ai_provider' => env('AI_PROVIDER', 'claude'),

];
