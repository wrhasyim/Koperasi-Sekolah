<?php
// pages/kasbon/index.php
require_once 'config/database.php';

// PROSES TAMBAH KASBON JAJAN
if(isset($_POST['tambah_kasbon'])){
    $anggota_id = $_POST['anggota_id'];
    $total      = $_POST['total'];
    $ket        = $_POST['keterangan'];
    $tanggal    = $_POST['tanggal']; // Dari input form

    // PERBAIKAN: Sesuaikan dengan nama kolom di database Anda
    // Kolom: anggota_id, tanggal_pinjam, total_belanja, sisa_pinjaman, keterangan, status
    $sql = "INSERT INTO kasbon (anggota_id, tanggal_pinjam, total_belanja, sisa_pinjaman, keterangan, status) VALUES (?, ?, ?, ?, ?, 'belum')";
    $stmt = $pdo->prepare($sql);
    
    // Kita set sisa_pinjaman = total_belanja di awal
    $stmt->execute([$anggota_id, $tanggal, $total, $total, $ket]);

    echo "<script>alert('Kasbon berhasil dicatat!'); window.location='index.php?page=kasbon/index';</script>";
}

// PROSES BAYAR KASBON (LUNAS)
if(isset($_POST['bayar_kasbon'])){
    $id = $_POST['id_kasbon'];
    $nominal = $_POST['nominal']; 
    
    // 1. Update Status Lunas & Sisa 0 di tabel 'kasbon'
    $pdo->prepare("UPDATE kasbon SET status = 'lunas', sisa_pinjaman = 0 WHERE id = ?")->execute([$id]);

    // 2. Masukkan Uang ke KAS
    $ket_kas = "Pelunasan Kasbon Jajan ID: $id";
    $pdo->prepare("INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (NOW(), 'pelunasan_kasbon', 'masuk', ?, ?, ?)")
        ->execute([$nominal, $ket_kas, $_SESSION['user']['id']]);

    echo "<script>alert('Kasbon Lunas & Masuk Kas!'); window.location='index.php?page=kasbon/index';</script>";
}

// DATA VIEW
$list_anggota = $pdo->query("SELECT * FROM anggota WHERE role != 'admin' ORDER BY nama_lengkap ASC")->fetchAll();

// QUERY KE TABEL KASBON
// Menggunakan 'tanggal_pinjam' sesuai struktur database Anda
$list_kasbon  = $pdo->query("SELECT k.*, a.nama_lengkap FROM kasbon k JOIN anggota a ON k.anggota_id = a.id ORDER BY k.status ASC, k.tanggal_pinjam DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Operasional</h6>
        <h2 class="h3 fw-bold mb-0">Kasbon Belanja (Jajan/ATK)</h2>
    </div>
    <button class="btn btn-warning text-dark fw-bold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalKasbon">
        <i class="fas fa-plus-circle me-2"></i> Catat Kasbon Baru
    </button>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Tanggal</th>
                        <th>Nama Anggota</th>
                        <th>Keterangan Belanja</th>
                        <th class="text-end">Total Tagihan</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($list_kasbon as $row): ?>
                    <tr>
                        <td class="ps-4"><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($row['keterangan']) ?></td>
                        
                        <td class="text-end fw-bold text-danger"><?= formatRp($row['total_belanja']) ?></td>
                        
                        <td class="text-center">
                            <?php if($row['status']=='lunas'): ?>
                                <span class="badge bg-success rounded-pill">LUNAS</span>
                            <?php else: ?>
                                <span class="badge bg-secondary rounded-pill">BELUM</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if($row['status']!='lunas'): ?>
                                <form method="POST" onsubmit="return confirm('Lunasi kasbon ini? Uang akan masuk ke Kas.')">
                                    <input type="hidden" name="id_kasbon" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="nominal" value="<?= $row['total_belanja'] ?>">
                                    <button type="submit" name="bayar_kasbon" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold">
                                        <i class="fas fa-check me-1"></i> Lunasi
                                    </button>
                                </form>
                            <?php else: ?>
                                <i class="fas fa-check-double text-success"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKasbon" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0 bg-warning">
                <h5 class="modal-title fw-bold text-dark">Catat Kasbon Jajan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Siapa yang Ngutang?</label>
                        <select name="anggota_id" class="form-select" required>
                            <option value="">-- Pilih Guru/Staff --</option>
                            <?php foreach($list_anggota as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_lengkap']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Total Belanja (Rp)</label>
                        <input type="number" name="total" class="form-control fw-bold" required min="500">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Keterangan Barang</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Cth: Kopi 2, Gorengan 5"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="tambah_kasbon" class="btn btn-warning w-100 rounded-pill fw-bold">Simpan Hutang</button>
                </div>
            </form>
        </div>
    </div>
</div>