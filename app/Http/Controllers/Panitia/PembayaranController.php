<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use App\Models\Pendaftar;
use App\Models\KunjunganPendaftar;
use App\Models\PengaturanSpmb;
use App\Models\User;
use App\Services\WhatsappCloudApiService;
use App\Services\ParticipantActivationService;
use App\Support\PendaftarSetup;
use App\Support\RegistrationFee;
use App\Support\ReRegistrationFee;
use App\Support\VerifiedPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;
use App\Support\Pagination;

class PembayaranController extends Controller
{
    public function index()
    {
        $transactions = TransaksiPembayaran::with([
            'tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user',
            'tagihan.pendaftar.kunjungan.penerima', 'verifier', 'treasurerReceiver',
        ])
            ->whereIn('status', ['pending', 'verified', 'rejected'])
            ->whereHas('tagihan.jenisTagihan', fn ($query) => $this->approvalFeeQuery($query))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'rejected' THEN 1 ELSE 2 END")
            ->orderByDesc('payment_date')
            ->paginate(Pagination::perPage())->withQueryString();

        $applicants = Pendaftar::with(['user', 'biodata'])
            ->latest('id')->limit(250)->get();
        $visits = KunjunganPendaftar::query()
            ->whereNull('applicant_id')
            ->whereNotNull('visitor_phone')
            ->latest('visited_at')->limit(250)->get();
        $formFeeAmount = (int) round((float) (PengaturanSpmb::query()->latest('id')->value('biaya_pendaftaran') ?? 0));

        return view('panitia.pembayaran.index', compact('transactions', 'applicants', 'visits', 'formFeeAmount'));
    }

    /**
     * Panitia may record a cash/transfer payment at the school desk. Recording
     * it is itself the required panitia approval; the proof remains mandatory.
     */
    public function store(Request $request, ParticipantActivationService $activation, WhatsappCloudApiService $whatsapp)
    {
        $validated = $request->validate([
            'candidate' => ['required', 'string', 'max:40'],
            'fee_type' => ['required', Rule::in(['formulir', 'daftar_ulang'])],
            'amount' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer'])],
            'proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:2048'],
            'selected_items' => ['nullable', 'array'],
            'selected_items.*' => ['string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $proofPath = $request->file('proof_file')->store('bukti_pembayaran', 'local');

        try {
            $result = DB::transaction(function () use ($validated, $proofPath) {
                $createdAccount = false;
                [$candidateType, $candidateId] = array_pad(explode(':', (string) $validated['candidate'], 2), 2, null);
                if (! in_array($candidateType, ['applicant', 'visit'], true) || ! ctype_digit((string) $candidateId)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['candidate' => 'Pilih calon siswa dari data kunjungan atau pendaftar.']);
                }

                if ($candidateType === 'applicant') {
                    $applicant = Pendaftar::with('user')->lockForUpdate()->findOrFail($candidateId);
                    $user = $applicant->user;
                } else {
                    $visit = KunjunganPendaftar::lockForUpdate()->findOrFail($candidateId);
                    if ($visit->applicant_id || ! $visit->visitor_phone) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['candidate' => 'Data kunjungan ini sudah diproses atau tidak memiliki nomor WhatsApp.']);
                    }
                    $phone = $this->normalizePhone((string) $visit->visitor_phone);
                    $user = User::where('phone', $phone)->lockForUpdate()->first();
                    if (! $user) {
                        $role = \App\Models\Peran::firstOrCreate(['name' => 'peserta'], ['description' => 'Peserta SPMB']);
                        $user = User::create([
                            'name' => $visit->full_name,
                            'phone' => $phone,
                            'email' => null,
                            'password' => Hash::make(Str::random(64)),
                            'role_id' => $role->id,
                        ]);
                        $createdAccount = true;
                    }
                    $applicant = PendaftarSetup::getOrCreateFor($user);
                    $applicant->biodata()->updateOrCreate([], ['full_name' => $visit->full_name]);
                    $visit->update(['applicant_id' => $applicant->id]);
                }

                $registrationBill = RegistrationFee::ensureBill($applicant);
                $isRegistration = $validated['fee_type'] === 'formulir';
                if ($isRegistration) {
                    $bill = $registrationBill;
                } else {
                    if (! RegistrationFee::isPaid($registrationBill)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['fee_type' => 'Pembayaran formulir harus disetujui terlebih dahulu sebelum DU.']);
                    }
                    $bill = ReRegistrationFee::ensureBill($applicant);
                }

                if ($bill->transaksi()->where('status', 'pending')->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'Masih ada bukti pembayaran yang menunggu approval panitia.']);
                }

                $quote = $isRegistration
                    ? \App\Support\PaymentQuote::forBill($bill, $validated['selected_items'] ?? [])
                    : [
                        'amount' => (int) $validated['amount'],
                        'discount' => 0,
                        'promotion_name' => null,
                        'selected_items' => null,
                        'valid_selection' => true,
                    ];
                if (! $quote['valid_selection']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['selected_items' => 'Pilih rincian biaya yang akan dibayar.']);
                }

                if (! $isRegistration && empty($validated['amount'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Masukkan nominal DU yang diterima.']);
                }

                // DU may be paid in installments. The treasurer allocates each
                // installment to the selected cost details when receiving it.
                if (! $isRegistration) {
                    $remaining = max(0, (int) round((float) $bill->remaining_amount));
                    if ((int) $validated['amount'] > $remaining) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Nominal DU tidak boleh melebihi sisa tagihan.']);
                    }
                }

                if (! $isRegistration && (int) $validated['amount'] !== (int) $quote['amount']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Nominal harus sama dengan tagihan dan rincian yang dipilih.']);
                }

                $transaction = TransaksiPembayaran::create([
                    'bill_id' => $bill->id,
                    'transaction_number' => 'TRX-'.strtoupper(uniqid()),
                    'reference_number' => $validated['reference_number'] ?: 'PAN-'.now()->format('Ymd-His'),
                    'payment_date' => now(),
                    'amount' => $quote['amount'],
                    'received_amount' => $quote['amount'],
                    'change_amount' => 0,
                    'selected_items' => $quote['selected_items'],
                    'discount_amount' => $quote['discount'] ?? 0,
                    'promotion_name' => $quote['promotion_name'] ?? null,
                    'bill_total_snapshot' => $bill->total_amount,
                    'payment_method' => $validated['payment_method'],
                    'proof_file' => $proofPath,
                    'status' => 'verified',
                    'verified_by' => Auth::id(),
                    'verified_at' => now(),
                    'notes' => $validated['notes'] ?: 'Diinput dan disetujui panitia.',
                ]);

                if ($isRegistration) {
                    VerifiedPayment::apply($bill, $transaction);
                }

                return compact('transaction', 'user', 'createdAccount', 'isRegistration');
            }, 3);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($proofPath);
            throw $exception;
        }

        $transaction = $result['transaction']->fresh(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'verifier']);
        try {
            if ($result['createdAccount']) {
                $activation->send($result['user'], $whatsapp);
            }
            $this->sendDecisionNotification($whatsapp, $transaction);
        } catch (Throwable $exception) {
            Log::warning('Notifikasi pembayaran input panitia gagal dikirim.', ['transaction_id' => $transaction->id, 'error' => $exception->getMessage()]);
        }

        return back()->with('success', $result['createdAccount']
            ? 'Pembayaran disetujui dan akun siswa dibuat. Tautan aktivasi sudah dikirim ke WhatsApp.'
            : 'Pembayaran disetujui oleh panitia. Notifikasi WhatsApp siswa sudah dikirim.');
    }

    public function verify(Request $request, TransaksiPembayaran $transaksi, WhatsappCloudApiService $whatsapp)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:verified,rejected'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($transaksi->status !== 'pending') {
            return back()->with('warning', 'Pembayaran ini sudah diproses sebelumnya.');
        }

        DB::transaction(function () use ($transaksi, $validated) {
            $transaction = TransaksiPembayaran::lockForUpdate()->findOrFail($transaksi->id);
            if ($transaction->status !== 'pending') {
                return;
            }
            $bill = TagihanPendaftar::with('jenisTagihan')->lockForUpdate()->find($transaction->bill_id);

            $transaction->update([
                'status' => $validated['status'],
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'notes' => $validated['notes'] ?? $transaction->notes,
            ]);

            if ($validated['status'] === 'verified' && $this->isRegistrationBill($bill)) {
                if ($bill) \App\Support\VerifiedPayment::apply($bill, $transaction);
            }
        });

        $transaksi->refresh()->load(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'verifier']);

        try {
            $this->sendDecisionNotification($whatsapp, $transaksi);
        } catch (Throwable $exception) {
            Log::warning('Notifikasi hasil approval pembayaran ke siswa gagal dikirim melalui WhatsApp Business API.', [
                'transaction_id' => $transaksi->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('warning', 'Pembayaran sudah diproses, tetapi notifikasi WhatsApp ke siswa belum terkirim.');
        }

        if ($validated['status'] === 'verified') {
            return back()->with('success', 'Pembayaran disetujui oleh '.Auth::user()->name.'. Pembayaran formulir langsung membuka formulir; pembayaran DU diteruskan ke bendahara untuk penerimaan.');
        }

        return back()->with('success', 'Pembayaran ditolak. Notifikasi WhatsApp sudah dikirim otomatis agar siswa dapat mengirim ulang bukti.');
    }

    public function viewProof(TransaksiPembayaran $transaksi)
    {
        $transaksi->load(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'tagihan.pendaftar.kunjungan.penerima', 'verifier']);
        $disk = Storage::disk('local')->exists((string) $transaksi->proof_file) ? 'local' : 'public';
        abort_if(! $transaksi->proof_file || ! Storage::disk($disk)->exists($transaksi->proof_file), 404, 'Bukti pembayaran tidak ditemukan.');

        return response()->file(Storage::disk($disk)->path($transaksi->proof_file));
    }

    private function applyPayment(\App\Models\TagihanPendaftar $bill, float $amount): void
    {
        $paid = min((float) $bill->total_amount, (float) $bill->paid_amount + $amount);
        $remaining = max(0, (float) $bill->total_amount - $paid);
        $bill->update([
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => $remaining <= 0 ? 'paid' : 'partial',
        ]);
    }

    private function sendDecisionNotification(WhatsappCloudApiService $whatsapp, TransaksiPembayaran $transaction): void
    {
        $applicant = $transaction->tagihan?->pendaftar;
        $name = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Calon siswa';
        $phone = $applicant?->user?->phone;
        $approver = $transaction->verifier?->name ?? Auth::user()?->name ?? 'panitia sekolah';
        $receiver = $applicant?->kunjunganPenerimaanUtama()?->penerima;
        $receiverName = $receiver?->name ?? 'Panitia SPMB';
        $receiverPhone = (string) ($receiver?->phone ?: config('services.panitia.whatsapp_number'));
        $receiverContact = $receiverPhone !== '' ? "{$receiverName} ({$receiverPhone})" : $receiverName;
        if (! $phone) {
            throw new \RuntimeException('Nomor WhatsApp siswa tidak tersedia.');
        }

        if ($transaction->status === 'verified') {
            if ($this->isRegistrationFee($transaction)) {
                $formUrl = URL::temporarySignedRoute('formulir.lanjut', now()->addMinutes(30));
                $message = "Assalamu'alaikum wr. wb.\n\n"
                    ."🎉 Selamat, kamu berhasil melakukan pembayaran Formulir SPMB 🎉\n\n"
                    ."*Berikut terlampir bukti pembayaran digital.*\n\n"
                    ."Selanjutnya, silakan kamu mengisi formulir pada link di bawah ini 👇🏻\n{$formUrl}\n\n"
                    ."Jika ada kendala silakan hubungi {$receiverContact}.\n\n"
                    ."Jika tautan tidak dapat dibuka, silakan masuk melalui: ".route('login')."\n\n"
                    ."Terima kasih 🙏🏻\nSenang berkenalan denganmu 🌹";
            } else {
                $message = "Halo {$name}, pembayaran daftar ulang SPMB kamu sudah disetujui oleh {$approver}.\n\n"
                    ."Pembayaran sedang diteruskan ke bendahara untuk penerimaan dan rincian biaya. Status dapat dipantau di: ".route('login');
            }
        } else {
            $reason = $transaction->notes ? "\nCatatan: {$transaction->notes}" : '';
            $message = "Halo {$name}, pembayaran formulir SPMB kamu belum dapat disetujui oleh {$approver}.{$reason}\n\n"
                ."Silakan periksa kembali dan kirim bukti pembayaran yang benar melalui sistem.";
        }

        $whatsapp->send((string) $phone, $message);
        if ($transaction->status === 'verified') {
            $pdfUrl = URL::temporarySignedRoute('invoice.public.pdf', now()->addMinutes(30), ['transaksi' => $transaction->id]);
            $caption = $this->isRegistrationFee($transaction)
                ? 'Invoice digital pembayaran formulir SPMB'
                : 'Invoice digital pembayaran daftar ulang SPMB';
            $whatsapp->sendDocument((string) $phone, $pdfUrl, 'invoice-spmb-'.$transaction->id.'.pdf', $caption);
            if (! $this->isRegistrationFee($transaction)) {
                $this->notifyTreasurer($whatsapp, $transaction, $approver);
            }
        }
    }

    private function notifyTreasurer(WhatsappCloudApiService $whatsapp, TransaksiPembayaran $transaction, string $approver): void
    {
        $target = (string) config('services.payments.admin_whatsapp_number');
        if ($target === '') {
            $target = (string) User::whereHas('role', fn ($query) => $query->where('name', 'bendahara'))->value('phone');
        }
        if ($target === '') return;

        $applicant = $transaction->tagihan?->pendaftar;
        $student = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Calon siswa';
        $amount = number_format((float) $transaction->amount, 0, ',', '.');
        $url = route('bendahara.pembayaran.index');
        $whatsapp->send($target, "Pembayaran DU menunggu penerimaan bendahara\n\nSiswa: {$student}\nNominal diterima: Rp {$amount}\nDisetujui panitia: {$approver}\n\nSilakan buka pembayaran untuk memilih rincian biaya dan klik Terima bendahara:\n{$url}");
    }

    private function approvalFeeQuery($query)
    {
        return $query->whereRaw('LOWER(name) LIKE ?', ['%formulir%'])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%pendaftaran%'])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%daftar ulang%'])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%du%']);
    }

    private function isRegistrationFee(TransaksiPembayaran $transaction): bool
    {
        $name = strtolower((string) $transaction->tagihan?->jenisTagihan?->name);
        return str_contains($name, 'formulir') || str_contains($name, 'pendaftaran');
    }

    private function isRegistrationBill(?TagihanPendaftar $bill): bool
    {
        $name = strtolower((string) $bill?->jenisTagihan?->name);
        return str_contains($name, 'formulir') || str_contains($name, 'pendaftaran');
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }
}
