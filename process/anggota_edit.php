<?php
// process/anggota_edit.php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

$id           = $_POST['id'];
$nama         = $_POST['nama'];
$username     = $_POST['username'];
$role         = $_POST['role'];
$status_aktif = $_POST['status_aktif'];
$no_hp        = $_POST['no_hp'] ?? null; // PERBAIKAN: Menangkap data nomor HP
$pw_baru      = $_POST['password_baru'];

try {
    if(!empty($pw_baru)){
        $hash = password_hash($pw_baru, PASSWORD_DEFAULT);
        // PERBAIKAN: Menambahkan kolom no_hp ke dalam query
        $sql = "UPDATE anggota SET nama_lengkap=?, username=?, role=?, status_aktif=?, no_hp=?, password=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $username, $role, $status_aktif, $no_hp, $hash, $id]);
    } else {
        // PERBAIKAN: Menambahkan kolom no_hp ke dalam query
        $sql = "UPDATE anggota SET nama_lengkap=?, username=?, role=?, status_aktif=?, no_hp=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $username, $role, $status_aktif, $no_hp, $id]);
    }
    
    // Jika user mengedit profilnya sendiri, update session nama agar tampilan di header berubah
    if($_SESSION['user']['id'] == $id){
        $_SESSION['user']['nama_lengkap'] = $nama;
    }

    setFlash('success', 'Data berhasil diperbarui.');
} catch (Exception $e) {
    setFlash('danger', 'Gagal update: ' . $e->getMessage());
}

// Logika redirect otomatis: jika diedit dari profil, balik ke profil. Jika dari admin, balik ke data anggota.
if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'profil') !== false){
    header("Location: ../index.php?page=profil");
} else {
    header("Location: ../index.php?page=data_anggota");
}
exit;