<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use App\Support\PromotionEvent;
use App\Services\WhatsappCloudApiService;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Throwable;

class PembayaranController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $billStatus = (string) $request->input('bill_status', '');
        $transactionStatus = (string) $request->input('transaction_status', '');
        $relations = [
            'jenisTagihan', 'pendaftar.biodata', 'pendaftar.user', 'pendaftar.gelombangPendaftaran',
            'transaksi' => fn ($query) => $query->with(['verifier', 'treasurerReceiver'])->latest('payment_date')->latest('id'),
        ];

        TagihanPendaftar::with(['jenisTagihan', 'pendaftar'])
            ->whereHas('jenisTagihan', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%daftar ulang%'])->orWhereRaw('LOWER(name) LIKE ?', ['%du%']))
            ->get()->each(fn (TagihanPendaftar $bill) => $bill->pendaftar ? \App\Support\ReRegistrationFee::ensureBill($bill->pendaftar) : null);

        $billsQuery = TagihanPendaftar::with($relations)->latest('id');
        $this->onlyShowReRegistrationAfterRegistrationPaid($billsQuery);
        if ($search !== '') {
            $billsQuery->where(function ($filter) use ($search) {
                $filter->whereHas('pendaftar', function ($applicant) use ($search) {
                    $applicant->where('registration_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('biodata', fn ($biodata) => $biodata->where('full_name', 'like', "%{$search}%"));
                })->orWhereHas('jenisTagihan', fn ($type) => $type->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('transaksi', fn ($transaction) => $transaction->where('transaction_number', 'like', "%{$search}%")->orWhere('reference_number', 'like', "%{$search}%"));
            });
        }
        if (in_array($billStatus, ['unpaid', 'partial', 'paid'], true)) $billsQuery->where('status', $billStatus);
        if (in_array($transactionStatus, ['pending', 'verified', 'rejected'], true)) $billsQuery->whereHas('transaksi', fn ($transaction) => $transaction->where('status', $transactionStatus));
        $bills = $billsQuery->paginate(Pagination::perPage())->withQueryString();

        $inputBills = TagihanPendaftar::with($relations)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereDoesntHave('transaksi', fn ($query) => $query->where('status', 'pending'));
        $this->onlyShowReRegistrationAfterRegistrationPaid($inputBills);
        $inputBills = $inputBills->orderBy('id')->get();

        $pendingApprovals = TransaksiPembayaran::with([
            'tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user',
        ])->where('status', 'pending')->latest('payment_date')->latest('id')->get();

        return view('bendahara.pembayaran.index', compact('bills', 'inputBills', 'pendingApprovals', 'search', 'billStatus', 'transactionStatus'));
    }

    public function refreshCheckout(\App\Models\PaymentCheckout $checkout, \App\Services\PaymentCheckoutService $service)
    {
        try {
            $checkout = $service->refresh($checkout);
        } catch (Throwable $exception) {
            return back()->with('warning', 'Status penyedia belum dapat dikonfirmasi. Jangan mencatat pembayaran pengganti dahulu.');
        }

        return back()->with('success', match ($checkout->status) {
            'paid' => 'Penyedia mengonfirmasi pembayaran. Tagihan lunas.',
            'closed' => 'Penyedia menutup pembayaran. Siswa dapat membuat pembayaran baru.',
            'needs_review' => 'Perlu rekonsiliasi dengan dashboard Midtrans. Jangan menyetujui secara manual.',
            default => 'Penyedia belum mengonfirmasi pembayaran.',
        });
    }

    public function store(Request $request, \App\Services\PaymentReceiptNotifier $receiptNotifier, \App\Services\PaymentCheckoutService $checkoutService, WhatsappCloudApiService $whatsapp, \App\Services\InvoiceEmailNotifier $invoiceEmail)
    {
        $validated = $request->validate([
            'bill_id' => ['required', Rule::exists('tagihan_pendaftar', 'id')],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:2048'],
            'selected_items' => ['nullable', 'array'],
            'selected_items.*' => ['string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'bill_id.required' => 'Pilih tagihan yang akan dicatat.',
            'amount.required' => 'Nominal yang diterima wajib diisi.',
            'amount.min' => 'Nominal yang diterima harus lebih dari Rp 0.',
            'payment_method.required' => 'Pilih metode pembayaran: Tunai atau Transfer.',
            'payment_method.in' => 'Metode pembayaran harus Tunai atau Transfer.',
            'proof_file.required' => 'Bukti pembayaran wajib diunggah sebelum disimpan.',
            'proof_file.file' => 'Bukti pembayaran tidak dapat dibaca.',
            'proof_file.mimes' => 'Bukti pembayaran harus berupa JPG, PNG, atau PDF.',
            'proof_file.max' => 'Ukuran bukti pembayaran maksimal 2 MB.',
        ]);

        $activeCheckout = \App\Models\PaymentCheckout::query()
            ->where('bill_id', $validated['bill_id'])
            ->whereNotNull('active_bill_id')
            ->first();
        if ($activeCheckout) {
            try {
                $checkoutService->cancel($activeCheckout);
            } catch (Throwable $exception) {
                return back()->withInput()->with('warning', 'Pembayaran digital masih aktif dan belum dapat dibatalkan. Cek statusnya terlebih dahulu sebelum mencatat pembayaran manual.');
            }
        }

        $proofPath = $request->file('proof_file')->store('bukti_pembayaran', 'local');

        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $proofPath) {
        $bill = TagihanPendaftar::with('jenisTagihan')->lockForUpdate()->findOrFail($validated['bill_id']);
        if ($bill->hasActiveCheckout()) {
            return back()->with('warning', 'Virtual account siswa masih terbuka. Cek status penyedia sebelum mencatat pembayaran lain.');
        }

        if ($bill->status === 'paid' || (float) $bill->remaining_amount <= 0) {
            return back()->with('warning', 'Tagihan ini sudah lunas.');
        }

        if ($bill->transaksi()->where('status', 'pending')->exists()) {
            return back()->with('warning', 'Peserta ini sudah mengirim bukti pembayaran. Silakan cek dan verifikasi di bagian Bukti Pembayaran Masuk.');
        }

        $receivedAmount = (float) $validated['amount'];
        $amount = $receivedAmount;
        $remainingAmount = (float) ($bill->remaining_amount ?: $bill->total_amount);
        $billTotalSnapshot = (float) $bill->total_amount;
        $isRegistration = $this->isRegistrationFee($bill);
        $isReRegistration = $this->isReRegistrationFee($bill);
        $promotion = $isRegistration && (float) $bill->paid_amount <= 0
            ? PromotionEvent::apply((float) $bill->total_amount, (string) $bill->jenisTagihan?->name, (int) $bill->applicant_id)
            : ['amount' => $remainingAmount, 'discount' => 0, 'event' => null];
        $requiredAmount = $isRegistration
            ? max(0, (float) $bill->total_amount - (float) ($promotion['discount'] ?? 0))
            : $remainingAmount;

        $selectedItems = null;
        if ($isReRegistration && ! $this->hasPaidRegistrationFee($bill)) {
            return back()->withInput()->with('warning', 'Pembayaran formulir harus lunas sebelum pembayaran daftar ulang dapat dicatat.');
        }

        if ($isReRegistration) {
            $selectionQuote = \App\Support\PaymentQuote::forBill($bill, $validated['selected_items'] ?? []);
            if (! $selectionQuote['valid_selection']) {
                return back()->withInput()->with('warning', 'Pilih biaya dan isi nominal yang sesuai.');
            }
            $appliedAmount = min($receivedAmount, (float) $selectionQuote['amount']);
            $quote = \App\Support\PaymentQuote::forBill($bill, $validated['selected_items'] ?? [], (int) $appliedAmount);
            $amount = (float) $quote['amount'];
            $selectedItems = $quote['selected_items'];
            $promotion = [
                'discount' => (float) ($quote['discount'] ?? 0),
                'event' => filled($quote['promotion_name'] ?? null) ? ['name' => $quote['promotion_name']] : null,
            ];
            $requiredAmount = max(0, (float) $bill->total_amount - (float) ($promotion['discount'] ?? 0));
        }

        $amount = min($amount, $remainingAmount);

        if (($promotion['discount'] ?? 0) > 0) {
            $bill->update(['total_amount' => $requiredAmount, 'remaining_amount' => $requiredAmount, 'status' => 'unpaid']);
        }

        $referenceNumber = $validated['reference_number'] ?? $this->generateManualReferenceNumber();

        $transaction = TransaksiPembayaran::create([
            'bill_id' => $bill->id,
            'transaction_number' => 'TRX-' . strtoupper(uniqid()),
            'reference_number' => $referenceNumber,
            'payment_date' => now(),
            'amount' => $amount,
            'received_amount' => $receivedAmount,
            'change_amount' => max(0, $receivedAmount - $amount),
            'selected_items' => $selectedItems,
            'discount_amount' => (int) round($promotion['discount'] ?? 0),
            'promotion_name' => $promotion['event']['name'] ?? null,
            'bill_total_snapshot' => $billTotalSnapshot,
            'payment_method' => $validated['payment_method'],
            'proof_file' => $proofPath,
            'status' => 'verified',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'treasurer_received_by' => Auth::id(),
            'treasurer_received_at' => now(),
            'treasurer_notes' => $validated['notes'] ?? 'Diinput dan diterima langsung oleh bendahara.',
            'notes' => $validated['notes'] ?? 'Diinput langsung oleh bendahara.',
        ]);

        $this->applyVerifiedPayment($bill, $amount);
        return ['transaction' => $transaction, 'is_registration_fee' => $isRegistration];
        }, 3);

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        /** @var TransaksiPembayaran $transaction */
        $transaction = $result['transaction'];
        $receiptNotifier->send($transaction);
        $whatsappUrl = null;
        $whatsappWarning = null;

        $whatsappUrl = $result['is_registration_fee'] ? $this->manualRegistrationWhatsAppUrl($transaction) : null;
        try {
            if ($result['is_registration_fee']) {
                // Kirim satu bubble WhatsApp berupa PDF invoice dengan pesan dan tautan formulir.
                $this->sendDecisionNotification($whatsapp, $transaction);
            } else {
                // Pembayaran DU yang dicatat bendahara sudah diterima pada saat yang sama.
                $this->sendReceivedNotification($whatsapp, $transaction);
            }
        } catch (Throwable $exception) {
            Log::warning('Notifikasi WhatsApp pembayaran manual bendahara gagal dikirim.', [
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);
            $whatsappWarning = 'Pembayaran tersimpan, tetapi notifikasi WhatsApp siswa belum terkirim.';
        }
        $invoiceEmail->send($transaction);

        $redirect = redirect()->route($this->routeName('pembayaran.index'))
            ->with('success', 'Pembayaran berhasil dicatat.');
        if ($whatsappUrl) {
            $redirect->with('payment_whatsapp_url', $whatsappUrl);
        }
        if ($whatsappWarning) {
            $redirect->with('warning', $whatsappWarning);
        }

        return $redirect;
    }

    public function verify(Request $request, TransaksiPembayaran $transaksi, WhatsappCloudApiService $whatsapp, \App\Services\PaymentReceiptNotifier $receiptNotifier)
    {
        abort_unless(Auth::user()?->hasRole('admin') || (Auth::user()?->hasRole('bendahara') && $request->input('status') === 'rejected'), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:verified,rejected'],
            'notes' => ['nullable', 'string', 'max:255'],
            'selected_items' => ['nullable', 'array'],
            'selected_items.*' => ['string', 'max:255'],
        ]);

        if ($transaksi->status !== 'pending') {
            return back()->with('warning', 'Transaksi ini sudah diproses.');
        }

        $processed = \Illuminate\Support\Facades\DB::transaction(function () use ($transaksi, $validated) {
            $bill = TagihanPendaftar::with('jenisTagihan')->lockForUpdate()->findOrFail($transaksi->bill_id);
            $transaction = TransaksiPembayaran::lockForUpdate()->findOrFail($transaksi->id);
            if ($transaction->status !== 'pending') return false;
            if ($validated['status'] === 'verified' && (float) $transaction->amount > (float) $bill->remaining_amount) {
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'Nominal melebihi sisa tagihan. Cocokkan transaksi sebelum menyetujui.']);
            }
            if ($validated['status'] === 'verified' && $this->isReRegistrationFee($bill)) {
                $quote = \App\Support\PaymentQuote::forBill($bill, $validated['selected_items'] ?? [], (int) $transaction->amount);
                if (! $quote['valid_selection']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'selected_items' => 'Pilih rincian biaya yang terkait dengan pembayaran ini. Total rincian yang dipilih minimal sebesar nominal transfer.',
                    ]);
                }
                $transaction->update(['selected_items' => $quote['selected_items']]);
            }
            $transaction->update([
                'status' => $validated['status'], 'verified_by' => Auth::id(), 'verified_at' => now(),
                'notes' => $validated['notes'] ?? $transaction->notes,
            ]);
            if ($validated['status'] === 'verified') {
                \App\Support\VerifiedPayment::apply($bill, $transaction);
            }
            return true;
        }, 3);
        if (! $processed) return back()->with('warning', 'Transaksi ini sudah diproses.');

        $transaksi->refresh()->load(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'verifier']);

        if ($transaksi->status === 'verified') {
            $receiptNotifier->send($transaksi);
        }

        try {
            $this->sendDecisionNotification($whatsapp, $transaksi);
        } catch (Throwable $exception) {
            Log::warning('Notifikasi approval pembayaran ke WhatsApp siswa gagal dikirim.', [
                'transaction_id' => $transaksi->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('warning', $validated['status'] === 'verified'
                ? 'Bukti pembayaran sudah disetujui dan formulir terbuka, tetapi WhatsApp siswa belum terkirim.'
                : 'Bukti pembayaran sudah ditolak, tetapi WhatsApp siswa belum terkirim.');
        }

        return back()->with('success', $validated['status'] === 'verified'
            ? 'Bukti pembayaran disetujui. Formulir siswa sudah terbuka dan notifikasi WhatsApp terkirim.'
            : 'Bukti pembayaran ditolak. Notifikasi WhatsApp terkirim agar siswa mengirim ulang bukti.');
    }

    /**
     * Bendahara may receive a pending transfer directly. That single action
     * verifies it, records the receipt, reduces the bill, and notifies the student.
     */
    public function receive(Request $request, TransaksiPembayaran $transaksi, WhatsappCloudApiService $whatsapp, \App\Services\PaymentReceiptNotifier $receiptNotifier, \App\Services\InvoiceEmailNotifier $invoiceEmail)
    {
        abort_unless(Auth::user()?->hasRole('bendahara'), 403);

        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($transaksi, $request) {
            $transaction = TransaksiPembayaran::lockForUpdate()->findOrFail($transaksi->id);
            $bill = TagihanPendaftar::with('jenisTagihan')->lockForUpdate()->findOrFail($transaction->bill_id);

            if ($transaction->treasurer_received_at) {
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'Pembayaran ini sudah diterima bendahara.']);
            }
            if ($transaction->status === 'rejected') {
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'Pembayaran ini sudah ditolak dan tidak dapat diterima.']);
            }
            if ($transaction->status === 'pending') {
                if ((float) $transaction->amount > (float) $bill->remaining_amount) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'Nominal melebihi sisa tagihan. Cocokkan transaksi sebelum diterima.']);
                }

                // Bendahara is the final financial receiver, so no separate
                // panitia/admin approval is needed for this direct path.
                $transaction->update([
                    'status' => 'verified',
                    'verified_by' => Auth::id(),
                    'verified_at' => now(),
                ]);
                \App\Support\VerifiedPayment::apply($bill, $transaction);
            }
            if ($transaction->status !== 'verified') {
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'Status pembayaran tidak dapat diterima.']);
            }

            $transaction->update([
                'treasurer_received_by' => Auth::id(),
                'treasurer_received_at' => now(),
                'treasurer_notes' => $request->string('notes')->toString() ?: null,
            ]);

            return $transaction->fresh(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'verifier', 'treasurerReceiver']);
        }, 3);

        $receiptNotifier->send($result);
        try {
            $this->sendReceivedNotification($whatsapp, $result);
        } catch (Throwable $exception) {
            Log::warning('Notifikasi penerimaan bendahara gagal dikirim.', ['transaction_id' => $result->id, 'error' => $exception->getMessage()]);
        }
        $invoiceEmail->send($result);

        return back()->with('success', 'Pembayaran diterima langsung oleh bendahara. Tagihan diperbarui dan notifikasi WhatsApp siswa telah dikirim.');
    }

    public function receipt(TransaksiPembayaran $transaksi)
    {
        abort_unless($transaksi->status === 'verified', 404, 'Nota pembayaran belum tersedia.');
        $transaksi->load(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'tagihan.pendaftar.kunjungan.penerima', 'verifier']);

        return view('payments.system-proof', ['transaction' => $transaksi, 'bmtProof' => true]);
    }

    public function receiptPdf(TransaksiPembayaran $transaksi)
    {
        abort_unless($transaksi->status === 'verified', 404, 'Invoice pembayaran belum tersedia.');
        $transaksi->load(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'tagihan.pendaftar.kunjungan.penerima', 'verifier', 'treasurerReceiver']);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('payments.system-proof', ['transaction' => $transaksi, 'bmtProof' => true])
            ->setPaper('a4')
            ->download(str_replace('Bukti Pembayaran Daftar Ulang - ', 'Bukti Pembayaran BTM ANNISA - ', \App\Support\PaymentProof::filename($transaksi)));
    }

    /** A short-lived signed URL used by Waslah to fetch the invoice PDF. */
    public function publicInvoicePdf(TransaksiPembayaran $transaksi)
    {
        abort_unless($transaksi->status === 'verified', 404, 'Invoice pembayaran belum tersedia.');

        $transaksi->load(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'verifier', 'treasurerReceiver']);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('payments.system-proof', ['transaction' => $transaksi])
            ->setPaper('a4')
            ->download(\App\Support\PaymentProof::filename($transaksi));
    }

    public function viewProof(TransaksiPembayaran $transaksi)
    {
        $transaksi->load(['checkout', 'tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'tagihan.pendaftar.kunjungan.penerima', 'verifier']);
        if (! $transaksi->proof_file && $transaksi->checkout) {
            return view('payments.system-proof', ['transaction' => $transaksi]);
        }
        $disk = Storage::disk('local')->exists((string) $transaksi->proof_file) ? 'local' : 'public';
        abort_if(! $transaksi->proof_file || ! Storage::disk($disk)->exists($transaksi->proof_file), 404, 'Bukti pembayaran tidak ditemukan.');

        return response()->file(Storage::disk($disk)->path($transaksi->proof_file));
    }

    private function onlyShowReRegistrationAfterRegistrationPaid($query): void
    {
        $query->where(function ($bills) {
            $bills->whereDoesntHave('jenisTagihan', function ($type) {
                $type->whereRaw('LOWER(name) LIKE ?', ['%daftar ulang%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%du%']);
            })->orWhereHas('pendaftar.tagihan', function ($registrationBill) {
                $registrationBill->where('status', 'paid')->whereHas('jenisTagihan', function ($type) {
                    $type->whereRaw('LOWER(name) LIKE ?', ['%formulir%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%pendaftaran%']);
                });
            });
        });
    }

    private function hasPaidRegistrationFee(TagihanPendaftar $bill): bool
    {
        return TagihanPendaftar::query()
            ->where('applicant_id', $bill->applicant_id)
            ->where('status', 'paid')
            ->whereHas('jenisTagihan', function ($type) {
                $type->whereRaw('LOWER(name) LIKE ?', ['%formulir%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%pendaftaran%']);
            })->exists();
    }

    private function isRegistrationFee(TagihanPendaftar $bill): bool
    {
        $name = strtolower((string) $bill->jenisTagihan?->name);

        return str_contains($name, 'formulir') || str_contains($name, 'pendaftaran');
    }

    private function isReRegistrationFee(TagihanPendaftar $bill): bool
    {
        $name = strtolower((string) $bill->jenisTagihan?->name);

        return str_contains($name, 'daftar ulang') || str_contains($name, 'du');
    }

    private function generateManualReferenceNumber(): string
    {
        $sequence = TransaksiPembayaran::whereDate('created_at', today())->count() + 1;

        return 'MAN-' . now()->format('Ymd') . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('admin.*') ? 'admin.' : 'bendahara.') . $name;
    }

    private function applyVerifiedPayment(TagihanPendaftar $bill, float $amount): void
    {
        $paidAmount = min((float) $bill->total_amount, (float) $bill->paid_amount + $amount);
        $newRemainingAmount = max(0, (float) $bill->total_amount - $paidAmount);

        $bill->update([
            'paid_amount' => $paidAmount,
            'remaining_amount' => $newRemainingAmount,
            'status' => $newRemainingAmount <= 0 ? 'paid' : 'partial',
        ]);
    }

    private function manualRegistrationWhatsAppUrl(TransaksiPembayaran $transaction): ?string
    {
        $transaction->loadMissing(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user']);
        $applicant = $transaction->tagihan?->pendaftar;
        $phone = (string) ($applicant?->user?->phone ?? '');
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') return null;
        $international = str_starts_with($digits, '0') ? '62' . substr($digits, 1) : $digits;

        return 'https://wa.me/' . $international . '?text=' . rawurlencode($this->manualRegistrationMessage($transaction));
    }

    private function sendManualRegistrationNotification(WhatsappCloudApiService $whatsapp, TransaksiPembayaran $transaction): void
    {
        $transaction->loadMissing(['tagihan.pendaftar.biodata', 'tagihan.pendaftar.user']);
        $applicant = $transaction->tagihan?->pendaftar;
        $phone = (string) ($applicant?->user?->phone ?? '');
        if ($phone === '') throw new \RuntimeException('Nomor WhatsApp siswa tidak tersedia.');

        $whatsapp->send($phone, $this->manualRegistrationMessage($transaction));
    }

    private function manualRegistrationMessage(TransaksiPembayaran $transaction): string
    {
        $transaction->loadMissing(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user']);
        $bill = $transaction->tagihan;
        $applicant = $bill?->pendaftar;
        $name = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Calon siswa';
        $amount = number_format((float) $transaction->amount, 0, ',', '.');
        $remaining = (float) ($bill?->fresh()?->remaining_amount ?? 0);
        $remainingText = number_format($remaining, 0, ',', '.');
        $method = $transaction->payment_method === 'cash' ? 'Tunai' : 'Transfer';
        $recordedBy = $transaction->verifier?->name ?? Auth::user()?->name ?? 'petugas sekolah';
        $nextStep = $remaining <= 0
            ? "Tagihan formulir sudah lunas. Silakan masuk untuk melanjutkan pengisian formulir:\n" . route('login')
            : "Sisa tagihan formulir: Rp {$remainingText}. Formulir dapat dilanjutkan setelah tagihan lunas.";

        return \App\Support\WhatsappGreeting::opening()."\n\n"
            ."Pembayaran formulir SPMB atas nama {$name} telah dicatat oleh {$recordedBy}.\n\nNominal diterima: Rp {$amount}\nMetode: {$method}\nReferensi: " . ($transaction->reference_number ?? $transaction->transaction_number) . "\n\n{$nextStep}";
    }

    private function sendDecisionNotification(WhatsappCloudApiService $whatsapp, TransaksiPembayaran $transaction): void
    {
        $applicant = $transaction->tagihan?->pendaftar;
        $name = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Calon siswa';
        $phone = $applicant?->user?->phone;
        $approver = $transaction->verifier?->name ?? Auth::user()?->name ?? 'bendahara sekolah';
        $billName = strtolower((string) $transaction->tagihan?->jenisTagihan?->name);
        $isRegistrationFee = str_contains($billName, 'formulir') || str_contains($billName, 'pendaftaran');
        $receiver = $applicant?->kunjunganPenerimaanUtama()?->penerima;
        $receiverName = $receiver?->name ?? 'Panitia SPMB';
        $receiverPhone = (string) ($receiver?->phone ?: config('services.panitia.whatsapp_number'));
        $receiverContact = $receiverPhone !== '' ? "{$receiverName} ({$receiverPhone})" : $receiverName;

        if (! $phone) {
            throw new \RuntimeException('Nomor WhatsApp siswa tidak tersedia.');
        }

        if ($transaction->status === 'verified') {
            if ($isRegistrationFee) {
                $message = "Assalamu'alaikum wr. wb.\n\n"
                    ."🎉 Selamat, pembayaran Formulir SPMB atas nama {$name} telah disetujui oleh {$approver}. 🎉\n\n"
                    ."Berikut terlampir bukti pembayaran.\n\n"
                    ."Selanjutnya, silahkan kamu mengisi formulir pada link di bawah ini 👇🏻\n".route('login')."\n\n"
                    ."Jika ada kendala silahkan hubungi {$receiverContact}.\n\n"
                    ."Terima kasih 🙏🏻\nSenang berkenalan denganmu 🌹";
            } else {
                $message = \App\Support\WhatsappGreeting::opening()."\n\n"
                    ."🎉 Pembayaran daftar ulang SPMB atas nama {$name} telah disetujui oleh {$approver}. 🎉\n\n"
                    ."Pembayaran daftar ulang melalui SPMB hanya satu kali. Pembayaran berikutnya dilakukan langsung di sekolah setiap Selasa dan Jumat pukul 07.30–14.30 WIB dan akan dicatat oleh panitia.\n\n"
                    .'Status pembayaran dapat dipantau di: '.route('login')."\n\n"
                    ."Terima kasih 🙏🏻";
            }
        } else {
            $reason = $transaction->notes ? "
Catatan: {$transaction->notes}" : '';
            $paymentType = $transaction->tagihan?->jenisTagihan?->name ?? 'SPMB';
            $message = \App\Support\WhatsappGreeting::opening()."\n\n"
                ."Bukti pembayaran {$paymentType} atas nama {$name} belum dapat disetujui oleh {$approver}.{$reason}\n\n"
                ."Silakan unggah ulang bukti transfer yang benar melalui menu Pembayaran:\n".route('login')."\n\n"
                ."Setelah dikirim ulang, petugas akan menerima notifikasi WhatsApp untuk memeriksa bukti tersebut.";
        }

        if ($transaction->status === 'verified') {
            try {
                $pdfUrl = URL::temporarySignedRoute('invoice.public.pdf', now()->addMinutes(30), ['transaksi' => $transaction->id]);
                $whatsapp->sendDocument((string) $phone, $pdfUrl, \App\Support\PaymentProof::filename($transaction), $message);
            } catch (Throwable $exception) {
                Log::warning('Invoice PDF WhatsApp gagal; mengirim teks approval sebagai fallback.', [
                    'transaction_id' => $transaction->id,
                    'error' => $exception->getMessage(),
                ]);
                $whatsapp->send((string) $phone, $message);
            }
            return;
        }
        $whatsapp->send((string) $phone, $message);
    }

    private function sendReceivedNotification(WhatsappCloudApiService $whatsapp, TransaksiPembayaran $transaction): void
    {
        $applicant = $transaction->tagihan?->pendaftar;
        $phone = (string) ($applicant?->user?->phone ?? '');
        if ($phone === '') return;
        $name = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Calon siswa';
        $type = $transaction->tagihan?->jenisTagihan?->name ?? 'Pembayaran SPMB';
        $receivedBy = $transaction->treasurerReceiver?->name ?? Auth::user()?->name ?? 'bendahara sekolah';
        $amount = number_format((float) $transaction->amount, 0, ',', '.');
        $receivedDirectly = $transaction->verified_by && $transaction->verified_by === $transaction->treasurer_received_by;
        $approvalLine = $receivedDirectly
            ? 'Pembayaran diterima dan disetujui langsung oleh bendahara.'
            : 'Disetujui petugas: '.($transaction->verifier?->name ?? '-').'.';
        $message = \App\Support\WhatsappGreeting::opening()."\n\n"
            ."Pembayaran {$type} atas nama {$name} sebesar Rp {$amount} sudah diterima oleh bendahara {$receivedBy}.\n\n"
            .$approvalLine."\n"
            ."Invoice dapat dilihat dari akun siswa atau email terverifikasi.\n\n"
            ."Untuk pembayaran lanjutan, silakan ke BMT PCM Cileungsi setiap Selasa dan Jumat, Kampus E SMK Muhammadiyah 4 Cileungsi, pukul 07.30–14.30 WIB.";
        // Receipt text is the reliable first delivery. The PDF is optional
        // and must never suppress the notification when media upload fails.
        $whatsapp->send($phone, $message);
        try {
            $pdf = URL::temporarySignedRoute('invoice.public.pdf', now()->addMinutes(30), ['transaksi' => $transaction->id]);
            $whatsapp->sendDocument($phone, $pdf, \App\Support\PaymentProof::filename($transaction), 'Invoice pembayaran SPMB.');
        } catch (Throwable $exception) {
            Log::warning('Invoice PDF WhatsApp gagal setelah notifikasi penerimaan terkirim.', [
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
