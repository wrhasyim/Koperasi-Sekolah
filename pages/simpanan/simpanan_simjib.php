<?php
if(isset($_POST['simpan'])){
    $anggota_id = $_POST['anggota_id'];
    $jumlah = $_POST['jumlah'];
    $ket = $_POST['keterangan'];
    $tanggal = date('Y-m-d');

    if($jumlah > 0){
        $stmt = $pdo->prepare("INSERT INTO simpanan (anggota_id, tanggal, jenis_simpanan, jumlah, tipe_transaksi, keterangan) VALUES (?, ?, 'wajib', ?, 'setor', ?)");
        $stmt->execute([$anggota_id, $tanggal, $jumlah, $ket]);
        // UPDATE REDIRECT
        echo "<script>alert('Simpanan Wajib Berhasil!'); window.location='index.php?page=simpanan/simpanan_simjib';</script>";
    }
}
$anggota = $pdo->query("SELECT * FROM anggota ORDER BY nama_lengkap ASC")->fetchAll();
$riwayat = $pdo->query("SELECT s.*, a.nama_lengkap FROM simpanan s JOIN anggota a ON s.anggota_id = a.id WHERE s.jenis_simpanan = 'wajib' ORDER BY s.tanggal DESC")->fetchAll();
?>

<h3 class="mb-4 text-success"><i class="fas fa-file-invoice-dollar"></i> Rekap Simjib (Wajib)</h3>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-success text-white">Input Simpanan Wajib</div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-5 mb-2">
                    <label>Nama Anggota</label>
                    <select name="anggota_id" class="form-select" required>
                        <option value="">-- Pilih Guru/Staff --</option>
                        <?php foreach($anggota as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= $a['nama_lengkap'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 mb-2">
                    <label>Nominal (Rp)</label>
                    <input type="number" name="jumlah" class="form-control" required placeholder="Biasanya 50000">
                </div>
                <div class="col-md-2 mb-2 d-grid">
                    <label>&nbsp;</label>
                    <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
                </div>
            </div>
            <input type="text" name="keterangan" class="form-control mt-2" placeholder="Keterangan (Misal: Iuran Bulan Agustus)">
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
                    <th>Keterangan</th>
                    <th>Jumlah Setor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($riwayat as $r): ?>
                <tr>
                    <td><?= tglIndo($r['tanggal']) ?></td>
                    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($r['keterangan']) ?></td>
                    <td class="text-end fw-bold"><?= formatRp($r['jumlah']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>