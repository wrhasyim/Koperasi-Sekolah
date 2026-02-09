<?php
// --- PROSES PHP ---

// 1. TAMBAH BARANG BARU
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
    
    echo "<script>alert('Barang Baru Berhasil Ditambahkan!'); window.location='titipan/titipan?guru_id=$anggota_id';</script>";
}

// 2. RESTOCK BARANG
if(isset($_POST['restock_barang'])){
    $id = $_POST['titipan_id'];
    $guru_redirect = $_POST['guru_id_redirect'];
    $tambah_stok = $_POST['tambah_stok'];

    if($tambah_stok < 1){
        echo "<script>alert('Error: Jumlah restock minimal 1!'); window.location='titipan/titipan?guru_id=$guru_redirect';</script>";
    } else {
        $pdo->prepare("UPDATE titipan SET stok_awal = stok_awal + ? WHERE id = ?")->execute([$tambah_stok, $id]);
        echo "<script>alert('Restock Berhasil! Stok bertambah $tambah_stok pcs'); window.location='titipan/titipan?guru_id=$guru_redirect';</script>";
    }
}

// 3. UPDATE STOK (STOCK OPNAME)
if(isset($_POST['update_sisa_fisik'])){
    $id = $_POST['titipan_id'];
    $guru_redirect = $_POST['guru_id_redirect'];
    $sisa_fisik_baru = $_POST['sisa_fisik']; 
    
    $cek = $pdo->prepare("SELECT stok_awal, stok_terjual FROM titipan WHERE id=?");
    $cek->execute([$id]);
    $dt = $cek->fetch();
    
    $stok_awal = $dt['stok_awal'];
    $terjual_lama = $dt['stok_terjual'];
    $sisa_lama = $stok_awal - $terjual_lama;

    // Validasi
    if($sisa_fisik_baru > $stok_awal){
        echo "<script>alert('ERROR: Sisa fisik tidak boleh melebihi Stok Awal ($stok_awal)! Lakukan Restock jika barang bertambah.'); window.location='titipan/titipan?guru_id=$guru_redirect';</script>";
    } 
    elseif($sisa_fisik_baru > $sisa_lama){
        echo "<script>alert('ERROR: Sisa fisik bertambah (Dari $sisa_lama jadi $sisa_fisik_baru)? Gunakan tombol RESTOCK untuk menambah barang.'); window.location='titipan/titipan?guru_id=$guru_redirect';</script>";
    } 
    else {
        $terjual_baru = $stok_awal - $sisa_fisik_baru;
        $pdo->prepare("UPDATE titipan SET stok_terjual = ? WHERE id = ?")->execute([$terjual_baru, $id]);
        echo "<script>alert('Stock Opname Berhasil! Total Laku: $terjual_baru pcs'); window.location='titipan/titipan?guru_id=$guru_redirect';</script>";
    }
}

// --- QUERY DATA ---
$guru_id = isset($_GET['guru_id']) ? $_GET['guru_id'] : '';
$where_clause = "";
if($guru_id != '') { $where_clause = "WHERE t.anggota_id = '$guru_id'"; }

$query = "SELECT t.*, a.nama_lengkap 
          FROM titipan t 
          JOIN anggota a ON t.anggota_id = a.id 
          $where_clause
          ORDER BY a.nama_lengkap ASC, t.nama_barang ASC";
$data = $pdo->query($query)->fetchAll();

$anggota = $pdo->query("SELECT * FROM anggota WHERE role IN ('guru', 'pengurus') AND status_aktif = 1 ORDER BY nama_lengkap ASC")->fetchAll();
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
                        <?php if($guru_id == ''): ?><th class="ps-4">Pemilik</th><?php endif; ?>
                        <th class="<?= $guru_id != '' ? 'ps-4' : '' ?>">Barang</th>
                        <th class="text-center bg-warning bg-opacity-10 text-dark border-start border-end">SISA FISIK</th>
                        <th class="text-center">Stok Awal</th>
                        <th class="text-center">Terjual</th>
                        <th class="text-end">Wajib Setor</th>
                        <th class="text-center pe-4" style="min-width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_hak_guru = 0; $total_laba = 0;
                    if(empty($data)): ?><tr><td colspan="7" class="text-center py-5 text-muted">Tidak ada data barang titipan.</td></tr><?php endif;

                    foreach($data as $row): 
                        $sisa = $row['stok_awal'] - $row['stok_terjual'];
                        $hak_guru = $row['stok_terjual'] * $row['harga_modal'];
                        $laba = $row['stok_terjual'] * ($row['harga_jual'] - $row['harga_modal']);
                        $total_hak_guru += $hak_guru; $total_laba += $laba;
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
                                Modal: <?= number_format($row['harga_modal']) ?>
                            </small>
                        </td>
                        
                        <td class="text-center bg-warning bg-opacity-10 border-start border-end">
                            <?php if($sisa <= 0): ?>
                                <span class="badge bg-danger shadow-sm px-3">HABIS</span>
                            <?php else: ?>
                                <span class="fw-bold fs-5 text-dark"><?= $sisa ?></span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center text-muted"><?= $row['stok_awal'] ?></td>
                        <td class="text-center text-success fw-bold"><?= $row['stok_terjual'] ?></td>
                        <td class="text-end text-dark fw-bold"><?= formatRp($hak_guru) ?></td>
                        
                        <td class="text-center pe-4">
                            <div class="btn-group shadow-sm rounded-pill">
                                <button class="btn btn-sm btn-success px-3" data-bs-toggle="modal" data-bs-target="#modalRestock<?= $row['id'] ?>" title="Tambah Stok">
                                    <i class="fas fa-plus"></i>
                                </button>

                                <?php if($sisa > 0): ?>
                                <button class="btn btn-sm btn-outline-primary px-3" data-bs-toggle="modal" data-bs-target="#modalUpdate<?= $row['id'] ?>" title="Stock Opname">
                                    <i class="fas fa-check"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="modal fade" id="modalRestock<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                        <form method="POST">
                                            <div class="modal-body p-4 text-start">
                                                <h6 class="fw-bold mb-3 text-center text-success"><i class="fas fa-plus-circle me-1"></i> Restock Barang</h6>
                                                <input type="hidden" name="titipan_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="guru_id_redirect" value="<?= $guru_id ?>">
                                                
                                                <p class="small text-center text-muted mb-2">
                                                    Menambah stok <strong><?= htmlspecialchars($row['nama_barang']) ?></strong>
                                                </p>

                                                <div class="bg-success bg-opacity-10 p-3 rounded-3 mb-3 border border-success border-opacity-25">
                                                    <label class="form-label small fw-bold text-success mb-1">Jumlah Masuk:</label>
                                                    <input type="number" name="tambah_stok" class="form-control form-control-lg fw-bold text-center border-0 shadow-sm text-success" placeholder="0" min="1" required>
                                                </div>
                                                
                                                <button type="submit" name="restock_barang" class="btn btn-success w-100 fw-bold py-2 rounded-3">Simpan Restock</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php if($sisa > 0): ?>
                            <div class="modal fade" id="modalUpdate<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                        <form method="POST">
                                            <div class="modal-body p-4 text-start">
                                                <h6 class="fw-bold mb-3 text-center text-primary"><i class="fas fa-clipboard-check me-1"></i> Stock Opname</h6>
                                                <input type="hidden" name="titipan_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="guru_id_redirect" value="<?= $guru_id ?>">
                                                
                                                <div class="text-center mb-3">
                                                    <span class="d-block small text-muted text-uppercase">Stok Awal</span>
                                                    <span class="fs-1 fw-bold text-dark"><?= $row['stok_awal'] ?></span>
                                                </div>

                                                <div class="alert <?= $sisa==0 ? 'alert-danger' : 'alert-info' ?> py-2 small text-center mb-3">
                                                    Sisa Terakhir: <strong><?= $sisa==0 ? 'HABIS (0)' : $sisa . ' pcs' ?></strong>
                                                </div>

                                                <div class="bg-light p-3 rounded-3 mb-3 border">
                                                    <label class="form-label small fw-bold text-dark mb-1">Sisa Fisik di Rak:</label>
                                                    <input type="number" name="sisa_fisik" class="form-control form-control-lg fw-bold text-center border-0 shadow-sm" value="<?= $sisa ?>" min="0" max="<?= $row['stok_awal'] ?>" required>
                                                </div>
                                                
                                                <button type="submit" name="update_sisa_fisik" class="btn btn-primary w-100 fw-bold py-2 rounded-3">Update Sisa</button>
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
            </table>
        </div>
        
        <div class="card-footer bg-white border-top p-4">
            <div class="row text-center">
                <div class="col-6 border-end">
                    <small class="text-muted d-block text-uppercase fw-bold ls-1">Total Wajib Setor</small>
                    <span class="fw-bold text-dark fs-3"><?= formatRp($total_hak_guru) ?></span>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block text-uppercase fw-bold ls-1">Estimasi Laba</small>
                    <span class="fw-bold text-success fs-3"><?= formatRp($total_laba) ?></span>
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
                    <h5 class="modal-title fw-bold">Barang Masuk Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Pemilik Barang</label>
                        <select name="anggota_id" class="form-select bg-light border-0" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach($anggota as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= $guru_id == $a['id'] ? 'selected' : '' ?>><?= $a['nama_lengkap'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-control bg-light border-0" placeholder="Contoh: Keripik Pisang" required>
                    </div>
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="small fw-bold text-muted">Stok Awal</label>
                            <input type="number" name="stok" class="form-control bg-light border-0 fw-bold" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="small fw-bold text-muted">Modal</label>
                            <input type="number" name="harga_modal" class="form-control bg-light border-0" placeholder="Setor" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="small fw-bold text-muted">Jual</label>
                            <input type="number" name="harga_jual" class="form-control bg-light border-0" placeholder="Toko" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="submit" name="tambah_titipan" class="btn btn-primary w-100 fw-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>