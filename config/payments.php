<?php

return [
    // Enable only after the sandbox/production checklist in docs/pembayaran-midtrans.md.
    'midtrans' => [
        'enabled' => env('MIDTRANS_ENABLED', false),
        'production' => env('MIDTRANS_PRODUCTION', false),
        'server_key' => env('MIDTRANS_SERVER_KEY', ''),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID', ''),
        'ca_bundle' => env('MIDTRANS_CA_BUNDLE'),
        'expiry_minutes' => 10,
        // Snap only shows channels that are active for this Midtrans merchant.
        'enabled_payments' => ['bca_va', 'bni_va', 'bri_va', 'permata_va', 'echannel', 'other_va', 'cimb_va', 'bsi_va', 'qris', 'gopay', 'shopeepay', 'dana', 'ovo'],
    ],
];
