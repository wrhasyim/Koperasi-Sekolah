<?php
// pages/guru/laporan_saya.php
$id_guru = $_SESSION['user']['id'];

// Query Barang Titipan Saya
$sql = "SELECT * FROM titipan WHERE anggota_id = ? ORDER BY tanggal_masuk DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_guru]);
$titipan = $stmt->fetchAll();
?>

<h4 class="fw-bold text-primary mb-4"><i class="fas fa-box me-2"></i> Laporan Barang Titipan Saya</h4>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Tanggal Masuk</th>
                        <th>Nama Barang</th>
                        <th class="text-end">Harga Modal (Anda)</th>
                        <th class="text-center">Awal</th>
                        <th class="text-center">Terjual</th>
                        <th class="text-center">Sisa</th>
                        <th class="text-end">Pendapatan</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_cuan = 0;
                    if(empty($titipan)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada barang titipan.</td></tr>
                    <?php endif;

                    foreach($titipan as $row): 
                        $pendapatan = $row['terjual'] * $row['harga_beli'];
                        $total_cuan += $pendapatan;
                    ?>
                    <tr>
                        <td class="ps-4"><?= date('d/m/y', strtotime($row['tanggal_masuk'])) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td class="text-end"><?= formatRp($row['harga_beli']) ?></td>
                        <td class="text-center"><?= $row['jumlah_awal'] ?></td>
                        <td class="text-center text-success fw-bold"><?= $row['terjual'] ?></td>
                        <td class="text-center text-danger"><?= $row['sisa'] ?></td>
                        <td class="text-end fw-bold text-primary"><?= formatRp($pendapatan) ?></td>
                        <td class="text-center">
                            <?php if($row['status'] == 'lunas'): ?>
                                <span class="badge bg-success">SUDAH DIBAYAR</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">BELUM CAIR</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>