<?php
// ... (Logika PHP Simpan/Update SAMA PERSIS dengan sebelumnya, tidak berubah) ...
// Copy bagian PHP logic dari file sebelumnya, paste di sini paling atas.
// Di bawah ini adalah bagian HTML Tampilannya yang baru.

if(isset($_POST['simpan_penjualan'])){
    $tanggal = $_POST['tanggal'];
    $jumlah = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];
    if(cekStatusPeriode($pdo, $tanggal)){
        echo "<script>alert('GAGAL! Periode TUTUP BUKU.'); window.location='kas/kas_penjualan';</script>";
    } elseif($jumlah > 0){
        $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (?, 'penjualan_harian', 'masuk', ?, ?, ?)";
        $pdo->prepare($sql)->execute([$tanggal, $jumlah, $keterangan, $_SESSION['user']['id']]);
        echo "<script>alert('Berhasil!'); window.location='kas/kas_penjualan';</script>";
    }
}
// Update logic juga sama...
if(isset($_POST['update_penjualan'])){
    $id = $_POST['id_transaksi']; $tanggal = $_POST['tanggal']; $jumlah = $_POST['jumlah']; $keterangan = $_POST['keterangan'];
    $cek = $pdo->prepare("SELECT tanggal FROM transaksi_kas WHERE id = ?"); $cek->execute([$id]); $tgl_lama = $cek->fetch()['tanggal'];
    if(cekStatusPeriode($pdo, $tgl_lama) || cekStatusPeriode($pdo, $tanggal)){
        echo "<script>alert('GAGAL! Periode TUTUP BUKU.'); window.location='kas/kas_penjualan';</script>";
    } else {
        $pdo->prepare("UPDATE transaksi_kas SET tanggal=?, jumlah=?, keterangan=? WHERE id=?")->execute([$tanggal, $jumlah, $keterangan, $id]);
        echo "<script>alert('Update Berhasil!'); window.location='kas/kas_penjualan';</script>";
    }
}

$hari_ini = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM transaksi_kas WHERE kategori='penjualan_harian' AND tanggal = ? ORDER BY id DESC");
$stmt->execute([$hari_ini]);
$penjualan_hari_ini = $stmt->fetchAll();
$total_hari_ini = 0; foreach($penjualan_hari_ini as $p) $total_hari_ini += $p['jumlah'];
?>

<div class="row align-items-center mb-4">
    <div class="col">
        <h6 class="text-uppercase text-muted small ls-1 mb-1">Pemasukan Toko</h6>
        <h2 class="h3 fw-bold mb-0">Kasir Penjualan</h2>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100 border-0 shadow-lg overflow-hidden">
            <div class="card-body bg-gradient-success text-white p-4 d-flex flex-column justify-content-center">
                <div class="mb-4 text-center">
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 d-inline-block mb-3">
                        <i class="fas fa-cash-register fa-3x"></i>
                    </div>
                    <h4 class="fw-bold">Input Omzet</h4>
                    <p class="text-white-50 small">Masukkan total penjualan kotor hari ini.</p>
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label class="small text-white-50 fw-bold text-uppercase">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control form-control-lg border-0 bg-white bg-opacity-10 text-white" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="small text-white-50 fw-bold text-uppercase">Nominal (Rp)</label>
                        <input type="number" name="jumlah" class="form-control form-control-lg border-0 bg-white text-success fw-bold" placeholder="0" required>
                    </div>
                    <div class="mb-4">
                        <label class="small text-white-50 fw-bold text-uppercase">Catatan</label>
                        <textarea name="keterangan" class="form-control border-0 bg-white bg-opacity-10 text-white" rows="2" placeholder="Contoh: Snack & ATK"></textarea>
                    </div>
                    <button type="submit" name="simpan_penjualan" class="btn btn-light w-100 py-3 text-success fw-bold shadow-sm">
                        <i class="fas fa-plus-circle me-2"></i> Simpan Data
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark m-0"><i class="fas fa-history me-2 text-muted"></i> Riwayat Hari Ini</h6>
                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                    Total: <?= formatRp($total_hari_ini) ?>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Waktu</th>
                                <th>Keterangan</th>
                                <th class="text-end">Jumlah</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($penjualan_hari_ini as $row): ?>
                            <tr>
                                <td class="ps-4 text-muted fw-bold"><?= date('H:i', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                <td class="text-end fw-bold text-dark">+ <?= number_format($row['jumlah']) ?></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="pages/cetak_struk.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-light text-dark" title="Print"><i class="fas fa-print"></i></a>
                                        <button class="btn btn-sm btn-light text-primary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>"><i class="fas fa-pen"></i></button>
                                        <a href="process/kas_hapus.php?id=<?= $row['id'] ?>&redirect=kas/kas_penjualan" class="btn btn-sm btn-light text-danger" onclick="return confirm('Hapus?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            
                            <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                        <form method="POST">
                                            <div class="modal-body p-4">
                                                <h6 class="fw-bold mb-3">Edit Transaksi</h6>
                                                <input type="hidden" name="id_transaksi" value="<?= $row['id'] ?>">
                                                <div class="mb-2">
                                                    <label class="small text-muted fw-bold">Tanggal</label>
                                                    <input type="date" name="tanggal" class="form-control bg-light border-0" value="<?= $row['tanggal'] ?>" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="small text-muted fw-bold">Jumlah</label>
                                                    <input type="number" name="jumlah" class="form-control bg-light border-0" value="<?= $row['jumlah'] ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small text-muted fw-bold">Ket</label>
                                                    <input type="text" name="keterangan" class="form-control bg-light border-0" value="<?= $row['keterangan'] ?>">
                                                </div>
                                                <button type="submit" name="update_penjualan" class="btn btn-success w-100 btn-sm fw-bold">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php endforeach; ?>
                            <?php if(empty($penjualan_hari_ini)): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada data masuk.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>