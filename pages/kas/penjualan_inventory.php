<?php
// --- LOGIKA PROSES TRANSAKSI ---
if(isset($_POST['proses_jual'])){
    $kategori    = $_POST['kategori_transaksi']; // 'seragam' atau 'eskul'
    $id_barang   = $_POST['id_barang'];          // ID dari dropdown
    
    // Data Siswa Manual
    $nama_siswa  = strtoupper($_POST['nama_siswa']); 
    $kelas       = strtoupper($_POST['kelas']);
    
    // Keuangan
    $total_tagihan = (float) $_POST['total_tagihan'];
    $uang_bayar    = (float) $_POST['uang_bayar']; // DP atau Lunas
    $metode        = $_POST['metode_pembayaran']; 
    $catatan       = $_POST['catatan'];
    
    $tanggal     = date('Y-m-d');
    $user_id     = $_SESSION['user']['id'];

    // 1. Validasi Input
    if(empty($nama_siswa) || empty($kelas) || empty($id_barang)){
        setFlash('danger', 'Nama Siswa, Kelas, dan Barang Wajib Dipilih!');
        echo "<script>window.history.back();</script>"; exit;
    }
    
    // Validasi Server Side (Backup jika JS dimatikan)
    if($uang_bayar > $total_tagihan){
        setFlash('danger', 'ERROR: Pembayaran melebihi total tagihan! Transaksi dibatalkan.');
        echo "<script>window.history.back();</script>"; exit;
    }

    // 2. Tentukan Tabel & Kategori Kas
    if($kategori == 'seragam'){
        $tabel_stok = 'stok_sekolah';
        $kategori_kas = 'penjualan_seragam';
    } else {
        $tabel_stok = 'stok_eskul';
        $kategori_kas = 'penjualan_eskul';
    }

    // 3. Ambil Nama Barang dari Database & Cek Stok
    $stmt = $pdo->prepare("SELECT nama_barang, stok, harga_jual FROM $tabel_stok WHERE id = ?");
    $stmt->execute([$id_barang]);
    $item = $stmt->fetch();

    if(!$item){
        setFlash('danger', 'Barang tidak ditemukan di database!');
        echo "<script>window.history.back();</script>"; exit;
    }
    
    // Cek Stok
    if($item['stok'] < 1){
         setFlash('danger', 'Stok Barang Habis! Silakan restock dulu.');
         echo "<script>window.history.back();</script>"; exit;
    }

    $nama_barang_fixed = $item['nama_barang']; 

    try {
        $pdo->beginTransaction();

        // A. Kurangi Stok (1 Pcs/Paket)
        $pdo->prepare("UPDATE $tabel_stok SET stok = stok - 1 WHERE id = ?")->execute([$id_barang]);

        // B. Masuk Laporan Kas (Uang yang diterima)
        if($uang_bayar > 0){
            $ket_kas = "Terima ($metode): $nama_barang_fixed - $nama_siswa ($kelas)";
            $sql_kas = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                        VALUES (?, ?, 'masuk', ?, ?, ?)";
            $pdo->prepare($sql_kas)->execute([$tanggal, $kategori_kas, $uang_bayar, $ket_kas, $user_id]);
        }

        // C. Simpan ke History / Cicilan
        $sisa = $total_tagihan - $uang_bayar;
        $status_akhir = ($sisa <= 0) ? 'lunas' : 'belum';

        $sql_history = "INSERT INTO cicilan (nama_siswa, kelas, kategori_barang, nama_barang, total_tagihan, terbayar, sisa, status, catatan) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql_history)->execute([$nama_siswa, $kelas, $kategori, $nama_barang_fixed, $total_tagihan, $uang_bayar, $sisa, $status_akhir, $catatan]);
        
        // D. Ambil ID Transaksi Terakhir (Untuk keperluan Cetak Struk)
        $id_trx = $pdo->lastInsertId();

        $pdo->commit();
        
        // Tampilkan Flash Message Sukses + Link Cetak Struk
        setFlash('success', "Transaksi Berhasil Disimpan! <a href='pages/cetak_struk.php?id=$id_trx' target='_blank' class='btn btn-sm btn-light ms-2 text-dark fw-bold text-decoration-underline'><i class='fas fa-print'></i> Cetak Struk</a>");
        
        echo "<script>window.location='kas/penjualan_inventory';</script>";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', 'Gagal: ' . $e->getMessage());
        echo "<script>window.history.back();</script>";
    }
}

// --- AMBIL DATA STOK DARI DATABASE ---
$seragam = []; $eskul = [];
try {
    $seragam = $pdo->query("SELECT * FROM stok_sekolah WHERE stok > 0 ORDER BY nama_barang ASC")->fetchAll();
    $eskul = $pdo->query("SELECT * FROM stok_eskul WHERE stok > 0 ORDER BY nama_barang ASC")->fetchAll();
} catch(Exception $e) {}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Transaksi</h6>
        <h2 class="h3 fw-bold mb-0">Kasir Seragam & Eskul</h2>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-primary text-white py-3 border-0 rounded-top-4">
                <h5 class="mb-0 fw-bold"><i class="fas fa-cash-register me-2"></i> Input Penjualan</h5>
            </div>
            <div class="card-body p-4 p-md-5">
                <form method="POST" id="formTransaksi">
                    
                    <h6 class="text-primary fw-bold text-uppercase small mb-3 ls-1">1. Identitas Siswa</h6>
                    <div class="row mb-4">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold text-dark">Nama Lengkap Siswa</label>
                            <input type="text" name="nama_siswa" class="form-control form-control-lg bg-light border-0 fw-bold" placeholder="Ketik nama siswa..." required autocomplete="off">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-dark">Kelas</label>
                            <input type="text" name="kelas" class="form-control form-control-lg bg-light border-0 fw-bold" placeholder="Cth: 10 TKJ 1" required autocomplete="off">
                        </div>
                    </div>

                    <hr class="my-4 dashed">

                    <h6 class="text-primary fw-bold text-uppercase small mb-3 ls-1">2. Pilih Barang / Paket</h6>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">KATEGORI</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="kategori_transaksi" id="kat_seragam" value="seragam" checked onclick="toggleDropdown('seragam')">
                            <label class="btn btn-outline-primary btn-lg py-2" for="kat_seragam">SERAGAM</label>
                            
                            <input type="radio" class="btn-check" name="kategori_transaksi" id="kat_eskul" value="eskul" onclick="toggleDropdown('eskul')">
                            <label class="btn btn-outline-info btn-lg py-2" for="kat_eskul">ESKUL</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Nama Item (Pilih dari Stok)</label>
                        
                        <select id="sel_seragam" name="id_barang" class="form-select form-select-lg border-primary item-select" required onchange="updateHarga(this)">
                            <option value="" data-harga="0">-- Pilih Seragam Sekolah --</option>
                            <?php foreach($seragam as $s): ?>
                                <option value="<?= $s['id'] ?>" data-harga="<?= $s['harga_jual'] ?>">
                                    <?= htmlspecialchars($s['nama_barang']) ?> (Sisa: <?= $s['stok'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select id="sel_eskul" name="id_barang" class="form-select form-select-lg border-info item-select" style="display:none;" disabled onchange="updateHarga(this)">
                            <option value="" data-harga="0">-- Pilih Atribut Eskul --</option>
                            <?php foreach($eskul as $e): ?>
                                <option value="<?= $e['id'] ?>" data-harga="<?= $e['harga_jual'] ?>">
                                    <?= htmlspecialchars($e['nama_barang']) ?> (Sisa: <?= $e['stok'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small text-muted"><i class="fas fa-info-circle me-1"></i> Stok akan berkurang otomatis.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Total Tagihan (Fixed)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light text-muted border-0 fw-bold">Rp</span>
                            <input type="number" name="total_tagihan" id="total_tagihan" class="form-control fw-bold border-0 bg-light text-end" placeholder="0" readonly>
                        </div>
                    </div>

                    <div class="bg-light p-4 rounded-3 border">
                        <h6 class="fw-bold text-uppercase small mb-3 text-muted">3. Pembayaran</h6>
                        <div class="row align-items-end">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-muted small">BAYAR SEKARANG (DP)</label>
                                <input type="number" name="uang_bayar" id="uang_bayar" class="form-control form-control-lg fw-bold border-success text-success" placeholder="0" min="0" required oninput="cekStatusBayar()">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-muted small">STATUS</label>
                                <input type="text" name="metode_pembayaran" id="status_bayar" class="form-control form-control-lg border-0 bg-transparent fw-bold text-uppercase" value="-" readonly>
                            </div>
                        </div>
                        
                        <div id="info_sisa" class="alert alert-danger mb-0 py-2 small fw-bold" style="display:none;">
                            Sisa Kekurangan (Hutang): <span id="nominal_sisa">Rp 0</span>
                        </div>
                        
                        <div id="info_lebih" class="alert alert-warning border-warning mb-0 py-2 small fw-bold text-dark" style="display:none;">
                            <i class="fas fa-exclamation-triangle me-1"></i> Nominal pembayaran melebihi harga barang!
                        </div>
                    </div>

                    <div class="mt-3">
                        <input type="text" name="catatan" class="form-control border-0 bg-light" placeholder="Catatan tambahan (Opsional)...">
                    </div>

                    <button type="submit" name="proses_jual" id="btn_submit" class="btn btn-primary w-100 py-3 mt-4 fw-bold shadow-sm rounded-pill fs-5" disabled>
                        <i class="fas fa-save me-2"></i> SIMPAN TRANSAKSI
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi Ganti Dropdown
function toggleDropdown(type) {
    document.querySelectorAll('.item-select').forEach(el => { 
        el.style.display = 'none'; 
        el.disabled = true; 
        el.removeAttribute('name'); 
    });
    
    let target = document.getElementById('sel_' + type);
    target.style.display = 'block'; 
    target.disabled = false; 
    target.setAttribute('name', 'id_barang');
    
    target.value = "";
    document.getElementById('total_tagihan').value = "";
    document.getElementById('uang_bayar').value = "";
    cekStatusBayar();
}

// Fungsi Update Harga Otomatis
function updateHarga(selectElement) {
    let harga = selectElement.options[selectElement.selectedIndex].getAttribute('data-harga') || 0;
    document.getElementById('total_tagihan').value = harga;
    document.getElementById('uang_bayar').value = ""; // Reset bayar
    cekStatusBayar();
}

// Fungsi Logika Pembayaran & Validasi
function cekStatusBayar() {
    let total = parseFloat(document.getElementById('total_tagihan').value) || 0;
    let bayar = parseFloat(document.getElementById('uang_bayar').value) || 0;
    
    let statusField = document.getElementById('status_bayar');
    let infoSisa = document.getElementById('info_sisa');
    let nominalSisa = document.getElementById('nominal_sisa');
    let infoLebih = document.getElementById('info_lebih');
    let btnSubmit = document.getElementById('btn_submit');

    // Reset Display
    infoSisa.style.display = 'none';
    infoLebih.style.display = 'none';
    btnSubmit.disabled = true; // Default disabled sampai valid
    statusField.className = "form-control form-control-lg border-0 bg-transparent fw-bold text-uppercase";

    if (total === 0) {
        statusField.value = "-";
        return; // Jangan lanjut jika belum pilih barang
    }

    if (bayar > total) {
        // KASUS: KELEBIHAN BAYAR
        statusField.value = "KELEBIHAN BAYAR";
        statusField.classList.add('text-danger');
        infoLebih.style.display = 'block';
        btnSubmit.disabled = true; // Kunci tombol submit
    } 
    else if (bayar === total) {
        // KASUS: LUNAS
        statusField.value = "LUNAS";
        statusField.classList.add('text-success');
        btnSubmit.disabled = false; // Buka kunci
    } 
    else if (bayar < total && bayar > 0) {
        // KASUS: CICILAN
        statusField.value = "CICILAN (HUTANG)";
        statusField.classList.add('text-warning');
        
        let sisa = total - bayar;
        nominalSisa.innerText = "Rp " + new Intl.NumberFormat('id-ID').format(sisa);
        infoSisa.style.display = 'block';
        btnSubmit.disabled = false; // Buka kunci
    } 
    else {
        // Belum input bayar
        statusField.value = "MENUNGGU INPUT...";
        btnSubmit.disabled = true;
    }
}
</script>