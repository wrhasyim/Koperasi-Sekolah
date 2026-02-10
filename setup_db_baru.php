<?php
require_once 'config/database.php';

// Fungsi helper untuk log
function logger($msg) {
    echo "<div style='margin-bottom:5px; padding:10px; background:#f0f0f0; border-left:4px solid #4e73df;'>$msg</div>";
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Database Koperasi</title>
    <style>body{font-family:sans-serif; max-width:800px; margin:20px auto; padding:20px;}</style>
</head>
<body>
    <h1><span style="color:red;">⚠</span> Reset & Setup Database</h1>
    <p>Script ini akan <strong>MENGHAPUS SEMUA DATA TRANSAKSI TESTING</strong> (Kas, Cicilan, Titipan) dan membuat ulang struktur tabel agar siap digunakan untuk produksi (Input Manual Siswa).</p>
    
    <form method="POST" onsubmit="return confirm('YAKIN INGIN MERESET DATA? Data transaksi testing akan hilang permanen!');">
        <button type="submit" name="reset_db" style="padding:15px 30px; background:red; color:white; font-weight:bold; border:none; cursor:pointer; border-radius:5px;">
            YA, RESET SEMUA DATA SEKARANG
        </button>
    </form>
    <br><hr><br>

<?php
if(isset($_POST['reset_db'])){
    try {
        // 1. Reset Tabel Cicilan (Drop & Create untuk memastikan struktur kolom benar)
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
        logger("✅ Tabel 'cicilan' berhasil dibuat ulang (Struktur Baru: Manual Nama & Kelas).");

        // 2. Kosongkan Riwayat Kas (Truncate)
        $pdo->exec("TRUNCATE TABLE transaksi_kas");
        logger("✅ Tabel 'transaksi_kas' berhasil dikosongkan.");

        // 3. Kosongkan Data Tutup Buku
        $pdo->exec("TRUNCATE TABLE tutup_buku");
        logger("✅ Tabel 'tutup_buku' berhasil dikosongkan.");

        // 4. (Opsional) Reset Stok Titipan jika perlu
        // $pdo->exec("TRUNCATE TABLE titipan"); 
        // logger("✅ Tabel 'titipan' dikosongkan.");

        echo "<h2 style='color:green;'>SUKSES! Database bersih dan siap digunakan.</h2>";
        echo "<a href='index.php' style='display:inline-block; padding:10px 20px; background:#4e73df; color:white; text-decoration:none; border-radius:5px;'>Kembali ke Dashboard</a>";

    } catch (PDOException $e) {
        echo "<h3 style='color:red;'>Error: " . $e->getMessage() . "</h3>";
    }
}
?>
</body>
</html>