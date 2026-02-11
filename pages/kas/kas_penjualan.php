<?php
// pages/kas/kas_penjualan.php
require_once 'config/database.php';

if (isset($_POST['simpan_kas'])) {
    $tanggal    = $_POST['tanggal'];
    $kategori   = $_POST['kategori'];
    $arus       = 'masuk'; 
    $jumlah     = (float) $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];
    $user_id    = $_SESSION['user']['id'];

    try {
        $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tanggal, $kategori, $arus, $jumlah, $keterangan, $user_id]);

        catatLog($pdo, $user_id, 'Tambah Kas', "Input Tunai ($kategori): $keterangan senilai " . formatRp($jumlah));
        
        setFlash('success', 'Pemasukan tunai berhasil disimpan.');
        echo "<script>window.location='index.php?page=kas/kas_penjualan';</script>";
        exit;
    } catch (Exception $e) {
        setFlash('danger', 'Gagal: ' . $e->getMessage());
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan Tunai</h6>
        <h2 class="h3 fw-bold mb-0 text-dark"><i class="fas fa-money-bill-wave me-2 text-success"></i> Kasir Omzet (Tunai)</h2>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold m-0 text-dark">Form Pemasukan Tunai</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">TANGGAL</label>
                        <input type="date" name="tanggal" class="form-control fw-bold" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">SUMBER PEMASUKAN</label>
                        <select name="kategori" class="form-select fw-bold" required>
                            <option value="penjualan_harian">Hasil Penjualan Harian (Tunai)</option>
                            <option value="modal_awal">MODAL AWAL (Sistem Baru)</option>
                            <option value="dana_hibah">Dana Hibah / Lain-lain</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">NOMINAL TUNAI (RP)</label>
                        <input type="number" name="jumlah" class="form-control form-control-lg fw-bold text-success" placeholder="0" required>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-muted">KETERANGAN</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Omzet kantin hari ini..." required></textarea>
                    </div>

                    <button type="submit" name="simpan_kas" class="btn btn-success w-100 py-3 rounded-pill fw-bold shadow">
                        <i class="fas fa-save me-2"></i> SIMPAN PEMASUKAN TUNAI
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 bg-light p-4 text-center">
            <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
            <h5 class="fw-bold">Pemisahan Alur Kas</h5>
            <p class="text-muted">Menu ini khusus untuk mencatat <b>Uang Tunai</b>. Untuk pembayaran melalui Scan QRIS atau Transfer Bank, silakan gunakan menu <b>Transaksi QRIS</b> agar audit bank lebih mudah dilakukan.</p>
        </div>
    </div>
</div>