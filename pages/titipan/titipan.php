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
    
    // Clean URL Redirect
    echo "<script>alert('Barang Berhasil Ditambahkan!'); window.location='titipan/titipan?guru_id=$anggota_id';</script>";
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
        // Clean URL Redirect
        echo "<script>window.location='titipan/titipan?guru_id=$guru_redirect';</script>";
    }
}

// --- FILTER DATA ---
$guru_id = isset($_GET['guru_id']) ? $_GET['guru_id'] : '';
$where_clause = "";
if($guru_id != '') {
    $where_clause = "WHERE t.anggota_id = '$guru_id'";
}

// QUERY DATA (Join Anggota)
$query = "SELECT t.*, a.nama_lengkap 
          FROM titipan t 
          JOIN anggota a ON t.anggota_id = a.id 
          $where_clause
          ORDER BY a.nama_lengkap ASC, t.nama_barang ASC";
$data = $pdo->query($query)->fetchAll();

// QUERY LIST GURU (Exclude Admin & Staff)
$anggota = $pdo->query("SELECT * FROM anggota 
                        WHERE role IN ('guru', 'pengurus') 
                        AND status_aktif = 1 
                        ORDER BY nama_lengkap ASC")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Inventory</h6>
        <h2 class="h3 fw-bold mb-0">Konsinyasi Guru</h2>
    </div>
    <button class="btn btn-primary shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTitip">
        <i class="fas fa-plus me-2"></i> Barang Baru
    </button>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light rounded-3 p-3">
        <form method="GET" action="titipan/titipan" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="fw-bold text-muted small text-uppercase"><i class="fas fa-filter me-2"></i>Filter Guru:</label>
            </div>
            <div class="col-auto flex-grow-1">
                <select name="guru_id" class="form-select border-0 shadow-sm" onchange="this.form.submit()">
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

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light text-secondary small">
                    <tr>
                        <?php if($guru_id == ''): ?>
                        <th class="ps-4">Pemilik</th>
                        <?php endif; ?>
                        <th class="<?= $guru_id != '' ? 'ps-4' : '' ?>">Barang</th>
                        <th class="text-center">Stok</th>
                        <th class="text-end">Hak Guru</th>
                        <th class="text-end">Laba Koperasi</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_hak_guru = 0;
                    $total_laba = 0;
                    
                    if(empty($data)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">Tidak ada data barang titipan.</td></tr>
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
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-2 bg-primary bg-opacity-10 text-primary" style="width:32px; height:32px; font-size:12px;">
                                    <?= strtoupper(substr($row['nama_lengkap'], 0, 1)) ?>
                                </div>
                                <span class="fw-bold text-dark small"><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                            </div>
                        </td>
                        <?php endif; ?>
                        
                        <td class="<?= $guru_id != '' ? 'ps-4' : '' ?>">
                            <span class="d-block text-dark fw-bold"><?= htmlspecialchars($row['nama_barang']) ?></span>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                Jual: <?= number_format($row['harga_jual']) ?> | Modal: <?= number_format($row['harga_modal']) ?>
                            </small>
                        </td>
                        
                        <td class="text-center">
                            <div class="d-inline-flex align-items-center bg-light rounded-pill px-3 py-1 border">
                                <span class="fw-bold text-secondary"><?= $row['stok_awal'] ?></span>
                                <i class="fas fa-arrow-right text-muted mx-2" style="font-size: 0.7rem;"></i>
                                <span class="fw-bold <?= $sisa == 0 ? 'text-danger' : 'text-success' ?>"><?= $sisa ?></span>
                            </div>
                        </td>
                        
                        <td class="text-end">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem;">Setor</small>
                            <span class="text-dark fw-bold"><?= formatRp($hak_guru) ?></span>
                        </td>
                        
                        <td class="text-end">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem;">Profit</small>
                            <span class="text-success fw-bold"><?= formatRp($laba) ?></span>
                        </td>
                        
                        <td class="text-center pe-4">
                            <button class="btn btn-sm btn-light text-primary shadow-sm rounded-circle" style="width: 32px; height: 32px;" data-bs-toggle="modal" data-bs-target="#modalUpdate<?= $row['id'] ?>" title="Update Stok">
                                <i class="fas fa-pen fa-xs"></i>
                            </button>
                            
                            <div class="modal fade" id="modalUpdate<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                        <form method="POST">
                                            <div class="modal-body p-4 text-start">
                                                <h6 class="fw-bold mb-3">Update Terjual</h6>
                                                <input type="hidden" name="titipan_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="guru_id_redirect" value="<?= $guru_id ?>">
                                                
                                                <div class="alert alert-info py-2 small mb-3 border-0 bg-info bg-opacity-10 text-info">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Stok Awal:</span> <strong><?= $row['stok_awal'] ?></strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Sisa Fisik:</span> <strong><?= $sisa ?></strong>
                                                    </div>
                                                </div>

                                                <label class="form-label small fw-bold text-muted text-uppercase">Total Laku (Akumulasi)</label>
                                                <input type="number" name="stok_terjual" class="form-control bg-light border-0 fw-bold" value="<?= $row['stok_terjual'] ?>" max="<?= $row['stok_awal'] ?>" required>
                                                <div class="form-text small mt-2">Masukkan jumlah <u>total</u> barang yang laku sampai hari ini.</div>
                                                
                                                <button type="submit" name="update_stok" class="btn btn-primary w-100 btn-sm fw-bold mt-3 py-2">Simpan Perubahan</button>
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
        
        <div class="card-footer bg-white border-top p-4">
            <div class="row text-center">
                <div class="col-6 border-end">
                    <small class="text-muted d-block text-uppercase fw-bold ls-1">Total Hak Guru</small>
                    <span class="fw-bold text-dark fs-4"><?= formatRp($total_hak_guru) ?></span>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block text-uppercase fw-bold ls-1">Total Laba Koperasi</small>
                    <span class="fw-bold text-success fs-4"><?= formatRp($total_laba) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTitip" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-box me-2"></i> Barang Titipan Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase">Pemilik Barang</label>
                        <select name="anggota_id" class="form-select bg-light border-0" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach($anggota as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= $guru_id == $a['id'] ? 'selected' : '' ?>>
                                <?= $a['nama_lengkap'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase">Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-control bg-light border-0" placeholder="Contoh: Keripik Pisang" required>
                    </div>
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="small fw-bold text-muted text-uppercase">Stok</label>
                            <input type="number" name="stok" class="form-control bg-light border-0 fw-bold" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="small fw-bold text-muted text-uppercase">Modal</label>
                            <input type="number" name="harga_modal" class="form-control bg-light border-0" placeholder="Setor" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="small fw-bold text-muted text-uppercase">Jual</label>
                            <input type="number" name="harga_jual" class="form-control bg-light border-0" placeholder="Toko" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="submit" name="tambah_titipan" class="btn btn-primary w-100 fw-bold py-2">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>