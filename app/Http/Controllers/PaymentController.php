<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Payment;
use App\Models\Siswa;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public const DU_FEES = ['Infaq Gedung' => 825000, 'ZIS' => 150000, 'Tabungan' => 40000, 'Fortasi' => 70000, 'Pembinaan' => 150000, 'Buku Wajib' => 222000, 'Seragam Sekolah' => 838000, 'SPP' => 250000];

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Payment::with(['siswa', 'bankAccount', 'approver', 'treasurer', 'details'])->latest();
        if ($user->role === 'siswa') $query->whereHas('siswa', fn ($q) => $q->where('user_id', $user->id));
        if ($user->role === 'panitia') $query->whereIn('payment_type', ['formulir', 'du']);
        return view('payments.index', ['payments' => $query->get(), 'accounts' => BankAccount::where('is_active', true)->get(), 'siswas' => $user->role === 'siswa' ? $user->siswa()->get() : Siswa::orderBy('nama')->get(), 'duFees' => self::DU_FEES]);
    }

    public function store(Request $request, WhatsAppService $whatsApp)
    {
        $user = $request->user();
        $rules = ['payment_type' => 'required|in:formulir,du', 'method' => 'required|in:transfer,cash', 'amount' => 'required|integer|min:1', 'paid_at' => 'required|date', 'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', 'bank_account_id' => 'nullable|exists:bank_accounts,id', 'notes' => 'nullable|string|max:1000'];
        if ($user->role === 'siswa') $rules['siswa_id'] = 'nullable';
        else $rules += ['siswa_id' => 'nullable|exists:siswas,id', 'nama_siswa' => 'required_without:siswa_id|string|max:255', 'phone_siswa' => 'required_without:siswa_id|string|max:30', 'email_siswa' => 'nullable|email'];
        $data = $request->validate($rules);
        if ($data['method'] === 'transfer' && empty($data['bank_account_id'])) return back()->withErrors(['bank_account_id' => 'Pilih rekening tujuan untuk pembayaran transfer.'])->withInput();
        if ($data['payment_type'] === 'formulir' && $data['amount'] <= 0) return back()->withErrors(['amount' => 'Nominal formulir harus diisi.'])->withInput();

        $payment = DB::transaction(function () use ($request, $user, $data) {
            $siswa = $user->role === 'siswa' ? $user->siswa : (!empty($data['siswa_id']) ? Siswa::findOrFail($data['siswa_id']) : $this->createSiswaAccount($data));
            abort_unless($siswa, 422, 'Akun siswa belum terhubung.');
            if ($data['payment_type'] === 'du' && $siswa->payments()->where('payment_type', 'du')->whereNotIn('status', ['rejected'])->exists()) abort(422, 'Pembayaran DU SPMB hanya dapat dibuat satu kali.');
            $path = $request->file('proof')->store('payment-proofs', 'public');
            $payment = Payment::create([...collect($data)->except(['proof', 'nama_siswa', 'phone_siswa', 'email_siswa'])->all(), 'siswa_id' => $siswa->id, 'proof_path' => $path, 'status' => 'pending_panitia', 'invoice_number' => 'SPMB-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)), 'submitted_by' => $user->id]);
            return $payment;
        });
        $whatsApp->paymentUpdate($payment);
        if ($payment->payment_type === 'formulir') $whatsApp->activation($payment->siswa);
        return back()->with('success', 'Pembayaran tercatat dan menunggu persetujuan panitia. Bukti pembayaran wajib tersimpan.');
    }

    public function approve(Request $request, Payment $payment, WhatsAppService $whatsApp)
    {
        abort_unless(in_array($request->user()->role, ['panitia', 'admin']), 403);
        abort_unless($payment->status === 'pending_panitia', 422, 'Pembayaran ini tidak dapat disetujui.');
        $payment->update(['status' => 'approved_panitia', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        $whatsApp->paymentUpdate($payment);
        return back()->with('success', 'Pembayaran disetujui panitia. Siswa kini dapat mengisi formulir.');
    }

    public function receive(Request $request, Payment $payment, WhatsAppService $whatsApp)
    {
        abort_unless(in_array($request->user()->role, ['bendahara', 'admin']), 403);
        abort_unless($payment->status === 'approved_panitia', 422, 'Pembayaran harus disetujui panitia terlebih dahulu.');
        $data = $request->validate(['fee_items' => 'array', 'fee_items.*' => 'in:'.implode(',', array_keys(self::DU_FEES))]);
        if ($payment->payment_type === 'du' && empty($data['fee_items'])) return back()->withErrors(['fee_items' => 'Pilih minimal satu rincian biaya DU yang diterima.']);
        DB::transaction(function () use ($payment, $data, $request) {
            if ($payment->payment_type === 'du') foreach ($data['fee_items'] as $fee) $payment->details()->firstOrCreate(['fee_name' => $fee], ['amount' => self::DU_FEES[$fee]]);
            $payment->update(['status' => 'received_bendahara', 'received_by' => $request->user()->id, 'received_at' => now()]);
        });
        $whatsApp->paymentUpdate($payment);
        return back()->with('success', 'Pembayaran diterima bendahara. Invoice siap dicetak untuk BMT.');
    }

    public function invoice(Payment $payment) { $this->canView($payment); $payment->load('siswa', 'details', 'approver', 'treasurer', 'bankAccount'); return view('payments.invoice', compact('payment')); }
    public function invoicePdf(Payment $payment, \App\Services\InvoicePdfService $pdf)
    {
        $this->canView($payment);
        return response($pdf->make($payment), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$payment->invoice_number.'.pdf"']);
    }
    public function publicInvoicePdf(Request $request, Payment $payment, \App\Services\InvoicePdfService $pdf)
    {
        abort_unless($request->hasValidSignature(), 403);
        return response($pdf->make($payment), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$payment->invoice_number.'.pdf"']);
    }

    private function createSiswaAccount(array $data): Siswa
    {
        $phone = $data['phone_siswa'];
        $user = User::firstOrCreate(['phone' => $phone], ['name' => $data['nama_siswa'], 'email' => $data['email_siswa'] ?? null, 'password' => Hash::make(Str::password(16)), 'role' => 'siswa']);
        return Siswa::firstOrCreate(['user_id' => $user->id], ['nama' => $data['nama_siswa'], 'nis' => 'DAFTAR-'.strtoupper(Str::random(8)), 'kelas' => 'Calon Siswa', 'jurusan' => '-', 'phone' => $phone, 'email' => $data['email_siswa'] ?? null]);
    }
    private function canView(Payment $payment): void
    {
        abort_unless(auth()->user()->role !== 'siswa' || $payment->siswa()->where('user_id', auth()->id())->exists(), 403);
    }
}
