<?php
// process/import_handler.php
session_start();
require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_FILES['file_csv'])){ header("Location: ../index.php"); exit; }

$tipe = $_POST['tipe_import'];
$file = $_FILES['file_csv']['tmp_name'];

// Ambil data barang (ID|Nama|Harga)
$barang_data = explode('|', $_POST['barang_id']);
$id_barang_asli = $barang_data[0];
$nama_barang    = $barang_data[1];
$harga          = $barang_data[2];

$tabel_stok  = ($tipe == 'mpls') ? 'stok_sekolah' : 'stok_eskul';
$kat_cicilan = ($tipe == 'mpls') ? 'seragam' : 'eskul';

try {
    $pdo->beginTransaction();
    
    // Logika NIS Berurutan untuk MPLS
    $next_num = 1;
    if($tipe == 'mpls'){
        $year = date('Y');
        $stmt = $pdo->prepare("SELECT nis FROM siswa WHERE nis LIKE ? ORDER BY nis DESC LIMIT 1");
        $stmt->execute([$year . "%"]);
        $last_nis = $stmt->fetchColumn();
        if($last_nis) { $next_num = (int)substr($last_nis, 4) + 1; }
    }

    $handle = fopen($file, "r");
    $row = 0; $sukses = 0;

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $row++; if($row == 1) continue; // Skip header

        $nama = trim($data[0]);
        if(empty($nama)) continue;

        if($tipe == 'mpls'){
            $kelas = "MPLS";
            $nis   = date('Y') . str_pad($next_num, 4, '0', STR_PAD_LEFT);
            $next_num++;
        } else {
            $nis   = trim($data[0] ?? '');
            $nama  = trim($data[1] ?? '');
            $kelas = trim($data[2] ?? '');
        }

        // 1. Simpan/Update Master Siswa
        $cek = $pdo->prepare("SELECT id FROM siswa WHERE nama_siswa = ? AND kelas = ?");
        $cek->execute([$nama, $kelas]);
        if($cek->rowCount() == 0){
            $pdo->prepare("INSERT INTO siswa (nis, nama_siswa, kelas, angkatan, status) VALUES (?, ?, ?, YEAR(NOW()), 'aktif')")
                ->execute([$nis, $nama, $kelas]);
        }

        // 2. Buat Tagihan/Distribusi Otomatis
        $pdo->prepare("INSERT INTO cicilan (nama_siswa, kelas, kategori_barang, nama_barang, total_tagihan, terbayar, sisa, status, catatan, is_archived) VALUES (?, ?, ?, ?, ?, 0, ?, 'belum', 'Import Massal', 0)")
            ->execute([$nama, $kelas, $kat_cicilan, $nama_barang, $harga, $harga]);

        // 3. SINKRONISASI STOK: Kurangi stok barang tersebut
        $pdo->prepare("UPDATE $tabel_stok SET stok = stok - 1 WHERE id = ?")->execute([$id_barang_asli]);
        
        $sukses++;
    }
    fclose($handle);
    $pdo->commit();
    $_SESSION['flash'] = ['type' => 'success', 'message' => "Import $sukses data $tipe sukses. Stok barang otomatis berkurang."];
} catch(Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash'] = ['type' => 'danger', 'message' => "Error: " . $e->getMessage()];
}
header("Location: ../index.php?page=utilitas/import_data");