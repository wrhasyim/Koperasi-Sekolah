<?php
// File ini tidak menggunakan layout index.php agar bersih saat diprint
require_once '../config/database.php';
require_once '../config/functions.php';

session_start();
if(!isset($_SESSION['user'])) exit;

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM transaksi_kas WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if(!$row) die("Data tidak ditemukan");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Struk #<?= $row['id'] ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 14px; max-width: 300px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
        .header h3 { margin: 0; text-transform: uppercase; }
        .info { margin-bottom: 15px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .total { border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; font-weight: bold; font-size: 16px; margin: 15px 0; }
        .footer { text-align: center; font-size: 12px; margin-top: 20px; }
        
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h3>KOPERASI SEKOLAH</h3>
        <p>Jl. Pendidikan No. 1<br>Telp: 0812-3456-7890</p>
    </div>

    <div class="info">
        <div class="row">
            <span>No. Transaksi</span>
            <span>#<?= $row['id'] ?></span>
        </div>
        <div class="row">
            <span>Tanggal</span>
            <span><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></span>
        </div>
        <div class="row">
            <span>Kasir</span>
            <span><?= $_SESSION['user']['nama'] ?></span>
        </div>
    </div>

    <div style="margin-bottom: 10px;">
        <strong>Keterangan:</strong><br>
        <?= $row['keterangan'] ?><br>
        <small>(<?= strtoupper(str_replace('_', ' ', $row['kategori'])) ?>)</small>
    </div>

    <div class="row total">
        <span>TOTAL</span>
        <span><?= formatRp($row['jumlah']) ?></span>
    </div>

    <div class="footer">
        <p>Terima Kasih atas Kunjungan Anda<br>Barang yang dibeli tidak dapat ditukar</p>
        <small>Dicetak pada: <?= date('d-m-Y H:i:s') ?></small>
    </div>

    <button class="no-print" style="width: 100%; padding: 10px; cursor: pointer; margin-top: 20px;" onclick="window.history.back()">Kembali</button>

</body>
</html>