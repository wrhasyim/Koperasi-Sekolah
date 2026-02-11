<?php
// pages/pinjaman/index.php
require_once 'config/database.php';

// AMBIL SETTING BUNGA
$bunga_persen = getPengaturan($pdo, 'bunga_pinjaman');

// --- PROSES 1: TAMBAH PINJAMAN BARU ---
if(isset($_POST['tambah_pinjaman'])){
    try {
        $anggota_id = $_POST['anggota_id'];
        $jumlah     = $_POST['jumlah'];
        $tanggal    = $_POST['tanggal'];
        $ket        = $_POST['keterangan'];

        // Hitung Bunga
        $nominal_bunga = $jumlah * ($bunga_persen / 100);
        $total_tagihan = $jumlah + $nominal_bunga;

        $pdo->beginTransaction();

        // 1. Insert ke Tabel Pinjaman
        $sql = "INSERT INTO pinjaman_dana (anggota_id, tanggal_pinjam, jumlah_pinjam, bunga_persen, nominal_bunga, total_tagihan, sisa_tagihan, keterangan, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'belum')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$anggota_id, $tanggal, $jumlah, $bunga_persen, $nominal_bunga, $total_tagihan, $total_tagihan, $ket]);

        // 2. Catat Uang Keluar di KAS (Hanya Pokok)
        $sql_kas = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (?, 'pinjaman_anggota', 'keluar', ?, ?, ?)";
        $stmt_kas = $pdo->prepare($sql_kas);
        $ket_kas = "Pencairan Pinjaman: " . getNamaAnggota($pdo, $anggota_id);
        $stmt_kas->execute([$tanggal, $jumlah, $ket_kas, $_SESSION['user']['id']]); 

        $pdo->commit();
        echo "<script>alert('Pinjaman berhasil dicairkan!'); window.location='index.php?page=pinjaman/index';</script>";
    } catch(Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Gagal: " . $e->getMessage() . "');</script>";
    }
}

// --- PROSES 2: BAYAR CICILAN PINJAMAN ---
if(isset($_POST['bayar_cicilan'])){
    try {
        $pinjaman_id = $_POST['pinjaman_id'];
        $bayar       = $_POST['jumlah_bayar'];
        $tanggal     = date('Y-m-d');

        // Cek Sisa
        $stmt = $pdo->prepare("SELECT * FROM pinjaman_dana WHERE id = ?");
        $stmt->execute([$pinjaman_id]);
        $data = $stmt->fetch();

        if($bayar > $data['sisa_tagihan']){
            throw new Exception("Jumlah bayar melebihi sisa hutang!");
        }

        $sisa_baru = $data['sisa_tagihan'] - $bayar;
        $status_baru = ($sisa_baru <= 0) ? 'lunas' : 'belum';

        $pdo->beginTransaction();

        // 1. Update Pinjaman
        $sql_upd = "UPDATE pinjaman_dana SET sisa_tagihan = ?, status = ? WHERE id = ?";
        $pdo->prepare($sql_upd)->execute([$sisa_baru, $status_baru, $pinjaman_id]);

        // 2. Catat Riwayat
        $sql_riw = "INSERT INTO riwayat_bayar_pinjaman (pinjaman_id, tanggal_bayar, jumlah_bayar, sisa_akhir, user_id) VALUES (?, ?, ?, ?, ?)";
        $pdo->prepare($sql_riw)->execute([$pinjaman_id, $tanggal, $bayar, $sisa_baru, $_SESSION['user']['id']]);

        // 3. Catat Pemasukan Kas (Cicilan Masuk)
        $sql_kas = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (?, 'bayar_pinjaman', 'masuk', ?, ?, ?)";
        $ket_kas = "Cicilan Pinjaman: " . getNamaAnggota($pdo, $data['anggota_id']);
        $pdo->prepare($sql_kas)->execute([$tanggal, $bayar, $ket_kas, $_SESSION['user']['id']]);

        $pdo->commit();
        echo "<script>alert('Pembayaran berhasil!'); window.location='index.php?page=pinjaman/index';</script>";

    } catch(Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}

// DATA VIEW
$list_anggota = $pdo->query("SELECT * FROM anggota WHERE role != 'admin' ORDER BY nama_lengkap ASC")->fetchAll();
$sql_list = "SELECT p.*, a.nama_lengkap FROM pinjaman_dana p JOIN anggota a ON p.anggota_id = a.id ORDER BY p.status ASC, p.tanggal_pinjam DESC";
$list_pinjaman = $pdo->query($sql_list)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan</h6>
        <h2 class="h3 fw-bold mb-0">Pinjaman Dana (Guru & Staff)</h2>
    </div>
    <button class="btn btn-primary rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPinjam">
        <i class="fas fa-plus-circle me-2"></i> Ajukan Pinjaman
    </button>
</div>

<div class="alert alert-info border-0 shadow-sm rounded-3 mb-4">
    <i class="fas fa-info-circle me-2"></i> Bunga Pinjaman: <strong><?= $bunga_persen ?>%</strong> (Setting di menu Pengaturan).
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Tanggal</th>
                        <th>Peminjam</th>
                        <th class="text-end">Pokok</th>
                        <th class="text-end">Bunga</th>
                        <th class="text-end">Total Tagihan</th>
                        <th class="text-end">Sisa Hutang</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($list_pinjaman as $row): ?>
                    <tr>
                        <td class="ps-4"><?= date('d/m/y', strtotime($row['tanggal_pinjam'])) ?></td>
                        <td class="fw-bold"><?= $row['nama_lengkap'] ?></td>
                        <td class="text-end"><?= number_format($row['jumlah_pinjam']) ?></td>
                        <td class="text-end text-danger">+ <?= number_format($row['nominal_bunga']) ?></td>
                        <td class="text-end fw-bold"><?= number_format($row['total_tagihan']) ?></td>
                        <td class="text-end fw-bold text-primary"><?= number_format($row['sisa_tagihan']) ?></td>
                        <td class="text-center">
                            <?php if($row['status']=='lunas'): ?>
                                <span class="badge bg-success rounded-pill">LUNAS</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark rounded-pill">BELUM</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if($row['status']!='lunas'): ?>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" 
                                    onclick="bayar('<?= $row['id'] ?>', '<?= $row['nama_lengkap'] ?>', '<?= $row['sisa_tagihan'] ?>')">
                                Bayar
                            </button>
                            <?php else: ?>
                                <i class="fas fa-check text-success"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'modal_pinjaman_partial.php'; // Atau paste modal code dari chat sebelumnya disini ?> 
<script>
function bayar(id, nama, sisa){
    document.getElementById('bayar_id').value = id;
    document.getElementById('bayar_nama').innerText = nama;
    document.getElementById('bayar_sisa_txt').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(sisa);
    new bootstrap.Modal(document.getElementById('modalBayar')).show();
}
</script>