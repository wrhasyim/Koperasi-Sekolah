<?php
// LOGIKA SIMPAN DATA
if(isset($_POST['simpan_penjualan'])){
    $tanggal = $_POST['tanggal'];
    $jumlah = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];

    if($jumlah > 0){
        // Kategori: penjualan_harian, Arus: masuk
        $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                VALUES (?, 'penjualan_harian', 'masuk', ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tanggal, $jumlah, $keterangan, $_SESSION['user']['id']]);
        
        echo "<script>alert('Data Penjualan Berhasil Disimpan!'); window.location='index.php?page=kas_penjualan';</script>";
    }
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
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Jam Input</th>
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
                                    <a href="process/kas_hapus.php?id=<?= $row['id'] ?>&redirect=kas_penjualan" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($penjualan_hari_ini)): ?>
                                <tr><td colspan="4" class="text-center">Belum ada input hari ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>