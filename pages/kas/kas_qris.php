<?php
// SIMPAN TRANSAKSI QRIS
if(isset($_POST['simpan_qris'])){
    $tanggal = $_POST['tanggal'];
    $jumlah = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];

    if($jumlah > 0){
        // Kategori: qris_masuk, Arus: masuk
        $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                VALUES (?, 'qris_masuk', 'masuk', ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tanggal, $jumlah, $keterangan, $_SESSION['user']['id']]);
        
        echo "<script>alert('Transaksi QRIS Berhasil Disimpan!'); window.location='kas/kas_qris';</script>";
    }
}

// AMBIL DATA QRIS HARI INI
$hari_ini = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM transaksi_kas WHERE kategori='qris_masuk' AND tanggal = ? ORDER BY id DESC");
$stmt->execute([$hari_ini]);
$data_qris = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Input Transaksi QRIS / Transfer</h1>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <i class="fas fa-qrcode"></i> Form Non-Tunai
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Nominal Masuk (Rp)</label>
                        <input type="number" name="jumlah" class="form-control" placeholder="Contoh: 150000" required>
                        <small class="text-muted">Masuk ke Rekening Bank/E-Wallet.</small>
                    </div>
                    <div class="mb-3">
                        <label>Keterangan / Sumber</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Pembayaran Seragam via Transfer Bu Ani"></textarea>
                    </div>
                    <button type="submit" name="simpan_qris" class="btn btn-info text-white w-100">Simpan Transaksi</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                Riwayat QRIS Hari Ini
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Jam</th>
                                <th>Keterangan</th>
                                <th class="text-end">Jumlah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = 0;
                            foreach($data_qris as $row): 
                                $total += $row['jumlah'];
                            ?>
                            <tr>
                                <td><?= date('H:i', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                <td class="text-end fw-bold"><?= formatRp($row['jumlah']) ?></td>
                                <td>
                                    <a href="process/kas_hapus.php?id=<?= $row['id'] ?>&redirect=kas/kas_qris" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="fw-bold">Total Hari Ini</td>
                                <td class="text-end fw-bold text-info"><?= formatRp($total) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>