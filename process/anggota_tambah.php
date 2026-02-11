<?php
// process/anggota_tambah.php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

$nama     = $_POST['nama'];
$username = $_POST['username'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$role     = $_POST['role'];
$no_hp    = $_POST['no_hp'] ?? null; // Menangkap input nomor HP

try {
    // Menambahkan kolom no_hp ke dalam query INSERT
    $sql = "INSERT INTO anggota (nama_lengkap, username, password, role, no_hp) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $username, $password, $role, $no_hp]);
    
    setFlash('success', 'Anggota baru berhasil ditambahkan!');
} catch (PDOException $e) {
    setFlash('danger', 'Gagal: Username mungkin sudah digunakan atau database error.');
}

header("Location: ../index.php?page=data_anggota");
exit;