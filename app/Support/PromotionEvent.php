<?php

namespace App\Support;

use App\Models\SystemSetting;

class PromotionEvent
{
    public static function all(): array
    {
        $events = json_decode(SystemSetting::values()['promotion_events'] ?? '', true);
        return is_array($events) ? array_values(array_filter($events, 'is_array')) : [];
    }

    public static function activeAnnouncements(): array
    {
        $today = now()->toDateString();
        return collect(self::all())->filter(fn (array $event) =>
            ($event['is_active'] ?? false)
            && ($event['starts_at'] ?? '') <= $today
            && ($event['ends_at'] ?? '') >= $today
        )->values()->all();
    }

    public static function activeFor(string $billName, ?int $applicantId = null, ?string $itemName = null): ?array
    {
        $now = now()->toDateString();
        $name = strtolower($billName);
        return collect(self::all())->first(function (array $event) use ($now, $name, $applicantId, $itemName) {
            if (!($event['is_active'] ?? false) || ($event['starts_at'] ?? '') > $now || ($event['ends_at'] ?? '') < $now) return false;
            if (filled($event['applicant_id'] ?? null) && (int) $event['applicant_id'] !== $applicantId) return false;
            $targetItems = collect($event['target_items'] ?? [])->filter()->values();
            if ($targetItems->isEmpty() && filled($event['target_item'] ?? null)) $targetItems->push($event['target_item']);
            if ($targetItems->isNotEmpty() && ! $targetItems->contains(fn ($item) => strcasecmp((string) $item, (string) $itemName) === 0)) return false;
            return match ($event['target'] ?? 'formulir') {
                'semua' => true,
                'formulir' => str_contains($name, 'formulir') || str_contains($name, 'pendaftaran'),
                'daftar_ulang' => str_contains($name, 'daftar ulang') || str_contains($name, 'du'),
                default => false,
            };
        });
    }

    public static function apply(float $amount, string $billName, ?int $applicantId = null, ?string $itemName = null): array
    {
        $event = self::activeFor($billName, $applicantId, $itemName);
        if (!$event) return ['amount' => $amount, 'discount' => 0, 'event' => null];
        $discount = ($event['discount_type'] ?? 'percent') === 'fixed'
            ? (float) ($event['discount_value'] ?? 0)
            : $amount * ((float) ($event['discount_value'] ?? 0) / 100);
        $discount = min(max(0, $discount), $amount);
        return ['amount' => max(0, $amount - $discount), 'discount' => $discount, 'event' => $event];
    }
}
