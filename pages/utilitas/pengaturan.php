<?php
// pages/utilitas/pengaturan.php
require_once 'config/database.php';

// PROSES SIMPAN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();
        foreach ($_POST['setting'] as $key => $val) {
            // Gunakan ON DUPLICATE KEY UPDATE agar jika setting baru belum ada, otomatis dibuat
            $stmt = $pdo->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = ?");
            $stmt->execute([$key, $val, $val]);
        }
        $pdo->commit();
        echo "<script>alert('Pengaturan berhasil disimpan!'); window.location='index.php?page=utilitas/pengaturan';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Gagal menyimpan: " . $e->getMessage() . "');</script>";
    }
}

// AMBIL DATA SAAT INI
// Pastikan fungsi getAllPengaturan() sudah ada di config/functions.php
$set = getAllPengaturan($pdo);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Utilitas Sistem</h6>
        <h2 class="h3 fw-bold mb-0 text-gray-800">Pengaturan Aplikasi</h2>
    </div>
    <button type="submit" form="formSettings" class="btn btn-primary shadow-sm rounded-pill px-4">
        <i class="fas fa-save me-2"></i> Simpan Perubahan
    </button>
</div>

<form id="formSettings" method="POST" action="">
    <div class="row g-4">
        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-primary mb-0"><i class="fas fa-print me-2"></i> Identitas Header Cetak</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Nama Instansi / Koperasi</label>
                        <input type="text" name="setting[header_nama]" class="form-control" value="<?= htmlspecialchars($set['header_nama'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Alamat Lengkap</label>
                        <textarea name="setting[header_alamat]" class="form-control" rows="2"><?= htmlspecialchars($set['header_alamat'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Kontak (Telp/Email)</label>
                        <input type="text" name="setting[header_kontak]" class="form-control" value="<?= htmlspecialchars($set['header_kontak'] ?? '') ?>">
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="fas fa-info-circle me-1"></i> Data ini akan muncul di kop surat seluruh laporan cetak.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold text-success mb-0"><i class="fas fa-coins me-2"></i> Pengaturan Keuangan</h6>
                </div>
                <div class="card-body">
                    
                    <div class="alert alert-warning border-0 py-2 mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <label class="form-label fw-bold mb-0 text-dark"><i class="fas fa-hand-holding-usd me-1"></i> Bunga Pinjaman Tunai</label>
                                <div class="small text-muted" style="font-size: 0.75rem;">Ditambahkan otomatis ke total tagihan.</div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.1" name="setting[bunga_pinjaman]" class="form-control fw-bold text-center" value="<?= $set['bunga_pinjaman'] ?? 0 ?>">
                                    <span class="input-group-text bg-warning text-dark fw-bold">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="small fw-bold text-uppercase text-muted mb-3">Pembagian Alokasi Surplus (SHU)</h6>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Honor Staff (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="setting[persen_staff]" class="form-control border-primary" value="<?= $set['persen_staff'] ?? 20 ?>">
                                <span class="input-group-text bg-primary text-white">%</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Honor Pengurus (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="setting[persen_pengurus]" class="form-control border-success" value="<?= $set['persen_pengurus'] ?? 15 ?>">
                                <span class="input-group-text bg-success text-white">%</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Honor Pembina (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="setting[persen_pembina]" class="form-control border-warning" value="<?= $set['persen_pembina'] ?? 5 ?>">
                                <span class="input-group-text bg-warning text-dark">%</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Dana Sosial (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="setting[persen_dansos]" class="form-control border-danger" value="<?= $set['persen_dansos'] ?? 10 ?>">
                                <span class="input-group-text bg-danger text-white">%</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Sisa Untuk Kas Koperasi (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="setting[persen_kas]" class="form-control bg-light" value="<?= $set['persen_kas'] ?? 50 ?>" readonly>
                                <span class="input-group-text bg-secondary text-white">%</span>
                            </div>
                            <small class="text-muted fst-italic">* Otomatis (Sisa dari 100%)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

<script>
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', hitungSisa);
    });

    function hitungSisa() {
        // Hanya ambil input yang terkait SHU, abaikan Bunga Pinjaman
        let staff = parseFloat(document.querySelector('input[name="setting[persen_staff]"]').value) || 0;
        let pengurus = parseFloat(document.querySelector('input[name="setting[persen_pengurus]"]').value) || 0;
        let pembina = parseFloat(document.querySelector('input[name="setting[persen_pembina]"]').value) || 0;
        let dansos = parseFloat(document.querySelector('input[name="setting[persen_dansos]"]').value) || 0;
        
        let total = staff + pengurus + pembina + dansos;
        let sisa = 100 - total;
        
        let inputKas = document.querySelector('input[name="setting[persen_kas]"]');
        inputKas.value = sisa.toFixed(1);
        
        if(sisa < 0) {
            inputKas.classList.add('is-invalid');
            inputKas.classList.remove('bg-light');
        } else {
            inputKas.classList.remove('is-invalid');
            inputKas.classList.add('bg-light');
        }
    }
</script>