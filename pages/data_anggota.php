<?php
$stmt = $pdo->query("SELECT * FROM anggota ORDER BY nama_lengkap ASC");
$anggota = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Anggota</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus"></i> Tambah Anggota
    </button>
</div>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; foreach($anggota as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td>
                            <?php 
                            if($row['role']=='admin') echo '<span class="badge bg-danger">ADMIN</span>';
                            elseif($row['role']=='staff') echo '<span class="badge bg-warning text-dark">STAFF</span>';
                            else echo '<span class="badge bg-info text-dark">GURU</span>'; 
                            ?>
                        </td>
                        <td>
                            <?= $row['status_aktif'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="process/anggota_hapus.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin hapus data ini? Data simpanan terkait mungkin akan hilang/error.')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="process/anggota_edit.php" method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Anggota</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <div class="mb-3">
                                            <label>Nama Lengkap</label>
                                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($row['nama_lengkap']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Username</label>
                                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($row['username']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Role</label>
                                            <select name="role" class="form-select">
                                                <option value="guru" <?= $row['role']=='guru'?'selected':'' ?>>Guru</option>
                                                <option value="staff" <?= $row['role']=='staff'?'selected':'' ?>>Staff</option>
                                                <option value="pengurus" <?= $row['role']=='pengurus'?'selected':'' ?>>Pengurus</option>
                                                <option value="admin" <?= $row['role']=='admin'?'selected':'' ?>>Admin</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label>Status Akun</label>
                                            <select name="status_aktif" class="form-select">
                                                <option value="1" <?= $row['status_aktif']==1?'selected':'' ?>>Aktif</option>
                                                <option value="0" <?= $row['status_aktif']==0?'selected':'' ?>>Non-Aktif (Blokir)</option>
                                            </select>
                                        </div>
                                        <hr>
                                        <div class="mb-3">
                                            <label class="text-muted small">Reset Password (Isi jika ingin mengganti)</label>
                                            <input type="password" name="password_baru" class="form-control" placeholder="Kosongkan jika tidak diganti">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process/anggota_tambah.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Anggota Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Budi Santoso">
                    </div>
                    <div class="mb-3">
                        <label>Username (Login)</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password Default</label>
                        <input type="text" name="password" class="form-control" value="123456" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Role / Jabatan</label>
                        <select name="role" class="form-select">
                            <option value="guru">Guru (Anggota)</option>
                            <option value="staff">Staff (David)</option>
                            <option value="pengurus">Pengurus</option>
                            <option value="admin">Admin System</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>