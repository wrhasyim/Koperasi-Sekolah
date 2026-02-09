<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if(isset($_GET['id']) && isset($_GET['redirect'])){
    $id = $_GET['id'];
    $page = $_GET['redirect']; 

    try {
        $cek = $pdo->prepare("SELECT tanggal FROM simpanan WHERE id = ?");
        $cek->execute([$id]);
        $data = $cek->fetch();

        if(cekStatusPeriode($pdo, $data['tanggal'])){
            echo "<script>alert('GAGAL! Periode TUTUP BUKU.'); window.location='../" . $page . "';</script>";
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM simpanan WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redirect aman (mendukung parameter ?tab=simpok)
        header("Location: ../" . $page);
    } catch (Exception $e) {
        echo "Gagal menghapus: " . $e->getMessage();
    }
} else {
    header("Location: ../dashboard");
}
?>