<?php

return [

    'gmail' => [
        'client_id' => env('GMAIL_CLIENT_ID'),
        'client_secret' => env('GMAIL_CLIENT_SECRET'),
        'refresh_token' => env('GMAIL_REFRESH_TOKEN'),
    ],

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

    'whatsapp' => [
        // Meta by default. Legacy credentials must never silently route to Waslah.
        'provider' => env('WHATSAPP_PROVIDER', 'meta'),
        'waslah_token' => env('WASLAH_API_TOKEN'),
        'waslah_instance_key' => env('WASLAH_INSTANCE_KEY'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v25.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'sender_number' => env('WHATSAPP_SENDER_NUMBER'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', '1066255482885051'),
        'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'id'),
        'templates' => [
            'authentication' => env('WHATSAPP_TEMPLATE_AUTHENTICATION', 'spmb_kode_verifikasi'),
            'notification' => env('WHATSAPP_TEMPLATE_NOTIFICATION'),
            'invoice' => env('WHATSAPP_TEMPLATE_INVOICE'),
            'activation' => env('WHATSAPP_TEMPLATE_ACTIVATION'),
            'password_reset' => env('WHATSAPP_TEMPLATE_PASSWORD_RESET'),
            'invoice_formulir' => env('WHATSAPP_TEMPLATE_INVOICE_FORMULIR', 'spmb_invoice_formulir'),
            'invoice_du' => env('WHATSAPP_TEMPLATE_INVOICE_DU', 'spmb_invoice_daftar_ulang'),
            'invoice_received' => env('WHATSAPP_TEMPLATE_INVOICE_RECEIVED', 'spmb_invoice_diterima_bendahara'),
            'transfer_pending' => env('WHATSAPP_TEMPLATE_TRANSFER_PENDING', 'spmb_bukti_transfer_masuk_v2'),
            'form_submitted' => env('WHATSAPP_TEMPLATE_FORM_SUBMITTED', 'spmb_formulir_masuk_v2'),
            'schedule_changed' => env('WHATSAPP_TEMPLATE_SCHEDULE_CHANGED', 'spmb_perubahan_jadwal_tes'),
            'staff_receipt' => env('WHATSAPP_TEMPLATE_STAFF_RECEIPT', 'spmb_penerimaan_petugas'),
            'treasurer_pending' => env('WHATSAPP_TEMPLATE_TREASURER_PENDING', 'spmb_du_penerimaan_bendahara'),
        ],
    ],

    'panitia' => [
        'whatsapp_number' => env('PANITIA_WHATSAPP_NUMBER', '087888405075'),
    ],

    'payments' => [
        // Nomor WhatsApp admin yang menerima pemberitahuan pembayaran yang sudah diterima.
        'admin_whatsapp_number' => env('PAYMENT_NOTIFICATION_WHATSAPP_NUMBER'),
    ],

];
