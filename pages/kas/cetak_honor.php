<?php
// pages/kas/cetak_honor.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

// 1. AMBIL PARAMETER
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$tipe      = isset($_GET['tipe']) ? $_GET['tipe'] : 'staff';

// 2. HITUNG SURPLUS (Sama dengan rekap)
$sql = "SELECT * FROM transaksi_kas 
        WHERE (tanggal BETWEEN ? AND ?) 
        AND kategori NOT IN ('penjualan_seragam', 'penjualan_eskul')";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tgl_awal, $tgl_akhir]);
$transaksi = $stmt->fetchAll();

$total_masuk = 0; $total_keluar = 0;
foreach($transaksi as $t){
    if($t['arus'] == 'masuk') $total_masuk += $t['jumlah'];
    else $total_keluar += $t['jumlah'];
}
$surplus = $total_masuk - $total_keluar;

// 3. TENTUKAN JUDUL & NOMINAL
$judul = "";
$nominal = 0;
$persen = "";
$penerima_ket = "";

switch($tipe){
    case 'staff':
        $judul = "HONORARIUM STAFF / PETUGAS";
        $persen = "20%";
        $nominal = $surplus * 0.20;
        $penerima_ket = "Perwakilan Staff";
        break;
    case 'pengurus':
        $judul = "HONORARIUM PENGURUS KOPERASI";
        $persen = "15%";
        $nominal = $surplus * 0.15;
        $penerima_ket = "Perwakilan Pengurus";
        break;
    case 'pembina':
        $judul = "HONORARIUM PEMBINA";
        $persen = "5%";
        $nominal = $surplus * 0.05;
        $penerima_ket = "Pembina Koperasi";
        break;
    case 'dansos':
        $judul = "DANA SOSIAL (DANSOS)";
        $persen = "10%";
        $nominal = $surplus * 0.10;
        $penerima_ket = "Pengelola Dansos";
        break;
}

// Fungsi Terbilang Sederhana
function penyebut($nilai) {
    $nilai = abs($nilai);
    $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
    $temp = "";
    if ($nilai < 12) {
        $temp = " ". $huruf[$nilai];
    } else if ($nilai <20) {
        $temp = penyebut($nilai - 10). " belas";
    } else if ($nilai < 100) {
        $temp = penyebut($nilai/10)." puluh". penyebut($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " seratus" . penyebut($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = penyebut($nilai/100) . " ratus" . penyebut($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " seribu" . penyebut($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = penyebut($nilai/1000) . " ribu" . penyebut($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = penyebut($nilai/1000000) . " juta" . penyebut($nilai % 1000000);
    }
    return $temp;
}
function terbilang($nilai) {
    if($nilai<0) {
        $hasil = "minus ". trim(penyebut($nilai));
    } else {
        $hasil = trim(penyebut($nilai));
    }
    return ucwords($hasil) . " Rupiah";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cetak Bukti - <?= $judul ?></title>
    <style>
        body { font-family: 'Times New Roman', serif; padding: 40px; }
        .voucher { border: 2px solid #000; padding: 30px; width: 100%; max-width: 800px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px double #000; padding-bottom: 10px; margin-bottom: 30px; }
        .header h2 { margin: 0; text-transform: uppercase; }
        .row { display: flex; margin-bottom: 15px; align-items: flex-start; }
        .label { width: 180px; font-weight: bold; }
        .sep { width: 20px; }
        .val { flex: 1; border-bottom: 1px dotted #999; padding-bottom: 5px; }
        .amount-box { background: #f0f0f0; border: 2px solid #000; padding: 10px 20px; font-size: 20px; font-weight: bold; float: right; margin-top: 20px; }
        .ttd-area { margin-top: 60px; display: flex; justify-content: space-between; text-align: center; }
        .ttd-box { width: 200px; }
        @media print {
            .no-print { display: none; }
            @page { size: A4 landscape; margin: 1cm; }
        }
    </style>
</head>
<body onload="window.print()">

    <button class="no-print" onclick="window.close()" style="margin-bottom: 20px; padding: 10px 20px; cursor: pointer;">Tutup</button>

    <div class="voucher">
        <div class="header">
            <h2>KOPERASI SEKOLAH</h2>
            <h3 style="margin: 5px 0 0;">BUKTI PENGELUARAN KAS</h3>
        </div>

        <div class="row">
            <div class="label">Sudah Terima Dari</div>
            <div class="sep">:</div>
            <div class="val">Bendahara Koperasi Sekolah</div>
        </div>
        
        <div class="row">
            <div class="label">Uang Sejumlah</div>
            <div class="sep">:</div>
            <div class="val" style="font-style: italic;"><?= terbilang($nominal) ?></div>
        </div>

        <div class="row">
            <div class="label">Untuk Pembayaran</div>
            <div class="sep">:</div>
            <div class="val"><?= $judul ?> (Alokasi <?= $persen ?> dari Surplus Kas)</div>
        </div>

        <div class="row">
            <div class="label">Periode Kas</div>
            <div class="sep">:</div>
            <div class="val"><?= date('d F Y', strtotime($tgl_awal)) ?> s/d <?= date('d F Y', strtotime($tgl_akhir)) ?></div>
        </div>

        <div style="overflow: hidden; margin-top: 20px;">
            <div class="amount-box">
                <?= formatRp($nominal) ?>,-
            </div>
        </div>

        <div class="ttd-area">
            <div class="ttd-box">
                <p>Disetujui Oleh,<br>Ketua Koperasi</p>
                <br><br><br>
                <p>( __________________ )</p>
            </div>
            <div class="ttd-box">
                <p>Dibayar Oleh,<br>Bendahara</p>
                <br><br><br>
                <p>( __________________ )</p>
            </div>
            <div class="ttd-box">
                <p><?= date('d F Y') ?><br>Penerima,</p>
                <br><br><br>
                <p>( <?= $penerima_ket ?> )</p>
            </div>
        </div>
    </div>

</body>
</html>