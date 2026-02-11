<?php
// pages/profil.php
require_once 'config/database.php';

$id_user = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT * FROM anggota WHERE id = ?");
$stmt->execute([$id_user]);
$u = $stmt->fetch();

if (!$u) { header("Location: logout"); exit; }
$initial = strtoupper(substr($u['nama_lengkap'], 0, 1));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Akun Saya</h6>
        <h2 class="h3 fw-bold mb-0 text-dark"><i class="fas fa-user-circle me-2"></i> Profil Pengguna</h2>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header bg-primary py-5 text-center border-0">
                <div class="avatar-circle mx-auto mb-3 shadow-lg border border-4 border-white" 
                     style="width: 100px; height: 100px; font-size: 40px; background-color: #ffffff; color: var(--primary);">
                    <?= $initial ?>
                </div>
                <h4 class="text-white fw-bold mb-0"><?= htmlspecialchars($u['nama_lengkap']) ?></h4>
                <span class="badge bg-white text-primary rounded-pill px-3 mt-2 fw-bold text-uppercase">
                    <?= htmlspecialchars($u['role']) ?>
                </span>
            </div>
            
            <div class="card-body p-4 p-md-5">
                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <label class="small text-muted fw-bold text-uppercase">Username</label>
                        <div class="h6 fw-bold border-bottom pb-2">@<?= htmlspecialchars($u['username']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted fw-bold text-uppercase">Terdaftar Sejak</label>
                        <div class="h6 fw-bold border-bottom pb-2"><?= date('d F Y', strtotime($u['created_at'])) ?></div>
                    </div>
                </div>

                <div class="p-4 bg-light rounded-4 border">
                    <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-edit me-2 text-warning"></i> Perbarui Informasi Profil</h6>
                    <form action="process/anggota_edit.php" method="POST">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="nama" value="<?= $u['nama_lengkap'] ?>">
                        <input type="hidden" name="username" value="<?= $u['username'] ?>">
                        <input type="hidden" name="role" value="<?= $u['role'] ?>">
                        <input type="hidden" name="status_aktif" value="<?= $u['status_aktif'] ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="small fw-bold text-muted">NOMOR HP / WHATSAPP</label>
                                <input type="text" name="no_hp" class="form-control form-control-lg border-0 shadow-sm" 
                                       value="<?= htmlspecialchars($u['no_hp'] ?: '') ?>" placeholder="Contoh: 081234567890">
                            </div>
                            <div class="col-md-12">
                                <label class="small fw-bold text-muted">GANTI PASSWORD (OPSIONAL)</label>
                                <input type="password" name="password_baru" class="form-control form-control-lg border-0 shadow-sm" 
                                       placeholder="Kosongkan jika tidak ingin ganti password">
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-warning w-100 py-3 fw-bold rounded-pill shadow">
                                    <i class="fas fa-save me-2"></i> SIMPAN PERUBAHAN
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>