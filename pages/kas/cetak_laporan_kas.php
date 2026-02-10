<?php
// File: pages/kas/cetak_laporan_kas.php
require_once '../../config/database.php';
require_once '../../config/functions.php'; // Jika butuh formatRp

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

$sql = "SELECT * FROM transaksi_kas 
        WHERE (tanggal BETWEEN ? AND ?) 
        AND kategori NOT IN ('penjualan_seragam', 'penjualan_eskul') 
        ORDER BY tanggal ASC, id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$tgl_awal, $tgl_akhir]);
$transaksi = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Kas</title>
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 11pt; padding: 20px; }
        .header { text-align: center; border-bottom: 3px double black; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 16pt; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 5px; font-size: 10pt; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        @media print {
            .no-print { display: none; }
            @page { size: A4; margin: 2cm; }
        }
    </style>
</head>
<body onload="window.print()">

    <button class="no-print" onclick="window.history.back()">Kembali</button>

    <div class="header">
        <h2>Koperasi Sekolah</h2>
        <p>Laporan Arus Kas Operasional</p>
        <small>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></small>
    </div>

    <table>
        <thead>
            <tr>
                <th width="12%">Tanggal</th>
                <th width="20%">Kategori</th>
                <th>Keterangan</th>
                <th width="15%">Masuk</th>
                <th width="15%">Keluar</th>
                <th width="15%">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $saldo = 0;
            $total_masuk = 0;
            $total_keluar = 0;
            
            foreach($transaksi as $row): 
                $masuk = ($row['arus'] == 'masuk') ? $row['jumlah'] : 0;
                $keluar = ($row['arus'] == 'keluar') ? $row['jumlah'] : 0;
                $saldo += ($masuk - $keluar);
                $total_masuk += $masuk;
                $total_keluar += $keluar;
                $kat_clean = strtoupper(str_replace('_', ' ', $row['kategori']));
            ?>
            <tr>
                <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                <td><?= $kat_clean ?></td>
                <td><?= $row['keterangan'] ?></td>
                <td class="text-right"><?= number_format($masuk) ?></td>
                <td class="text-right"><?= number_format($keluar) ?></td>
                <td class="text-right"><strong><?= number_format($saldo) ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">TOTAL PERIODE</th>
                <th class="text-right"><?= number_format($total_masuk) ?></th>
                <th class="text-right"><?= number_format($total_keluar) ?></th>
                <th class="text-right"><?= number_format($saldo) ?></th>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px; float: right; text-align: center; width: 200px;">
        <p><?= date('d F Y') ?></p>
        <p>Bendahara</p>
        <br><br><br>
        <p>___________________</p>
    </div>
</body>
</html>