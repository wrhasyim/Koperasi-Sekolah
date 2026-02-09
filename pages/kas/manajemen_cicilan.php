<?php
// --- PROSES BAYAR CICILAN ---
if(isset($_POST['bayar_cicilan'])){
    $id_cicilan = $_POST['id_cicilan'];
    $bayar      = $_POST['jumlah_bayar'];
    $ket        = $_POST['keterangan'];
    $sisa_lama  = $_POST['sisa_tagihan'];
    $nama_brg   = $_POST['nama_barang_hidden'];
    $nama_siswa = $_POST['nama_siswa_hidden'];
    
    $tanggal    = date('Y-m-d');
    $user_id    = $_SESSION['user']['id'];

    if($bayar > $sisa_lama){
        echo "<script>alert('Pembayaran melebihi sisa tagihan!');</script>";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Masuk Kas
            $ket_transaksi = "Cicilan $nama_siswa: $nama_brg - " . $ket;
            $pdo->prepare("INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (?, 'penjualan_seragam', 'masuk', ?, ?, ?)")
                ->execute([$tanggal, $bayar, $ket_transaksi, $user_id]);

            // 2. Update Data Cicilan
            $sisa_baru = $sisa_lama - $bayar;
            $status = ($sisa_baru <= 0) ? 'lunas' : 'belum';
            
            $pdo->prepare("UPDATE cicilan SET terbayar = terbayar + ?, sisa = ?, status = ? WHERE id = ?")
                ->execute([$bayar, $sisa_baru, $status, $id_cicilan]);

            $pdo->commit();
            echo "<script>alert('Pembayaran Cicilan Berhasil!'); window.location='kas/manajemen_cicilan';</script>";

        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        }
    }
}

// QUERY DATA CICILAN (BELUM LUNAS)
$sql = "SELECT c.*, a.nama_lengkap, a.no_hp 
        FROM cicilan c 
        JOIN anggota a ON c.anggota_id = a.id 
        WHERE c.status = 'belum' 
        ORDER BY c.created_at DESC";
$data = [];
try {
    $data = $pdo->query($sql)->fetchAll();
} catch(Exception $e) {
    // Tabel belum dibuat (karena belum ada transaksi cicilan)
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan</h6>
        <h2 class="h3 fw-bold mb-0">Manajemen Cicilan Siswa</h2>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-clock me-2"></i> Daftar Piutang Belum Lunas</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Nama Siswa</th>
                    <th>Barang</th>
                    <th>Total Tagihan</th>
                    <th>Sudah Bayar</th>
                    <th class="text-danger fw-bold">Sisa Hutang</th>
                    <th>Tanggal Beli</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">Tidak ada data cicilan aktif.</td></tr>
                <?php endif; 
                foreach($data as $row): ?>
                <tr>
                    <td class="ps-4 fw-bold"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                    <td><?= formatRp($row['total_tagihan']) ?></td>
                    <td class="text-success"><?= formatRp($row['terbayar']) ?></td>
                    <td class="text-danger fw-bold"><?= formatRp($row['sisa']) ?></td>
                    <td class="text-muted small"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                    <td class="text-center pe-4">
                        <button class="btn btn-sm btn-primary shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalBayar<?= $row['id'] ?>">
                            <i class="fas fa-money-bill-wave me-1"></i> Bayar
                        </button>

                        <div class="modal fade" id="modalBayar<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-sm modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg rounded-4">
                                    <form method="POST">
                                        <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                                            <h6 class="modal-title fw-bold">Input Pembayaran</h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4 text-start">
                                            <input type="hidden" name="id_cicilan" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="sisa_tagihan" value="<?= $row['sisa'] ?>">
                                            <input type="hidden" name="nama_barang_hidden" value="<?= $row['nama_barang'] ?>">
                                            <input type="hidden" name="nama_siswa_hidden" value="<?= $row['nama_lengkap'] ?>">

                                            <div class="mb-3">
                                                <small class="text-muted d-block">Sisa Tagihan:</small>
                                                <h4 class="fw-bold text-danger"><?= formatRp($row['sisa']) ?></h4>
                                            </div>

                                            <div class="mb-3">
                                                <label class="small fw-bold text-muted">Jumlah Bayar</label>
                                                <input type="number" name="jumlah_bayar" class="form-control form-control-lg fw-bold border-primary" required min="1000" max="<?= $row['sisa'] ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="small fw-bold text-muted">Catatan</label>
                                                <input type="text" name="keterangan" class="form-control bg-light" placeholder="Cicilan ke-...">
                                            </div>
                                            
                                            <button type="submit" name="bayar_cicilan" class="btn btn-primary w-100 fw-bold py-2">Proses Bayar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>