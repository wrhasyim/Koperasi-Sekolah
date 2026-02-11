<?php
// pages/kas/kas_belanja.php
require_once 'config/database.php';

// AMBIL DATA BARANG UNTUK DROPDOWN KULAKAN
$barang_toko = $pdo->query("SELECT * FROM stok_koperasi ORDER BY nama_barang ASC")->fetchAll();

if(isset($_POST['simpan_pengeluaran'])){
    try {
        $pdo->beginTransaction();

        $tanggal    = $_POST['tanggal'];
        $kategori   = $_POST['kategori']; 
        $keterangan = $_POST['keterangan'];
        $user_id    = $_SESSION['user']['id'];
        $total_nominal = 0;

        // KASUS 1: BELANJA STOK (Logic Average HPP tetap dipertahankan)
        if($kategori == 'belanja_stok'){
            $items  = $_POST['item_id'] ?? [];
            $qtys   = $_POST['item_qty'] ?? [];
            $prices = $_POST['item_price'] ?? []; 
            $detail_names = [];

            if(empty($items)){ throw new Exception("Belum ada barang yang dipilih!"); }

            for($i = 0; $i < count($items); $i++){
                $id_brg = $items[$i];
                $qty    = (int)$qtys[$i];
                $subtotal = (float)str_replace('.', '', $prices[$i]);

                if($qty > 0 && $subtotal > 0){
                    $total_nominal += $subtotal;

                    $cek_db = $pdo->prepare("SELECT stok, nama_barang, harga_modal FROM stok_koperasi WHERE id = ?");
                    $cek_db->execute([$id_brg]);
                    $data_db = $cek_db->fetch();

                    $stok_lama = ($data_db['stok'] < 0) ? 0 : $data_db['stok'];
                    $aset_lama = $stok_lama * $data_db['harga_modal'];
                    $stok_baru_calc = $stok_lama + $qty;

                    // Hitung Average HPP
                    $modal_final = ($stok_baru_calc > 0) ? round(($aset_lama + $subtotal) / $stok_baru_calc) : round($subtotal / $qty);

                    // Update Stok & Modal
                    $pdo->prepare("UPDATE stok_koperasi SET stok = stok + ?, harga_modal = ? WHERE id = ?")
                        ->execute([$qty, $modal_final, $id_brg]);

                    $detail_names[] = $data_db['nama_barang'] . " (x$qty)";
                }
            }
            $keterangan_full = "Belanja Stok: " . implode(", ", $detail_names) . ". " . $keterangan;

        // KASUS 2: BEBAN OPERASIONAL (Gaji, Listrik, Kebersihan, dll)
        } else {
            $total_nominal = (float)str_replace('.', '', $_POST['jumlah_single']);
            $keterangan_full = $keterangan;
        }

        if($total_nominal <= 0){ throw new Exception("Nominal tidak valid."); }

        // Simpan ke Transaksi Kas (Arus 'keluar')
        $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                VALUES (?, ?, 'keluar', ?, ?, ?)";
        $pdo->prepare($sql)->execute([$tanggal, $kategori, $total_nominal, $keterangan_full, $user_id]);

        catatLog($pdo, $user_id, 'Tarik Kas', "Input Pengeluaran ($kategori): $keterangan_full");

        $pdo->commit();
        echo "<script>alert('Pengeluaran Berhasil Dicatat!'); window.location='index.php?page=kas/kas_belanja';</script>";
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "<script>alert('Gagal: " . $e->getMessage() . "');</script>";
    }
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$riwayat = $pdo->query("SELECT * FROM transaksi_kas WHERE arus='keluar' ORDER BY tanggal DESC, id DESC LIMIT $limit")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan</h6>
        <h2 class="h3 fw-bold mb-0 text-dark"><i class="fas fa-shopping-cart me-2 text-danger"></i> Belanja & Pengeluaran</h2>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-danger text-white py-3">
                <h6 class="mb-0 fw-bold">Form Pengeluaran (Satu Pintu)</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Kategori Pengeluaran</label>
                        <select name="kategori" id="kategori" class="form-select fw-bold" required onchange="toggleForm()">
                            <optgroup label="Stok Barang">
                                <option value="belanja_stok">Belanja Stok (Kulakan)</option>
                            </optgroup>
                            <optgroup label="Beban Operasional">
                                <option value="biaya_kebersihan">Biaya Kebersihan & Keamanan</option>
                                <option value="biaya_listrik">Biaya Listrik / Air</option>
                                <option value="gaji_staff">Gaji / Honor Staff</option>
                                <option value="biaya_operasional">Beban Operasional Lainnya</option>
                            </optgroup>
                        </select>
                    </div>

                    <div id="area_stok" style="display:none;" class="mb-3 bg-light p-3 rounded border">
                        <div id="container_items">
                            <div class="row g-1 mb-2 item-row">
                                <div class="col-5">
                                    <select name="item_id[]" class="form-select form-select-sm">
                                        <option value="">- Pilih Barang -</option>
                                        <?php foreach($barang_toko as $b): ?>
                                            <option value="<?= $b['id'] ?>"><?= $b['nama_barang'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-2"><input type="number" name="item_qty[]" class="form-control form-control-sm" placeholder="Qty"></div>
                                <div class="col-4"><input type="number" name="item_price[]" class="form-control form-control-sm" placeholder="Total Rp" oninput="hitungTotal()"></div>
                                <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger px-1" onclick="hapusBaris(this)"><i class="fas fa-times"></i></button></div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-warning w-100 fw-bold mt-2" onclick="tambahBaris()"><i class="fas fa-plus"></i> Tambah Item</button>
                        <div class="mt-3 text-end"><span class="small fw-bold">Total Belanja:</span> <span class="fw-bold text-danger h5" id="tampilan_total">Rp 0</span></div>
                    </div>

                    <div id="area_single">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Nominal Pengeluaran (Rp)</label>
                            <input type="number" name="jumlah_single" id="jumlah_single" class="form-control form-control-lg fw-bold text-danger" placeholder="0">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Keterangan / Catatan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Detail pengeluaran..." required></textarea>
                    </div>
                    
                    <button type="submit" name="simpan_pengeluaran" class="btn btn-danger w-100 py-3 fw-bold rounded-pill shadow">
                        SIMPAN PENGELUARAN
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between">
                <h6 class="mb-0 fw-bold text-muted">Riwayat Pengeluaran Terakhir</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="bg-light">
                        <tr><th class="ps-3">Tanggal</th><th>Kategori & Ket</th><th class="text-end pe-3">Jumlah</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($riwayat as $row): ?>
                        <tr>
                            <td class="ps-3"><?= date('d/m/y', strtotime($row['tanggal'])) ?></td>
                            <td>
                                <span class="badge bg-light text-dark border rounded-pill me-1"><?= strtoupper(str_replace('_',' ',$row['kategori'])) ?></span>
                                <div class="text-muted mt-1"><?= htmlspecialchars($row['keterangan']) ?></div>
                            </td>
                            <td class="text-end text-danger fw-bold pe-3">- <?= number_format($row['jumlah']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<template id="template_row">
    <div class="row g-1 mb-2 item-row">
        <div class="col-5">
            <select name="item_id[]" class="form-select form-select-sm" required>
                <option value="">- Pilih -</option>
                <?php foreach($barang_toko as $b): ?><option value="<?= $b['id'] ?>"><?= $b['nama_barang'] ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-2"><input type="number" name="item_qty[]" class="form-control form-control-sm" required></div>
        <div class="col-4"><input type="number" name="item_price[]" class="form-control form-control-sm" required oninput="hitungTotal()"></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger px-1" onclick="hapusBaris(this)"><i class="fas fa-times"></i></button></div>
    </div>
</template>

<script>
function toggleForm() {
    const kat = document.getElementById("kategori").value;
    const areaStok = document.getElementById("area_stok");
    const areaSingle = document.getElementById("area_single");
    const inputSingle = document.getElementById("jumlah_single");

    if(kat === "belanja_stok") {
        areaStok.style.display = "block";
        areaSingle.style.display = "none";
        inputSingle.required = false;
    } else {
        areaStok.style.display = "none";
        areaSingle.style.display = "block";
        inputSingle.required = true;
    }
}
function tambahBaris() {
    document.getElementById("container_items").appendChild(document.getElementById("template_row").content.cloneNode(true));
}
function hapusBaris(btn) {
    btn.closest('.item-row').remove();
    hitungTotal();
}
function hitungTotal() {
    let total = 0;
    document.querySelectorAll('input[name="item_price[]"]').forEach(input => total += parseFloat(input.value) || 0);
    document.getElementById("tampilan_total").innerText = "Rp " + new Intl.NumberFormat('id-ID').format(total);
}
// Jalankan saat load awal
toggleForm();
</script>