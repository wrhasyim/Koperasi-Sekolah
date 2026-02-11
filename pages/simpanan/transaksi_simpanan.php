<?php
// pages/simpanan/transaksi_simpanan.php
require_once 'config/database.php';

// Tentukan Tab Aktif
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'sihara';

// Helper lokal untuk ambil nama (jika fungsi global hilang)
function getNamaAnggotaLocal($pdo, $id){
    $stmt = $pdo->query("SELECT nama_lengkap FROM anggota WHERE id = $id");
    return $stmt->fetchColumn() ?: 'Anggota';
}

// --- LOGIKA PHP UNTUK SEMUA SIMPANAN ---

// 1. PROSES SIHARA
if(isset($_POST['simpan_sihara'])){
    try {
        $anggota_id = $_POST['anggota_id']; 
        $jumlah = $_POST['jumlah']; 
        $tipe = $_POST['tipe']; 
        $ket = $_POST['keterangan']; 
        $tanggal = date('Y-m-d');
        
        // Cek Tutup Buku
        if(function_exists('cekStatusPeriode') && cekStatusPeriode($pdo, $tanggal)){
            echo "<script>alert('GAGAL! Periode ini sudah Tutup Buku.'); window.location='index.php?page=simpanan/transaksi_simpanan&tab=sihara';</script>";
            exit;
        }

        if($jumlah > 0){
            $pdo->beginTransaction();
            
            // A. Masuk Tabel Simpanan (Pencatatan Pribadi Anggota)
            $pdo->prepare("INSERT INTO simpanan (anggota_id, tanggal, jenis_simpanan, jumlah, tipe_transaksi, keterangan) VALUES (?, ?, 'hari_raya', ?, ?, ?)")
                ->execute([$anggota_id, $tanggal, $jumlah, $tipe, $ket]);
            
            // B. Masuk Tabel Kas (PENTING: Agar Uang Masuk Laporan Keuangan)
            $arus = ($tipe == 'setor') ? 'masuk' : 'keluar';
            $kat_kas = ($tipe == 'setor') ? 'simpanan_masuk' : 'simpanan_keluar';
            $nama = getNamaAnggotaLocal($pdo, $anggota_id);
            $ket_kas = ucfirst($tipe) . " Sihara: " . $nama . " (" . $ket . ")";
            
            $pdo->prepare("INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (NOW(), ?, ?, ?, ?, ?)")
                ->execute([$kat_kas, $arus, $jumlah, $ket_kas, $_SESSION['user']['id']]);

            $pdo->commit();
            echo "<script>alert('Transaksi Sihara Berhasil!'); window.location='index.php?page=simpanan/transaksi_simpanan&tab=sihara';</script>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: ".$e->getMessage()."'); window.location='index.php?page=simpanan/transaksi_simpanan&tab=sihara';</script>";
    }
}

// 2. PROSES SIMJIB (WAJIB)
if(isset($_POST['simpan_simjib'])){
    try {
        $anggota_id = $_POST['anggota_id']; $jumlah = $_POST['jumlah']; $ket = $_POST['keterangan']; $tanggal = date('Y-m-d');
        
        if(function_exists('cekStatusPeriode') && cekStatusPeriode($pdo, $tanggal)){
            echo "<script>alert('GAGAL! Periode Tutup Buku.'); window.location='index.php?page=simpanan/transaksi_simpanan&tab=simjib';</script>";
            exit;
        }

        if($jumlah > 0){
            $pdo->beginTransaction();
            // Tabel Simpanan
            $pdo->prepare("INSERT INTO simpanan (anggota_id, tanggal, jenis_simpanan, jumlah, tipe_transaksi, keterangan) VALUES (?, ?, 'wajib', ?, 'setor', ?)")
                ->execute([$anggota_id, $tanggal, $jumlah, $ket]);
            
            // Tabel Kas
            $nama = getNamaAnggotaLocal($pdo, $anggota_id);
            $ket_kas = "Setor Simjib: " . $nama . " (" . $ket . ")";
            $pdo->prepare("INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (NOW(), 'simpanan_masuk', 'masuk', ?, ?, ?)")
                ->execute([$jumlah, $ket_kas, $_SESSION['user']['id']]);

            $pdo->commit();
            echo "<script>alert('Simpanan Wajib Berhasil!'); window.location='index.php?page=simpanan/transaksi_simpanan&tab=simjib';</script>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: ".$e->getMessage()."');</script>";
    }
}

// 3. PROSES SIMPOK (POKOK)
if(isset($_POST['simpan_simpok'])){
    try {
        $anggota_id = $_POST['anggota_id']; $jumlah = $_POST['jumlah']; $ket = $_POST['keterangan']; $tanggal = date('Y-m-d');
        
        // Cek Duplikat Simpok
        $cek = $pdo->prepare("SELECT id FROM simpanan WHERE anggota_id = ? AND jenis_simpanan = 'pokok'"); 
        $cek->execute([$anggota_id]);
        
        if($cek->rowCount() > 0){
            echo "<script>alert('Gagal! Anggota ini sudah lunas Simpanan Pokok.'); window.location='index.php?page=simpanan/transaksi_simpanan&tab=simpok';</script>";
        } elseif($jumlah > 0){
            $pdo->beginTransaction();
            // Tabel Simpanan
            $pdo->prepare("INSERT INTO simpanan (anggota_id, tanggal, jenis_simpanan, jumlah, tipe_transaksi, keterangan) VALUES (?, ?, 'pokok', ?, 'setor', ?)")
                ->execute([$anggota_id, $tanggal, $jumlah, $ket]);
            
            // Tabel Kas
            $nama = getNamaAnggotaLocal($pdo, $anggota_id);
            $ket_kas = "Setor Simpok: " . $nama;
            $pdo->prepare("INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (NOW(), 'simpanan_masuk', 'masuk', ?, ?, ?)")
                ->execute([$jumlah, $ket_kas, $_SESSION['user']['id']]);

            $pdo->commit();
            echo "<script>alert('Simpanan Pokok Berhasil!'); window.location='index.php?page=simpanan/transaksi_simpanan&tab=simpok';</script>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: ".$e->getMessage()."');</script>";
    }
}

// --- QUERY DATA ---
$anggota_umum = $pdo->query("SELECT * FROM anggota WHERE role NOT IN ('admin', 'staff') AND status_aktif = 1 ORDER BY nama_lengkap ASC")->fetchAll();
$anggota_simpok = $pdo->query("SELECT * FROM anggota WHERE id NOT IN (SELECT anggota_id FROM simpanan WHERE jenis_simpanan = 'pokok') AND role NOT IN ('admin', 'staff') AND status_aktif = 1 ORDER BY nama_lengkap ASC")->fetchAll();

$hist_sihara = $pdo->query("SELECT s.*, a.nama_lengkap FROM simpanan s JOIN anggota a ON s.anggota_id = a.id WHERE s.jenis_simpanan = 'hari_raya' ORDER BY s.tanggal DESC LIMIT 10")->fetchAll();
$hist_simjib = $pdo->query("SELECT s.*, a.nama_lengkap FROM simpanan s JOIN anggota a ON s.anggota_id = a.id WHERE s.jenis_simpanan = 'wajib' ORDER BY s.tanggal DESC LIMIT 10")->fetchAll();
$hist_simpok = $pdo->query("SELECT s.*, a.nama_lengkap FROM simpanan s JOIN anggota a ON s.anggota_id = a.id WHERE s.jenis_simpanan = 'pokok' ORDER BY s.tanggal DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan Anggota</h6>
        <h2 class="h3 fw-bold mb-0">Transaksi Simpanan</h2>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-pill p-1 mb-4 d-inline-block bg-white">
    <ul class="nav nav-pills" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-bold px-4 <?= $active_tab=='sihara'?'active':'' ?>" id="tab-sihara" data-bs-toggle="pill" data-bs-target="#content-sihara" type="button">
                <i class="fas fa-wallet me-2"></i> SIHARA
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-bold px-4 <?= $active_tab=='simjib'?'active':'' ?>" id="tab-simjib" data-bs-toggle="pill" data-bs-target="#content-simjib" type="button">
                <i class="fas fa-calendar-check me-2"></i> WAJIB
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-bold px-4 <?= $active_tab=='simpok'?'active':'' ?>" id="tab-simpok" data-bs-toggle="pill" data-bs-target="#content-simpok" type="button">
                <i class="fas fa-key me-2"></i> POKOK
            </button>
        </li>
    </ul>
</div>

<div class="tab-content" id="pills-tabContent">
    
    <div class="tab-pane fade <?= $active_tab=='sihara'?'show active':'' ?>" id="content-sihara">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-lg bg-primary text-white h-100 rounded-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="fas fa-plus-circle me-2"></i> Input Sihara</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small text-white-50 fw-bold">Anggota</label>
                                <select name="anggota_id" class="form-select border-0 bg-white bg-opacity-25 text-white" required>
                                    <option value="" class="text-dark">-- Pilih Anggota --</option>
                                    <?php foreach($anggota_umum as $a): ?>
                                    <option value="<?= $a['id'] ?>" class="text-dark"><?= htmlspecialchars($a['nama_lengkap']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small text-white-50 fw-bold">Jenis Transaksi</label>
                                <select name="tipe" class="form-select border-0 bg-white bg-opacity-25 text-white">
                                    <option value="setor" class="text-dark">Setor (Menabung)</option>
                                    <option value="tarik" class="text-dark">Tarik (Ambil THR)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small text-white-50 fw-bold">Nominal (Rp)</label>
                                <input type="number" name="jumlah" class="form-control border-0 bg-white text-primary fw-bold" placeholder="0" required>
                            </div>
                            <div class="mb-4">
                                <label class="small text-white-50 fw-bold">Keterangan</label>
                                <input type="text" name="keterangan" class="form-control border-0 bg-white bg-opacity-25 text-white" placeholder="Opsional">
                            </div>
                            <button type="submit" name="simpan_sihara" class="btn btn-light text-primary w-100 fw-bold rounded-pill shadow-sm">Proses Sihara</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white py-3"><h6 class="fw-bold m-0 text-primary">Riwayat Terakhir</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light"><tr><th class="ps-4">Tanggal</th><th>Nama</th><th>Ket</th><th class="text-end pe-4">Jumlah</th></tr></thead>
                            <tbody>
                                <?php foreach($hist_sihara as $r): $masuk = ($r['tipe_transaksi'] == 'setor'); ?>
                                <tr>
                                    <td class="ps-4"><?= date('d/m/y', strtotime($r['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($r['keterangan']) ?></td>
                                    <td class="text-end pe-4 fw-bold <?= $masuk ? 'text-success' : 'text-danger' ?>">
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

    <div class="tab-pane fade <?= $active_tab=='simjib'?'show active':'' ?>" id="content-simjib">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-lg bg-success text-white h-100 rounded-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="fas fa-plus-circle me-2"></i> Input Simjib</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small text-white-50 fw-bold">Anggota</label>
                                <select name="anggota_id" class="form-select border-0 bg-white bg-opacity-25 text-white" required>
                                    <option value="" class="text-dark">-- Pilih Anggota --</option>
                                    <?php foreach($anggota_umum as $a): ?>
                                    <option value="<?= $a['id'] ?>" class="text-dark"><?= htmlspecialchars($a['nama_lengkap']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small text-white-50 fw-bold">Nominal (Rp)</label>
                                <input type="number" name="jumlah" class="form-control border-0 bg-white text-success fw-bold" placeholder="50000" required>
                            </div>
                            <div class="mb-4">
                                <label class="small text-white-50 fw-bold">Keterangan</label>
                                <input type="text" name="keterangan" class="form-control border-0 bg-white bg-opacity-25 text-white" placeholder="Cth: Januari 2026">
                            </div>
                            <button type="submit" name="simpan_simjib" class="btn btn-light text-success w-100 fw-bold rounded-pill shadow-sm">Simpan Wajib</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white py-3"><h6 class="fw-bold m-0 text-success">Riwayat Terakhir</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light"><tr><th class="ps-4">Tanggal</th><th>Nama</th><th>Ket</th><th class="text-end pe-4">Jumlah</th></tr></thead>
                            <tbody>
                                <?php foreach($hist_simjib as $r): ?>
                                <tr>
                                    <td class="ps-4"><?= date('d/m/y', strtotime($r['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($r['keterangan']) ?></td>
                                    <td class="text-end pe-4 fw-bold text-success">+ <?= number_format($r['jumlah']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?= $active_tab=='simpok'?'show active':'' ?>" id="content-simpok">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-lg bg-warning text-dark h-100 rounded-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="fas fa-plus-circle me-2"></i> Input Simpok</h5>
                        <?php if(count($anggota_simpok) > 0): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small text-dark fw-bold">Anggota Baru</label>
                                <select name="anggota_id" class="form-select border-0 bg-white bg-opacity-50" required>
                                    <option value="">-- Pilih Anggota --</option>
                                    <?php foreach($anggota_simpok as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_lengkap']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small text-dark fw-bold">Nominal (Rp)</label>
                                <input type="number" name="jumlah" class="form-control border-0 bg-white fw-bold" value="100000" required>
                            </div>
                            <div class="mb-4">
                                <label class="small text-dark fw-bold">Keterangan</label>
                                <input type="text" name="keterangan" class="form-control border-0 bg-white bg-opacity-50" value="Simpanan Pokok Awal">
                            </div>
                            <button type="submit" name="simpan_simpok" class="btn btn-dark w-100 fw-bold rounded-pill shadow-sm">Simpan Pokok</button>
                        </form>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x mb-3 opacity-50"></i>
                                <p class="fw-bold">Semua anggota aktif sudah lunas Simpanan Pokok.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white py-3"><h6 class="fw-bold m-0 text-warning text-dark">Anggota Sudah Bayar</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light"><tr><th class="ps-4">Tanggal</th><th>Nama</th><th class="text-end">Jumlah</th><th class="text-center">Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach($hist_simpok as $r): ?>
                                <tr>
                                    <td class="ps-4"><?= date('d/m/y', strtotime($r['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                                    <td class="text-end fw-bold text-warning text-dark">+ <?= number_format($r['jumlah']) ?></td>
                                    <td class="text-center">
                                        <a href="process/simpanan_hapus.php?id=<?= $r['id'] ?>&redirect=index.php?page=simpanan/transaksi_simpanan&tab=simpok" class="btn btn-sm btn-light text-danger shadow-sm rounded-circle" onclick="return confirm('Hapus transaksi ini? Saldo akan dikembalikan.')"><i class="fas fa-trash"></i></a>
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