<?php
require_once '../../config/database.php';
require_once '../../config/functions.php';

session_start();
if(!isset($_SESSION['user'])) exit;

$guru_id = isset($_GET['guru_id']) ? $_GET['guru_id'] : '';
$where_sql = "WHERE a.role NOT IN ('admin', 'staff')";
$judul_ket = "SEMUA GURU";

if($guru_id){
    $where_sql .= " AND t.anggota_id = '$guru_id'";
    $stmt = $pdo->prepare("SELECT nama_lengkap FROM anggota WHERE id = ?");
    $stmt->execute([$guru_id]);
    $guru = $stmt->fetch();
    if($guru) $judul_ket = strtoupper($guru['nama_lengkap']);
}

$sql = "SELECT t.*, a.nama_lengkap 
        FROM titipan t 
        JOIN anggota a ON t.anggota_id = a.id 
        $where_sql 
        ORDER BY a.nama_lengkap ASC, t.nama_barang ASC";
$data = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Titipan - <?= $judul_ket ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px double black; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 16pt; text-transform: uppercase; }
        .header p { margin: 0; font-size: 10pt; font-style: italic; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid black; padding: 6px 8px; font-size: 10pt; }
        th { background-color: #f0f0f0; text-align: center; text-transform: uppercase; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .footer { margin-top: 40px; width: 100%; }
        .ttd-box { width: 30%; float: right; text-align: center; }
        @media print {
            .no-print { display: none; }
            @page { size: A4; margin: 2cm; }
        }
    </style>
</head>
<body onload="window.print()">

    <button class="no-print" onclick="window.history.back()" style="margin-bottom: 20px;">&larr; Kembali</button>

    <div class="header">
        <h2>KOPERASI SEKOLAH</h2>
        <p>Laporan Rekapitulasi Barang Titipan (Konsinyasi)</p>
    </div>

    <div style="margin-bottom: 15px;">
        <strong>Periode Cetak:</strong> <?= date('d F Y') ?><br>
        <strong>Pemilik Barang:</strong> <?= $judul_ket ?>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Barang</th>
                <th width="20%">Pemilik</th>
                <th width="10%">Stok Awal</th>
                <th width="10%">Sisa Fisik</th>
                <th width="10%">Terjual</th>
                <th width="15%">Harga Modal</th>
                <th width="15%">Wajib Setor</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no=1; $g_hak=0; $g_terjual=0;
            foreach($data as $row): 
                $hak = $row['stok_terjual'] * $row['harga_modal'];
                $sisa = $row['stok_awal'] - $row['stok_terjual'];
                $g_hak += $hak;
                $g_terjual += $row['stok_terjual'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                <td class="text-center"><?= $row['stok_awal'] ?></td>
                
                <td class="text-center text-bold" style="background-color: #fcf8e3;"><?= $sisa ?></td>
                
                <td class="text-center"><?= $row['stok_terjual'] ?></td>
                <td class="text-right"><?= number_format($row['harga_modal']) ?></td>
                <td class="text-right text-bold"><?= number_format($hak) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-center text-bold" style="background-color: #eee;">GRAND TOTAL</td>
                <td class="text-center text-bold" style="background-color: #eee;"><?= number_format($g_terjual) ?></td>
                <td colspan="2" style="background-color: #eee;"></td>
                <td class="text-right text-bold" style="background-color: #ddd;">Rp <?= number_format($g_hak) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <div class="ttd-box">
            <p><?= date('d F Y') ?></p>
            <p>Petugas Koperasi</p>
            <br><br><br>
            <p class="text-bold">____________________</p>
        </div>
    </div>
</body>
</html>