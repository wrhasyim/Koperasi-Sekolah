<?php
// pages/kas/kas_penjualan.php
require_once 'config/database.php';

// PROSES SIMPAN OMZET
if(isset($_POST['simpan_omzet'])){
    try {
        $tanggal = $_POST['tanggal'];
        $jumlah  = $_POST['jumlah'];
        $ket_input = $_POST['keterangan'];
        
        $keterangan = "Omzet Harian Toko";
        if(!empty($ket_input)) $keterangan .= " (" . $ket_input . ")";

        // Langsung catat ke KAS (Uang Masuk)
        $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (?, 'penjualan_toko', 'masuk', ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tanggal, $jumlah, $keterangan, $_SESSION['user']['id']]);

        echo "<script>alert('Omzet berhasil disimpan!'); window.location='index.php?page=kas/kas_penjualan';</script>";
    } catch(Exception $e) {
        echo "<script>alert('Gagal: ".$e->getMessage()."');</script>";
    }
}

// RIWAYAT 5 HARI TERAKHIR
$riwayat = $pdo->query("SELECT * FROM transaksi_kas WHERE kategori = 'penjualan_toko' ORDER BY tanggal DESC LIMIT 5")->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0 text-success"><i class="fas fa-cash-register me-2"></i> Input Omzet Harian</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanggal Omzet</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Total Uang Masuk (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Rp</span>
                            <input type="number" name="jumlah" class="form-control fw-bold" placeholder="0" min="100" required>
                        </div>
                        <small class="text-muted">Masukkan total uang tunai yang didapat hari ini.</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Catatan (Opsional)</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Laris manis, atau Penjualan shift pagi"></textarea>
                    </div>
                    <button type="submit" name="simpan_omzet" class="btn btn-success w-100 rounded-pill fw-bold py-2 shadow-sm">
                        <i class="fas fa-save me-2"></i> SIMPAN OMZET
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold m-0 text-muted">Riwayat Input Terakhir</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Tanggal</th>
                            <th>Keterangan</th>
                            <th class="text-end pe-4">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($riwayat as $r): ?>
                        <tr>
                            <td class="ps-4"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($r['keterangan']) ?></td>
                            <td class="text-end pe-4 fw-bold text-success">+ <?= formatRp($r['jumlah']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>