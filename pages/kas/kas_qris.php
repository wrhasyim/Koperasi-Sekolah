<?php
// pages/kas/kas_qris.php
require_once 'config/database.php';

if(isset($_POST['simpan_qris'])){
    $tanggal = $_POST['tanggal'];
    $jumlah = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];
    $user_id = $_SESSION['user']['id'];

    if($jumlah > 0){
        try {
            $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                    VALUES (?, 'qris_masuk', 'masuk', ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tanggal, $jumlah, $keterangan, $user_id]);
            
            catatLog($pdo, $user_id, 'Tambah Kas', "Input QRIS: $keterangan senilai " . formatRp($jumlah));
            setFlash('success', 'Transaksi QRIS berhasil dicatat.');
            echo "<script>window.location='index.php?page=kas/kas_qris';</script>";
            exit;
        } catch (Exception $e) {
            setFlash('danger', 'Gagal: ' . $e->getMessage());
        }
    }
}

$hari_ini = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM transaksi_kas WHERE kategori='qris_masuk' AND tanggal = ? ORDER BY id DESC");
$stmt->execute([$hari_ini]);
$data_qris = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan Non-Tunai</h6>
        <h2 class="h3 fw-bold mb-0 text-dark"><i class="fas fa-qrcode me-2 text-info"></i> Transaksi QRIS & Transfer</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-info text-white py-3">
                <h6 class="mb-0 fw-bold">Form Input Non-Tunai</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="small fw-bold">TANGGAL</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">NOMINAL MASUK (RP)</label>
                        <input type="number" name="jumlah" class="form-control form-control-lg fw-bold text-info" placeholder="0" required>
                        <small class="text-muted small">Pastikan dana sudah masuk ke m-Banking/E-Wallet.</small>
                    </div>
                    <div class="mb-4">
                        <label class="small fw-bold">KETERANGAN / SUMBER</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Transfer pembelian seragam a/n Siswa Budi" required></textarea>
                    </div>
                    <button type="submit" name="simpan_qris" class="btn btn-info text-white w-100 py-3 rounded-pill fw-bold shadow">
                        SIMPAN TRANSAKSI
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between">
                <h6 class="fw-bold m-0 text-dark">Log QRIS Hari Ini</h6>
                <span class="badge bg-light text-dark border"><?= date('d M Y') ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Jam</th>
                            <th>Keterangan</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        if(empty($data_qris)): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada transaksi QRIS hari ini.</td></tr>
                        <?php endif;
                        foreach($data_qris as $row): 
                            $total += $row['jumlah'];
                        ?>
                        <tr>
                            <td class="ps-4 text-muted"><?= date('H:i', strtotime($row['created_at'])) ?></td>
                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                            <td class="text-end fw-bold text-info"><?= formatRp($row['jumlah']) ?></td>
                            <td class="text-center">
                                <a href="process/kas_hapus.php?id=<?= $row['id'] ?>&redirect=index.php?page=kas/kas_qris" class="text-danger" onclick="return confirm('Hapus transaksi ini?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if(!empty($data_qris)): ?>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td colspan="2" class="ps-4">Total Hari Ini</td>
                            <td class="text-end text-info"><?= formatRp($total) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>