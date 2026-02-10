<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Pastikan ID ada
if(!isset($_GET['id'])){
    die("ID Transaksi tidak ditemukan.");
}

$id = $_GET['id'];
// Ambil data dari tabel CICILAN (karena menyimpan detail lengkap transaksi)
$stmt = $pdo->prepare("SELECT * FROM cicilan WHERE id = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if(!$trx){
    die("Data transaksi tidak ditemukan.");
}

$sekolah = "KOPERASI SEKOLAH"; // Ganti nama sekolah Anda
$alamat  = "Jl. Pendidikan No. 123";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran</title>
    <style>
        body { font-family: 'Courier New', monospace; font-size: 12px; max-width: 300px; margin: 0 auto; padding: 10px; background: #fff; color: #000; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .border-top { border-top: 1px dashed #000; margin: 5px 0; }
        .border-bottom { border-bottom: 1px dashed #000; margin: 5px 0; }
        .table { width: 100%; }
        .table td { vertical-align: top; }
        @media print {
            @page { margin: 0; }
            body { margin: 0; padding: 5px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="text-center">
        <div class="fw-bold" style="font-size: 14px;"><?= $sekolah ?></div>
        <div><?= $alamat ?></div>
        <div class="border-bottom"></div>
        <div><?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></div>
    </div>

    <div class="border-bottom"></div>

    <table class="table">
        <tr>
            <td>Siswa</td>
            <td class="text-end fw-bold"><?= $trx['nama_siswa'] ?></td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td class="text-end"><?= $trx['kelas'] ?></td>
        </tr>
    </table>

    <div class="border-bottom"></div>

    <div style="margin-bottom: 5px;">
        <div class="fw-bold"><?= $trx['nama_barang'] ?></div>
        <div class="text-end"><?= formatRp($trx['total_tagihan']) ?></div>
    </div>

    <div class="border-top"></div>

    <table class="table">
        <tr>
            <td>Total Tagihan</td>
            <td class="text-end"><?= formatRp($trx['total_tagihan']) ?></td>
        </tr>
        <tr>
            <td>Sudah Bayar</td>
            <td class="text-end"><?= formatRp($trx['terbayar']) ?></td>
        </tr>
        <tr class="fw-bold">
            <td>SISA HUTANG</td>
            <td class="text-end"><?= formatRp($trx['sisa']) ?></td>
        </tr>
    </table>

    <div class="border-bottom"></div>

    <div class="text-center" style="margin-top: 10px;">
        <div class="fw-bold">STATUS: <?= strtoupper($trx['status']) ?></div>
        <div style="margin-top: 5px;">Terima Kasih</div>
        <div style="font-size: 10px; color: #555;">Simpan struk ini sebagai bukti pembayaran yang sah.</div>
    </div>

    <button onclick="window.print()" class="no-print" style="width: 100%; padding: 10px; margin-top: 20px; cursor: pointer; font-weight: bold; background: #ddd; border: none;">CETAK ULANG</button>

</body>
</html>