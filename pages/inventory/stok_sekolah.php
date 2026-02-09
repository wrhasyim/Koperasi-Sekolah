<?php
$kategori_ini = 'seragam_sekolah';

// 1. TAMBAH BARANG
if(isset($_POST['tambah_barang'])){
    $nama = $_POST['nama_barang'];
    $ukuran = $_POST['ukuran'];
    $stok = $_POST['stok'];
    $modal = $_POST['harga_modal'];
    $jual = $_POST['harga_jual'];

    $kode = "SRG-" . strtoupper($ukuran) . "-" . time(); 

    $sql = "INSERT INTO stok_barang (kode_barang, kategori, nama_barang, ukuran, stok, harga_modal, harga_jual) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kode, $kategori_ini, $nama, $ukuran, $stok, $modal, $jual]);

    // REDIRECT BERSIH
    echo "<script>alert('Barang Berhasil Ditambahkan!'); window.location='inventory/stok_sekolah';</script>";
}

// 2. UPDATE BARANG
if(isset($_POST['update_barang'])){
    $id = $_POST['id_barang'];
    $stok_baru = $_POST['stok'];
    $modal_baru = $_POST['harga_modal'];
    $jual_baru = $_POST['harga_jual'];

    $sql = "UPDATE stok_barang SET stok=?, harga_modal=?, harga_jual=? WHERE id=?";
    $pdo->prepare($sql)->execute([$stok_baru, $modal_baru, $jual_baru, $id]);
    
    // REDIRECT BERSIH
    echo "<script>alert('Data Berhasil Diupdate!'); window.location='inventory/stok_sekolah';</script>";
}

$data = $pdo->query("SELECT * FROM stok_barang WHERE kategori='$kategori_ini' ORDER BY nama_barang ASC, ukuran ASC")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Stok Seragam Sekolah</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus"></i> Tambah Stok
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Nama Barang</th>
                        <th class="text-center">Ukuran</th>
                        <th class="text-center">Stok Fisik</th>
                        <th class="text-end">Harga Modal</th>
                        <th class="text-end">Harga Jual</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data as $row): 
                        $stok_class = ($row['stok'] < 5) ? 'bg-danger text-white' : 'bg-success text-white';
                    ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td class="text-center"><span class="badge bg-secondary"><?= $row['ukuran'] ?></span></td>
                        <td class="text-center">
                            <span class="badge <?= $stok_class ?> rounded-pill px-3"><?= $row['stok'] ?></span>
                        </td>
                        <td class="text-end text-muted"><?= formatRp($row['harga_modal']) ?></td>
                        <td class="text-end fw-bold text-success"><?= formatRp($row['harga_jual']) ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="process/stok_hapus.php?id=<?= $row['id'] ?>&redirect=inventory/stok_sekolah" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus barang ini?')">
                                <i class="fas fa-trash"></i>
                            </a>

                            <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header bg-light py-2">
                                                <h6 class="modal-title fw-bold">Update Stok</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <input type="hidden" name="id_barang" value="<?= $row['id'] ?>">
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">Barang:</small>
                                                    <strong><?= $row['nama_barang'] ?> (<?= $row['ukuran'] ?>)</strong>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small">Sisa Stok</label>
                                                    <input type="number" name="stok" class="form-control" value="<?= $row['stok'] ?>" required>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6 mb-2">
                                                        <label class="form-label small">Modal</label>
                                                        <input type="number" name="harga_modal" class="form-control form-control-sm" value="<?= $row['harga_modal'] ?>">
                                                    </div>
                                                    <div class="col-6 mb-2">
                                                        <label class="form-label small">Jual</label>
                                                        <input type="number" name="harga_jual" class="form-control form-control-sm" value="<?= $row['harga_jual'] ?>">
                                                    </div>
                                                </div>
                                                <button type="submit" name="update_barang" class="btn btn-primary w-100 btn-sm mt-2">Simpan</button>
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
        <?php if(empty($data)): ?>
            <div class="p-4 text-center text-muted">Belum ada data stok seragam sekolah.</div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Input Seragam Sekolah Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-control" placeholder="Contoh: Kemeja Putih" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Ukuran</label>
                            <select name="ukuran" class="form-select">
                                <option value="S">S</option>
                                <option value="M">M</option>
                                <option value="L">L</option>
                                <option value="XL">XL</option>
                                <option value="XXL">XXL</option>
                                <option value="ALL">All Size</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Stok Awal</label>
                            <input type="number" name="stok" class="form-control" placeholder="0" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Harga Modal</label>
                            <input type="number" name="harga_modal" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Harga Jual</label>
                            <input type="number" name="harga_jual" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_barang" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>