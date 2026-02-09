<?php
require_once '../../config/database.php';
require_once '../../config/functions.php';

session_start();
if(!isset($_SESSION['user'])) exit;

// Ambil Parameter
$type = isset($_GET['type']) ? $_GET['type'] : 'rekap';
$id_anggota = isset($_GET['id']) ? $_GET['id'] : null;

// --- LOGIKA DATA ---
if($type == 'detail' && $id_anggota){
    // 1. DATA ANGGOTA
    $stmt = $pdo->prepare("SELECT * FROM anggota WHERE id = ?");
    $stmt->execute([$id_anggota]);
    $member = $stmt->fetch();
    
    // 2. DATA TRANSAKSI
    $sql = "SELECT * FROM simpanan WHERE anggota_id = ? ORDER BY tanggal ASC, id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_anggota]);
    $transaksi = $stmt->fetchAll();
    
    $judul = "LAPORAN RINCIAN SIMPANAN ANGGOTA";
} else {
    // 1. DATA REKAP (SEMUA)
    $sql = "SELECT a.id, a.nama_lengkap, a.role, a.no_hp,
            SUM(CASE WHEN s.jenis_simpanan='pokok' THEN s.jumlah ELSE 0 END) as total_pokok,
            SUM(CASE WHEN s.jenis_simpanan='wajib' THEN s.jumlah ELSE 0 END) as total_wajib,
            SUM(CASE WHEN s.jenis_simpanan='hari_raya' AND s.tipe_transaksi='setor' THEN s.jumlah ELSE 0 END) as sihara_masuk,
            SUM(CASE WHEN s.jenis_simpanan='hari_raya' AND s.tipe_transaksi='tarik' THEN s.jumlah ELSE 0 END) as sihara_keluar
            FROM anggota a
            LEFT JOIN simpanan s ON a.id = s.anggota_id
            WHERE a.role NOT IN ('admin', 'staff') AND a.status_aktif = 1
            GROUP BY a.id
            ORDER BY a.nama_lengkap ASC";
    $data = $pdo->query($sql)->fetchAll();
    
    $judul = "LAPORAN REKAPITULASI SIMPANAN KOPERASI";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.4; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 3px double black; }
        .header h2, .header h3, .header p { margin: 0; }
        .header h2 { font-size: 16pt; font-weight: bold; text-transform: uppercase; }
        .header p { font-size: 10pt; font-style: italic; }
        
        .meta-info { margin-bottom: 15px; }
        .meta-table td { padding: 2px 10px 2px 0; }
        
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data th, table.data td { border: 1px solid black; padding: 6px 8px; font-size: 11pt; }
        table.data th { background-color: #f0f0f0; text-align: center; font-weight: bold; text-transform: uppercase; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        
        .footer { margin-top: 40px; width: 100%; }
        .ttd-box { width: 30%; float: right; text-align: center; }
        .ttd-space { height: 70px; }
        
        @media print {
            .no-print { display: none; }
            @page { size: A4; margin: 2cm; }
        }
    </style>
</head>
<body onload="window.print()">

    <button class="no-print" onclick="window.history.back()" style="padding: 10px 20px; margin-bottom: 20px; cursor: pointer;">&larr; Kembali</button>

    <div class="header">
        <h2>KOPERASI SEKOLAH UNGGULAN</h2>
        <p>Jl. Pendidikan No. 123, Kota Pelajar, Indonesia</p>
        <p>Telp: (021) 1234-5678 | Email: koperasi@sekolah.sch.id</p>
    </div>

    <h3 style="text-align: center; margin: 20px 0; text-transform: uppercase; text-decoration: underline;"><?= $judul ?></h3>

    <?php if($type == 'detail'): ?>
        <div class="meta-info">
            <table class="meta-table">
                <tr>
                    <td><strong>Nama Anggota</strong></td>
                    <td>: <?= htmlspecialchars($member['nama_lengkap']) ?></td>
                </tr>
                <tr>
                    <td><strong>ID Anggota</strong></td>
                    <td>: <?= sprintf("%04d", $member['id']) ?></td>
                </tr>
                <tr>
                    <td><strong>Tanggal Cetak</strong></td>
                    <td>: <?= date('d F Y') ?></td>
                </tr>
            </table>
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th width="15%">Tanggal</th>
                    <th width="15%">Jenis</th>
                    <th>Uraian Transaksi</th>
                    <th width="15%">Masuk (Debit)</th>
                    <th width="15%">Keluar (Kredit)</th>
                    <th width="15%">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $saldo = 0; $tot_masuk = 0; $tot_keluar = 0;
                foreach($transaksi as $row): 
                    $masuk = ($row['tipe_transaksi'] == 'setor') ? $row['jumlah'] : 0;
                    $keluar = ($row['tipe_transaksi'] == 'tarik') ? $row['jumlah'] : 0;
                    $saldo += ($masuk - $keluar);
                    $tot_masuk += $masuk; $tot_keluar += $keluar;
                ?>
                <tr>
                    <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td class="text-center"><?= strtoupper($row['jenis_simpanan']=='hari_raya' ? 'Sihara' : $row['jenis_simpanan']) ?></td>
                    <td><?= htmlspecialchars($row['keterangan']) ?></td>
                    <td class="text-right"><?= $masuk!=0 ? formatRp($masuk) : '-' ?></td>
                    <td class="text-right"><?= $keluar!=0 ? formatRp($keluar) : '-' ?></td>
                    <td class="text-right text-bold"><?= formatRp($saldo) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-center text-bold">TOTAL AKHIR</td>
                    <td class="text-right text-bold"><?= formatRp($tot_masuk) ?></td>
                    <td class="text-right text-bold"><?= formatRp($tot_keluar) ?></td>
                    <td class="text-right text-bold" style="background-color: #eee;"><?= formatRp($saldo) ?></td>
                </tr>
            </tfoot>
        </table>

    <?php else: ?>
        <p style="text-align: center; font-size: 10pt;">Periode Cetak: <?= date('d F Y') ?></p>
        <table class="data">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Anggota</th>
                    <th width="10%">Status</th>
                    <th width="18%">Simpanan Pokok</th>
                    <th width="18%">Simpanan Wajib</th>
                    <th width="18%">Saldo Sihara</th>
                    <th width="18%">Total Aset</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1; $g_pokok = 0; $g_wajib = 0; $g_sihara = 0; $g_total = 0;
                foreach($data as $row): 
                    $saldo_sihara = $row['sihara_masuk'] - $row['sihara_keluar'];
                    $total_aset = $row['total_pokok'] + $row['total_wajib'] + $saldo_sihara;
                    
                    $g_pokok += $row['total_pokok'];
                    $g_wajib += $row['total_wajib'];
                    $g_sihara += $saldo_sihara;
                    $g_total += $total_aset;
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                    <td class="text-center"><?= ucfirst($row['role']) ?></td>
                    <td class="text-right"><?= formatRp($row['total_pokok']) ?></td>
                    <td class="text-right"><?= formatRp($row['total_wajib']) ?></td>
                    <td class="text-right"><?= formatRp($saldo_sihara) ?></td>
                    <td class="text-right text-bold"><?= formatRp($total_aset) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-center text-bold" style="background-color: #eee;">GRAND TOTAL ASET</td>
                    <td class="text-right text-bold" style="background-color: #eee;"><?= formatRp($g_pokok) ?></td>
                    <td class="text-right text-bold" style="background-color: #eee;"><?= formatRp($g_wajib) ?></td>
                    <td class="text-right text-bold" style="background-color: #eee;"><?= formatRp($g_sihara) ?></td>
                    <td class="text-right text-bold" style="background-color: #ddd;"><?= formatRp($g_total) ?></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <div class="footer">
        <div class="ttd-box">
            <p><?= date('d F Y') ?></p>
            <p>Bendahara Koperasi</p>
            <div class="ttd-space"></div>
            <p class="text-bold">____________________</p>
        </div>
        <div style="clear: both;"></div>
    </div>

</body>
</html>