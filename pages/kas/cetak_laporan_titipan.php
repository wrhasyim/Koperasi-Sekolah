<?php
require_once '../../config/database.php';

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$guru_id  = isset($_GET['guru_id']) ? $_GET['guru_id'] : '';

// LOGIKA QUERY
$sql = "SELECT t.*, a.nama_lengkap 
        FROM titipan t 
        JOIN anggota a ON t.anggota_id = a.id 
        WHERE (t.tanggal_titip BETWEEN ? AND ?)";
$params = [$tgl_awal, $tgl_akhir];
$judul_pemilik = "SEMUA GURU";

if (!empty($guru_id)) {
    $sql .= " AND t.anggota_id = ?";
    $params[] = $guru_id;
    
    $stmt_guru = $pdo->prepare("SELECT nama_lengkap FROM anggota WHERE id = ?");
    $stmt_guru->execute([$guru_id]);
    $guru = $stmt_guru->fetch();
    if($guru) $judul_pemilik = strtoupper($guru['nama_lengkap']);
}

$sql .= " ORDER BY t.tanggal_titip DESC"; // Urutkan tanggal

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cetak Titipan</title>
    <style>
        body { font-family: 'Times New Roman', serif; padding: 20px; font-size: 11pt; }
        .header { text-align: center; border-bottom: 3px double black; margin-bottom: 20px; padding-bottom: 10px; }
        .header h2 { margin: 0; text-transform: uppercase; }
        .info-table { width: 100%; margin-bottom: 15px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid black; padding: 5px; font-size: 10pt; }
        table.data th { background: #f0f0f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        @media print {
            .no-print { display: none; }
            @page { size: A4 landscape; margin: 1cm; }
        }
    </style>
</head>
<body onload="window.print()">

    <button class="no-print" onclick="window.close()">Tutup</button>

    <div class="header">
        <h2>LAPORAN BARANG TITIPAN</h2>
        <p style="margin:5px 0 0 0;">Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
    </div>

    <div style="margin-bottom: 15px;">
        <strong>Pemilik Barang:</strong> <?= $judul_pemilik ?>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="10%">Tanggal</th>
                <th width="20%">Pemilik</th>
                <th>Nama Barang</th>
                <th width="8%">Awal</th>
                <th width="8%">Terjual</th>
                <th width="8%">Sisa</th>
                <th width="12%">Wajib Setor</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; $total_setor = 0;
            foreach($data as $row): 
                $sisa = $row['stok_awal'] - $row['stok_terjual'];
                $kewajiban = $row['stok_terjual'] * $row['harga_modal'];
                $total_setor += $kewajiban;
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center"><?= date('d/m/y', strtotime($row['tanggal_titip'])) ?></td>
                <td><?= $row['nama_lengkap'] ?></td>
                <td><?= $row['nama_barang'] ?></td>
                <td class="text-center"><?= $row['stok_awal'] ?></td>
                <td class="text-center"><?= $row['stok_terjual'] ?></td>
                <td class="text-center"><?= $sisa ?></td>
                <td class="text-right"><?= number_format($kewajiban) ?></td>
                <td class="text-center"><?= ucfirst($row['status_bayar']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="7" class="text-right">TOTAL WAJIB SETOR</th>
                <th class="text-right"><?= number_format($total_setor) ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px; float: right; text-align: center; width: 200px;">
        <p><?= date('d F Y') ?></p>
        <p>Petugas Koperasi</p>
        <br><br><br>
        <p>____________________</p>
    </div>
</body>
</html>