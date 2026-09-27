<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\TagihanPendaftar;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\Pagination;

class TagihanController extends Controller
{
    public function index()
    {
        return redirect()->route(request()->routeIs('admin.*') ? 'admin.pembayaran.index' : 'bendahara.pembayaran.index');
    }

    public function invoice(TagihanPendaftar $bill)
    {
        $bill->load([
            'jenisTagihan',
            'pendaftar.biodata',
            'pendaftar.user',
            'pendaftar.alamat',
            'pendaftar.dataAyah',
            'pendaftar.dataIbu',
            'pendaftar.dataWali',
            'pendaftar.jalurPendaftaran',
            'pendaftar.jurusan1',
            'transaksi' => fn ($query) => $query->with('verifier')->latest('payment_date')->latest('id'),
        ]);

        $summary = \App\Support\PaymentSummary::forBill($bill);
        $settings = SystemSetting::publicValues();
        $letterheadPath = $settings['letterhead_path'] ?? null;
        $letterheadFile = $letterheadPath ? public_path($letterheadPath) : null;
        $letterheadSrc = $letterheadFile && is_file($letterheadFile)
            ? 'data:image/'.pathinfo($letterheadFile, PATHINFO_EXTENSION).';base64,'.base64_encode((string) file_get_contents($letterheadFile))
            : null;
        $logoPath = public_path($settings['school_logo'] ?? 'images/logo-sekolah.png');
        $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath)) : null;

        return Pdf::loadView('bendahara.tagihan.invoice', compact('bill', 'summary', 'logo', 'settings', 'letterheadSrc'))
            ->setPaper('a4')
            ->stream('invoice-tagihan-'.$bill->id.'.pdf');
    }
}
