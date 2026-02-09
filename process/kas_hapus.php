<?php
session_start();
require_once '../config/database.php';

if(isset($_GET['id']) && isset($_GET['redirect'])){
    $id = $_GET['id'];
    $page = $_GET['redirect']; // Contoh: kas/kas_penjualan

    try {
        $stmt = $pdo->prepare("DELETE FROM transaksi_kas WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redirect Clean: Kembali ke folder root -> lalu ke alamat halaman
        header("Location: ../" . $page);
    } catch (Exception $e) {
        echo "Gagal menghapus: " . $e->getMessage();
    }
} else {
    header("Location: ../dashboard");
}
?>