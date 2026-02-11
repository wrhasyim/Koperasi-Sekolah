<?php
// pages/kas/rekap_honor.php

// AMBIL PENGATURAN PERSENTASE
$set = getAllPengaturan($pdo);

// --- LOGIKA 1: HITUNG TOTAL SURPLUS BERSIH (SEUMUR HIDUP) ---
// Surplus = (Semua Pemasukan - Semua Pengeluaran Operasional)
// Pengecualian: Transaksi Seragam, Eskul, Simpan Pinjam, dan Pembayaran Honor itu sendiri
$sql_surplus = "SELECT 
                    SUM(CASE WHEN arus = 'masuk' THEN jumlah ELSE 0 END) as total_masuk,
                    SUM(CASE WHEN arus = 'keluar' THEN jumlah ELSE 0 END) as total_keluar
                FROM transaksi_kas 
                WHERE kategori NOT IN (
                    'penjualan_seragam', 'penjualan_eskul', 
                    'bagi_hasil_staff', 'bagi_hasil_pengurus', 'bagi_hasil_pembina', 'bagi_hasil_dansos',
                    'pinjaman_anggota', 'bayar_pinjaman',
                    'simpanan_masuk', 'simpanan_keluar'
                )";

$q_surplus = $pdo->query($sql_surplus)->fetch();
$surplus_kotor = $q_surplus['total_masuk'] - $q_surplus['total_keluar'];

// --- LOGIKA 2: HITUNG TOTAL YANG SUDAH DIBAYARKAN (REALISASI ALL TIME) ---
$sql_paid = "SELECT kategori, SUM(jumlah) as total_dibayar FROM transaksi_kas 
             WHERE kategori IN ('bagi_hasil_staff', 'bagi_hasil_pengurus', 'bagi_hasil_pembina', 'bagi_hasil_dansos')
             GROUP BY kategori";
$q_paid = $pdo->query($sql_paid)->fetchAll(PDO::FETCH_KEY_PAIR);

// --- LOGIKA 3: HITUNG SISA HONOR (RUNNING BALANCE) ---
$data_honor = [];
$tipe_list = ['staff', 'pengurus', 'pembina', 'dansos'];

foreach($tipe_list as $tipe){
    $persen = $set['persen_'.$tipe] ?? 0;
    
    // Hak Seharusnya (Sejak awal berdiri)
    $total_hak = $surplus_kotor * ($persen / 100);
    
    // Yang Sudah Dibayar (Sejak awal berdiri)
    $kat_db = "bagi_hasil_" . $tipe;
    $sudah_bayar = $q_paid[$kat_db] ?? 0;
    
    // Sisa yang BISA DIBAYAR SEKARANG (Akumulasi)
    $sisa_bayar = $total_hak - $sudah_bayar;
    
    $data_honor[$tipe] = [
        'label' => ucfirst($tipe),
        'persen' => $persen,
        'hak_total' => $total_hak,
        'terbayar' => $sudah_bayar,
        'sisa' => $sisa_bayar // Angka ini yang akan dibayar
    ];
}

// RIWAYAT (Ambil 10 Terakhir Saja)
$riwayat = $pdo->query("SELECT * FROM transaksi_kas WHERE kategori IN ('bagi_hasil_staff', 'bagi_hasil_pengurus', 'bagi_hasil_pembina', 'bagi_hasil_dansos') ORDER BY tanggal DESC LIMIT 10")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan</h6>
        <h2 class="h3 fw-bold mb-0 text-gray-800">Rekapitulasi Honor (Akumulasi)</h2>
    </div>
    <a href="index.php?page=utilitas/pengaturan" class="btn btn-sm btn-light text-primary fw-bold rounded-pill shadow-sm">
        <i class="fas fa-cog me-2"></i> Pengaturan Persentase
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-primary text-white mb-4 overflow-hidden position-relative">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-8 position-relative" style="z-index: 2;">
                <h5 class="text-white-50 text-uppercase small fw-bold mb-1">Total Surplus Operasional (All Time)</h5>
                <h2 class="mb-0 fw-bold"><?= formatRp($surplus_kotor) ?></h2>
                <small class="text-white-50">Total akumulasi laba bersih operasional sejak awal sistem.</small>
            </div>
            <i class="fas fa-chart-line fa-5x position-absolute end-0 bottom-0 opacity-25 me-4 mb-n2" style="transform: rotate(-15deg);"></i>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <?php 
    $colors = ['staff'=>'primary', 'pengurus'=>'success', 'pembina'=>'warning', 'dansos'=>'danger'];
    $icons = ['staff'=>'users', 'pengurus'=>'user-tie', 'pembina'=>'chalkboard-teacher', 'dansos'=>'hand-holding-heart'];

    foreach($data_honor as $key => $d): 
        $color = $colors[$key];
        $txt_cls = ($color == 'warning') ? 'text-dark' : 'text-white';
        $btn_cls = ($color == 'warning') ? 'btn-warning text-dark' : 'btn-'.$color;
    ?>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm rounded-4">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <h6 class="fw-bold text-muted text-uppercase small mb-1"><?= $d['label'] ?> (<?= $d['persen'] ?>%)</h6>
                        <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> rounded-pill">
                            Jatah: <?= formatRp($d['hak_total']) ?>
                        </span>
                    </div>
                    <div class="bg-<?= $color ?> bg-opacity-10 p-2 rounded-circle text-<?= $color ?>" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-<?= $icons[$key] ?>"></i>
                    </div>
                </div>
                
                <div class="mb-4">
                    <small class="text-muted d-block" style="font-size: 0.75rem;">Sisa Belum Dibayar (Akumulasi)</small>
                    <h3 class="fw-bold text-dark mb-0"><?= formatRp($d['sisa']) ?></h3>
                </div>

                <?php if($d['sisa'] > 500): // Hanya muncul tombol jika ada sisa > 500 rupiah ?>
                    <form action="process/kas_bayar_honor.php" method="POST" onsubmit="return confirm('Bayarkan akumulasi honor <?= $d['label'] ?> sebesar <?= formatRp($d['sisa']) ?>?')">
                        <input type="hidden" name="tipe" value="<?= $key ?>">
                        <input type="hidden" name="nominal" value="<?= $d['sisa'] ?>"> <button type="submit" class="btn <?= $btn_cls ?> w-100 rounded-pill fw-bold shadow-sm">
                            <i class="fas fa-money-bill-wave me-2"></i> Bayar Lunas
                        </button>
                    </form>
                <?php else: ?>
                    <button class="btn btn-light text-muted w-100 rounded-pill border" disabled>
                        <i class="fas fa-check me-2"></i> Lunas
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-history me-2"></i> 10 Pembayaran Terakhir</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Tanggal</th>
                    <th>Jenis</th>
                    <th>Keterangan</th>
                    <th class="text-end pe-4">Jumlah Keluar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($riwayat as $r): ?>
                <tr>
                    <td class="ps-4 text-muted small fw-bold"><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 text-dark border">
                            <?= strtoupper(str_replace('bagi_hasil_', '', $r['kategori'])) ?>
                        </span>
                    </td>
                    <td class="small"><?= htmlspecialchars($r['keterangan']) ?></td>
                    <td class="text-end pe-4 fw-bold text-danger">- <?= formatRp($r['jumlah']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>