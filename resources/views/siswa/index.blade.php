<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel • Data Siswa</title>
    <style>
        *{box-sizing:border-box}body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;background:#f4f7fb;color:#172033}header{background:#173b68;color:#fff;padding:18px 6%;display:flex;justify-content:space-between;align-items:center}header b{font-size:20px}.badge{font-size:12px;background:#2c5b91;padding:7px 10px;border-radius:20px}main{max-width:1050px;margin:34px auto;padding:0 20px}.hero{display:flex;justify-content:space-between;align-items:end;margin-bottom:24px;gap:20px}.hero h1{margin:0 0 7px}.hero p{margin:0;color:#647085}.card{background:#fff;border-radius:16px;box-shadow:0 8px 28px #19395e16;padding:22px}.grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;margin-bottom:22px}input{border:1px solid #d9e0e9;border-radius:9px;padding:11px 12px;font:inherit;width:100%}button{border:0;border-radius:9px;padding:11px 15px;font-weight:650;cursor:pointer}.primary{background:#146ee8;color:#fff}.danger{background:#fff0f0;color:#c52c2c}.edit{background:#edf5ff;color:#1461bd}table{border-collapse:collapse;width:100%}th,td{text-align:left;padding:13px 10px;border-bottom:1px solid #edf0f4}th{color:#6d788b;font-size:13px}.actions{display:flex;gap:7px}.flash,.errors{padding:12px 14px;border-radius:9px;margin-bottom:16px}.flash{background:#eaf9ef;color:#176b35}.errors{background:#fff0f0;color:#a52626}dialog{border:0;border-radius:16px;width:min(430px,90vw);padding:24px;box-shadow:0 25px 70px #17203344}dialog::backdrop{background:#17203388}.form{display:grid;gap:13px}.form h2{margin:0 0 5px}.row{display:flex;justify-content:flex-end;gap:8px}.secondary{background:#eef1f5;color:#354052}code{background:#eaf0f8;padding:5px 8px;border-radius:6px}@media(max-width:780px){.grid{grid-template-columns:1fr 1fr}.grid input:first-child{grid-column:1/-1}.hero{align-items:start;flex-direction:column}table{font-size:13px}}
        .fee-heading{margin:30px 0 14px}.fee-heading h2{margin:0 0 5px}.fee-heading p{margin:0;color:#647085}.fee-wrap{overflow-x:auto}.fee-table{min-width:900px}.fee-table th,.fee-table td{vertical-align:top}.fee-table thead th{background:#f5f8fc;color:#43536b}.fee-table .group td{background:#eef3f9;font-weight:700;color:#173b68}.fee-table .money{text-align:right;white-space:nowrap}.fee-table tfoot td{font-weight:800;background:#173b68;color:#fff;border-bottom:0}.fee-note{font-size:12px;color:#7c8799;margin:12px 0 0}
    </style>
</head>
<body>
<header><b>SMK • Sistem Siswa</b><span class="badge">Laravel {{ app()->version() }}</span></header>
<main>
    <section class="hero"><div><h1>Data Siswa</h1><p>CRUD sungguhan: Laravel, Eloquent ORM, validation, Blade, dan SQLite.</p></div><code>{{ $siswas->count() }} siswa</code></section>
    @if(session('success'))<div class="flash">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
    <section class="card">
        <form class="grid" method="POST" action="{{ route('siswa.store') }}">
            @csrf
            <input name="nama" placeholder="Nama lengkap" value="{{ old('nama') }}" required>
            <input name="nis" placeholder="NIS" value="{{ old('nis') }}" required>
            <input name="kelas" placeholder="Kelas" value="{{ old('kelas') }}" required>
            <input name="jurusan" placeholder="Jurusan" value="{{ old('jurusan') }}" required>
            <button class="primary">+ Tambah</button>
        </form>
        <table><thead><tr><th>Nama</th><th>NIS</th><th>Kelas</th><th>Jurusan</th><th>Aksi</th></tr></thead><tbody>
        @forelse($siswas as $siswa)
            <tr><td><b>{{ $siswa->nama }}</b></td><td>{{ $siswa->nis }}</td><td>{{ $siswa->kelas }}</td><td>{{ $siswa->jurusan }}</td><td><div class="actions">
                <button class="edit" onclick='editSiswa(@json($siswa))'>Edit</button>
                <form method="POST" action="{{ route('siswa.destroy', $siswa) }}" onsubmit="return confirm('Hapus data {{ $siswa->nama }}?')">@csrf @method('DELETE')<button class="danger">Hapus</button></form>
            </div></td></tr>
        @empty
            <tr><td colspan="5">Belum ada data siswa.</td></tr>
        @endforelse
        </tbody></table>
    </section>
    @php
        $biayaPpdb = [
            ['', 'FORMULIR', 150000, 150000, 150000, 150000], [1, 'INFAQ GEDUNG', 825000, 825000, 825000, 825000], [2, 'ZIS', 150000, 150000, 150000, 150000], [3, 'TABUNGAN', 40000, 40000, 40000, 40000], [4, 'FORTASI', 70000, 70000, 70000, 70000], [5, 'PEMBINAAN', 150000, 150000, 150000, 150000], [6, 'BUKU WAJIB', 222000, 222000, 222000, 222000], [7, 'BUKU RAPORT/SKIL, PASPORT/KHS/SKRS', 120000, 120000, 120000, 120000], [8, 'KARTU PELAJAR, PHOTO', 120000, 120000, 120000, 120000],
            ['group', 'SERAGAM SEKOLAH'], [9, 'PAKAIAN OLAH RAGA', 170000, 170000, 170000, 170000], [10, 'SERAGAM HW', 269000, 269000, 269000, 269000], [11, 'BAJU BATIK', 147000, 147000, 147000, 147000], [12, 'BAJU ALMAMATER SMK 4', 252000, 252000, 252000, 252000], [13, 'Baju Jurusan SMK 4', 230000, 230000, 230000, 230000], [14, 'SPP', 250000, 250000, 250000, 250000], [15, 'IPM', 120000, 120000, 120000, 120000], [16, 'SIMULASI DIGITAL', 192000, 192000, 192000, 192000], [17, 'UKS', 120000, 120000, 120000, 120000], [18, "DANA TA'AWIN", 35000, 35000, 35000, 35000], [19, 'PRAKTEK (KEGIATAN JURUSAN)', 530000, 650000, 650000, 500000], [20, 'MAJALAH SEKOLAH', 50000, 50000, 50000, 50000], [21, 'GO SISWA', 80000, 80000, 80000, 80000], [22, 'UJIAN CBT', 20000, 20000, 20000, 20000], [23, 'CAMBRIDGE ENGLISH PROGRAM', 150000, 150000, 150000, 150000], [24, 'BAHASA JEPANG', 200000, 200000, 200000, 200000], [25, 'Buku Bahasa Jepang', 194250, 194250, 194250, 194250]
        ];
    @endphp
    <section class="fee-heading"><h2>Rincian Biaya PPDB</h2><p>SMK Muhammadiyah 4 Cilengsi • Tahun Ajaran 2026/2027</p></section>
    <section class="card">
        <div class="fee-wrap"><table class="fee-table"><thead><tr><th>No.</th><th>Uraian</th><th>Teknik</th><th>TAYOR</th><th>Farmasi</th><th>Perawat</th></tr></thead><tbody>
        @foreach($biayaPpdb as $biaya)
            @if($biaya[0] === 'group')<tr class="group"><td></td><td colspan="5">{{ $biaya[1] }}</td></tr>
            @else<tr><td>{{ $biaya[0] }}</td><td>{{ $biaya[1] }}</td>@foreach(array_slice($biaya, 2) as $nominal)<td class="money">Rp {{ number_format($nominal, 0, ',', '.') }}</td>@endforeach</tr>@endif
        @endforeach
        </tbody><tfoot><tr><td colspan="2">JUMLAH</td><td class="money">Rp 4.512.000</td><td class="money">Rp 4.632.000</td><td class="money">Rp 4.632.000</td><td class="money">Rp 4.482.000</td></tr></tfoot></table></div>
        <p class="fee-note">Nominal dan keterangan “Potongan 50%” dimasukkan sesuai tabel sumber.</p>
    </section>
</main>
<dialog id="modal"><form id="editForm" class="form" method="POST">@csrf @method('PUT')<h2>Edit siswa</h2><input id="editNama" name="nama" required><input id="editNis" name="nis" required><input id="editKelas" name="kelas" required><input id="editJurusan" name="jurusan" required><div class="row"><button type="button" class="secondary" onclick="modal.close()">Batal</button><button class="primary">Simpan perubahan</button></div></form></dialog>
<script>
const modal=document.querySelector('#modal');function editSiswa(s){editForm.action=`{{ url('/siswa') }}/${s.id}`;editNama.value=s.nama;editNis.value=s.nis;editKelas.value=s.kelas;editJurusan.value=s.jurusan;modal.showModal()}
</script>
</body>
</html>
