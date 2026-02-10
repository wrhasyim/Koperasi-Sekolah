<?php
require_once 'config/database.php';

// Fungsi helper untuk log visual
function logger($msg, $type = 'success') {
    $color = ($type == 'success') ? '#1cc88a' : '#e74a3b';
    $icon  = ($type == 'success') ? 'check-circle' : 'times-circle';
    echo "<div style='margin-bottom:8px; padding:12px; background:#fff; border-left:5px solid $color; box-shadow: 0 2px 4px rgba(0,0,0,0.1); font-family: monospace; display:flex; align-items:center;'>
            <i class='fas fa-$icon' style='color:$color; margin-right:10px;'></i> $msg
          </div>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FACTORY RESET KOPERASI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background-color: #f8f9fc; color: #333; }
        h1 { color: #e74a3b; border-bottom: 2px solid #e74a3b; padding-bottom: 10px; }
        .warning-box { background-color: #ffe5e5; color: #b71c1c; padding: 20px; border-radius: 8px; border: 1px solid #ffcdd2; margin-bottom: 30px; }
        .btn-reset { background-color: #e74a3b; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.3s; width: 100%; }
        .btn-reset:hover { background-color: #c0392b; }
        .btn-back { display: inline-block; margin-top: 20px; text-decoration: none; color: #4e73df; font-weight: bold; }
        .log-area { margin-top: 20px; padding: 10px; background: #eaecf4; border-radius: 8px; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>

    <h1><i class="fas fa-radiation"></i> SYSTEM FACTORY RESET</h1>

    <div class="warning-box">
        <h3>⚠ PERINGATAN KERAS!</h3>
        <p>Anda akan melakukan <strong>RESET TOTAL</strong> pada sistem. Tindakan ini tidak dapat dibatalkan.</p>
        <ul>
            <li>Semua Riwayat Transaksi (Kas, QRIS, Belanja, Honor) akan <strong>DIHAPUS</strong>.</li>
            <li>Semua Data Inventory (Stok Barang, Titipan) akan <strong>DIKOSONGKAN</strong>.</li>
            <li>Semua Data Tabungan, Cicilan & Utang akan <strong>HILANG</strong>.</li>
            <li>Semua Akun Anggota (Siswa, Guru, Staff) akan <strong>DIHAPUS</strong>.</li>
            <li>Pengaturan (Header Cetak & Persentase) akan kembali ke <strong>DEFAULT</strong>.</li>
            <li><strong>HANYA AKUN 'ADMIN' (ID 1) YANG AKAN DISISAKAN.</strong></li>
        </ul>
        <p>Pastikan Anda sudah melakukan <strong>BACKUP</strong> jika data ini penting.</p>
    </div>
    
    <form method="POST" onsubmit="return confirm('APAKAH ANDA YAKIN 100%? SEMUA DATA AKAN HILANG PERMANEN!');">
        <button type="submit" name="reset_db" class="btn-reset">
            <i class="fas fa-trash-alt me-2"></i> YA, HAPUS SEMUA DATA & SISAKAN ADMIN
        </button>
    </form>
    
    <div class="log-area">
    <?php
    if(isset($_POST['reset_db'])){
        try {
            $pdo->beginTransaction();

            // 1. MATIKAN FOREIGN KEY CHECKS
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            logger("Pengecekan Foreign Key dinonaktifkan sementara.");

            // 2. DAFTAR TABEL YANG AKAN DIKOSONGKAN (TRUNCATE)
            $tables = [
                'transaksi_kas',
                'cicilan',
                'simpanan',
                'riwayat_pengambilan',
                'titipan',
                'stok_koperasi',
                'stok_sekolah',
                'stok_eskul',
                'stok_barang', // Jika ada tabel ini
                'tutup_buku',
                'log_aktivitas'
            ];

            foreach($tables as $table){
                // Cek apakah tabel ada sebelum truncate untuk menghindari error
                $check = $pdo->query("SHOW TABLES LIKE '$table'");
                if($check->rowCount() > 0){
                    $pdo->exec("TRUNCATE TABLE $table");
                    logger("Tabel <b>$table</b> berhasil dikosongkan.");
                }
            }

            // 3. RESET ANGGOTA (HAPUS SEMUA KECUALI ADMIN ID 1)
            $stmt = $pdo->prepare("DELETE FROM anggota WHERE id != 1");
            $stmt->execute();
            logger("Semua anggota dihapus, menyisakan Admin Utama.");

            // Reset Auto Increment Anggota agar mulai dari 2 lagi
            $pdo->exec("ALTER TABLE anggota AUTO_INCREMENT = 2");
            logger("Auto Increment tabel Anggota di-reset.");

            // 4. RESET PENGATURAN (CREATE IF NOT EXISTS & INSERT DEFAULT)
            // Pastikan tabel pengaturan ada
            $sql_setting = "CREATE TABLE IF NOT EXISTS `pengaturan` (
                `kunci` varchar(50) NOT NULL,
                `nilai` text DEFAULT NULL,
                `keterangan` varchar(100) DEFAULT NULL,
                PRIMARY KEY (`kunci`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $pdo->exec($sql_setting);
            
            // Kosongkan pengaturan lama
            $pdo->exec("TRUNCATE TABLE pengaturan");

            // Masukkan pengaturan default
            $defaults = [
                ['header_nama', 'KOPERASI SEKOLAH', 'Nama Instansi'],
                ['header_alamat', 'Alamat Sekolah', 'Alamat Lengkap'],
                ['header_kontak', 'Telp: -', 'Kontak'],
                ['persen_staff', '20', 'Honor Staff (%)'],
                ['persen_pengurus', '15', 'Honor Pengurus (%)'],
                ['persen_pembina', '5', 'Honor Pembina (%)'],
                ['persen_dansos', '10', 'Dana Sosial (%)'],
                ['persen_kas', '50', 'Sisa Kas (%)']
            ];

            $stmt_set = $pdo->prepare("INSERT INTO pengaturan (kunci, nilai, keterangan) VALUES (?, ?, ?)");
            foreach($defaults as $def){
                $stmt_set->execute($def);
            }
            logger("Pengaturan sistem dikembalikan ke default.");

            // 5. SELESAI
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $pdo->commit();

            logger("✅ <b>FACTORY RESET SUKSES!</b> Sistem kini bersih seperti baru.", "success");
            echo "<br><center><a href='index.php' class='btn-back'>KEMBALI KE DASHBOARD</a></center>";

        } catch(Exception $e) {
            $pdo->rollBack();
            logger("❌ TERJADI ERROR: " . $e->getMessage(), "error");
        }
    }
    ?>
    </div>

</body>
</html>
