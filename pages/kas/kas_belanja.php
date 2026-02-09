<?php
// LOGIKA SIMPAN PENGELUARAN
if(isset($_POST['simpan_pengeluaran'])){
    $tanggal = $_POST['tanggal'];
    $kategori = $_POST['kategori']; // belanja_stok, gaji_staff, dll
    $jumlah = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];

    if($jumlah > 0){
        // Arus: keluar
        $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                VALUES (?, ?, 'keluar', ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tanggal, $kategori, $jumlah, $keterangan, $_SESSION['user']['id']]);
        
        echo "<script>alert('Pengeluaran Berhasil Disimpan!'); window.location='index.php?page=kas_belanja';</script>";
    }
}

// AMBIL 10 PENGELUARAN TERAKHIR
$stmt = $pdo->query("SELECT * FROM transaksi_kas WHERE arus='keluar' ORDER BY tanggal DESC, id DESC LIMIT 10");
$riwayat = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Input Pengeluaran Operasional</h1>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-money-bill-wave"></i> Form Uang Keluar
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Tanggal Transaksi</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label>Jenis Pengeluaran (Kategori)</label>
                        <select name="kategori" class="form-select" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="belanja_stok">Belanja Barang Toko (Kulakan)</option>
                            <option value="gaji_staff">Gaji Staff (David)</option>
                            <option value="honor_pengurus">Honor Pengurus</option>
                            <option value="dana_sosial">Dana Sosial / Sumbangan</option>
                            <option value="operasional_lain">Lain-lain (Listrik/Air/ATK)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Nominal (Rp)</label>
                        <input type="number" name="jumlah" class="form-control" placeholder="0" required>
                    </div>

                    <div class="mb-3">
                        <label>Rincian Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Beli Kopi 5 renceng, Gula 1kg" required></textarea>
                    </div>
                    <button type="submit" name="simpan_pengeluaran" class="btn btn-danger w-100">Simpan Pengeluaran</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                10 Transaksi Pengeluaran Terakhir
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Keterangan</th>
                                <th class="text-end">Jumlah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($riwayat as $row): ?>
                            <tr>
                                <td><?= tglIndo($row['tanggal']) ?></td>
                                <td>
                                    <?php 
                                        if($row['kategori'] == 'belanja_stok') echo '<span class="badge bg-warning text-dark">BELANJA</span>';
                                        elseif($row['kategori'] == 'gaji_staff') echo '<span class="badge bg-info text-dark">GAJI</span>';
                                        elseif($row['kategori'] == 'honor_pengurus') echo '<span class="badge bg-primary">HONOR</span>';
                                        elseif($row['kategori'] == 'dana_sosial') echo '<span class="badge bg-success">SOSIAL</span>';
                                        else echo '<span class="badge bg-secondary">LAINNYA</span>';
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                <td class="text-end text-danger fw-bold"><?= formatRp($row['jumlah']) ?></td>
                                <td>
                                    <a href="process/kas_hapus.php?id=<?= $row['id'] ?>&redirect=kas_belanja" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus transaksi ini?')"><i class="fas fa-times"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>