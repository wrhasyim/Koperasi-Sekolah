<?php
require_once '../config/database.php';

// 1. AMBIL PARAMETER
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$guru_id  = isset($_GET['guru_id']) ? $_GET['guru_id'] : '';

// 2. SIAPKAN QUERY & JUDUL
$sql = "SELECT t.*, a.nama_lengkap 
        FROM titipan t 
        JOIN anggota a ON t.anggota_id = a.id 
        WHERE (t.tanggal_titip BETWEEN ? AND ?)";
$params = [$tgl_awal, $tgl_akhir];
$judul_pemilik = "SEMUA GURU / PENITIP";

// Filter per Guru
if (!empty($guru_id)) {
    $sql .= " AND t.anggota_id = ?";
    $params[] = $guru_id;
    
    // Ambil nama guru untuk judul
    $stmt_guru = $pdo->prepare("SELECT nama_lengkap FROM anggota WHERE id = ?");
    $stmt_guru->execute([$guru_id]);
    $guru = $stmt_guru->fetch();
    if($guru) $judul_pemilik = strtoupper($guru['nama_lengkap']);
}

$sql .= " ORDER BY a.nama_lengkap ASC, t.tanggal_titip DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// 3. SET HEADER EXCEL
$filename = "Laporan_Titipan_" . str_replace(' ', '_', $judul_pemilik) . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

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
        .bg-total { background-color: #eaecf4; }
        .bg-sukses { background-color: #f0fff4; }
        .bg-danger { background-color: #fff5f5; }
        .num-col { mso-number-format:"\#\,\#\#0"; }
        .date-col { mso-number-format:"Short Date"; }
    </style>
</head>
<body>

    <table border="0" width="100%">
        <tr><td colspan="10" class="header-title" height="30">LAPORAN BARANG TITIPAN</td></tr>
        <tr><td colspan="10" class="header-sub">Pemilik: <?= $judul_pemilik ?></td></tr>
        <tr><td colspan="10" class="header-sub">Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></td></tr>
        <tr><td colspan="10"></td></tr>
    </table>

    <table border="1">
        <thead>
            <tr>
                <th width="40">NO</th>
                <th width="90">TANGGAL</th>
                <th width="200">PEMILIK</th>
                <th width="250">NAMA BARANG</th>
                <th width="100">HARGA MODAL</th>
                <th width="60">AWAL</th>
                <th width="60">TERJUAL</th>
                <th width="60">SISA</th>
                <th width="120">WAJIB SETOR</th>
                <th width="100">STATUS</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; $total_setor = 0; $total_terjual = 0;
            foreach($data as $row): 
                $sisa = $row['stok_awal'] - $row['stok_terjual'];
                $kewajiban = $row['stok_terjual'] * $row['harga_modal'];
                $total_setor += $kewajiban;
                $total_terjual += $row['stok_terjual'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center date-col"><?= $row['tanggal_titip'] ?></td>
                <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                <td class="text-right num-col"><?= $row['harga_modal'] ?></td>
                <td class="text-center num-col"><?= $row['stok_awal'] ?></td>
                <td class="text-center num-col bg-sukses"><?= $row['stok_terjual'] ?></td>
                <td class="text-center num-col bg-danger"><?= $sisa ?></td>
                <td class="text-right num-col text-bold bg-total"><?= $kewajiban ?></td>
                <td class="text-center"><?= strtoupper($row['status_bayar']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right text-bold bg-total" height="25">TOTAL</td>
                <td class="text-center text-bold bg-total num-col"><?= $total_terjual ?></td>
                <td class="bg-total"></td>
                <td class="text-right text-bold bg-total num-col" style="border-top: 2px solid #000;"><?= $total_setor ?></td>
                <td class="bg-total"></td>
            </tr>
        </tfoot>
    </table>
    
    <br><br>
    
    <table border="0">
        <tr>
            <td colspan="2"></td>
            <td colspan="3" align="center">Penerima,<br><br><br><br>( ________________ )</td>
            <td colspan="2"></td>
            <td colspan="3" align="center"><?= date('d F Y') ?><br>Petugas Admin,<br><br><br><br>( ________________ )</td>
        </tr>
    </table>
</body>
</html>