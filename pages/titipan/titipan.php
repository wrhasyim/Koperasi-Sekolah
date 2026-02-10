<?php
// --- 1. LOGIKA TAMBAH BARANG BARU ---
if(isset($_POST['tambah_titipan'])){
    $id_guru    = $_POST['anggota_id'];
    $nama_brg   = htmlspecialchars($_POST['nama_barang']);
    $modal      = (float) $_POST['harga_modal'];
    $jual       = (float) $_POST['harga_jual'];
    $stok       = (int) $_POST['stok_awal'];
    
    if($modal >= $jual) {
        setFlash('danger', 'Harga Modal harus lebih kecil dari Harga Jual!');
    } else {
        $stmt = $pdo->prepare("INSERT INTO titipan (anggota_id, nama_barang, harga_modal, harga_jual, stok_awal, stok_terjual, tanggal_titip) VALUES (?, ?, ?, ?, ?, 0, CURDATE())");
        $stmt->execute([$id_guru, $nama_brg, $modal, $jual, $stok]);
        setFlash('success', 'Barang titipan baru berhasil ditambahkan.');
        echo "<script>window.location='titipan/titipan';</script>";
    }
}

// --- 2. LOGIKA RESTOCK (ISI ULANG) ---
if(isset($_POST['restock_barang'])){
    $id_titipan  = $_POST['id_titipan'];
    $tambah_stok = (int) $_POST['tambah_stok'];

    if($tambah_stok > 0){
        $pdo->prepare("UPDATE titipan SET stok_awal = stok_awal + ?, tanggal_titip = CURDATE() WHERE id = ?")
            ->execute([$tambah_stok, $id_titipan]);
        setFlash('success', "Stok berhasil ditambahkan +$tambah_stok.");
        echo "<script>window.location='titipan/titipan';</script>";
    }
}

// --- 3. LOGIKA RETUR / PENARIKAN BARANG (BARU) ---
if(isset($_POST['tarik_barang'])){
    $id_titipan   = $_POST['id_titipan'];
    $jumlah_tarik = (int) $_POST['jumlah_tarik'];
    $stok_sisa    = (int) $_POST['stok_sisa_hidden'];
    $alasan       = $_POST['alasan']; // Opsional (Basi/Ditarik)

    if($jumlah_tarik > $stok_sisa){
        setFlash('danger', "Gagal! Jumlah penarikan ($jumlah_tarik) melebihi sisa stok yang ada ($stok_sisa).");
    } else {
        // Kurangi Stok Awal (Tanpa mempengaruhi keuangan)
        $pdo->prepare("UPDATE titipan SET stok_awal = stok_awal - ? WHERE id = ?")
            ->execute([$jumlah_tarik, $id_titipan]);
        
        setFlash('warning', "Berhasil menarik <b>$jumlah_tarik pcs</b> barang dari gudang. (Alasan: $alasan)");
        echo "<script>window.location='titipan/titipan';</script>";
    }
}

// --- 4. LOGIKA CEK BARANG (INPUT SISA FISIK) ---
if(isset($_POST['update_sisa'])){
    $id_titipan = $_POST['id_titipan'];
    $sisa_fisik = (int) $_POST['sisa_fisik'];
    $stok_awal  = (int) $_POST['stok_awal_hidden'];

    if($sisa_fisik > $stok_awal){
        setFlash('danger', "Error: Sisa fisik ($sisa_fisik) melebihi catatan stok awal ($stok_awal).");
    } else {
        $terjual = $stok_awal - $sisa_fisik;
        $pdo->prepare("UPDATE titipan SET stok_terjual = ? WHERE id = ?")->execute([$terjual, $id_titipan]);
        setFlash('success', "Data diupdate. Terhitung laku: <b>$terjual pcs</b>");
        echo "<script>window.location='titipan/titipan';</script>";
    }
}

// --- 5. LOGIKA PEMBAYARAN (SETOR KE GURU) ---
if(isset($_POST['bayar_guru'])){
    $id_titipan = $_POST['id_titipan_bayar'];
    $nama_guru  = $_POST['nama_guru_bayar'];
    $nama_brg   = $_POST['nama_barang_bayar'];
    $jml_terjual= (int) $_POST['jml_terjual_bayar'];
    $harga_modal= (float) $_POST['harga_modal_bayar']; 
    
    $total_bayar = $jml_terjual * $harga_modal;
    $tanggal     = date('Y-m-d');
    $user_id     = $_SESSION['user']['id'];

    if($total_bayar > 0){
        try {
            $pdo->beginTransaction();

            $ket = "Setor Titipan: $nama_brg ($jml_terjual pcs) - $nama_guru";
            $sql_kas = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                        VALUES (?, 'pembayaran_titipan', 'keluar', ?, ?, ?)";
            $pdo->prepare($sql_kas)->execute([$tanggal, $total_bayar, $ket, $user_id]);

            $pdo->prepare("UPDATE titipan SET stok_awal = stok_awal - stok_terjual, stok_terjual = 0 WHERE id = ?")
                ->execute([$id_titipan]);

            $pdo->commit();
            setFlash('success', "Pembayaran <b>Rp ".number_format($total_bayar)."</b> ke $nama_guru berhasil.");
            echo "<script>window.location='titipan/titipan?tab=bayar';</script>"; 

        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// --- VIEW DATA ---
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'stok';
$guru = $pdo->query("SELECT * FROM anggota WHERE role IN ('guru', 'staff') ORDER BY nama_lengkap ASC")->fetchAll();

// Data Stok
$data_stok = $pdo->query("SELECT t.*, a.nama_lengkap as nama_guru FROM titipan t JOIN anggota a ON t.anggota_id = a.id ORDER BY a.nama_lengkap ASC")->fetchAll();

// Data Tagihan
$data_bayar = $pdo->query("SELECT t.*, a.nama_lengkap as nama_guru FROM titipan t JOIN anggota a ON t.anggota_id = a.id WHERE t.stok_terjual > 0 ORDER BY a.nama_lengkap ASC")->fetchAll();

// Total Kewajiban
$total_tagihan_pending = 0;
foreach($data_bayar as $d) $total_tagihan_pending += ($d['stok_terjual'] * $d['harga_modal']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Inventory</h6>
        <h2 class="h3 fw-bold mb-0">Titipan Guru</h2>
    </div>
    
    <?php if($tab == 'stok'): ?>
    <button class="btn btn-primary shadow-sm rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus me-2"></i> Barang Baru
    </button>
    <?php endif; ?>
</div>

<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border">
    <li class="nav-item">
        <a class="nav-link <?= $tab=='stok' ? 'active fw-bold' : 'text-dark' ?>" href="titipan/titipan?tab=stok">
            <i class="fas fa-boxes me-2"></i> Cek Stok Fisik
        </a>
    </li>
    <li class="nav-item position-relative">
        <a class="nav-link <?= $tab=='bayar' ? 'active fw-bold bg-success text-white' : 'text-dark' ?>" href="titipan/titipan?tab=bayar">
            <i class="fas fa-money-bill-wave me-2"></i> Pembayaran
            <?php if($total_tagihan_pending > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= count($data_bayar) ?>
                </span>
            <?php endif; ?>
        </a>
    </li>
</ul>

<?php if($tab == 'stok'): ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-clipboard-list me-2"></i> Data Barang Titipan</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Barang & Pemilik</th>
                        <th class="text-center">Sisa Stok</th>
                        <th class="text-center bg-warning bg-opacity-10">Laku</th>
                        <th class="text-center pe-4">Aksi Gudang</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data_stok)): ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada barang.</td></tr>
                    <?php endif; 
                    foreach($data_stok as $row): 
                        $sisa_sekarang = $row['stok_awal'] - $row['stok_terjual'];
                    ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['nama_barang']) ?></span>
                            <small class="text-muted"><i class="fas fa-user me-1"></i> <?= htmlspecialchars($row['nama_guru']) ?></small>
                        </td>
                        
                        <td class="text-center">
                            <?php if($sisa_sekarang > 0): ?>
                                <span class="fw-bold text-primary fs-5"><?= $sisa_sekarang ?></span>
                            <?php else: ?>
                                <span class="badge bg-danger">HABIS</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center bg-warning bg-opacity-10">
                            <?php if($row['stok_terjual'] > 0): ?>
                                <span class="text-success fw-bold">+<?= $row['stok_terjual'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center pe-4">
                            <div class="d-flex gap-2 justify-content-center">
                                <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalRestock<?= $row['id'] ?>" title="Isi Ulang">
                                    <i class="fas fa-plus-circle"></i>
                                </button>

                                <?php if($sisa_sekarang > 0): ?>
                                    <button class="btn btn-sm btn-outline-dark fw-bold px-3" data-bs-toggle="modal" data-bs-target="#modalCek<?= $row['id'] ?>" title="Hitung Sisa">
                                        <i class="fas fa-search me-1"></i> Cek
                                    </button>
                                    
                                    <button class="btn btn-sm btn-outline-danger fw-bold" data-bs-toggle="modal" data-bs-target="#modalRetur<?= $row['id'] ?>" title="Retur Barang Basi/Tarik">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="modal fade" id="modalRestock<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST">
                                            <div class="modal-header border-0 pb-0"><h6 class="modal-title fw-bold">Isi Ulang Stok</h6></div>
                                            <div class="modal-body text-center">
                                                <input type="hidden" name="id_titipan" value="<?= $row['id'] ?>">
                                                <small class="d-block text-muted mb-2"><?= $row['nama_barang'] ?></small>
                                                <input type="number" name="tambah_stok" class="form-control text-center border-primary fw-bold fs-4" placeholder="+ Qty" min="1" required>
                                            </div>
                                            <div class="modal-footer border-0 pt-0"><button type="submit" name="restock_barang" class="btn btn-primary w-100 btn-sm">Simpan</button></div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php if($sisa_sekarang > 0): ?>
                            <div class="modal fade" id="modalCek<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                        <form method="POST">
                                            <div class="modal-header bg-dark text-white border-0">
                                                <h6 class="modal-title fw-bold">Hitung Sisa Fisik</h6>
                                            </div>
                                            <div class="modal-body text-center p-4">
                                                <input type="hidden" name="id_titipan" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="stok_awal_hidden" value="<?= $row['stok_awal'] ?>">
                                                <p class="mb-1 small text-muted">Stok Tercatat (Awal)</p>
                                                <h2 class="fw-bold text-dark mb-3"><?= $row['stok_awal'] ?></h2>
                                                <label class="form-label fw-bold text-primary small">SISA DI RAK ADA BERAPA?</label>
                                                <input type="number" name="sisa_fisik" class="form-control form-control-lg text-center fw-bold border-primary" 
                                                       value="<?= $sisa_sekarang ?>" min="0" max="<?= $row['stok_awal'] ?>" required>
                                                <div class="form-text small mt-2">Sistem akan otomatis menghitung jumlah terjual.</div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                                                <button type="submit" name="update_sisa" class="btn btn-dark w-100 fw-bold rounded-pill">Simpan Data</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="modalRetur<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                        <form method="POST">
                                            <div class="modal-header bg-danger text-white border-0">
                                                <h6 class="modal-title fw-bold">Retur / Tarik Barang</h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="id_titipan" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="stok_sisa_hidden" value="<?= $sisa_sekarang ?>">
                                                
                                                <div class="alert alert-light border text-danger small mb-3">
                                                    <i class="fas fa-info-circle me-1"></i> Stok akan dikurangi tanpa mempengaruhi keuangan (karena barang dikembalikan/dibuang).
                                                </div>

                                                <div class="mb-2">
                                                    <label class="small fw-bold text-muted">Jumlah Ditarik</label>
                                                    <input type="number" name="jumlah_tarik" class="form-control text-center fw-bold border-danger" placeholder="0" min="1" max="<?= $sisa_sekarang ?>" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="small fw-bold text-muted">Alasan (Opsional)</label>
                                                    <select name="alasan" class="form-select text-center small">
                                                        <option value="Basi / Rusak">Basi / Rusak</option>
                                                        <option value="Ditarik Pemilik">Ditarik Pemilik</option>
                                                        <option value="Salah Input">Salah Input</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                                                <button type="submit" name="tarik_barang" class="btn btn-danger w-100 fw-bold rounded-pill">Konfirmasi Retur</button>
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
    </div>

<?php else: ?>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card bg-success text-white border-0 shadow-sm rounded-4">
                <div class="card-body p-4 text-center">
                    <h6 class="text-white-50 text-uppercase fw-bold small mb-2">Total Wajib Setor</h6>
                    <h2 class="mb-0 fw-bold"><?= formatRp($total_tagihan_pending) ?></h2>
                    <small>Uang tunai milik guru di laci kasir</small>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-success"><i class="fas fa-list me-2"></i> Daftar Barang Laku (Siap Bayar)</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Guru & Barang</th>
                                <th class="text-center">Laku</th>
                                <th class="text-end text-danger fw-bold pe-4">Nominal Setor</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($data_bayar)): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">Tidak ada tagihan pending.</td></tr>
                            <?php endif; 
                            foreach($data_bayar as $row): 
                                $bayar = $row['stok_terjual'] * $row['harga_modal'];
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold d-block text-dark"><?= htmlspecialchars($row['nama_guru']) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($row['nama_barang']) ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark fs-6"><?= $row['stok_terjual'] ?> pcs</span>
                                </td>
                                <td class="text-end text-danger fw-bold pe-4"><?= formatRp($bayar) ?></td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-sm btn-success fw-bold px-3 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBayar<?= $row['id'] ?>">
                                        Bayar
                                    </button>

                                    <div class="modal fade" id="modalBayar<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg rounded-4">
                                                <div class="modal-header bg-success text-white">
                                                    <h6 class="modal-title fw-bold">Konfirmasi Setor</h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body p-4 text-start">
                                                        <input type="hidden" name="id_titipan_bayar" value="<?= $row['id'] ?>">
                                                        <input type="hidden" name="nama_guru_bayar" value="<?= $row['nama_guru'] ?>">
                                                        <input type="hidden" name="nama_barang_bayar" value="<?= $row['nama_barang'] ?>">
                                                        <input type="hidden" name="jml_terjual_bayar" value="<?= $row['stok_terjual'] ?>">
                                                        <input type="hidden" name="harga_modal_bayar" value="<?= $row['harga_modal'] ?>">

                                                        <div class="text-center mb-3">
                                                            <small class="text-muted">Setorkan kepada <b><?= $row['nama_guru'] ?></b></small>
                                                            <h2 class="fw-bold text-success mt-1"><?= formatRp($bayar) ?></h2>
                                                            <div class="badge bg-light text-dark border mt-2"><?= $row['nama_barang'] ?> (Laku: <?= $row['stok_terjual'] ?>)</div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0 px-4 pb-4 justify-content-center">
                                                        <button type="submit" name="bayar_guru" class="btn btn-success w-100 rounded-pill fw-bold">Sudah Disetor</button>
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
        </div>
    </div>
<?php endif; ?>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold">Tambah Barang Titipan</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Pemilik Barang</label>
                        <select name="anggota_id" class="form-select" required>
                            <option value="">-- Pilih Guru/Staff --</option>
                            <?php foreach($guru as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= $g['nama_lengkap'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Harga Modal</label>
                            <input type="number" name="harga_modal" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Harga Jual</label>
                            <input type="number" name="harga_jual" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Stok Awal</label>
                        <input type="number" name="stok_awal" class="form-control" value="10" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="submit" name="tambah_titipan" class="btn btn-primary w-100 rounded-pill fw-bold">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>