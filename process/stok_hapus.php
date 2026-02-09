<?php
session_start();
require_once '../config/database.php';

if(isset($_GET['id']) && isset($_GET['redirect'])){
    $id = $_GET['id'];
    $page = $_GET['redirect']; // Isinya sekarang: inventory/stok_sekolah

    try {
        $stmt = $pdo->prepare("DELETE FROM stok_barang WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redirect bersih (Naik satu folder dari process/ ke root, lalu ke inventory/...)
        header("Location: ../" . $page);
    } catch (Exception $e) {
        echo "Gagal menghapus: " . $e->getMessage();
    }
} else {
    header("Location: ../dashboard");
}
?>