<?php
// pages/guru/pinjaman_saya.php
$id_guru = $_SESSION['user']['id'];

// 1. Ambil Pinjaman Tunai
$sql_pinjam = "SELECT * FROM pinjaman_dana WHERE anggota_id = ? ORDER BY tanggal_pinjam DESC";
$stmt = $pdo->prepare($sql_pinjam);
$stmt->execute([$id_guru]);
$pinjaman = $stmt->fetchAll();

// 2. Ambil Kasbon Belanja (Jajan)
// PERBAIKAN: Mengganti 'kasbon_belanja' menjadi 'kasbon' dan 'tanggal' menjadi 'tanggal_pinjam'
$sql_kasbon = "SELECT * FROM kasbon WHERE anggota_id = ? ORDER BY tanggal_pinjam DESC";
$stmt2 = $pdo->prepare($sql_kasbon);
$stmt2->execute([$id_guru]);
$kasbon = $stmt2->fetchAll();
?>

<h4 class="fw-bold text-danger mb-4"><i class="fas fa-money-bill-wave me-2"></i> Data Pinjaman & Kasbon Saya</h4>

<div class="row g-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4 border-start border-5 border-danger">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-danger">Riwayat Pinjaman Dana (Tunai)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Tanggal</th>
                                <th>Jumlah Pinjam</th>
                                <th>Bunga</th>
                                <th>Total Tagihan</th>
                                <th>Sisa Hutang</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pinjaman as $p): ?>
                            <tr>
                                <td class="ps-4"><?= date('d/m/Y', strtotime($p['tanggal_pinjam'])) ?></td>
                                <td><?= formatRp($p['jumlah_pinjam']) ?></td>
                                <td class="text-danger">+<?= formatRp($p['nominal_bunga']) ?></td>
                                <td class="fw-bold"><?= formatRp($p['total_tagihan']) ?></td>
                                <td class="fw-bold text-danger"><?= formatRp($p['sisa_tagihan']) ?></td>
                                <td>
                                    <?= $p['status']=='lunas' ? '<span class="badge bg-success">Lunas</span>' : '<span class="badge bg-warning text-dark">Belum Lunas</span>' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($pinjaman)): ?><tr><td colspan="6" class="text-center py-3">Tidak ada data.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4 border-start border-5 border-warning">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Riwayat Kasbon Belanja (Jajan/ATK)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Tanggal</th>
                                <th>Keterangan Barang</th>
                                <th>Total Belanja</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($kasbon as $k): ?>
                            <tr>
                                <td class="ps-4"><?= date('d/m/Y', strtotime($k['tanggal_pinjam'])) ?></td>
                                <td><?= htmlspecialchars($k['keterangan']) ?></td>
                                <td class="fw-bold text-danger"><?= formatRp($k['total_belanja']) ?></td>
                                <td>
                                    <?= $k['status']=='lunas' ? '<span class="badge bg-success">Lunas</span>' : '<span class="badge bg-warning text-dark">Belum Bayar</span>' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($kasbon)): ?><tr><td colspan="4" class="text-center py-3">Tidak ada data kasbon.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>