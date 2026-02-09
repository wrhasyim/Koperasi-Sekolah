<?php
// --- 1. LOGIKA SIMPAN DATA BARU ---
if(isset($_POST['simpan_penjualan'])){
    $tanggal = $_POST['tanggal'];
    $jumlah = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];

    if($jumlah > 0){
        $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                VALUES (?, 'penjualan_harian', 'masuk', ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tanggal, $jumlah, $keterangan, $_SESSION['user']['id']]);
        
        echo "<script>alert('Data Penjualan Berhasil Disimpan!'); window.location='kas/kas_penjualan';</script>";
    }
}

// --- 2. LOGIKA UPDATE DATA (PERBAIKAN: FITUR EDIT) ---
if(isset($_POST['update_penjualan'])){
    $id = $_POST['id_transaksi'];
    $tanggal = $_POST['tanggal'];
    $jumlah = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];

    $sql = "UPDATE transaksi_kas SET tanggal=?, jumlah=?, keterangan=? WHERE id=?";
    $pdo->prepare($sql)->execute([$tanggal, $jumlah, $keterangan, $id]);

    echo "<script>alert('Data Berhasil Diupdate!'); window.location='kas/kas_penjualan';</script>";
}

// AMBIL DATA HARI INI
$hari_ini = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM transaksi_kas WHERE kategori='penjualan_harian' AND tanggal = ? ORDER BY id DESC");
$stmt->execute([$hari_ini]);
$penjualan_hari_ini = $stmt->fetchAll();

// HITUNG TOTAL HARI INI
$total_hari_ini = 0;
foreach($penjualan_hari_ini as $p) $total_hari_ini += $p['jumlah'];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Input Penjualan Harian</h1>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <i class="fas fa-cash-register"></i> Form Omzet Toko
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Tanggal Transaksi</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Total Uang Masuk (Rp)</label>
                        <input type="number" name="jumlah" class="form-control" placeholder="Contoh: 500000" required>
                        <small class="text-muted">Masukkan total omzet kotor hari ini.</small>
                    </div>
                    <div class="mb-3">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Penjualan snack & atk senin"></textarea>
                    </div>
                    <button type="submit" name="simpan_penjualan" class="btn btn-success w-100">Simpan Pemasukan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                Riwayat Penjualan (Hari Ini: <?= tglIndo($hari_ini) ?>)
            </div>
            <div class="card-body">
                <h4 class="text-end text-success mb-3">Total: <?= formatRp($total_hari_ini) ?></h4>
                <div class="table-responsive">
                    <table class="table table-striped table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Jam</th>
                                <th>Keterangan</th>
                                <th class="text-end">Jumlah</th>
                                <th>Opsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($penjualan_hari_ini as $row): ?>
                            <tr>
                                <td><?= date('H:i', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                <td class="text-end fw-bold"><?= formatRp($row['jumlah']) ?></td>
                                <td>
                                    <a href="pages/cetak_struk.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Cetak Struk">
                                        <i class="fas fa-print"></i>
                                    </a>

                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </button>

                                    <a href="process/kas_hapus.php?id=<?= $row['id'] ?>&redirect=kas/kas_penjualan" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data ini?')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h6 class="modal-title">Edit Transaksi</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id_transaksi" value="<?= $row['id'] ?>">
                                                <div class="mb-2">
                                                    <label class="small">Tanggal</label>
                                                    <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= $row['tanggal'] ?>" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="small">Jumlah (Rp)</label>
                                                    <input type="number" name="jumlah" class="form-control form-control-sm" value="<?= $row['jumlah'] ?>" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="small">Keterangan</label>
                                                    <input type="text" name="keterangan" class="form-control form-control-sm" value="<?= $row['keterangan'] ?>">
                                                </div>
                                                <button type="submit" name="update_penjualan" class="btn btn-primary btn-sm w-100 mt-2">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if(empty($penjualan_hari_ini)): ?>
                                <tr><td colspan="4" class="text-center p-3 text-muted">Belum ada input penjualan hari ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>