<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use App\Models\Jurusan;
use App\Models\RekeningSekolah;
use App\Support\PendaftarSetup;
use App\Support\RegistrationFee;
use App\Support\ReRegistrationFee;
use App\Support\RegistrationNumber;
use App\Services\WhatsappCloudApiService;
use App\Services\PaymentCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;

class PembayaranController extends Controller
{
    public function index()
    {
        $pendaftar = PendaftarSetup::getOrCreateFor(Auth::user());
        $registrationFeeBill = RegistrationFee::ensureBill($pendaftar);
        $registrationFeePaid = RegistrationFee::isPaid($registrationFeeBill);
        $registrationFeePending = RegistrationFee::hasPendingVerification($registrationFeeBill);
        if ($registrationFeePaid) {
            ReRegistrationFee::ensureBill($pendaftar);
        }
        $paymentMethods = ['cash' => 'Tunai di Sekolah', 'transfer' => 'Transfer rekening sekolah'];

        $tagihansQuery = TagihanPendaftar::with(['jenisTagihan', 'transaksi' => function ($q) {
            $q->latest();
        }])->where('applicant_id', $pendaftar->id);

        if ($registrationFeePaid) {
            $tagihansQuery->whereHas('jenisTagihan', function ($type) {
                $type->whereRaw('LOWER(name) LIKE ?', ['%daftar ulang%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%du%']);
            });
        } else {
            $tagihansQuery->whereHas('jenisTagihan', function ($type) {
                $type->whereRaw('LOWER(name) LIKE ?', ['%formulir%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%pendaftaran%']);
            });
        }

        $tagihans = $tagihansQuery->get()->values();

        // Pembayaran digital/Midtrans dihentikan. Semua transfer memakai rekening
        // resmi dan setiap metode menyertakan bukti pembayaran.
        $gatewayReady = false;
        $activeCheckouts = collect();
        $rekeningAktif = RekeningSekolah::query()->where('status', true)->orderBy('id')->get();
        return view('peserta.pembayaran.index', compact(
            'pendaftar',
            'tagihans',
            'registrationFeeBill',
            'registrationFeePaid',
            'registrationFeePending',
            'paymentMethods',
            'gatewayReady',
            'activeCheckouts',
            'rekeningAktif'
        ));
    }

    public function majorFees()
    {
        $pendaftar = PendaftarSetup::getOrCreateFor(Auth::user());
        if ($pendaftar->major_choice_1) {
            return redirect()->route('peserta.pembayaran')
                ->with('info', 'Pilihan jurusan sudah tersimpan. Rincian biaya tersedia pada tagihan daftar ulang.');
        }
        $feeOptions = ReRegistrationFee::optionsFor($pendaftar, showAll: true);

        return view('peserta.biaya-jurusan.index', compact('pendaftar', 'feeOptions'));
    }

    public function startReRegistration(Jurusan $jurusan)
    {
        abort_unless($jurusan->status === 'aktif', 404);
        $pendaftar = PendaftarSetup::getOrCreateFor(Auth::user());
        $registrationBill = RegistrationFee::ensureBill($pendaftar);
        if (! RegistrationFee::isPaid($registrationBill)) {
            return redirect()->route('peserta.pembayaran')
                ->with('warning', 'Lunasi pembayaran formulir terlebih dahulu sebelum memilih pembayaran daftar ulang.');
        }

        $existingBill = TagihanPendaftar::where('applicant_id', $pendaftar->id)
            ->whereHas('jenisTagihan', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%daftar ulang%']))
            ->first();

        if ($existingBill && (int) $pendaftar->major_choice_1 !== (int) $jurusan->id) {
            return back()->with('warning', 'Pembayaran daftar ulang sudah dibuat untuk jurusan lain. Hubungi panitia bila pilihan jurusan perlu diubah.');
        }

        if (! $pendaftar->major_choice_1) {
            $pendaftar->update(['major_choice_1' => $jurusan->id]);
        }
        ReRegistrationFee::ensureBill($pendaftar->refresh());

        return redirect()->route('peserta.pembayaran')
            ->with('success', 'Rincian daftar ulang '.$jurusan->name.' sudah disiapkan. Kamu dapat memilih biaya yang akan dibayar.');
    }

    public function receipt(TransaksiPembayaran $transaksi)
    {
        abort_unless((int) $transaksi->tagihan?->applicant_id === (int) Auth::user()?->pendaftar?->id, 403);
        $transaksi->load(['checkout', 'tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'tagihan.pendaftar.kunjungan.penerima', 'verifier']);

        return view('payments.system-proof', ['transaction' => $transaksi]);
    }

    public function store(Request $request, TagihanPendaftar $tagihan, WhatsappCloudApiService $whatsapp)
    {
        // Pastikan tagihan ini milik pendaftar yang sedang login
        if (! Auth::user()?->pendaftar || (int) $tagihan->applicant_id !== (int) Auth::user()->pendaftar->id) {
            abort(403);
        }

        if ($tagihan->status === 'paid') {
            return redirect()->route('peserta.pembayaran')
                ->with('warning', 'Tagihan ini sudah lunas. Tidak perlu mengirim konfirmasi pembayaran lagi.');
        }

        if ($tagihan->transaksi()->where('status', 'pending')->exists()) {
            return redirect()->route('peserta.pembayaran')
                ->with('warning', 'Konfirmasi pembayaran kamu sudah dikirim dan sedang dicek panitia. Tunggu sampai diverifikasi, ya.');
        }

        $request->validate([
            'amount' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,transfer',
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|mimetypes:image/jpeg,image/png,application/pdf|max:2048',
            'selected_items' => 'nullable|array',
            'selected_items.*' => 'string|max:255',
        ]);
        $proofPath = $request->file('proof_file')->store('bukti_pembayaran', 'local');
        try {
            $transaction = \Illuminate\Support\Facades\DB::transaction(function () use ($tagihan, $request, $proofPath) {
                $bill = TagihanPendaftar::lockForUpdate()->findOrFail($tagihan->id);
                $quote = \App\Support\PaymentQuote::forBill($bill, $request->input('selected_items'), (int) $request->amount);
                if (! $quote['valid_selection']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['selected_items' => 'Pilih biaya yang ingin dibayar.']);
                }
                if ((int) $request->amount !== $quote['amount']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'Pilihan biaya atau nominal berubah. Muat ulang halaman sebelum mengirim bukti.']);
                }
                return TransaksiPembayaran::create([
                    'bill_id' => $bill->id, 'transaction_number' => 'TRX-'.strtoupper(uniqid()),
                    'payment_date' => now(), 'amount' => $quote['amount'],
                    'selected_items' => $quote['selected_items'], 'payment_method' => $request->payment_method,
                    'discount_amount' => $quote['discount'], 'promotion_name' => $quote['promotion_name'] ?? null, 'bill_total_snapshot' => $bill->total_amount,
                    'proof_file' => $proofPath, 'status' => 'pending',
                ]);
            }, 3);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($proofPath);
            throw $exception;
        }

        $isReRegistrationFee = $this->isReRegistrationFee($tagihan);
        if (! $isReRegistrationFee) {
            try {
                $pendaftar = Auth::user()->pendaftar;
                RegistrationNumber::ensure($pendaftar);
                $pendaftar->refresh()->loadMissing('biodata', 'kunjungan.penerima');
                $name = $pendaftar->biodata?->full_name ?? Auth::user()->name;
                $linkedVisit = $pendaftar->kunjunganPenerimaanUtama();
                $notificationTarget = $linkedVisit?->penerima?->phone
                    ?: (string) config('services.panitia.whatsapp_number');
                $method = $transaction->payment_method === 'cash' ? 'tunai' : 'transfer';
                $amount = number_format((float) $transaction->amount, 0, ',', '.');
                $message = "Halo Panitia SPMB, {$name} sudah mengirim bukti pembayaran {$method} formulir sebesar Rp {$amount}.\n"
                    ."Nomor pendaftaran: {$pendaftar->registration_number}.\n"
                    ."Nomor WA siswa: ".Auth::user()->phone.".\n"
                    .($linkedVisit ? "Kunjungan diterima oleh: {$linkedVisit->penerima?->name}.\n" : '')
                    ."\nMohon diperiksa melalui menu Approval Pembayaran.";
                $whatsapp->send($notificationTarget, $message);
            } catch (Throwable $exception) {
                Log::warning('Notifikasi pembayaran ke panitia gagal dikirim melalui WhatsApp Business API.', [
                    'transaction_id' => $transaction->id,
                    'error' => $exception->getMessage(),
                ]);

                return redirect()->route('peserta.pembayaran')
                    ->with('warning', 'Bukti pembayaran sudah tersimpan. Status pembayaran akan diperbarui setelah pemeriksaan selesai.');
            }
        }

        if ($isReRegistrationFee) {
            return redirect()->route('peserta.pembayaran')
                ->with('success', 'Bukti pembayaran dikirim. Tunggu bendahara memeriksa; tagihan belum dianggap lunas.');
        }

        // Status menunggu verifikasi sudah ditampilkan pada kartu utama halaman.
        return redirect()->route('peserta.pembayaran');
    }

    private function isRegistrationFee(TagihanPendaftar $tagihan): bool
    {
        $name = strtolower((string) $tagihan->jenisTagihan?->name);

        return str_contains($name, 'formulir') || str_contains($name, 'pendaftaran');
    }

    private function isReRegistrationFee(TagihanPendaftar $tagihan): bool
    {
        $name = strtolower((string) $tagihan->jenisTagihan?->name);

        return str_contains($name, 'daftar ulang') || str_contains($name, 'du');
    }

}

