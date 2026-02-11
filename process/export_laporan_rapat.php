<?php
// process/export_laporan_rapat.php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php'; 

// Cek Akses
if(!isset($_SESSION['user']) || !isset($_POST['export_rapat'])){ 
    die("Akses Ditolak."); 
}

// 1. AMBIL PENGATURAN SISTEM (PENTING UNTUK ALOKASI SHU)
$set = getAllPengaturan($pdo);

// 2. TANGKAP INPUT DARI FORM
$bln_awal  = (int)$_POST['bulan_awal'];
$bln_akhir = (int)$_POST['bulan_akhir'];
$tahun     = (int)$_POST['tahun'];
$tahun_lalu = $tahun - 1; // Untuk Komparasi
$berita_acara = $_POST['berita_acara'] ?: 'Tidak ada catatan khusus dalam rapat ini.';

// Filter Unit Usaha
$inc_seragam = isset($_POST['inc_seragam']);
$inc_eskul   = isset($_POST['inc_eskul']);

// Setup Header Excel
$nama_bln = [1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
$periode_teks = $nama_bln[$bln_awal] . " s.d " . $nama_bln[$bln_akhir] . " " . $tahun;
$filename = "Laporan_RAT_Koperasi_Tahun_$tahun.xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// ==========================================================================
// BAGIAN A: FUNGSI BANTUAN (HELPER)
// ==========================================================================

// Fungsi Hitung Pemasukan per Kategori & Tahun
function getRevenue($pdo, $thn, $b1, $b2, $kategori) {
    // PENTING: Kategori 'modal_awal' TIDAK diambil di sini karena kita sebutkan kategori spesifik
    $sql = "SELECT SUM(jumlah) FROM transaksi_kas 
            WHERE YEAR(tanggal) = ? AND MONTH(tanggal) BETWEEN ? AND ? 
            AND kategori = ? AND arus = 'masuk'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$thn, $b1, $b2, $kategori]);
    return $stmt->fetchColumn() ?: 0;
}

// Fungsi Hitung Pengeluaran (Beban) per Tahun
function getExpenseTotal($pdo, $thn, $b1, $b2) {
    // Ambil semua arus keluar
    $sql = "SELECT SUM(jumlah) FROM transaksi_kas 
            WHERE YEAR(tanggal) = ? AND MONTH(tanggal) BETWEEN ? AND ? 
            AND arus = 'keluar'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$thn, $b1, $b2]);
    return $stmt->fetchColumn() ?: 0;
}

// Fungsi Hitung Trend Persentase
function calcTrend($sekarang, $lalu) {
    if ($lalu <= 0) {
        return ($sekarang > 0) ? "100%" : "0%";
    }
    $persen = (($sekarang - $lalu) / $lalu) * 100;
    // Tambah tanda + jika positif
    $sign = ($persen > 0) ? "+" : "";
    return $sign . number_format($persen, 1) . "%";
}

// ==========================================================================
// BAGIAN B: PERHITUNGAN DATA KEUANGAN (LOGIKA INTI)
// ==========================================================================

// --- 1. PENDAPATAN UNIT USAHA (Current vs Last Year) ---
// a. Unit Toko (Harian + QRIS)
$rev_toko_ini = getRevenue($pdo, $tahun, $bln_awal, $bln_akhir, 'penjualan_harian') 
              + getRevenue($pdo, $tahun, $bln_awal, $bln_akhir, 'qris_masuk');
$rev_toko_lalu = getRevenue($pdo, $tahun_lalu, $bln_awal, $bln_akhir, 'penjualan_harian')
               + getRevenue($pdo, $tahun_lalu, $bln_awal, $bln_akhir, 'qris_masuk');

// b. Unit Seragam
$rev_srg_ini  = $inc_seragam ? getRevenue($pdo, $tahun, $bln_awal, $bln_akhir, 'penjualan_seragam') : 0;
$rev_srg_lalu = $inc_seragam ? getRevenue($pdo, $tahun_lalu, $bln_awal, $bln_akhir, 'penjualan_seragam') : 0;

// c. Unit Eskul
$rev_esk_ini  = $inc_eskul ? getRevenue($pdo, $tahun, $bln_awal, $bln_akhir, 'penjualan_eskul') : 0;
$rev_esk_lalu = $inc_eskul ? getRevenue($pdo, $tahun_lalu, $bln_awal, $bln_akhir, 'penjualan_eskul') : 0;

// TOTAL PENDAPATAN
$total_rev_ini  = $rev_toko_ini + $rev_srg_ini + $rev_esk_ini;
$total_rev_lalu = $rev_toko_lalu + $rev_srg_lalu + $rev_esk_lalu;


// --- 2. BEBAN OPERASIONAL (PENGELUARAN) ---
// Rincian Beban Tahun Ini (Untuk Tabel Detail)
$q_rincian = $pdo->prepare("SELECT kategori, SUM(jumlah) as total FROM transaksi_kas 
                            WHERE YEAR(tanggal)=? AND MONTH(tanggal) BETWEEN ? AND ? 
                            AND arus='keluar' GROUP BY kategori ORDER BY total DESC");
$q_rincian->execute([$tahun, $bln_awal, $bln_akhir]);
$detail_beban = $q_rincian->fetchAll(PDO::FETCH_ASSOC);

// Total Beban
$total_beban_ini  = getExpenseTotal($pdo, $tahun, $bln_awal, $bln_akhir);
$total_beban_lalu = getExpenseTotal($pdo, $tahun_lalu, $bln_awal, $bln_akhir);


// --- 3. SISA HASIL USAHA (LABA BERSIH) ---
$shu_ini  = $total_rev_ini - $total_beban_ini;
$shu_lalu = $total_rev_lalu - $total_beban_lalu;


// --- 4. NERACA KEKAYAAN (POSISI HARTA & HUTANG) ---
// A. HARTA (AKTIVA)
// Uang Kas Fisik (Semua kategori masuk - keluar sampai hari ini)
$kas_fisik = $pdo->query("SELECT SUM(CASE WHEN arus='masuk' THEN jumlah ELSE -jumlah END) FROM transaksi_kas")->fetchColumn() ?: 0;

// Stok Barang (Nilai Aset)
$stok_kop = $pdo->query("SELECT SUM(stok * harga_modal) FROM stok_koperasi")->fetchColumn() ?: 0;
$stok_srg = $inc_seragam ? ($pdo->query("SELECT SUM(stok * harga_modal) FROM stok_sekolah")->fetchColumn() ?: 0) : 0;
$stok_esk = $inc_eskul   ? ($pdo->query("SELECT SUM(stok * harga_modal) FROM stok_eskul")->fetchColumn() ?: 0)   : 0;
$total_stok = $stok_kop + $stok_srg + $stok_esk;

// Piutang (Cicilan Siswa Belum Lunas)
$q_piutang = $pdo->query("SELECT SUM(sisa) FROM cicilan WHERE status='belum'")->fetchColumn() ?: 0;

$total_harta = $kas_fisik + $total_stok + $q_piutang;

// B. KEWAJIBAN (PASIVA)
// Tabungan Anggota (Saldo Setor - Tarik)
$total_tabungan = $pdo->query("SELECT SUM(CASE WHEN tipe_transaksi='setor' THEN jumlah ELSE -jumlah END) FROM simpanan")->fetchColumn() ?: 0;

// Hutang Titipan (Konsinyasi laku tapi belum disetor ke Guru)
$hutang_titipan = $pdo->query("SELECT SUM(stok_terjual * harga_modal) FROM titipan WHERE status_bayar='belum'")->fetchColumn() ?: 0;

$total_kewajiban = $total_tabungan + $hutang_titipan;

// C. MODAL BERSIH (EKUITAS)
$modal_bersih = $total_harta - $total_kewajiban;


// --- 5. ANALISIS DATA LAINNYA ---
// Top 5 Penunggak (Bad Debt)
$macet = $pdo->query("SELECT nama_siswa, kelas, sisa FROM cicilan WHERE sisa > 0 ORDER BY sisa DESC LIMIT 5")->fetchAll();

// Status Likuiditas (Kesehatan Kas)
$rasio_kas = ($total_kewajiban > 0) ? ($kas_fisik / $total_kewajiban) * 100 : 100;
if($rasio_kas >= 100) { $status_dana = "SANGAT SEHAT (Dana Kas Cukup Bayar Semua Kewajiban)"; $color_dana = "#c6efce"; }
elseif($rasio_kas >= 50){ $status_dana = "CUKUP (Sebagian Dana Tertanam di Stok/Piutang)"; $color_dana = "#ffeb9c"; }
else { $status_dana = "WASPADA (Kas Menipis, Segera Tagih Piutang)"; $color_dana = "#ffc7ce"; }

?>

<style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    .title { font-size: 16px; font-weight: bold; text-align: center; }
    .subtitle { font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 20px; }
    
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #000; padding: 5px 8px; }
    
    .bg-header { background-color: #1f4e78; color: white; font-weight: bold; text-align: center; }
    .bg-sub { background-color: #bdd7ee; font-weight: bold; }
    .bg-total { background-color: #ffffcc; font-weight: bold; }
    
    .duit { text-align: right; mso-number-format:"\#\,\#\#0"; }
    .center { text-align: center; }
    .text-red { color: red; }
    .text-blue { color: blue; }
    .text-green { color: green; }
</style>

<div class="title">LAPORAN PERTANGGUNGJAWABAN PENGURUS (RAT)</div>
<div class="title">KOPERASI SEKOLAH DIGITAL</div>
<div class="subtitle">Periode Laporan: <?= $periode_teks ?></div>

<table>
    <tr class="bg-header"><th colspan="5">I. RINGKASAN KINERJA (YEAR-ON-YEAR)</th></tr>
    <tr class="bg-sub">
        <td colspan="2">INDIKATOR UTAMA</td>
        <td class="center">TAHUN <?= $tahun_lalu ?></td>
        <td class="center">TAHUN <?= $tahun ?></td>
        <td class="center">PERTUMBUHAN</td>
    </tr>
    <tr>
        <td colspan="2">Total Pendapatan (Omzet)</td>
        <td class="duit"><?= $total_rev_lalu ?></td>
        <td class="duit"><?= $total_rev_ini ?></td>
        <td class="center"><?= calcTrend($total_rev_ini, $total_rev_lalu) ?></td>
    </tr>
    <tr>
        <td colspan="2">Sisa Hasil Usaha (Laba Bersih)</td>
        <td class="duit"><?= $shu_lalu ?></td>
        <td class="duit font-weight-bold"><?= $shu_ini ?></td>
        <td class="center fw-bold"><?= calcTrend($shu_ini, $shu_lalu) ?></td>
    </tr>
    <tr>
        <td colspan="2">Total Aset (Kekayaan)</td>
        <td class="center">-</td>
        <td class="duit text-blue"><?= $total_harta ?></td>
        <td class="center">-</td>
    </tr>
</table>

<table>
    <tr class="bg-header"><th colspan="5">II. RINCIAN PENDAPATAN & BEBAN</th></tr>
    
    <tr class="bg-sub"><td colspan="5">A. PENDAPATAN UNIT USAHA</td></tr>
    <tr>
        <td width="5%" class="center">1.</td>
        <td width="45%">Unit Toko (Harian & QRIS)</td>
        <td width="15%" class="duit"><?= $rev_toko_lalu ?></td>
        <td width="15%" class="duit"><?= $rev_toko_ini ?></td>
        <td width="20%" class="center"><?= calcTrend($rev_toko_ini, $rev_toko_lalu) ?></td>
    </tr>
    <?php if($inc_seragam): ?>
    <tr>
        <td class="center">2.</td>
        <td>Unit Seragam Sekolah</td>
        <td class="duit"><?= $rev_srg_lalu ?></td>
        <td class="duit"><?= $rev_srg_ini ?></td>
        <td class="center"><?= calcTrend($rev_srg_ini, $rev_srg_lalu) ?></td>
    </tr>
    <?php endif; ?>
    <?php if($inc_eskul): ?>
    <tr>
        <td class="center">3.</td>
        <td>Unit Atribut Eskul</td>
        <td class="duit"><?= $rev_esk_lalu ?></td>
        <td class="duit"><?= $rev_esk_ini ?></td>
        <td class="center"><?= calcTrend($rev_esk_ini, $rev_esk_lalu) ?></td>
    </tr>
    <?php endif; ?>
    <tr class="bg-total">
        <td colspan="2" align="right">TOTAL PENDAPATAN</td>
        <td class="duit"><?= $total_rev_lalu ?></td>
        <td class="duit"><?= $total_rev_ini ?></td>
        <td class="center"><?= calcTrend($total_rev_ini, $total_rev_lalu) ?></td>
    </tr>

    <tr><td colspan="5"></td></tr>

    <tr class="bg-sub"><td colspan="5">B. RINCIAN BEBAN OPERASIONAL (TAHUN <?= $tahun ?>)</td></tr>
    <?php 
    $no=1; 
    // Mapping nama beban agar lebih rapi
    $map_beban = [
        'belanja_stok' => 'Belanja Stok Barang (HPP)',
        'gaji_staff' => 'Honor/Gaji Karyawan',
        'biaya_operasional' => 'Listrik, Air & Internet',
        'dana_sosial' => 'Sumbangan Sosial/Kematian',
        'biaya_atk' => 'Perlengkapan Kantor (ATK)'
    ];

    foreach($detail_beban as $row): 
        $nama = isset($map_beban[$row['kategori']]) ? $map_beban[$row['kategori']] : ucwords(str_replace('_',' ',$row['kategori']));
    ?>
    <tr>
        <td class="center"><?= $no++ ?>.</td>
        <td colspan="2"><?= $nama ?></td>
        <td class="duit"><?= $row['total'] ?></td>
        <td class="center">-</td>
    </tr>
    <?php endforeach; ?>
    <tr class="bg-total">
        <td colspan="3" align="right">TOTAL BEBAN</td>
        <td class="duit text-red"><?= $total_beban_ini ?></td>
        <td class="center"><?= calcTrend($total_beban_ini, $total_beban_lalu) ?></td>
    </tr>

    <tr><td colspan="5"></td></tr>
    <tr style="background-color: #2e7d32; color: white; font-weight: bold;">
        <td colspan="3" align="right">SISA HASIL USAHA (SHU) BERSIH</td>
        <td class="duit"><?= $shu_ini ?></td>
        <td class="center"><?= calcTrend($shu_ini, $shu_lalu) ?></td>
    </tr>
</table>

<table>
    <tr class="bg-header"><th colspan="4">III. NERACA & KESEHATAN KEUANGAN (Per <?= date('d-m-Y') ?>)</th></tr>
    
    <tr>
        <td colspan="2" class="bg-sub center">AKTIVA (HARTA)</td>
        <td colspan="2" class="bg-sub center">PASIVA (KEWAJIBAN & MODAL)</td>
    </tr>
    <tr>
        <td>1. Kas Tunai & Bank</td>
        <td class="duit"><?= $kas_fisik ?></td>
        <td>1. Tabungan Anggota</td>
        <td class="duit"><?= $total_tabungan ?></td>
    </tr>
    <tr>
        <td>2. Persediaan Barang (Stok)</td>
        <td class="duit"><?= $total_stok ?></td>
        <td>2. Hutang Titipan (Konsinyasi)</td>
        <td class="duit"><?= $hutang_titipan ?></td>
    </tr>
    <tr>
        <td>3. Piutang Usaha (Cicilan)</td>
        <td class="duit"><?= $q_piutang ?></td>
        <td class="text-blue fw-bold">3. Modal Bersih (Ekuitas)</td>
        <td class="duit text-blue fw-bold"><?= $modal_bersih ?></td>
    </tr>
    <tr class="bg-total">
        <td>TOTAL AKTIVA</td>
        <td class="duit"><?= $total_harta ?></td>
        <td>TOTAL PASIVA</td>
        <td class="duit"><?= $total_kewajiban + $modal_bersih ?></td>
    </tr>
</table>

<table>
    <tr class="bg-header"><th colspan="3">IV. ANALISIS MANAJEMEN & ALOKASI PROFIT</th></tr>
    
    <tr>
        <td width="30%" class="bg-sub">1. KESEHATAN KAS (LIKUIDITAS)</td>
        <td colspan="2" style="background-color: <?= $color_dana ?>; font-weight: bold; text-align: center;">
            <?= $status_dana ?> (Rasio: <?= number_format($rasio_kas, 1) ?>%)
        </td>
    </tr>
    
    <tr>
        <td class="bg-sub" valign="top">2. DATA TUNGGAKAN (Top 5)</td>
        <td colspan="2">
            <?php if($macet): ?>
                <ul style="margin: 0; padding-left: 20px;">
                <?php foreach($macet as $m): ?>
                    <li><?= $m['nama_siswa'] ?> (<?= $m['kelas'] ?>): <span class="text-red fw-bold">Rp <?= number_format($m['sisa']) ?></span></li>
                <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <i>Tidak ada tunggakan pembayaran.</i>
            <?php endif; ?>
        </td>
    </tr>

    <tr>
        <td class="bg-sub" valign="top">3. PEMBAGIAN SHU (Estimasi)</td>
        <td colspan="2">
            <table style="width: 100%; border: none; margin: 0;">
                <tr>
                    <td style="border:none;">- Cadangan Kas (<?= $set['persen_kas'] ?>%)</td>
                    <td style="border:none;" class="duit"><?= $shu_ini * ($set['persen_kas']/100) ?></td>
                </tr>
                <tr>
                    <td style="border:none;">- Jasa Pengurus (<?= $set['persen_pengurus'] ?>%)</td>
                    <td style="border:none;" class="duit"><?= $shu_ini * ($set['persen_pengurus']/100) ?></td>
                </tr>
                <tr>
                    <td style="border:none;">- Jasa Anggota/Staff (<?= $set['persen_staff'] ?>%)</td>
                    <td style="border:none;" class="duit"><?= $shu_ini * ($set['persen_staff']/100) ?></td>
                </tr>
                <tr>
                    <td style="border:none;">- Dana Sosial (<?= $set['persen_dansos'] ?>%)</td>
                    <td style="border:none;" class="duit"><?= $shu_ini * ($set['persen_dansos']/100) ?></td>
                </tr>
                <tr>
                    <td style="border:none;">- Jasa Pembina (<?= $set['persen_pembina'] ?>%)</td>
                    <td style="border:none;" class="duit"><?= $shu_ini * ($set['persen_pembina']/100) ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table>
    <tr class="bg-header"><th style="background-color: #ed7d31; text-align: left;">V. CATATAN & KEPUTUSAN RAPAT (BERITA ACARA)</th></tr>
    <tr>
        <td style="height: 150px; vertical-align: top; padding: 15px; font-style: italic;">
            <?= nl2br(htmlspecialchars($berita_acara)) ?>
        </td>
    </tr>
</table>

<br>

<table style="border: none; margin-top: 30px;">
    <tr>
        <td width="33%" align="center" style="border: none;">
            Mengetahui,<br>Kepala Sekolah
            <br><br><br><br><br>
            ( _______________________ )
        </td>
        <td width="33%" align="center" style="border: none;">
            Mengesahkan,<br><b>Pengawas Koperasi</b>
            <br><br><br><br><br>
            ( _______________________ )
        </td>
        <td width="33%" align="center" style="border: none;">
            <?= date('d F Y') ?><br>Bendahara Koperasi
            <br><br><br><br><br>
            ( _______________________ )
        </td>
    </tr>
</table>

<?php
exit; // Selesai

?>