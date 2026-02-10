<?php
// pages/kas/cetak_laporan_distribusi.php
require_once '../../config/database.php';
require_once '../../config/functions.php';

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'seragam';

// Konfigurasi
if($tab == 'seragam'){
    $kategori_kas = 'penjualan_seragam';
    $kategori_cicilan = 'seragam';
    $judul = 'LAPORAN KEUANGAN SERAGAM SEKOLAH';
} else {
    $kategori_kas = 'penjualan_eskul';
    $kategori_cicilan = 'eskul';
    $judul = 'LAPORAN KEUANGAN ATRIBUT ESKUL';
}

// Query 1: Pemasukan
$sql_masuk = "SELECT * FROM transaksi_kas 
              WHERE (tanggal BETWEEN ? AND ?) 
              AND kategori = ? 
              ORDER BY tanggal ASC";
$stmt = $pdo->prepare($sql_masuk);
$stmt->execute([$tgl_awal, $tgl_akhir, $kategori_kas]);
$data_masuk = $stmt->fetchAll();

// Query 2: Piutang (Status Belum Lunas)
$sql_piutang = "SELECT * FROM cicilan 
                WHERE status = 'belum' 
                AND kategori_barang = ? 
                ORDER BY created_at DESC";
$stmt2 = $pdo->prepare($sql_piutang);
$stmt2->execute([$kategori_cicilan]);
$data_piutang = $stmt2->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Distribusi</title>
    <style>
        body { font-family: 'Times New Roman', serif; padding: 20px; font-size: 11pt; }
        .header { text-align: center; border-bottom: 3px double black; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 14pt; }
        .row { display: flex; gap: 20px; }
        .col { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid black; padding: 4px 6px; font-size: 10pt; }
        th { background-color: #f0f0f0; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        @media print {
            .no-print { display: none; }
            @page { size: A4 landscape; margin: 1cm; }
        }
    </style>
</head>
<body onload="window.print()">

    <button class="no-print" onclick="window.close()">Tutup</button>

    <div class="header">
        <h2>KOPERASI SEKOLAH</h2>
        <h3><?= $judul ?></h3>
        <small>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></small>
    </div>

    <div class="row">
        <div class="col">
            <h4 style="margin: 0 0 10px 0; text-decoration: underline;">I. Rincian Pemasukan Uang</h4>
            <table>
                <thead>
                    <tr>
                        <th width="20%">Tanggal</th>
                        <th>Keterangan</th>
                        <th width="25%">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_masuk = 0;
                    if(empty($data_masuk)): ?>
                        <tr><td colspan="3" class="text-center">- Tidak ada data -</td></tr>
                    <?php endif;
                    foreach($data_masuk as $row): 
                        $total_masuk += $row['jumlah'];
                    ?>
                    <tr>
                        <td class="text-center"><?= date('d/m/y', strtotime($row['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                        <td class="text-right"><?= number_format($row['jumlah']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-right text-bold">TOTAL MASUK</td>
                        <td class="text-right text-bold" style="background: #eee;"><?= number_format($total_masuk) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="col">
            <h4 style="margin: 0 0 10px 0; text-decoration: underline;">II. Daftar Piutang Siswa (Tertahan)</h4>
            <table>
                <thead>
                    <tr>
                        <th width="35%">Siswa & Kelas</th>
                        <th>Barang</th>
                        <th width="25%">Sisa Tagihan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_piutang = 0;
                    if(empty($data_piutang)): ?>
                        <tr><td colspan="3" class="text-center">- Tidak ada piutang -</td></tr>
                    <?php endif;
                    foreach($data_piutang as $row): 
                        $total_piutang += $row['sisa'];
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_siswa']) ?></strong><br>
                            <small><?= htmlspecialchars($row['kelas']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td class="text-right"><?= number_format($row['sisa']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-right text-bold">TOTAL PIUTANG</td>
                        <td class="text-right text-bold" style="background: #eee;"><?= number_format($total_piutang) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: right; margin-right: 50px;">
        <p><?= date('d F Y') ?></p>
        <p>Petugas Administrasi</p>
        <br><br>
        <p>____________________</p>
    </div>

</body>
</html>