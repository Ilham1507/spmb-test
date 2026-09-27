<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\PaymentCheckout;
use App\Models\TagihanPendaftar;
use App\Services\MidtransClient;
use App\Services\PaymentCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentCheckoutController extends Controller
{
    public function store(Request $request, TagihanPendaftar $tagihan, PaymentCheckoutService $service, MidtransClient $client)
    {
        abort_unless($request->user()?->pendaftar && (int) $tagihan->applicant_id === (int) $request->user()->pendaftar->id, 403);
        $validated = $request->validate([
            'expected_amount' => 'required|integer|min:1',
            'selected_items' => 'nullable|array',
            'selected_items.*' => 'string|max:255',
        ]);
        $checkout = $service->start($tagihan, (int) $validated['expected_amount'], $validated['selected_items'] ?? null);
        if ($checkout->status !== 'pending' || ! $checkout->redirect_url) {
            return back()->with('warning', 'Pembayaran sedang diperiksa. Gunakan Cek status atau hubungi bendahara; jangan membayar ulang.');
        }
        $client->assertRedirect($checkout->redirect_url, $checkout->production);

        return redirect()->away($checkout->redirect_url);
    }

    public function refresh(Request $request, PaymentCheckout $checkout, PaymentCheckoutService $service)
    {
        abort_unless($request->user()?->pendaftar && (int) $checkout->bill?->applicant_id === (int) $request->user()->pendaftar->id, 403);
        try {
            $checkout = $service->refresh($checkout);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['payment' => 'Status belum bisa diperiksa. Coba lagi nanti; jangan membayar ulang.']);
        }

        return redirect()->route('peserta.pembayaran')->with('success', match ($checkout->status) {
            'paid' => 'Pembayaran diterima. Tagihan sudah lunas.',
            'awaiting_approval' => 'Pembayaran diterima penyedia dan menunggu approval petugas. Formulir terbuka setelah disetujui.',
            'closed' => 'Pembayaran sebelumnya sudah ditutup oleh penyedia. Kamu dapat membuat pembayaran baru.',
            'needs_review' => 'Pembayaran perlu diperiksa bendahara. Jangan membayar ulang.',
            default => 'Pembayaran belum dikonfirmasi oleh penyedia. Jika sudah transfer, tunggu lalu cek kembali.',
        });
    }

    public function cancel(Request $request, PaymentCheckout $checkout, PaymentCheckoutService $service)
    {
        abort_unless($request->user()?->pendaftar && (int) $checkout->bill?->applicant_id === (int) $request->user()->pendaftar->id, 403);
        try {
            $service->cancel($checkout);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        } catch (Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Pembatalan VA belum dikonfirmasi penyedia.', ['checkout_id' => $checkout->id, 'error' => $exception->getMessage()]);
            return back()->withErrors(['payment' => 'VA belum bisa dibatalkan oleh penyedia. Tekan Cek status, lalu coba Batalkan lagi.']);
        }

        return redirect()->route('peserta.pembayaran')->with('success', 'Pembayaran dibatalkan. Kamu dapat membuat pembayaran baru saat siap.');
    }
}
