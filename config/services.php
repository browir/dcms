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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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
    | n8n (WhatsApp bridge)
    |--------------------------------------------------------------------------
    |
    | Notifikasi DCMS dikirim ke sebuah Webhook n8n (HTTP POST). Di dalam n8n,
    | payload diteruskan ke node WhatsApp untuk menghasilkan pop-up di HP.
    |
    |  N8N_WEBHOOK_URL : URL Production Webhook dari node Webhook di n8n.
    |  N8N_WEBHOOK_SECRET : (opsional) token rahasia; dikirim sebagai header
    |                       X-DCMS-Signature dan divalidasi di dalam workflow n8n.
    |
    */
    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
        'secret' => env('N8N_WEBHOOK_SECRET'),
        // ID grup WhatsApp default untuk pengumuman kegiatan kalau unit/perusahaan
        // belum punya grup sendiri (Data Master → Unit / Perusahaan → Grup WhatsApp).
        'default_group' => env('N8N_WHATSAPP_GROUP'),
    ],

];
