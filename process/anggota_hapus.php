<?php
session_start();
require_once '../config/database.php';

$id = $_GET['id'];
try {
    $pdo->prepare("DELETE FROM anggota WHERE id = ?")->execute([$id]);
    $_SESSION['success'] = "Anggota berhasil dihapus!";
} catch (Exception $e) {
    $_SESSION['error'] = "Gagal menghapus data. Mungkin masih ada data simpanan terkait.";
}

// Redirect Clean
header("Location: ../data_anggota");
?>