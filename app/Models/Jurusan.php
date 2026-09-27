<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    protected $table = 'jurusan';
    protected $guarded = ['id'];

    /**
     * A visitor-facing summary used consistently wherever a concentration appears.
     */
    public function visitorSummary(): string
    {
        if (filled($this->description)) {
            return $this->description;
        }

        return match ($this->name) {
            'Kuliner' => 'Belajar mengolah dan menyajikan makanan, membuat produk kuliner, serta mengelola usaha boga.',
            'Desain dan Produksi Busana' => 'Belajar merancang busana, membuat pola, menjahit, hingga menghasilkan produk fashion.',
            'Layanan Kefarmasian Klinis dan Komunitas' => 'Belajar menyiapkan obat, pelayanan kefarmasian, dan komunikasi dasar kepada pasien.',
            'Layanan Penunjang Keperawatan dan Caregiving' => 'Belajar membantu perawatan pasien, mendampingi lansia, serta menerapkan layanan kesehatan dasar.',
            default => 'Pembelajaran vokasi berbasis praktik untuk membangun keterampilan dan kesiapan masa depan.',
        };
    }
}
