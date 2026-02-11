<?php
// pages/utilitas/import_data.php
require_once 'config/database.php';
$stok_seragam = $pdo->query("SELECT id, nama_barang, harga_jual FROM stok_sekolah ORDER BY nama_barang ASC")->fetchAll();
$stok_eskul   = $pdo->query("SELECT id, nama_barang, harga_jual FROM stok_eskul ORDER BY nama_barang ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Utilitas Sistem</h6>
        <h2 class="h3 fw-bold mb-0 text-gray-800">Import Data Masal (CSV)</h2>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4">
        <ul class="nav nav-pills card-header-pills bg-light rounded-pill p-1" id="pills-tab" role="tablist">
            <li class="nav-item"><button class="nav-link rounded-pill active fw-bold px-4" data-bs-toggle="pill" data-bs-target="#pills-mpls"><i class="fas fa-user-graduate me-2"></i> 1. Siswa Baru (MPLS)</button></li>
            <li class="nav-item"><button class="nav-link rounded-pill fw-bold px-4" data-bs-toggle="pill" data-bs-target="#pills-eskul"><i class="fas fa-users me-2"></i> 2. Anggota Eskul</button></li>
        </ul>
    </div>
    
    <div class="card-body p-4">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="pills-mpls">
                <form action="process/import_handler.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="tipe_import" value="mpls">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Pilih Paket Seragam</label>
                            <select name="barang_id" class="form-select" required>
                                <option value="">-- Pilih Paket --</option>
                                <?php foreach($stok_seragam as $b): ?>
                                    <option value="<?= $b['id'] ?>|<?= $b['nama_barang'] ?>|<?= $b['harga_jual'] ?>"><?= $b['nama_barang'] ?> (Rp <?= number_format($b['harga_jual']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">File CSV</label>
                            <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                        </div>
                        <div class="col-12 text-end">
                            <a href="index.php?action=download_template&type=mpls" class="btn btn-outline-secondary rounded-pill me-2">
                                <i class="fas fa-download me-1"></i> Download Template
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Upload</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="pills-eskul">
                <form action="process/import_handler.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="tipe_import" value="eskul">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Pilih Atribut Eskul</label>
                            <select name="barang_id" class="form-select" required>
                                <option value="">-- Pilih Atribut --</option>
                                <?php foreach($stok_eskul as $b): ?>
                                    <option value="<?= $b['id'] ?>|<?= $b['nama_barang'] ?>|<?= $b['harga_jual'] ?>"><?= $b['nama_barang'] ?> (Rp <?= number_format($b['harga_jual']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">File CSV</label>
                            <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                        </div>
                        <div class="col-12 text-end">
                            <a href="index.php?action=download_template&type=eskul" class="btn btn-outline-secondary rounded-pill me-2">
                                <i class="fas fa-download me-1"></i> Download Template
                            </a>
                            <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold">Upload</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>