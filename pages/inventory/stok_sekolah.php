<?php
$kategori_ini = 'seragam_sekolah';

// --- PROSES 1: TAMBAH STOK BARU (BARANG DATANG DARI KONVEKSI) ---
if(isset($_POST['tambah_stok_baru'])){
    $nama = $_POST['nama_barang'];
    $ukuran = $_POST['ukuran'];
    $stok = $_POST['stok'];
    $nominal = $_POST['nominal']; // Nilai barang (bukan harga jual retail)

    $kode = "SRG-" . strtoupper($ukuran) . "-" . time(); 

    // Kita simpan 'nominal' ke kolom harga_jual sebagai referensi nilai aset
    $sql = "INSERT INTO stok_barang (kode_barang, kategori, nama_barang, ukuran, stok, harga_modal, harga_jual) 
            VALUES (?, ?, ?, ?, ?, 0, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kode, $kategori_ini, $nama, $ukuran, $stok, $nominal]);

    echo "<script>alert('Stok Awal Berhasil Ditambahkan!'); window.location='inventory/stok_sekolah';</script>";
}

// --- PROSES 2: CATAT PENGAMBILAN SERAGAM (SISWA) ---
if(isset($_POST['catat_pengambilan'])){
    $barang_id = $_POST['barang_id'];
    $nama_siswa = $_POST['nama_siswa'];
    $kelas = $_POST['kelas'];
    $status = $_POST['status_bayar'];
    $jumlah = $_POST['jumlah_ambil'];
    $catatan = $_POST['catatan'];
    $tanggal = date('Y-m-d');

    // 1. Cek Stok Dulu
    $cek = $pdo->prepare("SELECT stok FROM stok_barang WHERE id = ?");
    $cek->execute([$barang_id]);
    $data_stok = $cek->fetch();

    if($data_stok['stok'] < $jumlah){
        echo "<script>alert('Gagal! Stok barang tidak cukup.'); window.location='inventory/stok_sekolah';</script>";
    } else {
        // 2. Kurangi Stok
        $kurang = $pdo->prepare("UPDATE stok_barang SET stok = stok - ? WHERE id = ?");
        $kurang->execute([$jumlah, $barang_id]);

        // 3. Catat di Riwayat Pengambilan (Bukan Kas)
        $log = $pdo->prepare("INSERT INTO riwayat_pengambilan (barang_id, nama_siswa, kelas, jumlah_ambil, status_bayar, catatan, tanggal_ambil) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $log->execute([$barang_id, $nama_siswa, $kelas, $jumlah, $status, $catatan, $tanggal]);

        echo "<script>alert('Pengambilan Berhasil Dicatat!'); window.location='inventory/stok_sekolah';</script>";
    }
}

// --- PROSES 3: UPDATE DATA BARANG (EDIT) ---
if(isset($_POST['update_barang'])){
    $id = $_POST['id_barang'];
    $stok_baru = $_POST['stok'];
    $nominal_baru = $_POST['harga_jual'];

    $sql = "UPDATE stok_barang SET stok=?, harga_jual=? WHERE id=?";
    $pdo->prepare($sql)->execute([$stok_baru, $nominal_baru, $id]);
    
    echo "<script>alert('Data Stok Diupdate!'); window.location='inventory/stok_sekolah';</script>";
}

// AMBIL DATA STOK
$data = $pdo->query("SELECT * FROM stok_barang WHERE kategori='$kategori_ini' ORDER BY nama_barang ASC, ukuran ASC")->fetchAll();

// AMBIL 10 RIWAYAT PENGAMBILAN TERAKHIR (JOIN TABEL)
$riwayat = $pdo->query("SELECT r.*, b.nama_barang, b.ukuran 
                        FROM riwayat_pengambilan r 
                        JOIN stok_barang b ON r.barang_id = b.id 
                        ORDER BY r.tanggal_ambil DESC, r.id DESC LIMIT 10")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Distribusi Seragam Sekolah</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus"></i> Stok Masuk (Baru)
    </button>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-boxes me-1"></i> Stok Gudang
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-sm">
                        <thead class="bg-light">
                            <tr>
                                <th>Barang</th>
                                <th class="text-center">Ukuran</th>
                                <th class="text-center">Sisa Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data as $row): 
                                $stok_class = ($row['stok'] < 5) ? 'bg-danger' : 'bg-success';
                            ?>
                            <tr>
                                <td class="fw-bold">
                                    <?= htmlspecialchars($row['nama_barang']) ?>
                                </td>
                                <td class="text-center"><span class="badge bg-secondary"><?= $row['ukuran'] ?></span></td>
                                <td class="text-center">
                                    <span class="badge <?= $stok_class ?> rounded-pill px-3"><?= $row['stok'] ?></span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAmbil<?= $row['id'] ?>" title="Catat Pengambilan Siswa">
                                        <i class="fas fa-hand-holding"></i> Ambil
                                    </button>
                                    
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>" title="Edit Stok Manual">
                                        <i class="fas fa-cog"></i>
                                    </button>

                                    <div class="modal fade" id="modalAmbil<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header bg-success text-white">
                                                        <h5 class="modal-title">Catat Pengambilan Seragam</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <input type="hidden" name="barang_id" value="<?= $row['id'] ?>">
                                                        
                                                        <div class="alert alert-light border mb-3">
                                                            <strong>Barang:</strong> <?= $row['nama_barang'] ?> (<?= $row['ukuran'] ?>)<br>
                                                            <strong>Sisa Stok:</strong> <?= $row['stok'] ?> pcs
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-8 mb-3">
                                                                <label class="form-label small fw-bold">Nama Siswa</label>
                                                                <input type="text" name="nama_siswa" class="form-control" required placeholder="Nama Lengkap">
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label small fw-bold">Kelas</label>
                                                                <input type="text" name="kelas" class="form-control" required placeholder="X-A">
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label small fw-bold">Jumlah Ambil</label>
                                                                <input type="number" name="jumlah_ambil" class="form-control" value="1" min="1" max="<?= $row['stok'] ?>" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label small fw-bold">Status Pembayaran</label>
                                                                <select name="status_bayar" class="form-select">
                                                                    <option value="Lunas">✅ Lunas (Ada Bukti)</option>
                                                                    <option value="Belum Lunas">❌ Belum Lunas (Hutang)</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Catatan Tambahan</label>
                                                            <textarea name="catatan" class="form-control" rows="2" placeholder="Contoh: Bukti Lunas No. 123 atau Janji bayar tgl sekian..."></textarea>
                                                        </div>

                                                        <button type="submit" name="catat_pengambilan" class="btn btn-success w-100">Simpan & Kurangi Stok</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title">Koreksi Stok</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <input type="hidden" name="id_barang" value="<?= $row['id'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label small">Stok Fisik Saat Ini</label>
                                                            <input type="number" name="stok" class="form-control" value="<?= $row['stok'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small">Nilai Barang (Rp)</label>
                                                            <input type="number" name="harga_jual" class="form-control" value="<?= $row['harga_jual'] ?>">
                                                        </div>
                                                        <button type="submit" name="update_barang" class="btn btn-primary w-100 btn-sm">Simpan Koreksi</button>
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

    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold text-primary">
                <i class="fas fa-history me-1"></i> Riwayat Pengambilan Terakhir
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach($riwayat as $r): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?= htmlspecialchars($r['nama_siswa']) ?></strong> <span class="text-muted small">(<?= htmlspecialchars($r['kelas']) ?>)</span>
                            <div class="small text-muted">
                                Mengambil: <?= $r['nama_barang'] ?> (<?= $r['ukuran'] ?>) - <b><?= $r['jumlah_ambil'] ?> pcs</b>
                            </div>
                            <?php if(!empty($r['catatan'])): ?>
                                <div class="small text-info fst-italic mt-1"><i class="fas fa-sticky-note"></i> "<?= htmlspecialchars($r['catatan']) ?>"</div>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <span class="badge <?= $r['status_bayar']=='Lunas'?'bg-success':'bg-danger' ?> rounded-pill">
                                <?= $r['status_bayar'] ?>
                            </span>
                            <div class="small text-muted mt-1"><?= date('d/m/y', strtotime($r['tanggal_ambil'])) ?></div>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
                <?php if(empty($riwayat)): ?>
                    <li class="list-group-item text-center text-muted py-4">Belum ada data pengambilan.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Input Stok Masuk (Konveksi)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jenis Seragam</label>
                        <input type="text" name="nama_barang" class="form-control" placeholder="Contoh: Kemeja Putih Pendek" required>
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
                            <label class="form-label fw-bold">Jumlah Masuk</label>
                            <input type="number" name="stok" class="form-control" placeholder="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nilai Barang (Rp)</label>
                        <input type="number" name="nominal" class="form-control" placeholder="Hanya untuk data aset (Opsional)" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_stok_baru" class="btn btn-primary">Simpan Stok</button>
                </div>
            </form>
        </div>
    </div>
</div>