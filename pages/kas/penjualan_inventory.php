<?php
// --- LOGIKA PROSES PENJUALAN ---
if(isset($_POST['proses_jual'])){
    $tipe_barang = $_POST['tipe_barang']; 
    $id_barang   = $_POST['id_barang'];
    $qty         = $_POST['qty'];
    $catatan     = $_POST['catatan'];
    $metode      = $_POST['metode_pembayaran']; 
    $anggota_id  = $_POST['anggota_id']; 
    $uang_muka   = $_POST['uang_muka']; 
    
    $tanggal     = date('Y-m-d');
    $user_id     = $_SESSION['user']['id'];

    // 1. Tentukan Tabel & Kategori
    if($tipe_barang == 'sekolah'){ 
        $tabel = "stok_sekolah"; 
        $kategori_kas = "penjualan_seragam"; 
    } elseif($tipe_barang == 'eskul'){ 
        $tabel = "stok_eskul"; 
        $kategori_kas = "penjualan_eskul";   
    } else { 
        $tabel = "stok_koperasi"; 
        $kategori_kas = "penjualan_harian";  
    }

    // --- [FIX] CEK TABEL CICILAN DI LUAR TRANSAKSI ---
    // Agar tidak menyebabkan implicit commit saat transaksi berjalan
    if($metode == 'cicilan'){
        $pdo->exec("CREATE TABLE IF NOT EXISTS cicilan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            anggota_id INT,
            kategori_barang VARCHAR(50), 
            nama_barang VARCHAR(100),
            total_tagihan DECIMAL(10,2),
            terbayar DECIMAL(10,2),
            sisa DECIMAL(10,2),
            status ENUM('lunas','belum') DEFAULT 'belum',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // 2. Cek Barang
    $cek = $pdo->prepare("SELECT nama_barang, harga_jual, stok FROM $tabel WHERE id = ?");
    $cek->execute([$id_barang]);
    $item = $cek->fetch();

    if(!$item){
        echo "<script>alert('Barang tidak ditemukan!');</script>";
    } elseif($qty > $item['stok']){
        echo "<script>alert('GAGAL! Stok tidak cukup. Sisa: " . $item['stok'] . "');</script>";
    } else {
        $total_harga = $item['harga_jual'] * $qty;
        
        // Validasi Cicilan
        if($metode == 'cicilan'){
            if($tipe_barang == 'koperasi'){
                echo "<script>alert('Maaf, Barang Koperasi (Jajanan/ATK) tidak bisa dicicil!'); window.history.back(); exit;</script>";
            }
            if(empty($anggota_id)){
                echo "<script>alert('Wajib pilih nama siswa untuk cicilan.'); window.history.back(); exit;</script>";
            }
            if($uang_muka >= $total_harga) $metode = 'tunai'; 
        } else {
            $uang_muka = $total_harga; 
        }

        try {
            // MULAI TRANSAKSI
            $pdo->beginTransaction();

            // A. Kurangi Stok
            $pdo->prepare("UPDATE $tabel SET stok = stok - ? WHERE id = ?")->execute([$qty, $id_barang]);

            // B. Masuk Transaksi Kas
            $ket_transaksi = "Jual ($metode): " . $item['nama_barang'] . " ($qty) - " . $catatan;
            $pdo->prepare("INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (?, ?, 'masuk', ?, ?, ?)")
                ->execute([$tanggal, $kategori_kas, $uang_muka, $ket_transaksi, $user_id]);

            // C. Catat Cicilan (Jika Kredit)
            if($metode == 'cicilan'){
                $sisa_tagihan = $total_harga - $uang_muka;
                $sql_cicilan = "INSERT INTO cicilan (anggota_id, kategori_barang, nama_barang, total_tagihan, terbayar, sisa, status) VALUES (?, ?, ?, ?, ?, ?, 'belum')";
                $pdo->prepare($sql_cicilan)->execute([$anggota_id, $tipe_barang, $item['nama_barang']." ($qty pcs)", $total_harga, $uang_muka, $sisa_tagihan]);
            }

            // SELESAI TRANSAKSI
            $pdo->commit();
            echo "<script>alert('Transaksi Berhasil!'); window.location='kas/penjualan_inventory';</script>";

        } catch (Exception $e) {
            // [FIX] Cek apakah transaksi aktif sebelum rollback agar tidak error fatal
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "<script>alert('Error Transaksi: " . $e->getMessage() . "');</script>";
        }
    }
}

// QUERY DATA DROPDOWN
$seragam = []; $eskul = []; $umum = []; $siswa = [];
try { $seragam = $pdo->query("SELECT * FROM stok_sekolah WHERE stok > 0 ORDER BY nama_barang ASC")->fetchAll(); } catch(Exception $e){}
try { $eskul = $pdo->query("SELECT * FROM stok_eskul WHERE stok > 0 ORDER BY nama_barang ASC")->fetchAll(); } catch(Exception $e){}
try { $umum = $pdo->query("SELECT * FROM stok_koperasi WHERE stok > 0 ORDER BY nama_barang ASC")->fetchAll(); } catch(Exception $e){}
try { $siswa = $pdo->query("SELECT * FROM anggota WHERE role='siswa' OR role='guru' ORDER BY nama_lengkap ASC")->fetchAll(); } catch(Exception $e){}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Transaksi</h6>
        <h2 class="h3 fw-bold mb-0">Kasir Barang & Seragam</h2>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100 border-0 shadow-lg">
            <div class="card-header bg-primary text-white py-3 border-0">
                <h6 class="mb-0 fw-bold"><i class="fas fa-shopping-cart me-2"></i> Form Penjualan</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">KATEGORI</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="tipe_barang" id="t_sekolah" value="sekolah" checked onclick="showDropdown('sekolah')">
                            <label class="btn btn-outline-primary" for="t_sekolah">Seragam</label>
                            
                            <input type="radio" class="btn-check" name="tipe_barang" id="t_eskul" value="eskul" onclick="showDropdown('eskul')">
                            <label class="btn btn-outline-primary" for="t_eskul">Eskul</label>
                            
                            <input type="radio" class="btn-check" name="tipe_barang" id="t_umum" value="koperasi" onclick="showDropdown('koperasi')">
                            <label class="btn btn-outline-primary" for="t_umum">Stok Koperasi</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">PILIH BARANG</label>
                        <select id="sel_sekolah" name="id_barang" class="form-select form-select-lg bg-light border-0 item-select">
                            <option value="">-- Pilih Seragam --</option>
                            <?php foreach($seragam as $b): ?><option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_jual'] ?>"><?= $b['nama_barang'] ?> (Stok: <?= $b['stok'] ?>)</option><?php endforeach; ?>
                        </select>
                        <select id="sel_eskul" name="id_barang" class="form-select form-select-lg bg-light border-0 item-select" style="display:none;" disabled>
                            <option value="">-- Pilih Atribut Eskul --</option>
                            <?php foreach($eskul as $b): ?><option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_jual'] ?>"><?= $b['nama_barang'] ?> (Stok: <?= $b['stok'] ?>)</option><?php endforeach; ?>
                        </select>
                        <select id="sel_koperasi" name="id_barang" class="form-select form-select-lg bg-light border-0 item-select" style="display:none;" disabled>
                            <option value="">-- Pilih Barang Koperasi --</option>
                            <?php foreach($umum as $b): ?><option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_jual'] ?>"><?= $b['nama_barang'] ?> (Stok: <?= $b['stok'] ?>)</option><?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="small fw-bold text-muted">HARGA</label>
                            <input type="text" id="view_harga" class="form-control fw-bold border-0" readonly value="Rp 0">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted">JUMLAH</label>
                            <input type="number" name="qty" id="qty" class="form-control fw-bold border-primary" value="1" min="1" required>
                        </div>
                    </div>

                    <div class="alert alert-info d-flex justify-content-between align-items-center px-3 py-2 mb-4">
                        <span class="small fw-bold">TOTAL TAGIHAN</span>
                        <h4 class="mb-0 fw-bold" id="total_bayar">Rp 0</h4>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">METODE PEMBAYARAN</label>
                        <select name="metode_pembayaran" id="metode" class="form-select mb-2" onchange="cekMetode()">
                            <option value="tunai">Tunai Lunas</option>
                            <option value="cicilan">Cicilan (Kredit)</option>
                        </select>
                    </div>

                    <div id="box_cicilan" style="display:none;" class="bg-warning bg-opacity-10 p-3 rounded mb-3 border border-warning">
                        <div class="mb-3">
                            <label class="small fw-bold text-dark">NAMA SISWA (Wajib)</label>
                            <select name="anggota_id" class="form-select border-warning">
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach($siswa as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= $s['nama_lengkap'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-dark">UANG MUKA (DP)</label>
                            <input type="number" name="uang_muka" id="uang_muka" class="form-control border-warning fw-bold" placeholder="0">
                            <div class="form-text text-danger small">*Hanya Seragam & Eskul yang boleh dicicil.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <input type="text" name="catatan" class="form-control border-0 bg-light" placeholder="Catatan tambahan...">
                    </div>

                    <button type="submit" name="proses_jual" class="btn btn-primary w-100 py-3 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i> Simpan Transaksi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function showDropdown(type) {
        document.querySelectorAll('.item-select').forEach(el => { el.style.display = 'none'; el.disabled = true; el.removeAttribute('name'); });
        let target = document.getElementById('sel_' + type);
        target.style.display = 'block'; target.disabled = false; target.setAttribute('name', 'id_barang');
        target.value = ""; updateTotal();
        
        // Auto disable cicilan jika koperasi
        let selMetode = document.getElementById('metode');
        if(type === 'koperasi'){
            selMetode.value = 'tunai';
            selMetode.disabled = true; 
            cekMetode();
        } else {
            selMetode.disabled = false;
        }
    }

    document.querySelectorAll('.item-select').forEach(sel => {
        sel.addEventListener('change', function() {
            let price = this.options[this.selectedIndex].getAttribute('data-harga') || 0;
            document.getElementById('view_harga').value = "Rp " + new Intl.NumberFormat('id-ID').format(price);
            updateTotal();
        });
    });

    document.getElementById('qty').addEventListener('input', updateTotal);

    function updateTotal() {
        let activeSelect = document.querySelector('.item-select:not([disabled])');
        if(activeSelect && activeSelect.value) {
            let harga = activeSelect.options[activeSelect.selectedIndex].getAttribute('data-harga');
            let qty = document.getElementById('qty').value;
            let total = harga * qty;
            document.getElementById('total_bayar').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(total);
        } else {
            document.getElementById('total_bayar').innerText = "Rp 0";
        }
    }

    function cekMetode(){
        let m = document.getElementById('metode').value;
        let box = document.getElementById('box_cicilan');
        if(m == 'cicilan'){
            box.style.display = 'block';
            document.getElementById('uang_muka').required = true;
        } else {
            box.style.display = 'none';
            document.getElementById('uang_muka').required = false;
        }
    }
</script>