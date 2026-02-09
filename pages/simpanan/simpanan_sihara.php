<?php
if(isset($_POST['simpan'])){
    $anggota_id = $_POST['anggota_id'];
    $jumlah = $_POST['jumlah'];
    $tipe = $_POST['tipe']; 
    $ket = $_POST['keterangan'];
    $tanggal = date('Y-m-d');

    if($jumlah > 0){
        $stmt = $pdo->prepare("INSERT INTO simpanan (anggota_id, tanggal, jenis_simpanan, jumlah, tipe_transaksi, keterangan) VALUES (?, ?, 'hari_raya', ?, ?, ?)");
        $stmt->execute([$anggota_id, $tanggal, $jumlah, $tipe, $ket]);
        echo "<script>alert('Transaksi Sihara Berhasil!'); window.location='index.php?page=simpanan_sihara';</script>";
    }
}
// Data untuk Dropdown & Tabel
$anggota = $pdo->query("SELECT * FROM anggota ORDER BY nama_lengkap ASC")->fetchAll();
$riwayat = $pdo->query("SELECT s.*, a.nama_lengkap FROM simpanan s JOIN anggota a ON s.anggota_id = a.id WHERE s.jenis_simpanan = 'hari_raya' ORDER BY s.tanggal DESC")->fetchAll();
?>

<h3 class="mb-4 text-primary"><i class="fas fa-wallet"></i> Rekap Sihara (Hari Raya)</h3>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">Input Transaksi Sihara</div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label>Nama Anggota</label>
                    <select name="anggota_id" class="form-select" required>
                        <option value="">-- Pilih Guru/Staff --</option>
                        <?php foreach($anggota as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= $a['nama_lengkap'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label>Jenis Transaksi</label>
                    <select name="tipe" class="form-select">
                        <option value="setor">Setor (Menabung)</option>
                        <option value="tarik">Tarik (Ambil THR)</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label>Nominal (Rp)</label>
                    <input type="number" name="jumlah" class="form-control" required placeholder="0">
                </div>
                <div class="col-md-2 mb-2 d-grid">
                    <label>&nbsp;</label>
                    <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
                </div>
            </div>
            <input type="text" name="keterangan" class="form-control mt-2" placeholder="Keterangan tambahan (Opsional)">
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Anggota</th>
                    <th>Ket</th>
                    <th>Masuk (Debit)</th>
                    <th>Keluar (Kredit)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($riwayat as $r): 
                    $masuk = ($r['tipe_transaksi'] == 'setor') ? $r['jumlah'] : 0;
                    $keluar = ($r['tipe_transaksi'] == 'tarik') ? $r['jumlah'] : 0;
                ?>
                <tr>
                    <td><?= tglIndo($r['tanggal']) ?></td>
                    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($r['keterangan']) ?></td>
                    <td class="text-success text-end"><?= $masuk > 0 ? formatRp($masuk) : '-' ?></td>
                    <td class="text-danger text-end"><?= $keluar > 0 ? formatRp($keluar) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>