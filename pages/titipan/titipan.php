<?php
// PROSES 1: TAMBAH BARANG TITIPAN BARU
if(isset($_POST['tambah_titipan'])){
    $anggota_id = $_POST['anggota_id'];
    $nama_barang = $_POST['nama_barang'];
    $stok = $_POST['stok'];
    $modal = $_POST['harga_modal'];
    $jual = $_POST['harga_jual'];
    $tgl = date('Y-m-d');

    $sql = "INSERT INTO titipan (anggota_id, nama_barang, tanggal_titip, stok_awal, harga_modal, harga_jual) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$anggota_id, $nama_barang, $tgl, $stok, $modal, $jual]);
    echo "<script>alert('Barang Titipan Ditambahkan!'); window.location='index.php?page=titipan';</script>";
}

// PROSES 2: UPDATE STOK TERJUAL
if(isset($_POST['update_stok'])){
    $id = $_POST['titipan_id'];
    $terjual_baru = $_POST['stok_terjual'];
    
    // Validasi agar terjual tidak melebihi stok awal
    $cek = $pdo->prepare("SELECT stok_awal FROM titipan WHERE id=?");
    $cek->execute([$id]);
    $dt = $cek->fetch();

    if($terjual_baru > $dt['stok_awal']){
        echo "<script>alert('Error: Jumlah terjual melebihi stok awal!');</script>";
    } else {
        $pdo->prepare("UPDATE titipan SET stok_terjual = ? WHERE id = ?")->execute([$terjual_baru, $id]);
        echo "<script>window.location='index.php?page=titipan';</script>";
    }
}

// AMBIL DATA GABUNGAN
$query = "SELECT t.*, a.nama_lengkap 
          FROM titipan t 
          JOIN anggota a ON t.anggota_id = a.id 
          ORDER BY t.tanggal_titip DESC";
$data = $pdo->query($query)->fetchAll();
$anggota = $pdo->query("SELECT * FROM anggota ORDER BY nama_lengkap ASC")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Konsinyasi / Titipan Guru</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTitip">
        <i class="fas fa-plus"></i> Titip Barang Baru
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-sm">
                <thead class="table-light">
                    <tr>
                        <th>Pemilik (Guru)</th>
                        <th>Barang</th>
                        <th>Stok</th>
                        <th>Laku</th>
                        <th>Sisa</th>
                        <th>Hak Guru (Modal)</th>
                        <th>Laba Koperasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_setor = 0;
                    $total_laba = 0;
                    foreach($data as $row): 
                        $sisa = $row['stok_awal'] - $row['stok_terjual'];
                        $hak_guru = $row['stok_terjual'] * $row['harga_modal'];
                        $laba = $row['stok_terjual'] * ($row['harga_jual'] - $row['harga_modal']);
                        
                        $total_setor += $hak_guru;
                        $total_laba += $laba;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                        <td>
                            <b><?= htmlspecialchars($row['nama_barang']) ?></b><br>
                            <small class="text-muted">Jual: <?= number_format($row['harga_jual']) ?></small>
                        </td>
                        <td class="text-center bg-light"><?= $row['stok_awal'] ?></td>
                        <td class="text-center fw-bold text-success"><?= $row['stok_terjual'] ?></td>
                        <td class="text-center text-danger"><?= $sisa ?></td>
                        
                        <td class="text-end bg-warning bg-opacity-10">
                            <?= formatRp($hak_guru) ?>
                        </td>
                        
                        <td class="text-end fw-bold text-success">
                            <?= formatRp($laba) ?>
                        </td>
                        
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalUpdate<?= $row['id'] ?>">
                                <i class="fas fa-edit"></i> Update
                            </button>
                            
                            <div class="modal fade" id="modalUpdate<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h6 class="modal-title">Update Terjual</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="titipan_id" value="<?= $row['id'] ?>">
                                                <div class="mb-2">
                                                    <label>Stok Awal: <b><?= $row['stok_awal'] ?></b></label>
                                                </div>
                                                <div class="mb-3">
                                                    <label>Total Terjual (Akumulasi)</label>
                                                    <input type="number" name="stok_terjual" class="form-control" value="<?= $row['stok_terjual'] ?>" max="<?= $row['stok_awal'] ?>" required>
                                                    <small class="text-danger">Masukkan jumlah total yang laku sampai hari ini.</small>
                                                </div>
                                                <button type="submit" name="update_stok" class="btn btn-primary w-100">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-dark">
                        <td colspan="5" class="text-end">TOTAL KESELURUHAN</td>
                        <td class="text-end"><?= formatRp($total_setor) ?></td>
                        <td class="text-end"><?= formatRp($total_laba) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTitip" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Input Barang Titipan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Pemilik Barang (Guru)</label>
                        <select name="anggota_id" class="form-select" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach($anggota as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= $a['nama_lengkap'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Nama Barang</label>
                        <input type="text" name="nama_barang" class="form-control" placeholder="Contoh: Keripik Pisang" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Stok Awal</label>
                            <input type="number" name="stok" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Harga Modal</label>
                            <input type="number" name="harga_modal" class="form-control" placeholder="Setor ke Guru" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Harga Jual</label>
                            <input type="number" name="harga_jual" class="form-control" placeholder="Jual di Toko" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_titipan" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>