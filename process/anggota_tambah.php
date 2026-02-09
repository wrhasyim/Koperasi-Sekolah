<?php
session_start();
require_once '../config/database.php';

$nama = $_POST['nama'];
$username = $_POST['username'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$role = $_POST['role'];

try {
    $stmt = $pdo->prepare("INSERT INTO anggota (nama_lengkap, username, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nama, $username, $password, $role]);
    $_SESSION['success'] = "Anggota berhasil ditambahkan!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Gagal: Username mungkin sudah dipakai.";
}
header("Location: ../index.php?page=data_anggota");
?>