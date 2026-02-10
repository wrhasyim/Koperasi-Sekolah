<?php
require_once '../config/database.php';

// 1. AMBIL DATA DARI DATABASE
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

$sql = "SELECT * FROM transaksi_kas 
        WHERE (tanggal BETWEEN ? AND ?) 
        AND kategori NOT IN ('penjualan_seragam', 'penjualan_eskul') 
        ORDER BY tanggal ASC, id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$tgl_awal, $tgl_akhir]);
$transaksi = $stmt->fetchAll();

// 2. SET HEADER AGAR DIKENALI SEBAGAI FILE EXCEL
$filename = "Laporan_Kas_Koperasi_" . date('d-m-Y', strtotime($tgl_awal)) . "_sd_" . date('d-m-Y', strtotime($tgl_akhir)) . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// 3. MULAI OUTPUT HTML UNTUK STYLING EXCEL
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .table-header { background-color: #4e73df; color: #ffffff; font-weight: bold; text-align: center; vertical-align: middle; }
        .table-footer { background-color: #eaecf4; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .date-col { mso-number-format:"Short Date"; } /* Format tanggal agar dikenali Excel */
        .num-col { mso-number-format:"\#\,\#\#0"; } /* Format angka ribuan */
        
        /* Warna Kategori */
        .masuk { color: #1cc88a; } /* Hijau */
        .keluar { color: #e74a3b; } /* Merah */
    </style>
</head>
<body>

    <table border="0" width="100%">
        <tr>
            <td colspan="6" style="font-size: 18px; font-weight: bold; text-align: center; height: 30px;">
                LAPORAN ARUS KAS KOPERASI SEKOLAH
            </td>
        </tr>
        <tr>
            <td colspan="6" style="font-size: 12px; text-align: center; font-style: italic;">
                Periode: <?= date('d F Y', strtotime($tgl_awal)) ?> s/d <?= date('d F Y', strtotime($tgl_akhir)) ?>
            </td>
        </tr>
        <tr><td colspan="6"></td></tr> </table>

    <table border="1" width="100%" style="border-collapse: collapse; border: 1px solid #000;">
        <thead>
            <tr>
                <th class="table-header" height="30" width="120">TANGGAL</th>
                <th class="table-header" width="180">KATEGORI</th>
                <th class="table-header" width="350">KETERANGAN TRANSAKSI</th>
                <th class="table-header" width="130">MASUK (Rp)</th>
                <th class="table-header" width="130">KELUAR (Rp)</th>
                <th class="table-header" width="130">SALDO (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $saldo = 0;
            $total_masuk = 0;
            $total_keluar = 0;

            if(empty($transaksi)): ?>
                <tr>
                    <td colspan="6" class="text-center" height="30">Tidak ada transaksi pada periode ini.</td>
                </tr>
            <?php endif;

            foreach($transaksi as $row): 
                $masuk = ($row['arus'] == 'masuk') ? $row['jumlah'] : 0;
                $keluar = ($row['arus'] == 'keluar') ? $row['jumlah'] : 0;
                
                $saldo += ($masuk - $keluar);
                $total_masuk += $masuk;
                $total_keluar += $keluar;
                
                $kat_clean = ucwords(str_replace('_', ' ', $row['kategori']));
                $color_class = ($row['arus'] == 'masuk') ? '#f0fff4' : '#fff5f5'; // Warna baris tipis
            ?>
            <tr>
                <td class="text-center date-col" style="background-color: <?= $color_class ?>;">
                    <?= $row['tanggal'] ?>
                </td>
                <td class="text-left" style="background-color: <?= $color_class ?>;">
                    <?= $kat_clean ?>
                </td>
                <td class="text-left" style="background-color: <?= $color_class ?>;">
                    <?= htmlspecialchars($row['keterangan']) ?>
                </td>
                <td class="text-right num-col" style="background-color: <?= $color_class ?>; color: #1cc88a;">
                    <?= $masuk > 0 ? $masuk : '-' ?>
                </td>
                <td class="text-right num-col" style="background-color: <?= $color_class ?>; color: #e74a3b;">
                    <?= $keluar > 0 ? $keluar : '-' ?>
                </td>
                <td class="text-right num-col" style="font-weight: bold; background-color: #f8f9fc;">
                    <?= $saldo ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="table-footer text-right" height="30">TOTAL AKHIR PERIODE</td>
                <td class="table-footer text-right num-col" style="color: #1cc88a;"><?= $total_masuk ?></td>
                <td class="table-footer text-right num-col" style="color: #e74a3b;"><?= $total_keluar ?></td>
                <td class="table-footer text-right num-col" style="background-color: #dde2f1;"><?= $saldo ?></td>
            </tr>
        </tfoot>
    </table>

    <br><br>

    <table border="0" width="100%">
        <tr>
            <td width="10%"></td>
            <td width="30%" align="center">
                Mengetahui,<br>Kepala Koperasi
                <br><br><br><br><br>
                ( ____________________ )
            </td>
            <td width="20%"></td>
            <td width="30%" align="center">
                <?= date('d F Y') ?><br>Bendahara
                <br><br><br><br><br>
                ( ____________________ )
            </td>
            <td width="10%"></td>
        </tr>
    </table>

</body>
</html>