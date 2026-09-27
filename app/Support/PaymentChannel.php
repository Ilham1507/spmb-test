<?php

namespace App\Support;

class PaymentChannel
{
    public static function fromPayload(array $payload): array
    {
        $type = (string) ($payload['payment_type'] ?? '');
        $bank = (string) ($payload['bank'] ?? data_get($payload, 'va_numbers.0.bank') ?? '');

        $label = match ($type) {
            'echannel' => 'Mandiri',
            'bank_transfer' => strtoupper($bank ?: 'Transfer bank'),
            'qris' => 'QRIS',
            'gopay' => 'GoPay',
            'shopeepay' => 'ShopeePay',
            'dana' => 'DANA',
            'ovo' => 'OVO',
            default => 'Pembayaran digital',
        };

        return compact('type', 'bank', 'label');
    }

    public static function label(?string $type, ?string $bank = null): string
    {
        return self::fromPayload(['payment_type' => $type, 'bank' => $bank])['label'];
    }
}
