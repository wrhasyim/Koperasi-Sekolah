<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

// Proteksi: Hanya Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Akses ditolak!");
}

try {
    // 1. Matikan Foreign Key Check agar bisa menghapus tabel yang berelasi
    $pdo->query("SET FOREIGN_KEY_CHECKS = 0");

    // 2. Daftar semua tabel yang akan dikosongkan (TRUNCATE)
    // Perintah TRUNCATE akan mereset ID (Auto Increment) kembali ke 1
    $tables = [
        'transaksi_kas', 'simpanan', 'cicilan', 'siswa', 
        'kasbon', 'titipan', 'log_aktivitas', 'riwayat_bayar_pinjaman', 
        'riwayat_bayar_kasbon', 'riwayat_pengambilan', 'pinjaman_dana',
        'stok_koperasi', 'stok_sekolah', 'stok_eskul', 'tutup_buku'
    ];

    foreach ($tables as $table) {
        $pdo->query("TRUNCATE TABLE $table");
    }

    // 3. Hapus Anggota KECUALI Admin Utama (ID: 1)
    // Jangan gunakan TRUNCATE di sini karena kita ingin menyisakan 1 user
    $pdo->query("DELETE FROM anggota WHERE id != 1");
    
    // Reset urutan ID anggota agar mulai dari 2 untuk anggota berikutnya
    $pdo->query("ALTER TABLE anggota AUTO_INCREMENT = 2");

    // 4. Nyalakan kembali Foreign Key Check
    $pdo->query("SET FOREIGN_KEY_CHECKS = 1");

    setFlash('success', 'Factory Reset Berhasil! Seluruh data transaksi dan stok telah dikosongkan. Akun Administrator tetap dipertahankan.');
} catch (Exception $e) {
    // Pastikan FK check nyala kembali meskipun error
    $pdo->query("SET FOREIGN_KEY_CHECKS = 1");
    setFlash('danger', 'Gagal Reset: ' . $e->getMessage());
}

header("Location: ../index.php?page=utilitas/backup");
exit;