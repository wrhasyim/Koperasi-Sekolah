<?php
// pages/inventory/stok_koperasi.php
require_once 'config/database.php';

// 1. TAMBAH BARANG
if(isset($_POST['tambah_barang'])){
    try {
        $nama = $_POST['nama_barang']; $modal = (float)str_replace('.', '', $_POST['harga_modal']); $jual = (float)str_replace('.', '', $_POST['harga_jual']); $stok = (int)$_POST['stok'];
        $cek = $pdo->prepare("SELECT id FROM stok_koperasi WHERE nama_barang = ?"); $cek->execute([$nama]);
        if($cek->rowCount() > 0){ echo "<script>alert('Gagal! Nama barang sudah ada.');</script>"; } 
        else {
            $pdo->prepare("INSERT INTO stok_koperasi (nama_barang, harga_modal, harga_jual, stok, kategori, created_at) VALUES (?, ?, ?, ?, 'jajan', NOW())")->execute([$nama, $modal, $jual, $stok]);
            echo "<script>alert('Barang ditambahkan!'); window.location='index.php?page=inventory/stok_koperasi';</script>";
        }
    } catch(Exception $e){ echo "<script>alert('Error: ".$e->getMessage()."');</script>"; }
}

// 2. EDIT BARANG (Hanya Nama & Harga)
if(isset($_POST['edit_barang'])){
    try {
        $id = $_POST['id_barang']; $nama = $_POST['nama_barang']; $jual = (float)str_replace('.', '', $_POST['harga_jual']);
        // Harga modal sebaiknya tidak diedit manual disini kecuali terpaksa, karena otomatis dari belanja
        $pdo->prepare("UPDATE stok_koperasi SET nama_barang = ?, harga_jual = ? WHERE id = ?")->execute([$nama, $jual, $id]);
        echo "<script>alert('Data diupdate!'); window.location='index.php?page=inventory/stok_koperasi';</script>";
    } catch(Exception $e){ echo "<script>alert('Error: ".$e->getMessage()."');</script>"; }
}

// 3. STOCK OPNAME (Koreksi Mutlak)
if(isset($_POST['simpan_opname'])){
    try {
        $id = $_POST['id_barang_opname'];
        $stok_fisik = (int)$_POST['stok_fisik']; // Angka mutlak dari fisik
        $keterangan = $_POST['keterangan_opname'];
        $user_id = $_SESSION['user']['id'];

        // Ambil stok sistem saat ini
        $stmt = $pdo->prepare("SELECT stok, nama_barang FROM stok_koperasi WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        $stok_sistem = $data['stok'];
        
        $selisih = $stok_fisik - $stok_sistem;

        if($selisih != 0){
            // Update stok menjadi angka fisik
            $pdo->prepare("UPDATE stok_koperasi SET stok = ? WHERE id = ?")->execute([$stok_fisik, $id]);
            
            // Catat Log
            $jenis_selisih = ($selisih > 0) ? "Surplus (Lebih)" : "Defisit (Kurang)";
            $msg = "Opname $data[nama_barang]: Sistem($stok_sistem) -> Fisik($stok_fisik). Selisih: $selisih ($jenis_selisih). Ket: $keterangan";
            catatLog($pdo, $user_id, 'Stock Opname', $msg);
            
            echo "<script>alert('Stok dikoreksi jadi $stok_fisik! Selisih $selisih tercatat di log.'); window.location='index.php?page=inventory/stok_koperasi';</script>";
        } else {
            echo "<script>alert('Jumlah sama. Tidak ada perubahan.'); window.location='index.php?page=inventory/stok_koperasi';</script>";
        }
    } catch(Exception $e){ echo "<script>alert('Error: ".$e->getMessage()."');</script>"; }
}

$list_barang = $pdo->query("SELECT * FROM stok_koperasi ORDER BY nama_barang ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h6 class="text-muted small ls-1 mb-1">Inventory</h6><h2 class="h3 fw-bold mb-0">Stok Koperasi</h2></div>
    <button class="btn btn-primary rounded-pill shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="fas fa-plus me-2"></i> Tambah Barang</button>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Nama Barang</th>
                        <th class="text-end">Modal (HPP)</th>
                        <th class="text-end">Jual</th>
                        <th class="text-end">Profit</th>
                        <th class="text-center">Stok</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($list_barang as $row): 
                        $profit = $row['harga_jual'] - $row['harga_modal'];
                        $bg_stok = ($row['stok'] <= 5) ? 'bg-danger text-white' : 'bg-light text-dark border';
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td class="text-end text-muted">Rp <?= number_format($row['harga_modal']) ?></td>
                        <td class="text-end fw-bold text-primary">Rp <?= number_format($row['harga_jual']) ?></td>
                        <td class="text-end text-success">Rp <?= number_format($profit) ?></td>
                        <td class="text-center"><span class="badge rounded-pill <?= $bg_stok ?> px-3"><?= $row['stok'] ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning rounded-circle me-1" onclick="editBarang('<?= $row['id'] ?>','<?= htmlspecialchars($row['nama_barang']) ?>','<?= $row['harga_jual'] ?>')" title="Edit Nama/Harga"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-info rounded-circle me-1" onclick="opnameBarang('<?= $row['id'] ?>','<?= htmlspecialchars($row['nama_barang']) ?>','<?= $row['stok'] ?>')" title="Stock Opname"><i class="fas fa-clipboard-check"></i></button>
                            <a href="process/stok_hapus.php?id=<?= $row['id'] ?>&redirect=inventory/stok_koperasi" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Hapus permanen?')" title="Hapus"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header border-0 bg-primary text-white"><h5 class="modal-title fw-bold">Tambah Barang</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="POST"><div class="modal-body"><div class="mb-3"><label class="fw-bold small">Nama Barang</label><input type="text" name="nama_barang" class="form-control" required></div><div class="row g-2"><div class="col-6 mb-3"><label class="fw-bold small">Harga Modal</label><input type="number" name="harga_modal" class="form-control" required></div><div class="col-6 mb-3"><label class="fw-bold small">Harga Jual</label><input type="number" name="harga_jual" class="form-control" required></div></div><div class="mb-3"><label class="fw-bold small">Stok Awal</label><input type="number" name="stok" class="form-control" value="0" required></div></div><div class="modal-footer border-0"><button type="submit" name="tambah_barang" class="btn btn-primary w-100 rounded-pill fw-bold">Simpan</button></div></form></div></div></div>

<div class="modal fade" id="modalEdit" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header border-0 bg-warning"><h5 class="modal-title fw-bold text-dark">Edit Barang</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST"><div class="modal-body"><input type="hidden" name="id_barang" id="edit_id"><div class="mb-3"><label class="fw-bold small">Nama Barang</label><input type="text" name="nama_barang" id="edit_nama" class="form-control" required></div><div class="mb-3"><label class="fw-bold small text-primary">Harga Jual</label><input type="number" name="harga_jual" id="edit_jual" class="form-control fw-bold border-primary" required></div></div><div class="modal-footer border-0"><button type="submit" name="edit_barang" class="btn btn-warning w-100 rounded-pill fw-bold">Update</button></div></form></div></div></div>

<div class="modal fade" id="modalOpname" tabindex="-1"><div class="modal-dialog"><div class="modal-content border-0"><div class="modal-header border-0 bg-info text-white"><h5 class="modal-title fw-bold">Stock Opname (Cek Fisik)</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="POST"><div class="modal-body"><input type="hidden" name="id_barang_opname" id="opname_id"><div class="text-center mb-4"><h6 class="text-muted small mb-1">Barang</h6><h4 class="fw-bold text-dark" id="opname_nama"></h4></div><div class="row g-3 align-items-center justify-content-center mb-3"><div class="col-5 text-center"><label class="d-block small fw-bold text-muted mb-1">Stok Sistem</label><input type="text" id="opname_sistem" class="form-control text-center bg-light fw-bold" readonly></div><div class="col-2 text-center"><i class="fas fa-arrow-right text-muted"></i></div><div class="col-5 text-center"><label class="d-block small fw-bold text-primary mb-1">Fisik Nyata</label><input type="number" name="stok_fisik" class="form-control text-center fw-bold border-primary" required placeholder="0"></div></div><div class="mb-3"><label class="form-label small fw-bold">Alasan Koreksi</label><textarea name="keterangan_opname" class="form-control" rows="2" placeholder="Cth: Barang rusak, hilang, atau salah hitung" required></textarea></div></div><div class="modal-footer border-0"><button type="submit" name="simpan_opname" class="btn btn-info w-100 rounded-pill fw-bold text-white">Simpan Perubahan Stok</button></div></form></div></div></div>

<script>
function editBarang(id, nama, jual){
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_jual').value = jual;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
function opnameBarang(id, nama, stok){
    document.getElementById('opname_id').value = id;
    document.getElementById('opname_nama').innerText = nama;
    document.getElementById('opname_sistem').value = stok;
    new bootstrap.Modal(document.getElementById('modalOpname')).show();
}
</script>