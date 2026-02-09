<?php
session_start();
require_once '../config/database.php';

// Cek apakah ada ID dan Redirect
if(isset($_GET['id']) && isset($_GET['redirect'])){
    $id = $_GET['id'];
    $page = $_GET['redirect'];

    try {
        $stmt = $pdo->prepare("DELETE FROM transaksi_kas WHERE id = ?");
        $stmt->execute([$id]);
        // Kembali ke halaman asal
        header("Location: ../index.php?page=" . $page);
    } catch (Exception $e) {
        echo "Gagal menghapus: " . $e->getMessage();
    }
} else {
    header("Location: ../index.php");
}
?>