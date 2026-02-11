<?php
// pages/kas/kas_belanja.php
require_once 'config/database.php';

// AMBIL DATA BARANG
$barang_toko = $pdo->query("SELECT * FROM stok_koperasi ORDER BY nama_barang ASC")->fetchAll();

// --- LOGIKA SIMPAN ---
if(isset($_POST['simpan_pengeluaran'])){
    try {
        $pdo->beginTransaction();

        $tanggal    = $_POST['tanggal'];
        $kategori   = $_POST['kategori']; 
        $keterangan = $_POST['keterangan'];
        $user_id    = $_SESSION['user']['id'];
        $total_nominal = 0;

        // KASUS 1: BELANJA STOK (KULAKAN)
        if($kategori == 'belanja_stok'){
            $items  = $_POST['item_id'] ?? [];
            $qtys   = $_POST['item_qty'] ?? [];
            $prices = $_POST['item_price'] ?? []; // Total harga per baris
            $detail_names = [];

            if(empty($items)){ throw new Exception("Belum ada barang yang dipilih!"); }

            for($i = 0; $i < count($items); $i++){
                $id_brg = $items[$i];
                $qty    = (int)$qtys[$i];
                $subtotal = (float)str_replace('.', '', $prices[$i]);

                if($qty > 0 && $subtotal > 0){
                    $total_nominal += $subtotal;

                    // --- LOGIKA AVERAGE HPP (DIBULATKAN) ---
                    $cek_db = $pdo->prepare("SELECT stok, nama_barang, harga_modal FROM stok_koperasi WHERE id = ?");
                    $cek_db->execute([$id_brg]);
                    $data_db = $cek_db->fetch();

                    $stok_lama = ($data_db['stok'] < 0) ? 0 : $data_db['stok']; // Guard stok minus
                    $aset_lama = $stok_lama * $data_db['harga_modal'];
                    $aset_baru = $subtotal;
                    $stok_baru_calc = $stok_lama + $qty;

                    // Hitung Average & Rounding (Pembulatan)
                    if($stok_baru_calc > 0){
                        $modal_final = round(($aset_lama + $aset_baru) / $stok_baru_calc); 
                    } else {
                        $modal_final = round($subtotal / $qty);
                    }

                    // Update Stok Real & Harga Modal
                    $stok_real_update = $data_db['stok'] + $qty;
                    $pdo->prepare("UPDATE stok_koperasi SET stok = ?, harga_modal = ? WHERE id = ?")
                        ->execute([$stok_real_update, $modal_final, $id_brg]);

                    $detail_names[] = $data_db['nama_barang'] . " (x$qty)";
                }
            }
            $keterangan_full = "Belanja Stok: " . implode(", ", $detail_names) . ". " . $keterangan;

        // KASUS 2: BIAYA LAIN
        } else {
            $total_nominal = (float)str_replace('.', '', $_POST['jumlah_single']);
            $keterangan_full = $keterangan;
        }

        if($total_nominal <= 0){ throw new Exception("Nominal 0 rupiah."); }

        // INSERT KAS
        $sql = "INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) 
                VALUES (?, ?, 'keluar', ?, ?, ?)";
        $pdo->prepare($sql)->execute([$tanggal, $kategori, $total_nominal, $keterangan_full, $user_id]);

        $pdo->commit();
        echo "<script>alert('Transaksi Berhasil Disimpan!'); window.location='index.php?page=kas/kas_belanja';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Gagal: " . $e->getMessage() . "');</script>";
    }
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$riwayat = $pdo->query("SELECT * FROM transaksi_kas WHERE arus='keluar' ORDER BY tanggal DESC, id DESC LIMIT $limit")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Keuangan</h6>
        <h2 class="h3 fw-bold mb-0">Input Pengeluaran</h2>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card shadow-lg border-0 rounded-4 mb-4">
            <div class="card-header bg-danger text-white py-3 rounded-top-4">
                <h6 class="mb-0 fw-bold"><i class="fas fa-money-bill-wave me-2"></i> Form Uang Keluar</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control fw-bold" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Jenis Pengeluaran</label>
                        <select name="kategori" id="kategori" class="form-select fw-bold" required onchange="toggleForm()">
                            <option value="">-- Pilih Jenis --</option>
                            <option value="belanja_stok">Belanja Stok (Kulakan)</option>
                            <option value="biaya_operasional">Listrik / Air / Internet</option>
                            <option value="operasional_lain">Lain-lain (ATK, Kebersihan)</option>
                        </select>
                    </div>

                    <div id="area_stok" style="display:none;" class="mb-3 bg-light p-2 rounded border">
                        <div class="alert alert-warning py-1 px-2 small mb-2"><i class="fas fa-info-circle"></i> Isi total harga beli per baris. HPP otomatis dihitung rata-rata.</div>
                        <div id="container_items">
                            <div class="row g-1 mb-2 item-row">
                                <div class="col-5">
                                    <select name="item_id[]" class="form-select form-select-sm" required>
                                        <option value="">- Barang -</option>
                                        <?php foreach($barang_toko as $b): ?>
                                            <option value="<?= $b['id'] ?>"><?= $b['nama_barang'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-2">
                                    <input type="number" name="item_qty[]" class="form-control form-control-sm" placeholder="Qty" min="1" required>
                                </div>
                                <div class="col-4">
                                    <input type="number" name="item_price[]" class="form-control form-control-sm" placeholder="Total Rp" required oninput="hitungTotal()">
                                </div>
                                <div class="col-1">
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="hapusBaris(this)" disabled><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-warning w-100 fw-bold mt-2" onclick="tambahBaris()"><i class="fas fa-plus"></i> Tambah Baris Barang</button>
                        
                        <div class="mt-2 text-end">
                            <span class="small fw-bold text-muted">Total Bayar:</span>
                            <span class="fw-bold text-danger fs-5" id="tampilan_total">Rp 0</span>
                        </div>
                    </div>

                    <div id="area_single">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Nominal (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">Rp</span>
                                <input type="number" name="jumlah_single" id="jumlah_single" class="form-control fw-bold" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Keterangan</label>
                        <textarea name="keterangan" class="form-control bg-light" rows="2" placeholder="Detail pengeluaran..." required></textarea>
                    </div>
                    
                    <button type="submit" name="simpan_pengeluaran" class="btn btn-danger w-100 py-2 fw-bold rounded-pill shadow-sm">
                        <i class="fas fa-save me-2"></i> SIMPAN TRANSAKSI
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-muted"><i class="fas fa-history me-2"></i> Riwayat</h6>
                <form method="GET" class="d-inline-block">
                    <input type="hidden" name="page" value="kas/kas_belanja">
                    <select name="limit" class="form-select form-select-sm py-0 bg-light border-0" onchange="this.form.submit()">
                        <option value="10" <?= $limit==10?'selected':'' ?>>10</option>
                        <option value="25" <?= $limit==25?'selected':'' ?>>25</option>
                        <option value="50" <?= $limit==50?'selected':'' ?>>50</option>
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="bg-light"><tr><th class="ps-3">Tanggal</th><th>Ket</th><th class="text-end pe-3">Jumlah</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach($riwayat as $row): 
                                $bg = ($row['kategori']=='belanja_stok') ? 'warning text-dark' : 'secondary';
                            ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?= date('d/m/y', strtotime($row['tanggal'])) ?></td>
                                <td>
                                    <span class='badge bg-<?= $bg ?> rounded-pill me-1'><?= strtoupper(str_replace('_',' ',$row['kategori'])) ?></span>
                                    <?= htmlspecialchars($row['keterangan']) ?>
                                </td>
                                <td class="text-end text-danger fw-bold pe-3">- <?= number_format($row['jumlah']) ?></td>
                                <td class="text-end pe-3"><a href="process/kas_hapus.php?id=<?= $row['id'] ?>&redirect=index.php?page=kas/kas_belanja" class="text-danger" onclick="return confirm('Hapus?')"><i class="fas fa-trash-alt"></i></a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="template_row">
    <div class="row g-1 mb-2 item-row">
        <div class="col-5">
            <select name="item_id[]" class="form-select form-select-sm" required>
                <option value="">- Barang -</option>
                <?php foreach($barang_toko as $b): ?><option value="<?= $b['id'] ?>"><?= $b['nama_barang'] ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-2"><input type="number" name="item_qty[]" class="form-control form-control-sm" placeholder="Qty" min="1" required></div>
        <div class="col-4"><input type="number" name="item_price[]" class="form-control form-control-sm" placeholder="Total Rp" required oninput="hitungTotal()"></div>
        <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="hapusBaris(this)"><i class="fas fa-times"></i></button></div>
    </div>
</template>

<script>
function toggleForm() {
    var kat = document.getElementById("kategori").value;
    var stok = document.getElementById("area_stok");
    var single = document.getElementById("area_single");
    var inputSingle = document.getElementById("jumlah_single");

    if(kat === "belanja_stok") {
        stok.style.display = "block";
        single.style.display = "none";
        inputSingle.required = false;
        document.querySelectorAll('#container_items input, #container_items select').forEach(el => el.required = true);
    } else {
        stok.style.display = "none";
        single.style.display = "block";
        inputSingle.required = true;
        document.querySelectorAll('#container_items input, #container_items select').forEach(el => el.required = false);
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
    var total = 0;
    document.querySelectorAll('input[name="item_price[]"]').forEach(input => total += parseFloat(input.value) || 0);
    document.getElementById("tampilan_total").innerText = "Rp " + new Intl.NumberFormat('id-ID').format(total);
}
toggleForm();
</script>