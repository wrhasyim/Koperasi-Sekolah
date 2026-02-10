<?php
require_once 'config/database.php';

// Fungsi helper untuk log visual
function logger($msg, $type = 'success') {
    $color = ($type == 'success') ? '#1cc88a' : '#e74a3b';
    echo "<div style='margin-bottom:8px; padding:12px; background:#fff; border-left:5px solid $color; box-shadow: 0 2px 4px rgba(0,0,0,0.1); font-family: monospace;'>$msg</div>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FACTORY RESET KOPERASI</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background-color: #f8f9fc; color: #333; }
        h1 { color: #e74a3b; border-bottom: 2px solid #e74a3b; padding-bottom: 10px; }
        .warning-box { background-color: #ffe5e5; color: #b71c1c; padding: 20px; border-radius: 8px; border: 1px solid #ffcdd2; margin-bottom: 30px; }
        .btn-reset { background-color: #e74a3b; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.3s; width: 100%; }
        .btn-reset:hover { background-color: #c0392b; }
        .btn-back { display: inline-block; margin-top: 20px; text-decoration: none; color: #4e73df; font-weight: bold; }
    </style>
</head>
<body>

    <h1><i class="fas fa-radiation"></i> SYSTEM FACTORY RESET</h1>

    <div class="warning-box">
        <h3>⚠ PERINGATAN KERAS!</h3>
        <p>Anda akan melakukan <strong>RESET TOTAL</strong> pada sistem. Tindakan ini tidak dapat dibatalkan.</p>
        <ul>
            <li>Semua Riwayat Transaksi (Kas, QRIS, Belanja) akan <strong>DIHAPUS</strong>.</li>
            <li>Semua Data Inventory (Stok Barang, Titipan) akan <strong>DIKOSONGKAN</strong>.</li>
            <li>Semua Data Tabungan & Cicilan akan <strong>HILANG</strong>.</li>
            <li>Semua Akun Anggota (Siswa, Guru, Staff) akan <strong>DIHAPUS</strong>.</li>
            <li><strong>HANYA AKUN 'ADMIN' YANG AKAN DISISAKAN.</strong></li>
        </ul>
        <p>Pastikan Anda sudah melakukan <strong>BACKUP</strong> jika data ini penting.</p>
    </div>
    
    <form method="POST" onsubmit="return confirm('APAKAH ANDA YAKIN 100%? SEMUA DATA AKAN HILANG PERMANEN!');">
        <button type="submit" name="reset_db" class="btn-reset">
            YA, HAPUS SEMUA DATA & SISAKAN ADMIN
        </button>
    </form>
    
    <br>

<?php
if(isset($_POST['reset_db'])){
    echo "<h3>⏳ Memproses Reset...</h3>";
    
    try {
        // Matikan pengecekan foreign key sementara agar truncate berjalan mulus
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        // --- 1. RESET KEUANGAN ---
        $pdo->exec("TRUNCATE TABLE transaksi_kas");
        logger("✅ Tabel 'transaksi_kas' berhasil dikosongkan.");

        $pdo->exec("TRUNCATE TABLE simpanan");
        logger("✅ Tabel 'simpanan' (Tabungan) berhasil dikosongkan.");

        $pdo->exec("TRUNCATE TABLE tutup_buku");
        logger("✅ Tabel 'tutup_buku' berhasil dikosongkan.");

        // --- 2. RESET INVENTORY ---
        $pdo->exec("TRUNCATE TABLE stok_koperasi");
        logger("✅ Tabel 'stok_koperasi' berhasil dikosongkan.");

        $pdo->exec("TRUNCATE TABLE stok_sekolah");
        logger("✅ Tabel 'stok_sekolah' berhasil dikosongkan.");

        $pdo->exec("TRUNCATE TABLE stok_eskul");
        logger("✅ Tabel 'stok_eskul' berhasil dikosongkan.");

        $pdo->exec("TRUNCATE TABLE titipan");
        logger("✅ Tabel 'titipan' (Konsinyasi Guru) berhasil dikosongkan.");

        // --- 3. RESET STRUKTUR CICILAN (DROP & CREATE) ---
        // Kita drop untuk memastikan strukturnya benar-benar baru (support input manual)
        $pdo->exec("DROP TABLE IF EXISTS cicilan");
        $sql_cicilan = "CREATE TABLE cicilan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama_siswa VARCHAR(100) NOT NULL,
            kelas VARCHAR(50) NOT NULL,
            kategori_barang VARCHAR(50) NOT NULL,
            nama_barang VARCHAR(255) NOT NULL,
            total_tagihan DECIMAL(15,2) NOT NULL,
            terbayar DECIMAL(15,2) NOT NULL DEFAULT 0,
            sisa DECIMAL(15,2) NOT NULL,
            status ENUM('lunas','belum') DEFAULT 'belum',
            catatan TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql_cicilan);
        logger("✅ Tabel 'cicilan' berhasil di-reset ulang dengan struktur baru.");

        // --- 4. BERSIHKAN ANGGOTA (SISAKAN ADMIN) ---
        // Hapus semua user yang role-nya BUKAN admin
        $sql_del_user = "DELETE FROM anggota WHERE role != 'admin'";
        $stmt_user = $pdo->prepare($sql_del_user);
        $stmt_user->execute();
        $count_deleted = $stmt_user->rowCount();
        
        logger("✅ Tabel 'anggota' dibersihkan. $count_deleted akun (Siswa/Guru/Staff) dihapus. Admin aman.");

        // Hidupkan kembali foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        echo "<hr>";
        echo "<h2 style='color:green;'>🎉 RESET COMPLETE! Sistem Siap Digunakan Dari Nol.</h2>";
        echo "<a href='index.php' class='btn-back'>&larr; Kembali ke Dashboard</a>";

    } catch (PDOException $e) {
        logger("❌ TERJADI ERROR: " . $e->getMessage(), 'error');
    }
}
?>
</body>
</html>