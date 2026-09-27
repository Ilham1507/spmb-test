<?php

namespace App\Support;

use App\Models\TagihanPendaftar;
use Illuminate\Support\Collection;

class ReRegistrationFormulirCredit
{
    /**
     * The registration-form fee is sometimes included again in an imported
     * daftar-ulang breakdown. Treat the verified registration payment as the
     * settlement of that component so a student is never charged twice.
     */
    public static function forBill(TagihanPendaftar $bill): Collection
    {
        if (! str_contains(strtolower((string) $bill->jenisTagihan?->name), 'daftar ulang')) {
            return collect();
        }
        if (! $bill->applicant_id) {
            return collect();
        }

        $registrationBill = RegistrationFee::billFor($bill->pendaftar ?? $bill->pendaftar()->first());
        if (! RegistrationFee::isPaid($registrationBill)) {
            return collect();
        }

        return collect($bill->rincian_biaya ?? [])
            ->filter(fn ($item) => str_contains(strtolower((string) ($item['name'] ?? '')), 'formulir'))
            ->mapWithKeys(fn ($item) => [(string) $item['name'] => (float) $item['amount']]);
    }
}
