<?php
// pages/utilitas/backup.php
if ($_SESSION['user']['role'] !== 'admin') {
    echo "<script>location.href='index.php';</script>"; exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Sistem Admin</h6>
        <h2 class="h3 fw-bold mb-0 text-dark"><i class="fas fa-tools me-2 text-primary"></i> Pemeliharaan Sistem</h2>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 text-center">
            <div class="card-body p-4">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-download fa-2x"></i>
                </div>
                <h5 class="fw-bold">Backup</h5>
                <p class="text-muted small">Download salinan database terbaru untuk berjaga-jaga.</p>
                <a href="process/backup_db.php" class="btn btn-primary w-100 rounded-pill fw-bold">
                    <i class="fas fa-file-download me-2"></i> Download SQL
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 text-center border-start border-5 border-success">
            <div class="card-body p-4">
                <div class="bg-success bg-opacity-10 text-success rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-upload fa-2x"></i>
                </div>
                <h5 class="fw-bold text-success">Restore</h5>
                <p class="text-muted small">Unggah file .sql hasil backup untuk mengembalikan data.</p>
                <button type="button" class="btn btn-success w-100 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalRestore">
                    <i class="fas fa-file-import me-2"></i> Upload & Pulihkan
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 text-center border-start border-5 border-danger">
            <div class="card-body p-4">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-undo-alt fa-2x"></i>
                </div>
                <h5 class="fw-bold text-danger">Factory Reset</h5>
                <p class="text-muted small">Hapus semua data & transaksi. Akun Admin TIDAK dihapus.</p>
                <button type="button" class="btn btn-danger w-100 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalReset">
                    <i class="fas fa-trash-restore me-2"></i> Kosongkan Data
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRestore" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="process/restore_db.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-success">Restore Database</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                    <p class="text-secondary">Peringatan: Proses ini akan menimpa data yang ada saat ini dengan data dari file backup.</p>
                    <input type="file" name="backup_file" class="form-control" accept=".sql" required>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="restore" class="btn btn-success rounded-pill px-4 fw-bold">Jalankan Restore</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReset" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body p-5 text-center">
                <i class="fas fa-radiation fa-4x text-danger mb-4"></i>
                <h3 class="fw-bold text-dark">Hapus Semua Data?</h3>
                <p class="text-secondary small">Data transaksi, tabungan, dan stok akan hilang permanen. <b>Akun Admin tetap bisa login.</b></p>
                <div class="d-flex gap-2 mt-4">
                    <button type="button" class="btn btn-light w-100 rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <a href="process/factory_reset.php" class="btn btn-danger w-100 rounded-pill fw-bold">Ya, Reset Sekarang</a>
                </div>
            </div>
        </div>
    </div>
</div>