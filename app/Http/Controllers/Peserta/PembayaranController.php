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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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

        // Pembayaran digital dihentikan. Semua transfer memakai rekening
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
        $transaksi->load(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'tagihan.pendaftar.kunjungan.penerima', 'verifier']);

        return view('payments.system-proof', ['transaction' => $transaksi]);
    }

    public function store(Request $request, TagihanPendaftar $tagihan)
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

        if ($this->isReRegistrationFee($tagihan) && $tagihan->transaksi()->exists()) {
            return redirect()->route('peserta.pembayaran')
                ->with('warning', 'Pembayaran DU melalui SPMB hanya satu kali. Pembayaran berikutnya dilakukan langsung di sekolah setiap hari Jumat.');
        }

        $request->validate([
            'amount' => 'required|integer|min:1',
            // Peserta hanya dapat mengirim konfirmasi transfer. Pembayaran
            // tunai dicatat langsung oleh panitia di sekolah.
            'payment_method' => 'required|in:transfer',
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|mimetypes:image/jpeg,image/png,application/pdf|max:2048',
            'selected_items' => 'nullable|array',
            'selected_items.*' => 'string|max:255',
        ]);
        $proofPath = $request->file('proof_file')->store('bukti_pembayaran', 'local');
        try {
            $transaction = \Illuminate\Support\Facades\DB::transaction(function () use ($tagihan, $request, $proofPath) {
                $bill = TagihanPendaftar::lockForUpdate()->findOrFail($tagihan->id);
                $quote = \App\Support\PaymentQuote::forBill(
                    $bill,
                    $request->input('selected_items'),
                );
                if (! $quote['valid_selection']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['selected_items' => 'Nominal DU tidak boleh melebihi sisa tagihan.']);
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
        } catch (ValidationException $exception) {
            Storage::disk('local')->delete($proofPath);
            throw $exception;
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($proofPath);
            report($exception);

            return back()->withInput()->with('error', 'Bukti pembayaran belum dapat disimpan. Periksa file lalu coba lagi.');
        }

        $isReRegistrationFee = $this->isReRegistrationFee($tagihan);

        if ($isReRegistrationFee) {
            return redirect()->route('peserta.pembayaran')
                ->with('success', 'Bukti transfer DU dikirim. Setelah disetujui, pembayaran berikutnya dilakukan langsung di sekolah setiap hari Jumat.');
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

