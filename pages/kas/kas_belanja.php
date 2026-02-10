<?php
// AMBIL DATA BARANG UTK DROPDOWN
$barang_toko = $pdo->query("SELECT * FROM stok_koperasi ORDER BY nama_barang ASC")->fetchAll();

// --- LOGIKA SIMPAN PENGELUARAN & UPDATE STOK ---
if(isset($_POST['simpan_pengeluaran'])){
    $tanggal    = $_POST['tanggal'];
    $kategori   = $_POST['kategori']; 
    $jumlah     = (float) str_replace('.', '', $_POST['jumlah']); // Hapus titik jika ada format ribuan
    $keterangan = $_POST['keterangan'];
    $link_stok  = isset($_POST['link_stok']) ? $_POST['link_stok'] : 'tidak';
    
    $user_id    = $_SESSION['user']['id'];

    // Validasi Tutup Buku
    if(cekStatusPeriode($pdo, $tanggal)){
        setFlash('danger', 'Gagal! Periode transaksi tanggal tersebut sudah Tutup Buku.');
    } elseif($jumlah <= 0){
        setFlash('danger', 'Nominal pengeluaran harus lebih dari 0.');
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Simpan Transaksi Kas (Uang Keluar)
            $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                    VALUES (?, ?, 'keluar', ?, ?, ?)";
            $pdo->prepare($sql)->execute([$tanggal, $kategori, $jumlah, $keterangan, $user_id]);

            // 2. Jika Link Stok Aktif -> Update Inventory
            if($link_stok == 'ya' && $kategori == 'belanja_stok'){
                $id_barang = $_POST['id_barang_stok'];
                $qty_beli  = (int) $_POST['qty_beli'];
                
                if($qty_beli > 0){
                    // Update Stok & Harga Modal (Opsional: Disini pakai update stok saja)
                    $pdo->prepare("UPDATE stok_koperasi SET stok = stok + ? WHERE id = ?")
                        ->execute([$qty_beli, $id_barang]);
                    
                    // Update Keterangan Log
                    $nama_brg = "";
                    foreach($barang_toko as $b) { if($b['id'] == $id_barang) $nama_brg = $b['nama_barang']; }
                    
                    catatLog($pdo, $user_id, 'Restock', "Belanja $nama_brg sebanyak $qty_beli pcs via Kas Belanja.");
                }
            }

            // 3. Catat Log Umum
            catatLog($pdo, $user_id, 'Pengeluaran', "Input pengeluaran: $keterangan (Rp " . number_format($jumlah) . ")");

            $pdo->commit();
            setFlash('success', 'Pengeluaran Berhasil Disimpan & Sinkron!');
            echo "<script>window.location='index.php?page=kas/kas_belanja';</script>";

        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// History 10 Terakhir
$riwayat = $pdo->query("SELECT * FROM transaksi_kas WHERE arus='keluar' ORDER BY tanggal DESC, id DESC LIMIT 10")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan</h6>
        <h1 class="h2 fw-bold">Input Pengeluaran Operasional</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-lg border-0 rounded-4 mb-4">
            <div class="card-header bg-danger text-white py-3 rounded-top-4">
                <h6 class="mb-0 fw-bold"><i class="fas fa-money-bill-wave me-2"></i> Form Uang Keluar</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Tanggal Transaksi</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Jenis Pengeluaran</label>
                        <select name="kategori" id="kategori" class="form-select" required onchange="toggleStok()">
                            <option value="">-- Pilih Jenis --</option>
                            <option value="belanja_stok">Belanja Barang Toko (Kulakan)</option>
                            <option value="gaji_staff">Gaji Staff / Karyawan</option>
                            <option value="honor_pengurus">Honor Pengurus</option>
                            <option value="dana_sosial">Dana Sosial / Sumbangan</option>
                            <option value="biaya_operasional">Listrik / Air / Internet</option>
                            <option value="operasional_lain">Lain-lain (ATK Kantor, dll)</option>
                        </select>
                    </div>

                    <div id="area_stok" class="bg-warning bg-opacity-10 p-3 rounded-3 border border-warning mb-3" style="display:none;">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="link_stok" value="ya" id="cekLinkStok" checked onchange="toggleInputStok()">
                            <label class="form-check-label fw-bold text-dark small" for="cekLinkStok">Otomatis Tambah Stok?</label>
                        </div>
                        
                        <div id="input_detail_stok">
                            <div class="mb-2">
                                <select name="id_barang_stok" class="form-select form-select-sm">
                                    <option value="">-- Pilih Barang yg Dibeli --</option>
                                    <?php foreach($barang_toko as $b): ?>
                                        <option value="<?= $b['id'] ?>"><?= $b['nama_barang'] ?> (Sisa: <?= $b['stok'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text small"><a href="inventory/stok_koperasi" class="text-decoration-none">Barang baru? Input di Inventory dulu.</a></div>
                            </div>
                            <div class="mb-0">
                                <input type="number" name="qty_beli" class="form-control form-control-sm" placeholder="Jumlah Pcs (Qty)" min="1">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nominal (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 fw-bold">Rp</span>
                            <input type="number" name="jumlah" class="form-control border-start-0" placeholder="0" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Rincian Keterangan</label>
                        <textarea name="keterangan" class="form-control bg-light" rows="2" placeholder="Contoh: Beli Kopi 5 renceng, Gula 1kg" required></textarea>
                    </div>
                    
                    <button type="submit" name="simpan_pengeluaran" class="btn btn-danger w-100 py-2 fw-bold rounded-pill shadow-sm">
                        <i class="fas fa-save me-2"></i> SIMPAN PENGELUARAN
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-muted"><i class="fas fa-history me-2"></i> 10 Transaksi Terakhir</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">Tanggal</th>
                                <th>Kategori</th>
                                <th>Keterangan</th>
                                <th class="text-end pe-3">Jumlah</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($riwayat as $row): ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?= date('d/m/y', strtotime($row['tanggal'])) ?></td>
                                <td>
                                    <?php 
                                        $kat = $row['kategori'];
                                        $bg = 'secondary';
                                        if($kat == 'belanja_stok') $bg = 'warning text-dark';
                                        if($kat == 'gaji_staff') $bg = 'info text-white';
                                        if($kat == 'honor_pengurus') $bg = 'primary';
                                        echo "<span class='badge bg-$bg rounded-pill px-2'>".strtoupper(str_replace('_',' ',$kat))."</span>";
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                <td class="text-end text-danger fw-bold pe-3">- <?= number_format($row['jumlah']) ?></td>
                                <td class="text-end pe-3">
                                    <a href="process/kas_hapus.php?id=<?= $row['id'] ?>&redirect=kas/kas_belanja" class="text-danger" onclick="return confirm('Hapus transaksi ini? Stok tidak akan berkurang otomatis (harus manual).')"><i class="fas fa-trash-alt"></i></a>
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

<script>
function toggleStok() {
    var kat = document.getElementById("kategori").value;
    var area = document.getElementById("area_stok");
    if(kat === "belanja_stok") {
        area.style.display = "block";
    } else {
        area.style.display = "none";
    }
}

function toggleInputStok() {
    var cek = document.getElementById("cekLinkStok");
    var input = document.getElementById("input_detail_stok");
    input.style.display = cek.checked ? "block" : "none";
}
</script>