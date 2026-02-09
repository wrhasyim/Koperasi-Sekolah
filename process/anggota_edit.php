<?php
session_start();
require_once '../config/database.php';

$id = $_POST['id'];
$nama = $_POST['nama'];
$username = $_POST['username'];
$role = $_POST['role'];
$status = $_POST['status_aktif'];
$pass_baru = $_POST['password_baru'];

try {
    if(!empty($pass_baru)){
        // Jika password diisi, update password juga
        $hash = password_hash($pass_baru, PASSWORD_DEFAULT);
        $sql = "UPDATE anggota SET nama_lengkap=?, username=?, role=?, status_aktif=?, password=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $username, $role, $status, $hash, $id]);
    } else {
        // Jika password kosong, jangan update password
        $sql = "UPDATE anggota SET nama_lengkap=?, username=?, role=?, status_aktif=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $username, $role, $status, $id]);
    }

    $_SESSION['success'] = "Data anggota berhasil diperbarui!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Gagal: Username mungkin sudah dipakai orang lain.";
}

// Redirect Clean URL
header("Location: ../data_anggota");
?>