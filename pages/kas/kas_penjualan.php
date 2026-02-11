<?php
// pages/kas/kas_penjualan.php
require_once 'config/database.php';

if (isset($_POST['simpan_kas'])) {
    $tanggal   = $_POST['tanggal'];
    $kategori  = $_POST['kategori'];
    $arus      = $_POST['arus']; // 'masuk' atau 'keluar'
    $jumlah    = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];
    $user_id   = $_SESSION['user']['id'];

    try {
        $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tanggal, $kategori, $arus, $jumlah, $keterangan, $user_id]);

        catatLog($pdo, $user_id, 'Tambah Kas', "Input Kas $arus: $keterangan senilai " . formatRp($jumlah));
        
        setFlash('success', 'Transaksi kas berhasil disimpan.');
    } catch (Exception $e) {
        setFlash('danger', 'Gagal: ' . $e->getMessage());
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan</h6>
        <h2 class="h3 fw-bold mb-0 text-dark"><i class="fas fa-money-bill-wave me-2 text-success"></i> Kas Masuk & Keluar</h2>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold m-0 text-dark">Form Transaksi Kas</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">TANGGAL</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">ARUS KAS</label>
                        <select name="arus" class="form-select fw-bold" required onchange="updateKategori(this.value)">
                            <option value="masuk" class="text-success">TAMBAH KAS (MASUK)</option>
                            <option value="keluar" class="text-danger">TARIK KAS (KELUAR)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">KATEGORI</label>
                        <select name="kategori" id="sel_kategori" class="form-select" required>
                            <option value="modal_awal">MODAL AWAL (Sistem Baru)</option>
                            <option value="penjualan_harian">Hasil Penjualan Harian</option>
                            <option value="dana_hibah">Dana Hibah/Lain-lain</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">NOMINAL (RP)</label>
                        <input type="number" name="jumlah" class="form-control form-control-lg fw-bold text-primary" placeholder="0" required>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-muted">KETERANGAN DETAIL</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Saldo awal kas koperasi saat pindah ke sistem digital" required></textarea>
                    </div>

                    <button type="submit" name="simpan_kas" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow">
                        <i class="fas fa-save me-2"></i> SIMPAN TRANSAKSI
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 bg-light mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle me-3">
                        <i class="fas fa-info-circle fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Catatan Modal Awal</h6>
                        <p class="small text-muted mb-0">Input <b>Modal Awal</b> hanya dilakukan sekali saat memulai sistem. Transaksi ini tidak akan dihitung sebagai keuntungan (laba) pada laporan bulanan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateKategori(arus) {
    const sel = document.getElementById('sel_kategori');
    sel.innerHTML = '';
    
    if (arus === 'masuk') {
        sel.innerHTML += '<option value="modal_awal">MODAL AWAL (Sistem Baru)</option>';
        sel.innerHTML += '<option value="penjualan_harian">Hasil Penjualan Harian</option>';
        sel.innerHTML += '<option value="dana_hibah">Dana Hibah/Lain-lain</option>';
    } else {
        sel.innerHTML += '<option value="biaya_operasional">Biaya Operasional</option>';
        sel.innerHTML += '<option value="biaya_kebersihan">Biaya Kebersihan/Keamanan</option>';
        sel.innerHTML += '<option value="lain_lain">Lain-lain</option>';
    }
}
</script>