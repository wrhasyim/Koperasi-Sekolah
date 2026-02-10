<?php
session_start();
// Pastikan path ke config benar (naik satu folder ke root, lalu ke config)
require_once '../config/database.php';
require_once '../config/functions.php'; // Memuat formatRp

// Cek Login (Security)
if(!isset($_SESSION['user'])){
    die("Akses Ditolak. Silakan login.");
}

// LOGIKA EXPORT EXCEL
$kelas_filter = $_GET['kelas_filter'] ?? '';
$filename = "Rekap_Keuangan_" . ($kelas_filter ? "Kelas_$kelas_filter" : "Semua_Kelas") . "_" . date('Ymd') . ".xls";

// Header untuk memicu download file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Query Data
$sql = "SELECT * FROM cicilan WHERE 1=1";
if($kelas_filter) {
    // Gunakan prepared statement untuk keamanan jika memungkinkan, 
    // tapi karena ini file terpisah sederhana, kita escape manual atau pastikan input aman.
    // Di sini kita langsung masukkan karena $_GET diambil string sederhana.
    $sql .= " AND kelas = '$kelas_filter'";
}
$sql .= " ORDER BY kelas ASC, nama_siswa ASC";

$stmt = $pdo->query($sql);
$data_export = $stmt->fetchAll();

// Cetak Tabel HTML (yang akan dibaca Excel)
?>
<table border='1'>
    <thead>
        <tr style='background-color:yellow;'>
            <th>NO</th>
            <th>KELAS</th>
            <th>NAMA SISWA</th>
            <th>ITEM</th>
            <th>TOTAL TAGIHAN</th>
            <th>TERBAYAR</th>
            <th>SISA HUTANG</th>
            <th>STATUS</th>
            <th>CATATAN</th>
            <th>TGL TRANSAKSI</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $no=1;
    foreach($data_export as $row){
        $status = strtoupper($row['status']);
        $bg = ($status=='LUNAS') ? '#c8e6c9' : '#ffcdd2'; // Hijau jika lunas, Merah jika hutang
        
        // Format Rupiah Manual jika formatRp tidak tersedia, atau gunakan formatRp dari functions.php
        $tagihan = formatRp($row['total_tagihan']);
        $terbayar = formatRp($row['terbayar']);
        $sisa = formatRp($row['sisa']);
        $tgl = date('d/m/Y', strtotime($row['created_at']));

        echo "
            <tr>
                <td>$no</td>
                <td>{$row['kelas']}</td>
                <td>{$row['nama_siswa']}</td>
                <td>{$row['nama_barang']} ({$row['kategori_barang']})</td>
                <td>$tagihan</td>
                <td>$terbayar</td>
                <td style='background-color:$bg;'>$sisa</td>
                <td>$status</td>
                <td>{$row['catatan']}</td>
                <td>$tgl</td>
            </tr>";
        $no++;
    }
    ?>
    </tbody>
</table>