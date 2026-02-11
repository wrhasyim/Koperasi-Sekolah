<?php
// process/import_handler.php
session_start();
require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_FILES['file_csv'])){ header("Location: ../index.php"); exit; }

$tipe = $_POST['tipe_import'];
$file = $_FILES['file_csv']['tmp_name'];
$barang = explode('|', $_POST['barang_id']);
$nama_barang = $barang[1];
$harga = $barang[2];
$kat = ($tipe == 'mpls') ? 'seragam' : 'eskul';

try {
    $pdo->beginTransaction();
    $handle = fopen($file, "r");
    $row = 0; $sukses = 0;

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $row++; if($row == 1) continue; 

        $nis = ''; $nama = ''; $kelas = '';
        if($tipe == 'mpls'){
            $nis = trim($data[0]); $nama = trim($data[1]); $kelas = trim($data[2]);
            if(empty($kelas)) $kelas = "SISWA BARU";
            if(empty($nis)) $nis = "TMP-" . rand(1000,9999) . date('is');

            $cek = $pdo->prepare("SELECT id FROM siswa WHERE nis = ?");
            $cek->execute([$nis]);
            if($cek->rowCount() == 0){
                $pdo->prepare("INSERT INTO siswa (nis, nama_siswa, kelas, angkatan) VALUES (?, ?, ?, YEAR(NOW()))")->execute([$nis, $nama, $kelas]);
            }
        } else {
            $nama = trim($data[0]); $kelas = trim($data[1]);
        }

        if(!empty($nama)){
            $pdo->prepare("INSERT INTO cicilan (nama_siswa, kelas, kategori_barang, nama_barang, total_tagihan, terbayar, sisa, status, catatan) VALUES (?, ?, ?, ?, ?, 0, ?, 'belum', 'Import Auto')")->execute([$nama, $kelas, $kat, $nama_barang, $harga, $harga]);
            $sukses++;
        }
    }
    fclose($handle);
    $pdo->commit();
    $_SESSION['flash'] = ['type' => 'success', 'message' => "Import $sukses data sukses."];
} catch(Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash'] = ['type' => 'danger', 'message' => "Error: " . $e->getMessage()];
}
header("Location: ../index.php?page=utilitas/import_data");
?>