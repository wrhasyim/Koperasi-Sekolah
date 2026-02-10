<?php
// process/export_riwayat_honor.php
require_once '../config/database.php';

// 1. AMBIL FILTER
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-01-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$jenis     = isset($_GET['jenis']) ? $_GET['jenis'] : 'all'; // Filter Jenis

// 2. QUERY DATA PEMBAYARAN HONOR
$sql = "SELECT t.*, u.nama_lengkap as admin_name 
        FROM transaksi_kas t
        LEFT JOIN anggota u ON t.user_id = u.id
        WHERE (t.tanggal BETWEEN ? AND ?) 
        AND t.kategori IN ('bagi_hasil_staff', 'bagi_hasil_pengurus', 'bagi_hasil_pembina', 'bagi_hasil_dansos')";

$params = [$tgl_awal, $tgl_akhir];

// Tambahan Filter Jenis
if($jenis != 'all'){
    $kategori_target = 'bagi_hasil_' . $jenis;
    $sql .= " AND t.kategori = ?";
    $params[] = $kategori_target;
}

$sql .= " ORDER BY t.tanggal DESC, t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// 3. SET HEADER EXCEL
$filename = "Riwayat_Honor_" . ucfirst($jenis) . "_" . date('dmy', strtotime($tgl_awal)) . "-" . date('dmy', strtotime($tgl_akhir)) . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

function getLabel($kategori){
    switch($kategori){
        case 'bagi_hasil_staff': return 'Honor Staff';
        case 'bagi_hasil_pengurus': return 'Honor Pengurus';
        case 'bagi_hasil_pembina': return 'Honor Pembina';
        case 'bagi_hasil_dansos': return 'Dana Sosial';
        default: return 'Lainnya';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header-title { font-size: 18px; font-weight: bold; text-align: center; }
        .header-sub { font-size: 12px; text-align: center; font-style: italic; }
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #4e73df; color: #ffffff; padding: 10px; border: 1px solid #000; text-transform: uppercase; }
        td { padding: 5px; border: 1px solid #000; vertical-align: middle; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        
        /* FORMAT ANGKA DENGAN RP */
        .num-col { mso-number-format:"Rp \#\,\#\#0"; } 
        .date-col { mso-number-format:"Short Date"; }
        .bg-total { background-color: #eaecf4; }
    </style>
</head>
<body>

    <table border="0" width="100%">
        <tr><td colspan="6" class="header-title" height="30">LAPORAN RIWAYAT PEMBAYARAN HONOR & DANSOS</td></tr>
        <tr><td colspan="6" class="header-sub">Filter: <?= strtoupper($jenis) ?></td></tr>
        <tr><td colspan="6" class="header-sub">Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></td></tr>
        <tr><td colspan="6"></td></tr>
    </table>

    <table border="1">
        <thead>
            <tr>
                <th width="40">NO</th>
                <th width="100">TANGGAL</th>
                <th width="150">KATEGORI</th>
                <th width="350">KETERANGAN</th>
                <th width="150">ADMIN</th>
                <th width="150">NOMINAL</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; $total = 0;
            if(empty($data)): ?>
                <tr><td colspan="6" class="text-center" height="30">Tidak ada data pembayaran honor pada periode ini.</td></tr>
            <?php endif;

            foreach($data as $row): 
                $total += $row['jumlah'];
                $label = getLabel($row['kategori']);
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center date-col"><?= $row['tanggal'] ?></td>
                <td><?= $label ?></td>
                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                <td><?= htmlspecialchars($row['admin_name']) ?></td>
                <td class="text-right num-col"><?= $row['jumlah'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right text-bold bg-total" height="25">TOTAL PEMBAYARAN</td>
                <td class="text-right text-bold bg-total num-col"><?= $total ?></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>