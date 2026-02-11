<?php
// pages/kasbon/index.php
require_once 'config/database.php';

// PROSES TAMBAH KASBON (DENGAN POTONG STOK)
if(isset($_POST['tambah_kasbon'])){
    try {
        $anggota_id = $_POST['anggota_id'];
        $barang_id  = $_POST['barang_id']; // ID Barang Stok
        $qty        = $_POST['qty'];
        $tanggal    = $_POST['tanggal']; // Input tanggal dari form

        $pdo->beginTransaction();

        // 1. Ambil Info Barang & Harga
        $stmt_barang = $pdo->prepare("SELECT nama_barang, harga_jual, stok FROM stok_koperasi WHERE id = ?");
        $stmt_barang->execute([$barang_id]);
        $barang = $stmt_barang->fetch();

        if(!$barang){
            throw new Exception("Barang tidak ditemukan!");
        }
        if($barang['stok'] < $qty){
            throw new Exception("Stok tidak cukup! Sisa stok: " . $barang['stok']);
        }

        // 2. Hitung Total Hutang
        $total_belanja = $barang['harga_jual'] * $qty;
        $keterangan = $barang['nama_barang'] . " (x" . $qty . ")";

        // 3. Kurangi Stok
        $stmt_stok = $pdo->prepare("UPDATE stok_koperasi SET stok = stok - ? WHERE id = ?");
        $stmt_stok->execute([$qty, $barang_id]);

        // 4. Simpan ke Tabel Kasbon
        // PERBAIKAN: Menggunakan kolom 'tanggal_pinjam' dan 'sisa_pinjaman'
        $sql = "INSERT INTO kasbon (anggota_id, tanggal_pinjam, total_belanja, sisa_pinjaman, status, keterangan) VALUES (?, ?, ?, ?, 'belum', ?)";
        $stmt_kasbon = $pdo->prepare($sql);
        // Kita set sisa_pinjaman = total_belanja di awal
        $stmt_kasbon->execute([$anggota_id, $tanggal, $total_belanja, $total_belanja, $keterangan]);

        $pdo->commit();
        echo "<script>alert('Kasbon berhasil dicatat & Stok berkurang!'); window.location='index.php?page=kasbon/index';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Gagal: " . $e->getMessage() . "');</script>";
    }
}

// PROSES BAYAR KASBON (LUNAS)
if(isset($_POST['bayar_kasbon'])){
    $id = $_POST['id_kasbon'];
    $nominal = $_POST['nominal']; 
    
    // Update Status Lunas & Sisa jadi 0
    $pdo->prepare("UPDATE kasbon SET status = 'lunas', sisa_pinjaman = 0 WHERE id = ?")->execute([$id]);

    // Masuk Kas
    $ket_kas = "Pelunasan Kasbon ID: $id";
    $pdo->prepare("INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (NOW(), 'pelunasan_kasbon', 'masuk', ?, ?, ?)")
        ->execute([$nominal, $ket_kas, $_SESSION['user']['id']]);

    echo "<script>alert('Kasbon Lunas & Masuk Kas!'); window.location='index.php?page=kasbon/index';</script>";
}

// DATA VIEW
$list_anggota = $pdo->query("SELECT * FROM anggota WHERE role != 'admin' ORDER BY nama_lengkap ASC")->fetchAll();
$list_barang  = $pdo->query("SELECT * FROM stok_koperasi WHERE stok > 0 ORDER BY nama_barang ASC")->fetchAll();

// PERBAIKAN QUERY: Menggunakan 'tanggal_pinjam'
$list_kasbon  = $pdo->query("SELECT k.*, a.nama_lengkap FROM kasbon k JOIN anggota a ON k.anggota_id = a.id ORDER BY k.status ASC, k.tanggal_pinjam DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Operasional</h6>
        <h2 class="h3 fw-bold mb-0">Kasbon Belanja (Jajan/ATK)</h2>
    </div>
    <button class="btn btn-warning text-dark fw-bold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalKasbon">
        <i class="fas fa-plus-circle me-2"></i> Tambah Kasbon (Potong Stok)
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
                        <th>Barang Diambil</th>
                        <th class="text-end">Total Hutang</th>
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
                <h5 class="modal-title fw-bold text-dark">Ambil Barang (Kasbon)</h5>
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
                        <label class="form-label small fw-bold">Pilih Barang</label>
                        <select name="barang_id" class="form-select" id="selectBarang" required>
                            <option value="" data-harga="0">-- Pilih Stok Koperasi --</option>
                            <?php foreach($list_barang as $b): ?>
                                <option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_jual'] ?>">
                                    <?= htmlspecialchars($b['nama_barang']) ?> (Stok: <?= $b['stok'] ?>) - <?= formatRp($b['harga_jual']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jumlah (Qty)</label>
                        <input type="number" name="qty" id="inputQty" class="form-control" value="1" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tanggal Ambil</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="alert alert-warning small border-0 mb-0">
                        <i class="fas fa-info-circle me-1"></i> Total Hutang: 
                        <strong id="totalLabel">Rp 0</strong>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="tambah_kasbon" class="btn btn-warning w-100 rounded-pill fw-bold">Simpan & Kurangi Stok</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script Sederhana Hitung Total Realtime
const selectBarang = document.getElementById('selectBarang');
const inputQty = document.getElementById('inputQty');
const totalLabel = document.getElementById('totalLabel');

function hitung(){
    let harga = 0;
    if(selectBarang.selectedIndex > 0){
        harga = selectBarang.options[selectBarang.selectedIndex].getAttribute('data-harga') || 0;
    }
    let qty = inputQty.value || 0;
    let total = harga * qty;
    totalLabel.innerText = "Rp " + new Intl.NumberFormat('id-ID').format(total);
}

selectBarang.addEventListener('change', hitung);
inputQty.addEventListener('input', hitung);
</script>