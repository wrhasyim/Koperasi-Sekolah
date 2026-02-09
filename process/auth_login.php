<?php
session_start();
require_once '../config/database.php';

$username = $_POST['username'];
$password = $_POST['password'];

$stmt = $pdo->prepare("SELECT * FROM anggota WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

// Cek User & Password Hash
if($user && password_verify($password, $user['password'])){
    if($user['status_aktif'] == 0){
        $_SESSION['error'] = "Akun dinonaktifkan.";
        header("Location: ../login.php");
        exit;
    }

    // Set Session
    $_SESSION['user'] = [
        'id' => $user['id'],
        'nama' => $user['nama_lengkap'],
        'role' => $user['role']
    ];
    header("Location: ../index.php");
} else {
    $_SESSION['error'] = "Username atau Password salah!";
    header("Location: ../login.php");
}
?>