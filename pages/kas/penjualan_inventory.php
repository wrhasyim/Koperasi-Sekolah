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

    // 1. Tentukan Tabel & Kategori (HANYA SEKOLAH & ESKUL)
    if($tipe_barang == 'sekolah'){ 
        $tabel = "stok_sekolah"; 
        $kategori_kas = "penjualan_seragam"; 
    } elseif($tipe_barang == 'eskul'){ 
        $tabel = "stok_eskul"; 
        $kategori_kas = "penjualan_eskul";   
    } else {
        echo "<script>alert('Kategori tidak valid!');</script>";
        exit;
    }

    // [FIX] PASTIKAN TABEL CICILAN ADA
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
            if(empty($anggota_id)){
                echo "<script>alert('WAJIB PILIH SISWA untuk transaksi cicilan!'); window.history.back(); exit;</script>";
            }
            if($uang_muka >= $total_harga) $metode = 'tunai'; // Auto switch jika DP lunas
        } else {
            $uang_muka = $total_harga; 
        }

        try {
            $pdo->beginTransaction();

            // A. Kurangi Stok
            $pdo->prepare("UPDATE $tabel SET stok = stok - ? WHERE id = ?")->execute([$qty, $id_barang]);

            // B. Masuk Transaksi Kas (Uang Muka / Full)
            $ket_transaksi = "Jual ($metode): " . $item['nama_barang'] . " ($qty) - " . $catatan;
            $pdo->prepare("INSERT INTO transaksi_kas (tanggal, kategori, arus, jumlah, keterangan, user_id) VALUES (?, ?, 'masuk', ?, ?, ?)")
                ->execute([$tanggal, $kategori_kas, $uang_muka, $ket_transaksi, $user_id]);

            // C. Catat Cicilan (Jika Kredit)
            if($metode == 'cicilan'){
                $sisa_tagihan = $total_harga - $uang_muka;
                $sql_cicilan = "INSERT INTO cicilan (anggota_id, kategori_barang, nama_barang, total_tagihan, terbayar, sisa, status) VALUES (?, ?, ?, ?, ?, ?, 'belum')";
                $pdo->prepare($sql_cicilan)->execute([$anggota_id, $tipe_barang, $item['nama_barang']." ($qty pcs)", $total_harga, $uang_muka, $sisa_tagihan]);
            }

            $pdo->commit();
            echo "<script>alert('Transaksi Berhasil!'); window.location='kas/penjualan_inventory';</script>";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        }
    }
}

// QUERY DATA (HANYA SEKOLAH & ESKUL)
$seragam = []; $eskul = []; $siswa = [];
try { $seragam = $pdo->query("SELECT * FROM stok_sekolah WHERE stok > 0 ORDER BY nama_barang ASC")->fetchAll(); } catch(Exception $e){}
try { $eskul = $pdo->query("SELECT * FROM stok_eskul WHERE stok > 0 ORDER BY nama_barang ASC")->fetchAll(); } catch(Exception $e){}
try { $siswa = $pdo->query("SELECT * FROM anggota WHERE role='siswa' OR role='guru' ORDER BY nama_lengkap ASC")->fetchAll(); } catch(Exception $e){}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h6 class="text-muted text-uppercase small ls-1 mb-1">Transaksi</h6>
        <h2 class="h3 fw-bold mb-0">Kasir Seragam & Eskul</h2>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8 mx-auto">
        <div class="card h-100 border-0 shadow-lg">
            <div class="card-header bg-primary text-white py-3 border-0">
                <h6 class="mb-0 fw-bold"><i class="fas fa-tshirt me-2"></i> Form Penjualan</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-4 text-center">
                        <label class="small fw-bold text-muted mb-2 d-block">PILIH KATEGORI</label>
                        <div class="btn-group w-75" role="group">
                            <input type="radio" class="btn-check" name="tipe_barang" id="t_sekolah" value="sekolah" checked onclick="showDropdown('sekolah')">
                            <label class="btn btn-outline-primary btn-lg" for="t_sekolah">Seragam Sekolah</label>
                            
                            <input type="radio" class="btn-check" name="tipe_barang" id="t_eskul" value="eskul" onclick="showDropdown('eskul')">
                            <label class="btn btn-outline-primary btn-lg" for="t_eskul">Atribut Eskul</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="small fw-bold text-muted">NAMA BARANG</label>
                            <select id="sel_sekolah" name="id_barang" class="form-select form-select-lg bg-light border-0 item-select">
                                <option value="">-- Pilih Seragam --</option>
                                <?php foreach($seragam as $b): ?><option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_jual'] ?>"><?= $b['nama_barang'] ?> (Stok: <?= $b['stok'] ?>)</option><?php endforeach; ?>
                            </select>
                            <select id="sel_eskul" name="id_barang" class="form-select form-select-lg bg-light border-0 item-select" style="display:none;" disabled>
                                <option value="">-- Pilih Atribut --</option>
                                <?php foreach($eskul as $b): ?><option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_jual'] ?>"><?= $b['nama_barang'] ?> (Stok: <?= $b['stok'] ?>)</option><?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="small fw-bold text-muted">QTY</label>
                            <input type="number" name="qty" id="qty" class="form-control form-control-lg fw-bold border-primary text-center" value="1" min="1" required>
                        </div>
                    </div>

                    <div class="alert alert-primary d-flex justify-content-between align-items-center px-4 py-3 mb-4 rounded-3">
                        <div>
                            <small class="d-block text-white-50 text-uppercase fw-bold">Harga Satuan</small>
                            <input type="text" id="view_harga" class="form-control-plaintext text-white fw-bold p-0" readonly value="Rp 0">
                        </div>
                        <div class="text-end">
                            <small class="d-block text-white-50 text-uppercase fw-bold">Total Tagihan</small>
                            <h3 class="mb-0 fw-bold text-white" id="total_bayar">Rp 0</h3>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="small fw-bold text-dark">NAMA ANAK / SISWA</label>
                        <select name="anggota_id" class="form-select form-select-lg border-warning" required>
                            <option value="">-- Cari Nama Siswa --</option>
                            <?php foreach($siswa as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= $s['nama_lengkap'] ?> (<?= ucfirst($s['role']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small">Nama siswa wajib dipilih untuk pencatatan riwayat & cicilan.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-muted">METODE BAYAR</label>
                            <select name="metode_pembayaran" id="metode" class="form-select" onchange="cekMetode()">
                                <option value="tunai">Tunai (Lunas)</option>
                                <option value="cicilan">Cicilan (Kredit)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small fw-bold text-dark">UANG YANG DIBAYAR (DP)</label>
                            <input type="number" name="uang_muka" id="uang_muka" class="form-control fw-bold border-success" placeholder="Nominal Rp..." required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <input type="text" name="catatan" class="form-control border-0 bg-light" placeholder="Catatan tambahan (Opsional)...">
                    </div>

                    <button type="submit" name="proses_jual" class="btn btn-primary w-100 py-3 fw-bold shadow-sm rounded-pill">
                        <i class="fas fa-save me-2"></i> SIMPAN TRANSAKSI
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
            
            // Auto fill DP dengan Total jika Tunai
            if(document.getElementById('metode').value == 'tunai'){
                document.getElementById('uang_muka').value = total;
            }
        } else {
            document.getElementById('total_bayar').innerText = "Rp 0";
        }
    }

    function cekMetode(){
        // Hanya logika visual, validasi utama di PHP
        let totalStr = document.getElementById('total_bayar').innerText;
        let total = parseInt(totalStr.replace(/[^0-9]/g, '')) || 0;
        
        if(document.getElementById('metode').value == 'tunai'){
            document.getElementById('uang_muka').value = total;
        } else {
            document.getElementById('uang_muka').value = '';
            document.getElementById('uang_muka').placeholder = 'Masukkan jumlah DP';
        }
    }
</script>