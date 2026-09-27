<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use App\Services\WhatsappCloudApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Throwable;
use App\Support\Pagination;

class PembayaranController extends Controller
{
    public function index()
    {
        $transactions = TransaksiPembayaran::with([
            'tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user',
            'tagihan.pendaftar.kunjungan.penerima', 'verifier', 'checkout',
        ])
            ->whereIn('status', ['pending', 'verified', 'rejected'])
            ->whereHas('tagihan.jenisTagihan', fn ($query) => $this->approvalFeeQuery($query))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'rejected' THEN 1 ELSE 2 END")
            ->orderByDesc('payment_date')
            ->paginate(Pagination::perPage())->withQueryString();

        return view('panitia.pembayaran.index', compact('transactions'));
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
        $transaksi->load(['checkout', 'tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'tagihan.pendaftar.kunjungan.penerima', 'verifier']);
        if (! $transaksi->proof_file && $transaksi->checkout) {
            return view('payments.system-proof', ['transaction' => $transaksi]);
        }
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
        if (! $phone) {
            throw new \RuntimeException('Nomor WhatsApp siswa tidak tersedia.');
        }

        if ($transaction->status === 'verified') {
            if ($this->isRegistrationFee($transaction)) {
                $formUrl = URL::temporarySignedRoute('formulir.lanjut', now()->addMinutes(5));
                $message = "Halo {$name}, pembayaran formulir SPMB kamu sudah disetujui oleh {$approver}.\n\n"
                    ."Silakan lanjutkan pengisian formulir melalui tautan ini:\n{$formUrl}\n\n"
                    ."Jika tautan tidak dapat dibuka, silakan masuk melalui: ".route('login');
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
}
