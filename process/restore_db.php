<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

// Proteksi: Hanya Admin
if ($_SESSION['user']['role'] !== 'admin') {
    die("Akses ditolak!");
}

if (isset($_POST['restore'])) {
    $file = $_FILES['backup_file']['tmp_name'];
    
    if (empty($file)) {
        setFlash('danger', 'Silakan pilih file backup (.sql) terlebih dahulu.');
        header("Location: ../index.php?page=utilitas/backup");
        exit;
    }

    // Cek Ekstensi File
    $ext = pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION);
    if ($ext !== 'sql') {
        setFlash('danger', 'Format file tidak didukung. Harus file .sql');
        header("Location: ../index.php?page=utilitas/backup");
        exit;
    }

    try {
        // 1. Matikan Foreign Key Check
        $pdo->query("SET FOREIGN_KEY_CHECKS = 0");

        // 2. Baca isi file SQL
        $sql = file_get_contents($file);

        // 3. Jalankan Query (Menggunakan exec untuk multiple queries)
        $pdo->exec($sql);

        // 4. Hidupkan kembali Foreign Key Check
        $pdo->query("SET FOREIGN_KEY_CHECKS = 1");

        setFlash('success', 'Database berhasil dipulihkan (Restore) ke kondisi backup!');
    } catch (Exception $e) {
        setFlash('danger', 'Gagal Restore: ' . $e->getMessage());
    }
}

header("Location: ../index.php?page=utilitas/backup");
exit;