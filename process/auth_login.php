<?php
// process/auth_login.php
session_start();
require_once '../config/database.php';

$username = $_POST['username'];
$password = $_POST['password'];

$stmt = $pdo->prepare("SELECT * FROM anggota WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if($user && password_verify($password, $user['password'])){
    if($user['status_aktif'] == 0){
        $_SESSION['error'] = "Akun dinonaktifkan.";
        header("Location: ../login.php");
        exit;
    }

    // PERBAIKAN: Menggunakan 'nama_lengkap' agar cocok dengan dashboard
    $_SESSION['user'] = [
        'id' => $user['id'],
        'nama_lengkap' => $user['nama_lengkap'], 
        'role' => $user['role']
    ];
    header("Location: ../index.php");
} else {
    $_SESSION['error'] = "Username atau Password salah!";
    header("Location: ../login.php");
}
?>