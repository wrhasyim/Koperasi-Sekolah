<?php
// --- PROSES PHP (LOGIC) ---

// 1. TAMBAH BARANG TITIPAN
if(isset($_POST['tambah_titipan'])){
    $anggota_id = $_POST['anggota_id'];
    $nama_barang = $_POST['nama_barang'];
    $stok = $_POST['stok'];
    $modal = $_POST['harga_modal'];
    $jual = $_POST['harga_jual'];
    $tgl = date('Y-m-d');

    $sql = "INSERT INTO titipan (anggota_id, nama_barang, tanggal_titip, stok_awal, harga_modal, harga_jual) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$anggota_id, $nama_barang, $tgl, $stok, $modal, $jual]);
    echo "<script>alert('Barang Berhasil Ditambahkan!'); window.location='index.php?page=titipan/titipan&guru_id=$anggota_id';</script>";
}

// 2. UPDATE STOK
if(isset($_POST['update_stok'])){
    $id = $_POST['titipan_id'];
    $guru_redirect = $_POST['guru_id_redirect'];
    $terjual_baru = $_POST['stok_terjual'];
    
    $cek = $pdo->prepare("SELECT stok_awal FROM titipan WHERE id=?");
    $cek->execute([$id]);
    $dt = $cek->fetch();

    if($terjual_baru > $dt['stok_awal']){
        echo "<script>alert('Error: Terjual melebihi stok awal!');</script>";
    } else {
        $pdo->prepare("UPDATE titipan SET stok_terjual = ? WHERE id = ?")->execute([$terjual_baru, $id]);
        echo "<script>window.location='index.php?page=titipan/titipan&guru_id=$guru_redirect';</script>";
    }
}

// --- FILTER DATA ---
$guru_id = isset($_GET['guru_id']) ? $_GET['guru_id'] : '';
$where_clause = "";
if($guru_id != '') {
    $where_clause = "WHERE t.anggota_id = '$guru_id'";
}

// QUERY DATA
$query = "SELECT t.*, a.nama_lengkap 
          FROM titipan t 
          JOIN anggota a ON t.anggota_id = a.id 
          $where_clause
          ORDER BY a.nama_lengkap ASC, t.nama_barang ASC";
$data = $pdo->query($query)->fetchAll();

// QUERY LIST GURU (Untuk Dropdown)
$anggota = $pdo->query("SELECT * FROM anggota WHERE role IN ('guru', 'staff', 'pengurus') ORDER BY nama_lengkap ASC")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap align-items-center mb-3">
    <h1 class="h3">Konsinyasi Guru</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTitip">
        <i class="fas fa-plus"></i> Barang Baru
    </button>
</div>

<div class="card shadow-sm mb-3 border-0">
    <div class="card-body p-2 bg-light rounded">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="titipan/titipan">
            <div class="col-auto">
                <label class="fw-bold text-muted small">Pilih Guru:</label>
            </div>
            <div class="col-auto flex-grow-1">
                <select name="guru_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Tampilkan Semua Barang --</option>
                    <?php foreach($anggota as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $guru_id == $a['id'] ? 'selected' : '' ?>>
                        <?= $a['nama_lengkap'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light text-secondary small">
                    <tr>
                        <?php if($guru_id == ''): ?><th>Pemilik</th><?php endif; ?>
                        <th>Barang</th>
                        <th class="text-center">Stok</th>
                        <th class="text-end">Hak Guru</th>
                        <th class="text-end">Laba</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_hak_guru = 0;
                    $total_laba = 0;
                    
                    if(empty($data)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data barang.</td></tr>
                    <?php endif;

                    foreach($data as $row): 
                        $sisa = $row['stok_awal'] - $row['stok_terjual'];
                        $hak_guru = $row['stok_terjual'] * $row['harga_modal'];
                        $laba = $row['stok_terjual'] * ($row['harga_jual'] - $row['harga_modal']);
                        
                        $total_hak_guru += $hak_guru;
                        $total_laba += $laba;
                    ?>
                    <tr>
                        <?php if($guru_id == ''): ?>
                        <td><small class="fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap']) ?></small></td>
                        <?php endif; ?>
                        
                        <td>
                            <span class="d-block text-dark fw-bold"><?= htmlspecialchars($row['nama_barang']) ?></span>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                Jual: <?= number_format($row['harga_jual']) ?> | Modal: <?= number_format($row['harga_modal']) ?>
                            </small>
                        </td>
                        
                        <td class="text-center">
                            <span class="badge bg-secondary text-white rounded-pill px-2"><?= $row['stok_awal'] ?></span>
                            <i class="fas fa-arrow-right text-muted mx-1" style="font-size: 0.7rem;"></i>
                            <span class="badge bg-danger text-white rounded-pill px-2"><?= $sisa ?></span>
                        </td>
                        
                        <td class="text-end text-dark">
                            <small><?= formatRp($hak_guru) ?></small>
                        </td>
                        
                        <td class="text-end text-success fw-bold">
                            <small><?= formatRp($laba) ?></small>
                        </td>
                        
                        <td class="text-center">
                            <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#modalUpdate<?= $row['id'] ?>">
                                <i class="fas fa-pen text-primary"></i>
                            </button>
                            
                            <div class="modal fade" id="modalUpdate<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h6 class="modal-title">Update: <?= htmlspecialchars($row['nama_barang']) ?></h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <input type="hidden" name="titipan_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="guru_id_redirect" value="<?= $guru_id ?>">
                                                
                                                <div class="alert alert-info py-2 small mb-3">
                                                    Stok Awal: <b><?= $row['stok_awal'] ?></b><br>
                                                    Sisa Saat Ini: <b><?= $sisa ?></b>
                                                </div>

                                                <label class="form-label small fw-bold">Total Laku (Akumulasi)</label>
                                                <input type="number" name="stok_terjual" class="form-control" value="<?= $row['stok_terjual'] ?>" max="<?= $row['stok_awal'] ?>" required>
                                                <div class="form-text small">Masukkan jumlah total barang yang sudah laku sampai hari ini.</div>
                                            </div>
                                            <div class="modal-footer p-1">
                                                <button type="submit" name="update_stok" class="btn btn-primary w-100 btn-sm">Simpan Perubahan</button>
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
        
        <div class="card-footer bg-white border-top p-3">
            <div class="row text-center">
                <div class="col-6 border-end">
                    <small class="text-muted d-block">Total Hak Guru</small>
                    <span class="fw-bold text-dark fs-5"><?= formatRp($total_hak_guru) ?></span>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Total Laba Koperasi</small>
                    <span class="fw-bold text-success fs-5"><?= formatRp($total_laba) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTitip" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-box"></i> Barang Titipan Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small fw-bold">Pemilik Barang (Guru)</label>
                        <select name="anggota_id" class="form-select" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach($anggota as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= $guru_id == $a['id'] ? 'selected' : '' ?>>
                                <?= $a['nama_lengkap'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-control" placeholder="Contoh: Keripik Pisang" required>
                    </div>
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="small fw-bold">Stok</label>
                            <input type="number" name="stok" class="form-control" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="small fw-bold">Modal</label>
                            <input type="number" name="harga_modal" class="form-control" placeholder="Setor" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="small fw-bold">Jual</label>
                            <input type="number" name="harga_jual" class="form-control" placeholder="Toko" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_titipan" class="btn btn-primary w-100">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>