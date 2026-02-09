<?php
// Tentukan Tab Aktif (Default: Sihara)
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'sihara';

// --- LOGIKA PHP UNTUK SEMUA SIMPANAN ---

// 1. PROSES SIHARA
if(isset($_POST['simpan_sihara'])){
    $anggota_id = $_POST['anggota_id']; $jumlah = $_POST['jumlah']; $tipe = $_POST['tipe']; $ket = $_POST['keterangan']; $tanggal = date('Y-m-d');
    if(cekStatusPeriode($pdo, $tanggal)){
        echo "<script>alert('GAGAL! Periode Tutup Buku.'); window.location='simpanan/transaksi_simpanan?tab=sihara';</script>";
    } elseif($jumlah > 0){
        $pdo->prepare("INSERT INTO simpanan (anggota_id, tanggal, jenis_simpanan, jumlah, tipe_transaksi, keterangan) VALUES (?, ?, 'hari_raya', ?, ?, ?)")->execute([$anggota_id, $tanggal, $jumlah, $tipe, $ket]);
        echo "<script>alert('Transaksi Sihara Berhasil!'); window.location='simpanan/transaksi_simpanan?tab=sihara';</script>";
    }
}

// 2. PROSES SIMJIB (WAJIB)
if(isset($_POST['simpan_simjib'])){
    $anggota_id = $_POST['anggota_id']; $jumlah = $_POST['jumlah']; $ket = $_POST['keterangan']; $tanggal = date('Y-m-d');
    if(cekStatusPeriode($pdo, $tanggal)){
        echo "<script>alert('GAGAL! Periode Tutup Buku.'); window.location='simpanan/transaksi_simpanan?tab=simjib';</script>";
    } elseif($jumlah > 0){
        $pdo->prepare("INSERT INTO simpanan (anggota_id, tanggal, jenis_simpanan, jumlah, tipe_transaksi, keterangan) VALUES (?, ?, 'wajib', ?, 'setor', ?)")->execute([$anggota_id, $tanggal, $jumlah, $ket]);
        echo "<script>alert('Simpanan Wajib Berhasil!'); window.location='simpanan/transaksi_simpanan?tab=simjib';</script>";
    }
}

// 3. PROSES SIMPOK (POKOK)
if(isset($_POST['simpan_simpok'])){
    $anggota_id = $_POST['anggota_id']; $jumlah = $_POST['jumlah']; $ket = $_POST['keterangan']; $tanggal = date('Y-m-d');
    $cek = $pdo->prepare("SELECT id FROM simpanan WHERE anggota_id = ? AND jenis_simpanan = 'pokok'"); $cek->execute([$anggota_id]);
    
    if($cek->rowCount() > 0){
        echo "<script>alert('Gagal! Sudah pernah bayar.'); window.location='simpanan/transaksi_simpanan?tab=simpok';</script>";
    } elseif($jumlah > 0){
        $pdo->prepare("INSERT INTO simpanan (anggota_id, tanggal, jenis_simpanan, jumlah, tipe_transaksi, keterangan) VALUES (?, ?, 'pokok', ?, 'setor', ?)")->execute([$anggota_id, $tanggal, $jumlah, $ket]);
        echo "<script>alert('Simpanan Pokok Berhasil!'); window.location='simpanan/transaksi_simpanan?tab=simpok';</script>";
    }
}

// --- QUERY DATA (FILTER ADMIN & STAFF) ---

// 1. List Anggota Umum (Untuk Sihara & Simjib) - Exclude Admin & Staff
$anggota_umum = $pdo->query("SELECT * FROM anggota 
                             WHERE role NOT IN ('admin', 'staff') 
                             AND status_aktif = 1 
                             ORDER BY nama_lengkap ASC")->fetchAll();

// 2. List Anggota Khusus Simpok (Belum Bayar) - Exclude Admin & Staff
$anggota_simpok = $pdo->query("SELECT * FROM anggota 
                               WHERE id NOT IN (SELECT anggota_id FROM simpanan WHERE jenis_simpanan = 'pokok') 
                               AND role NOT IN ('admin', 'staff') 
                               AND status_aktif = 1 
                               ORDER BY nama_lengkap ASC")->fetchAll();

// 3. History Transaksi (Limit 10 per jenis)
$hist_sihara = $pdo->query("SELECT s.*, a.nama_lengkap FROM simpanan s JOIN anggota a ON s.anggota_id = a.id WHERE s.jenis_simpanan = 'hari_raya' ORDER BY s.tanggal DESC LIMIT 10")->fetchAll();
$hist_simjib = $pdo->query("SELECT s.*, a.nama_lengkap FROM simpanan s JOIN anggota a ON s.anggota_id = a.id WHERE s.jenis_simpanan = 'wajib' ORDER BY s.tanggal DESC LIMIT 10")->fetchAll();
$hist_simpok = $pdo->query("SELECT s.*, a.nama_lengkap FROM simpanan s JOIN anggota a ON s.anggota_id = a.id WHERE s.jenis_simpanan = 'pokok' ORDER BY s.tanggal DESC")->fetchAll();
?>

<div class="row align-items-center mb-4">
    <div class="col">
        <h6 class="text-uppercase text-muted small ls-1 mb-1">Keuangan Anggota</h6>
        <h2 class="h3 fw-bold mb-0">Transaksi Simpanan</h2>
    </div>
</div>

<ul class="nav nav-pills nav-fill mb-4 p-1 bg-white shadow-sm rounded-pill border" id="pills-tab" role="tablist" style="max-width: 600px;">
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill fw-bold <?= $active_tab=='sihara'?'active':'' ?>" id="tab-sihara" data-bs-toggle="pill" data-bs-target="#content-sihara" type="button" role="tab">
            <i class="fas fa-wallet me-2"></i> SIHARA
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill fw-bold <?= $active_tab=='simjib'?'active':'' ?>" id="tab-simjib" data-bs-toggle="pill" data-bs-target="#content-simjib" type="button" role="tab">
            <i class="fas fa-file-invoice-dollar me-2"></i> WAJIB
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill fw-bold <?= $active_tab=='simpok'?'active':'' ?>" id="tab-simpok" data-bs-toggle="pill" data-bs-target="#content-simpok" type="button" role="tab">
            <i class="fas fa-coins me-2"></i> POKOK
        </button>
    </li>
</ul>

<div class="tab-content" id="pills-tabContent">
    
    <div class="tab-pane fade <?= $active_tab=='sihara'?'show active':'' ?>" id="content-sihara" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-lg overflow-hidden">
                    <div class="card-body bg-gradient-primary text-white p-4">
                        <div class="mb-4 text-center">
                            <div class="bg-white bg-opacity-25 rounded-circle p-3 d-inline-block mb-3"><i class="fas fa-wallet fa-3x"></i></div>
                            <h4 class="fw-bold">Input Sihara</h4>
                            <p class="text-white-50 small">Simpanan Hari Raya (Sukarela)</p>
                        </div>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small text-white-50 fw-bold text-uppercase">Anggota</label>
                                <select name="anggota_id" class="form-select border-0 bg-white bg-opacity-10 text-white" required>
                                    <option value="" class="text-dark">-- Pilih Anggota --</option>
                                    <?php foreach($anggota_umum as $a): ?>
                                    <option value="<?= $a['id'] ?>" class="text-dark"><?= $a['nama_lengkap'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small text-white-50 fw-bold text-uppercase">Jenis</label>
                                <select name="tipe" class="form-select border-0 bg-white bg-opacity-10 text-white">
                                    <option value="setor" class="text-dark">Setor (Menabung)</option>
                                    <option value="tarik" class="text-dark">Tarik (Ambil THR)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small text-white-50 fw-bold text-uppercase">Nominal</label>
                                <input type="number" name="jumlah" class="form-control border-0 bg-white text-primary fw-bold" placeholder="0" required>
                            </div>
                            <div class="mb-4">
                                <label class="small text-white-50 fw-bold text-uppercase">Ket</label>
                                <input type="text" name="keterangan" class="form-control border-0 bg-white bg-opacity-10 text-white" placeholder="Opsional">
                            </div>
                            <button type="submit" name="simpan_sihara" class="btn btn-light w-100 py-3 text-primary fw-bold shadow-sm">Proses Transaksi</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold"><i class="fas fa-history me-2 text-primary"></i> Riwayat Sihara Terakhir</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light"><tr><th>Tanggal</th><th>Nama</th><th>Ket</th><th class="text-end">Jumlah</th></tr></thead>
                            <tbody>
                                <?php foreach($hist_sihara as $r): $masuk = ($r['tipe_transaksi'] == 'setor'); ?>
                                <tr>
                                    <td><?= tglIndo($r['tanggal']) ?></td>
                                    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($r['keterangan']) ?></td>
                                    <td class="text-end fw-bold <?= $masuk ? 'text-success' : 'text-danger' ?>">
                                        <?= $masuk ? '+' : '-' ?> <?= number_format($r['jumlah']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?= $active_tab=='simjib'?'show active':'' ?>" id="content-simjib" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-lg overflow-hidden">
                    <div class="card-body bg-gradient-success text-white p-4">
                        <div class="mb-4 text-center">
                            <div class="bg-white bg-opacity-25 rounded-circle p-3 d-inline-block mb-3"><i class="fas fa-file-invoice-dollar fa-3x"></i></div>
                            <h4 class="fw-bold">Input Simjib</h4>
                            <p class="text-white-50 small">Simpanan Wajib Bulanan</p>
                        </div>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small text-white-50 fw-bold text-uppercase">Anggota</label>
                                <select name="anggota_id" class="form-select border-0 bg-white bg-opacity-10 text-white" required>
                                    <option value="" class="text-dark">-- Pilih Anggota --</option>
                                    <?php foreach($anggota_umum as $a): ?>
                                    <option value="<?= $a['id'] ?>" class="text-dark"><?= $a['nama_lengkap'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small text-white-50 fw-bold text-uppercase">Nominal</label>
                                <input type="number" name="jumlah" class="form-control border-0 bg-white text-success fw-bold" placeholder="50000" required>
                            </div>
                            <div class="mb-4">
                                <label class="small text-white-50 fw-bold text-uppercase">Ket</label>
                                <input type="text" name="keterangan" class="form-control border-0 bg-white bg-opacity-10 text-white" placeholder="Cth: Iuran Januari">
                            </div>
                            <button type="submit" name="simpan_simjib" class="btn btn-light w-100 py-3 text-success fw-bold shadow-sm">Simpan Wajib</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold"><i class="fas fa-history me-2 text-success"></i> Riwayat Simjib Terakhir</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light"><tr><th>Tanggal</th><th>Nama</th><th>Ket</th><th class="text-end">Jumlah</th></tr></thead>
                            <tbody>
                                <?php foreach($hist_simjib as $r): ?>
                                <tr>
                                    <td><?= tglIndo($r['tanggal']) ?></td>
                                    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($r['keterangan']) ?></td>
                                    <td class="text-end fw-bold text-success">+ <?= number_format($r['jumlah']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?= $active_tab=='simpok'?'show active':'' ?>" id="content-simpok" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-lg overflow-hidden">
                    <div class="card-body bg-gradient-warning text-white p-4">
                        <div class="mb-4 text-center">
                            <div class="bg-white bg-opacity-25 rounded-circle p-3 d-inline-block mb-3"><i class="fas fa-coins fa-3x"></i></div>
                            <h4 class="fw-bold">Input Simpok</h4>
                            <p class="text-white-50 small">Simpanan Pokok (Sekali di Awal)</p>
                        </div>
                        <?php if(count($anggota_simpok) > 0): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small text-white-50 fw-bold text-uppercase">Anggota Baru</label>
                                <select name="anggota_id" class="form-select border-0 bg-white bg-opacity-10 text-white" required>
                                    <option value="" class="text-dark">-- Pilih Anggota --</option>
                                    <?php foreach($anggota_simpok as $a): ?>
                                    <option value="<?= $a['id'] ?>" class="text-dark"><?= $a['nama_lengkap'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small text-white-50 fw-bold text-uppercase">Nominal</label>
                                <input type="number" name="jumlah" class="form-control border-0 bg-white text-warning fw-bold" value="100000" required>
                            </div>
                            <div class="mb-4">
                                <label class="small text-white-50 fw-bold text-uppercase">Ket</label>
                                <input type="text" name="keterangan" class="form-control border-0 bg-white bg-opacity-10 text-white" placeholder="Uang Pangkal">
                            </div>
                            <button type="submit" name="simpan_simpok" class="btn btn-light w-100 py-3 text-warning fw-bold shadow-sm">Simpan Pokok</button>
                        </form>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-2x text-white mb-2"></i>
                                <p>Semua anggota aktif sudah lunas Simpok.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold"><i class="fas fa-history me-2 text-warning"></i> Anggota Sudah Bayar</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light"><tr><th>Tanggal</th><th>Nama</th><th class="text-end">Jumlah</th><th class="text-center">Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach($hist_simpok as $r): ?>
                                <tr>
                                    <td><?= tglIndo($r['tanggal']) ?></td>
                                    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                                    <td class="text-end fw-bold text-warning">+ <?= number_format($r['jumlah']) ?></td>
                                    <td class="text-center">
                                        <a href="process/simpanan_hapus.php?id=<?= $r['id'] ?>&redirect=simpanan/transaksi_simpanan?tab=simpok" class="btn btn-sm btn-light text-danger" onclick="return confirm('Hapus?')" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>