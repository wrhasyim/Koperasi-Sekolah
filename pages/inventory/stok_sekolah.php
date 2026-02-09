<?php
// --- PROSES PHP ---

// 1. TAMBAH SERAGAM BARU
if(isset($_POST['tambah_barang'])){
    $nama = $_POST['nama_barang'];
    $stok = $_POST['stok'];
    $modal = $_POST['harga_modal'];
    $jual = $_POST['harga_jual'];
    
    // Auto Create Tabel jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS stok_sekolah (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_barang VARCHAR(100),
        harga_modal DECIMAL(10,2),
        harga_jual DECIMAL(10,2),
        stok INT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $sql = "INSERT INTO stok_sekolah (nama_barang, stok, harga_modal, harga_jual) VALUES (?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$nama, $stok, $modal, $jual]);
    
    echo "<script>alert('Data Seragam Berhasil Ditambahkan!'); window.location='inventory/stok_sekolah';</script>";
}

// 2. RESTOCK (TAMBAH STOK)
if(isset($_POST['restock_barang'])){
    $id = $_POST['id_barang'];
    $tambah = $_POST['tambah_stok'];
    
    if($tambah < 1) {
        echo "<script>alert('Jumlah restock minimal 1!'); window.location='inventory/stok_sekolah';</script>";
    } else {
        $pdo->prepare("UPDATE stok_sekolah SET stok = stok + ? WHERE id = ?")->execute([$tambah, $id]);
        echo "<script>alert('Restock Seragam Berhasil!'); window.location='inventory/stok_sekolah';</script>";
    }
}

// 3. STOCK OPNAME (SESUAIKAN FISIK)
if(isset($_POST['update_opname'])){
    $id = $_POST['id_barang'];
    $fisik = $_POST['stok_fisik'];
    
    if($fisik < 0) {
        echo "<script>alert('Stok tidak boleh negatif!'); window.location='inventory/stok_sekolah';</script>";
    } else {
        $pdo->prepare("UPDATE stok_sekolah SET stok = ? WHERE id = ?")->execute([$fisik, $id]);
        echo "<script>alert('Stok Opname Berhasil!'); window.location='inventory/stok_sekolah';</script>";
    }
}

// 4. HAPUS BARANG
if(isset($_GET['hapus'])){
    $id = $_GET['hapus'];
    $pdo->prepare("DELETE FROM stok_sekolah WHERE id = ?")->execute([$id]);
    echo "<script>alert('Data seragam dihapus!'); window.location='inventory/stok_sekolah';</script>";
}

// --- QUERY DATA ---
$data = [];
try {
    $data = $pdo->query("SELECT * FROM stok_sekolah ORDER BY nama_barang ASC")->fetchAll();
} catch (Exception $e) {
    // Tabel belum ada
}
?>

<div class="d-flex justify-content-between flex-wrap align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Inventory</h6>
        <h2 class="h3 fw-bold mb-0">Stok Seragam Sekolah</h2>
    </div>
    <button class="btn btn-primary shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus me-2"></i> Input Seragam
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light text-secondary small">
                    <tr>
                        <th class="ps-4">Nama Item & Ukuran</th>
                        <th class="text-end">Harga Modal</th>
                        <th class="text-end">Harga Jual</th>
                        <th class="text-center">Sisa Stok</th>
                        <th class="text-end">Nilai Aset</th>
                        <th class="text-center pe-4" style="min-width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_aset = 0;
                    if(empty($data)): ?><tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data seragam.</td></tr><?php endif;

                    foreach($data as $row): 
                        $aset = $row['stok'] * $row['harga_modal'];
                        $total_aset += $aset;
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark">
                            <i class="fas fa-tshirt text-muted me-2"></i>
                            <?= htmlspecialchars($row['nama_barang']) ?>
                        </td>
                        <td class="text-end text-muted"><?= number_format($row['harga_modal']) ?></td>
                        <td class="text-end text-dark"><?= number_format($row['harga_jual']) ?></td>
                        
                        <td class="text-center">
                            <?php if($row['stok'] <= 0): ?>
                                <span class="badge bg-danger px-3 shadow-sm">HABIS</span>
                            <?php elseif($row['stok'] <= 10): ?>
                                <span class="fw-bold text-danger"><?= $row['stok'] ?></span>
                            <?php else: ?>
                                <span class="fw-bold text-dark"><?= $row['stok'] ?></span>
                            <?php endif; ?>
                        </td>

                        <td class="text-end fw-bold text-success"><?= formatRp($aset) ?></td>
                        
                        <td class="text-center pe-4">
                            <div class="btn-group shadow-sm rounded-pill">
                                <button class="btn btn-sm btn-success px-3" data-bs-toggle="modal" data-bs-target="#modalRestock<?= $row['id'] ?>" title="Tambah Stok">
                                    <i class="fas fa-plus"></i>
                                </button>

                                <?php if($row['stok'] > 0): ?>
                                <button class="btn btn-sm btn-outline-primary px-3" data-bs-toggle="modal" data-bs-target="#modalOpname<?= $row['id'] ?>" title="Cek Fisik">
                                    <i class="fas fa-check"></i>
                                </button>
                                <?php endif; ?>
                                
                                <a href="inventory/stok_sekolah?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-light text-danger px-3" onclick="return confirm('Hapus data seragam ini?')" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>

                            <div class="modal fade" id="modalRestock<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                        <form method="POST">
                                            <div class="modal-body p-4 text-start">
                                                <h6 class="fw-bold mb-3 text-center text-success">Restock Seragam</h6>
                                                <input type="hidden" name="id_barang" value="<?= $row['id'] ?>">
                                                <p class="small text-center text-muted mb-2"><?= htmlspecialchars($row['nama_barang']) ?></p>

                                                <div class="bg-success bg-opacity-10 p-3 rounded-3 mb-3 border border-success border-opacity-25">
                                                    <label class="form-label small fw-bold text-success mb-1">Jumlah Masuk:</label>
                                                    <input type="number" name="tambah_stok" class="form-control fw-bold text-center border-0 shadow-sm text-success" placeholder="0" min="1" required>
                                                </div>
                                                <button type="submit" name="restock_barang" class="btn btn-success w-100 fw-bold btn-sm">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php if($row['stok'] > 0): ?>
                            <div class="modal fade" id="modalOpname<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                        <form method="POST">
                                            <div class="modal-body p-4 text-start">
                                                <h6 class="fw-bold mb-3 text-center text-primary">Cek Fisik</h6>
                                                <input type="hidden" name="id_barang" value="<?= $row['id'] ?>">
                                                
                                                <div class="text-center mb-3">
                                                    <span class="d-block small text-muted">Stok Sistem</span>
                                                    <span class="fs-2 fw-bold text-dark"><?= $row['stok'] ?></span>
                                                </div>

                                                <div class="bg-light p-3 rounded-3 mb-3 border">
                                                    <label class="form-label small fw-bold text-dark mb-1">Fisik Nyata:</label>
                                                    <input type="number" name="stok_fisik" class="form-control fw-bold text-center border-0 shadow-sm" value="<?= $row['stok'] ?>" min="0" required>
                                                </div>
                                                <button type="submit" name="update_opname" class="btn btn-primary w-100 fw-bold btn-sm">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light fw-bold border-top">
                    <tr>
                        <td colspan="4" class="text-end text-uppercase small ls-1 text-muted py-3">Total Aset Seragam</td>
                        <td class="text-end text-success py-3"><?= formatRp($total_aset) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">Input Data Seragam</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Nama Item & Ukuran</label>
                        <input type="text" name="nama_barang" class="form-control bg-light border-0" placeholder="Contoh: Seragam Batik - Ukuran L" required>
                        <div class="form-text small">Sertakan ukuran agar stok akurat.</div>
                    </div>
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="small fw-bold text-muted">Stok Awal</label>
                            <input type="number" name="stok" class="form-control bg-light border-0 fw-bold" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="small fw-bold text-muted">Modal</label>
                            <input type="number" name="harga_modal" class="form-control bg-light border-0" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="small fw-bold text-muted">Jual</label>
                            <input type="number" name="harga_jual" class="form-control bg-light border-0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="submit" name="tambah_barang" class="btn btn-primary w-100 fw-bold">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>