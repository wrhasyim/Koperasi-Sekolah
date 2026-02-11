<?php
// pages/data_anggota.php
require_once 'config/database.php';

// PERBAIKAN: Menambahkan filter role != 'admin' agar admin tidak muncul di daftar
$stmt = $pdo->query("SELECT * FROM anggota WHERE role != 'admin' ORDER BY nama_lengkap ASC");
$anggota = $stmt->fetchAll();
$colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6f42c1'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Master Data</h6>
        <h2 class="h3 fw-bold mb-0">Data Anggota</h2>
    </div>
    <button type="button" class="btn btn-primary shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus me-2"></i> Tambah Baru
    </button>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Nama Anggota</th>
                    <th>Kontak (WA)</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($anggota as $row): 
                    $bg_color = $colors[$row['id'] % count($colors)];
                    $initial = strtoupper(substr($row['nama_lengkap'] ?? 'A', 0, 1));
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-3 shadow-sm" style="background-color: <?= $bg_color ?>;">
                                <?= $initial ?>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                                <small class="text-muted">@<?= htmlspecialchars($row['username']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($row['no_hp'] ?: '-') ?></td>
                    <td>
                        <?php 
                        if($row['role']=='staff') echo '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2 rounded-pill">STAFF</span>';
                        elseif($row['role']=='pengurus') echo '<span class="badge bg-info bg-opacity-10 text-info border border-info px-3 py-2 rounded-pill">PENGURUS</span>';
                        else echo '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-3 py-2 rounded-pill">GURU</span>'; 
                        ?>
                    </td>
                    <td>
                        <?= $row['status_aktif'] 
                            ? '<span class="text-success fw-bold"><i class="fas fa-check-circle small me-1"></i> Aktif</span>' 
                            : '<span class="text-muted"><i class="fas fa-ban small me-1"></i> Nonaktif</span>' 
                        ?>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-light border text-primary shadow-sm rounded-circle" style="width: 32px; height: 32px;" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>">
                            <i class="fas fa-pen fa-xs"></i>
                        </button>
                        <a href="process/anggota_hapus.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-light border text-danger shadow-sm rounded-circle ms-1" style="width: 32px; height: 32px;" onclick="return confirm('Hapus user ini?')">
                            <i class="fas fa-trash fa-xs"></i>
                        </a>
                    </td>
                </tr>

                <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <form action="process/anggota_edit.php" method="POST">
                                <div class="modal-header border-bottom-0 pb-0">
                                    <h5 class="modal-title fw-bold">Edit Anggota</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <div class="mb-3">
                                        <label class="small text-muted fw-bold">NAMA LENGKAP</label>
                                        <input type="text" name="nama" class="form-control bg-light border-0" value="<?= htmlspecialchars($row['nama_lengkap']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="small text-muted fw-bold">USERNAME</label>
                                        <input type="text" name="username" class="form-control bg-light border-0" value="<?= htmlspecialchars($row['username']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="small text-muted fw-bold">NOMOR HP / WA</label>
                                        <input type="text" name="no_hp" class="form-control bg-light border-0" value="<?= htmlspecialchars($row['no_hp']) ?>" placeholder="0812...">
                                    </div>
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <label class="small text-muted fw-bold">ROLE</label>
                                            <select name="role" class="form-select bg-light border-0">
                                                <option value="guru" <?= $row['role']=='guru'?'selected':'' ?>>Guru</option>
                                                <option value="staff" <?= $row['role']=='staff'?'selected':'' ?>>Staff</option>
                                                <option value="pengurus" <?= $row['role']=='pengurus'?'selected':'' ?>>Pengurus</option>
                                            </select>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="small text-muted fw-bold">STATUS</label>
                                            <select name="status_aktif" class="form-select bg-light border-0">
                                                <option value="1" <?= $row['status_aktif']==1?'selected':'' ?>>Aktif</option>
                                                <option value="0" <?= $row['status_aktif']==0?'selected':'' ?>>Blokir</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="small text-muted fw-bold">PASSWORD BARU (KOSONGKAN JIKA TETAP)</label>
                                        <input type="password" name="password_baru" class="form-control bg-light border-0">
                                    </div>
                                </div>
                                <div class="modal-footer border-top-0 pt-0">
                                    <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-bold shadow-sm">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="process/anggota_tambah.php" method="POST">
                <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i> Tambah Anggota</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small text-muted fw-bold">NAMA LENGKAP</label>
                        <input type="text" name="nama" class="form-control form-control-lg bg-light border-0" required placeholder="Nama Lengkap">
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-bold">USERNAME</label>
                        <input type="text" name="username" class="form-control form-control-lg bg-light border-0" required placeholder="Username Login">
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-bold">NOMOR HP / WA</label>
                        <input type="text" name="no_hp" class="form-control form-control-lg bg-light border-0" placeholder="0812...">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small text-muted fw-bold">PASSWORD AWAL</label>
                            <input type="text" name="password" class="form-control form-control-lg bg-light border-0" value="123456" readonly>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small text-muted fw-bold">ROLE</label>
                            <select name="role" class="form-select form-select-lg bg-light border-0">
                                <option value="guru">Guru</option>
                                <option value="staff">Staff</option>
                                <option value="pengurus">Pengurus</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow">Simpan Data Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>