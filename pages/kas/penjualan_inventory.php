<?php
// pages/kas/penjualan_inventory.php
require_once 'config/database.php';

// --- A. LOGIKA ARSIP DATA (PINDAH KE HISTORY) ---
if(isset($_POST['arsipkan_lunas'])){
    try {
        $pdo->beginTransaction();
        
        // 1. Pindahkan siswa yang sudah lunas di tabel master 'siswa' menjadi status 'arsip'
        $sql_arsip_siswa = "UPDATE siswa s 
                            SET s.status = 'arsip' 
                            WHERE EXISTS (
                                SELECT 1 FROM cicilan c 
                                WHERE c.nama_siswa = s.nama_siswa 
                                AND c.kelas = s.kelas 
                                AND c.status = 'lunas'
                            )";
        $pdo->query($sql_arsip_siswa);

        // 2. Tandai transaksi di tabel cicilan sebagai 'archived'
        $pdo->query("UPDATE cicilan SET is_archived = 1 WHERE status = 'lunas'");

        $pdo->commit();
        setFlash('success', 'Data siswa yang sudah LUNAS berhasil dipindahkan ke History.');
        echo "<script>window.location='index.php?page=kas/penjualan_inventory';</script>"; exit;
    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', 'Gagal mengarsipkan: ' . $e->getMessage());
    }
}

// --- B. LOGIKA PROSES TRANSAKSI JUAL ---
if(isset($_POST['proses_jual'])){
    $kategori     = $_POST['kategori_transaksi']; 
    $id_barang    = $_POST['id_barang'];          
    $siswa_id     = $_POST['siswa_id'];           
    
    $total_tagihan = (float) $_POST['total_tagihan'];
    $uang_bayar    = (float) $_POST['uang_bayar']; 
    $catatan       = $_POST['catatan'];
    
    $tanggal     = date('Y-m-d');
    $user_id     = $_SESSION['user']['id'];

    try {
        $pdo->beginTransaction();

        // 1. Ambil Data Siswa
        $st_siswa = $pdo->prepare("SELECT nama_siswa, kelas FROM siswa WHERE id = ?");
        $st_siswa->execute([$siswa_id]);
        $data_siswa = $st_siswa->fetch();

        if(!$data_siswa){ throw new Exception("Data siswa tidak ditemukan!"); }
        $nama_siswa = $data_siswa['nama_siswa'];
        $kelas      = $data_siswa['kelas'];

        // 2. Tentukan Tabel Stok
        $tabel_stok = ($kategori == 'seragam') ? 'stok_sekolah' : 'stok_eskul';
        $kategori_kas = ($kategori == 'seragam') ? 'penjualan_seragam' : 'penjualan_eskul';

        // 3. Detail Barang & Cek Stok
        $stmt = $pdo->prepare("SELECT nama_barang, stok FROM $tabel_stok WHERE id = ?");
        $stmt->execute([$id_barang]);
        $item = $stmt->fetch();

        if(!$item || $item['stok'] < 1){ throw new Exception("Stok barang habis!"); }
        $nama_barang_fixed = $item['nama_barang'];

        // 4. Update Stok (Sinkronisasi)
        $pdo->prepare("UPDATE $tabel_stok SET stok = stok - 1 WHERE id = ?")->execute([$id_barang]);

        // 5. Masuk Laporan Kas
        if($uang_bayar > 0){
            $metode = ($uang_bayar >= $total_tagihan) ? "Tunai" : "DP/Cicil";
            $ket_kas = "Terima ($metode): $nama_barang_fixed - $nama_siswa ($kelas)";
            $sql_kas = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (?, ?, 'masuk', ?, ?, ?)";
            $pdo->prepare($sql_kas)->execute([$tanggal, $kategori_kas, $uang_bayar, $ket_kas, $user_id]);
        }

        // 6. Simpan ke Cicilan
        $sisa = $total_tagihan - $uang_bayar;
        $status_akhir = ($sisa <= 0) ? 'lunas' : 'belum';

        $sql_history = "INSERT INTO cicilan (nama_siswa, kelas, kategori_barang, nama_barang, total_tagihan, terbayar, sisa, status, catatan, is_archived) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $pdo->prepare($sql_history)->execute([$nama_siswa, $kelas, $kategori, $nama_barang_fixed, $total_tagihan, $uang_bayar, $sisa, $status_akhir, $catatan]);
        
        $id_trx = $pdo->lastInsertId();
        catatLog($pdo, $user_id, 'Tambah', "Penjualan $kategori: $nama_barang_fixed ke $nama_siswa");

        $pdo->commit();
        setFlash('success', "Transaksi Berhasil! <a href='pages/cetak_struk.php?id=$id_trx' target='_blank' class='btn btn-sm btn-light border text-dark fw-bold ms-2'><i class='fas fa-print'></i> Struk</a>");
        echo "<script>window.location='index.php?page=kas/penjualan_inventory';</script>"; exit;

    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', 'Gagal: ' . $e->getMessage());
    }
}

// --- C. AMBIL DATA PENDUKUNG ---
// Hanya ambil siswa yang statusnya 'aktif' (Bukan arsip)
$list_siswa = $pdo->query("SELECT * FROM siswa WHERE status = 'aktif' ORDER BY kelas ASC, nama_siswa ASC")->fetchAll();

$seragam = $pdo->query("SELECT * FROM stok_sekolah WHERE stok > 0 ORDER BY nama_barang ASC")->fetchAll();
$eskul   = $pdo->query("SELECT * FROM stok_eskul WHERE stok > 0 ORDER BY nama_barang ASC")->fetchAll();

// List untuk monitoring (Siswa yang sudah upload tapi belum pernah transaksi seragam)
$sql_belum = "SELECT * FROM siswa s 
              WHERE s.status = 'aktif' 
              AND s.kelas = 'MPLS'
              AND NOT EXISTS (
                SELECT 1 FROM cicilan c 
                WHERE c.nama_siswa = s.nama_siswa 
                AND c.kategori_barang = 'seragam'
              )
              ORDER BY nama_siswa ASC";
$siswa_belum_ambil = $pdo->query($sql_belum)->fetchAll();

$filter_status = isset($_GET['f_status']) ? $_GET['f_status'] : 'all';
$sql_monitor = "SELECT * FROM cicilan WHERE is_archived = 0";
if($filter_status == 'belum') { $sql_monitor .= " AND status = 'belum'"; }
$sql_monitor .= " ORDER BY id DESC LIMIT 50";
$history = $pdo->query($sql_monitor)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Kasir Inventaris</h6>
        <h2 class="h3 fw-bold mb-0 text-dark">Penjualan Seragam & Eskul</h2>
    </div>
    <form method="POST" onsubmit="return confirm('Siswa yang sudah LUNAS akan dipindahkan ke History. Lanjutkan?')">
        <button type="submit" name="arsipkan_lunas" class="btn btn-dark rounded-pill shadow-sm fw-bold">
            <i class="fas fa-archive me-2"></i> Pindahkan Lunas ke History
        </button>
    </form>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-lg rounded-4 mb-4">
            <div class="card-header bg-primary text-white py-3 rounded-top-4">
                <h6 class="mb-0 fw-bold"><i class="fas fa-cash-register me-2"></i> Input Penjualan</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" id="formTransaksi">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Siswa</label>
                        <select name="siswa_id" id="pilih_siswa" class="form-select form-select-lg border-primary select2" required onchange="proteksiKategori()">
                            <option value="">-- Cari Nama Siswa --</option>
                            <?php foreach($list_siswa as $s): ?>
                                <option value="<?= $s['id'] ?>" data-kelas="<?= $s['kelas'] ?>">
                                    [<?= $s['kelas'] ?>] <?= $s['nama_siswa'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">KATEGORI (Proteksi Otomatis)</label>
                        <div class="btn-group w-100">
                            <input type="radio" class="btn-check" name="kategori_transaksi" id="kat_seragam" value="seragam" onclick="toggleDropdown('seragam')">
                            <label class="btn btn-outline-primary py-2" for="kat_seragam" id="lbl_seragam">SERAGAM</label>
                            
                            <input type="radio" class="btn-check" name="kategori_transaksi" id="kat_eskul" value="eskul" onclick="toggleDropdown('eskul')">
                            <label class="btn btn-outline-info py-2" for="kat_eskul" id="lbl_eskul">ESKUL</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Item</label>
                        <select id="sel_seragam" name="id_barang" class="form-select item-select" required onchange="updateHarga(this)">
                            <option value="" data-harga="0">-- Pilih Paket Seragam --</option>
                            <?php foreach($seragam as $s): ?>
                                <option value="<?= $s['id'] ?>" data-harga="<?= $s['harga_jual'] ?>"><?= $s['nama_barang'] ?> (Sisa: <?= $s['stok'] ?>)</option>
                            <?php endforeach; ?>
                        </select>

                        <select id="sel_eskul" class="form-select item-select" style="display:none;" disabled onchange="updateHarga(this)">
                            <option value="" data-harga="0">-- Pilih Atribut Eskul --</option>
                            <?php foreach($eskul as $e): ?>
                                <option value="<?= $e['id'] ?>" data-harga="<?= $e['harga_jual'] ?>"><?= $e['nama_barang'] ?> (Sisa: <?= $e['stok'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Total Tagihan</label>
                        <input type="number" name="total_tagihan" id="total_tagihan" class="form-control fw-bold bg-light" readonly>
                    </div>

                    <div class="bg-light p-3 rounded border mb-3">
                        <label class="form-label small fw-bold text-success">Uang Bayar</label>
                        <input type="number" name="uang_bayar" id="uang_bayar" class="form-control form-control-lg fw-bold border-success text-success" required oninput="cekStatusBayar()">
                        <div id="status_box" class="mt-2 small fw-bold text-uppercase">Status: <span id="status_bayar_txt">-</span></div>
                        <div id="info_sisa" class="mt-1 text-danger small fw-bold" style="display:none;">Kurang: <span id="nominal_sisa">Rp 0</span></div>
                    </div>

                    <div class="mb-3">
                        <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan..."></textarea>
                    </div>

                    <button type="submit" name="proses_jual" id="btn_submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-sm" disabled>SIMPAN TRANSAKSI</button>
                    <input type="hidden" name="metode_pembayaran" id="metode_hidden">
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-danger text-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-exclamation-circle me-2"></i> Belum Ambil Seragam (<?= count($siswa_belum_ambil) ?>)</h6>
            </div>
            <div class="card-body p-0" style="max-height: 250px; overflow-y: auto;">
                <ul class="list-group list-group-flush small">
                    <?php if(empty($siswa_belum_ambil)): ?>
                        <li class="list-group-item text-center text-muted py-3">Semua siswa MPLS sudah dilayani.</li>
                    <?php endif; ?>
                    <?php foreach($siswa_belum_ambil as $sb): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div><span class="fw-bold"><?= $sb['nama_siswa'] ?></span></div>
                            <span class="badge bg-light text-danger border">BELUM</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-muted"><i class="fas fa-list me-2"></i> Monitoring Transaksi Aktif</h6>
                <select class="form-select form-select-sm w-auto" onchange="location.href='index.php?page=kas/penjualan_inventory&f_status='+this.value">
                    <option value="all" <?= $filter_status=='all'?'selected':'' ?>>Semua Status</option>
                    <option value="belum" <?= $filter_status=='belum'?'selected':'' ?>>Belum Lunas</option>
                </select>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Siswa</th>
                            <th>Barang</th>
                            <th class="text-end">Sisa</th>
                            <th class="text-center">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($history as $row): 
                            $cls = ($row['status']=='lunas') ? 'bg-success' : 'bg-danger';
                        ?>
                        <tr>
                            <td class="ps-3 fw-bold"><?= $row['nama_siswa'] ?><br><small class="text-muted"><?= $row['kelas'] ?></small></td>
                            <td><span class="badge bg-light text-dark border me-1"><?= strtoupper($row['kategori_barang']) ?></span> <?= $row['nama_barang'] ?></td>
                            <td class="text-end text-danger fw-bold"><?= formatRp($row['sisa']) ?></td>
                            <td class="text-center"><span class="badge <?= $cls ?> rounded-pill"><?= strtoupper($row['status']) ?></span></td>
                            <td class="text-center">
                                <a href="index.php?page=kas/manajemen_cicilan&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">Detail</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// FUNGSI PROTEKSI: MPLS HANYA BISA SERAGAM, LAINNYA HANYA BISA ESKUL
function proteksiKategori() {
    const sel = document.getElementById('pilih_siswa');
    const kelas = sel.options[sel.selectedIndex].getAttribute('data-kelas');
    const radSeragam = document.getElementById('kat_seragam');
    const radEskul = document.getElementById('kat_eskul');

    if (kelas === "MPLS") {
        radSeragam.checked = true;
        radSeragam.disabled = false;
        radEskul.disabled = true;
        toggleDropdown('seragam');
    } else if (kelas !== "" && kelas !== null) {
        radEskul.checked = true;
        radEskul.disabled = false;
        radSeragam.disabled = true;
        toggleDropdown('eskul');
    } else {
        radSeragam.disabled = false;
        radEskul.disabled = false;
    }
}

function toggleDropdown(type) {
    document.querySelectorAll('.item-select').forEach(el => { el.style.display = 'none'; el.disabled = true; el.removeAttribute('name'); });
    let target = document.getElementById('sel_' + type);
    target.style.display = 'block'; target.disabled = false; target.setAttribute('name', 'id_barang');
    target.value = ""; document.getElementById('total_tagihan').value = ""; document.getElementById('uang_bayar').value = "";
    cekStatusBayar();
}

function updateHarga(sel) {
    let harga = sel.options[sel.selectedIndex].getAttribute('data-harga') || 0;
    document.getElementById('total_tagihan').value = harga;
    cekStatusBayar();
}

function cekStatusBayar() {
    let total = parseFloat(document.getElementById('total_tagihan').value) || 0;
    let bayar = parseFloat(document.getElementById('uang_bayar').value) || 0;
    let btn = document.getElementById('btn_submit');
    let s_txt = document.getElementById('status_bayar_txt');
    let i_sisa = document.getElementById('info_sisa');
    
    if(total <= 0) { btn.disabled = true; return; }
    
    if(bayar >= total) {
        s_txt.innerText = "LUNAS"; s_txt.className = "text-success"; i_sisa.style.display = "none";
        document.getElementById('metode_hidden').value = "Tunai";
    } else if (bayar > 0) {
        s_txt.innerText = "CICILAN"; s_txt.className = "text-warning"; i_sisa.style.display = "block";
        document.getElementById('nominal_sisa').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(total-bayar);
        document.getElementById('metode_hidden').value = "DP/Cicil";
    } else {
        s_txt.innerText = "Belum Bayar"; s_txt.className = "text-danger"; i_sisa.style.display = "none";
    }
    btn.disabled = (bayar > total || total == 0);
}
</script>