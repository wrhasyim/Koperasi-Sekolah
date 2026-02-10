<?php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php'; 

if(!isset($_SESSION['user'])){ die("Akses Ditolak."); }

if(isset($_POST['export_rapat'])){
    $bln_awal = $_POST['bulan_awal'];
    $bln_akhir = $_POST['bulan_akhir'];
    $tahun = $_POST['tahun'];
    
    // Filter Unit
    $inc_seragam = isset($_POST['inc_seragam']);
    $inc_eskul   = isset($_POST['inc_eskul']);

    // Nama File
    $nama_bln = [1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
    $periode_teks = $nama_bln[$bln_awal] . " s/d " . $nama_bln[$bln_akhir] . " " . $tahun;
    $filename = "Laporan_Kinerja_Koperasi_$tahun.xls";

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    // ==========================================
    // 1. HITUNG ARUS KAS (PEMASUKAN & PENGELUARAN)
    // ==========================================
    
    // Pemasukan Koperasi (Harian)
    $q_kop = $pdo->prepare("SELECT SUM(jumlah) as total FROM transaksi_kas 
        WHERE YEAR(tanggal)=? AND MONTH(tanggal) BETWEEN ? AND ? 
        AND kategori='penjualan_harian' AND arus='masuk'");
    $q_kop->execute([$tahun, $bln_awal, $bln_akhir]);
    $rev_koperasi = $q_kop->fetch()['total'] ?? 0;

    // Pemasukan Seragam
    $rev_seragam = 0;
    if($inc_seragam){
        $q_srg = $pdo->prepare("SELECT SUM(jumlah) as total FROM transaksi_kas 
            WHERE YEAR(tanggal)=? AND MONTH(tanggal) BETWEEN ? AND ? 
            AND kategori='penjualan_seragam' AND arus='masuk'");
        $q_srg->execute([$tahun, $bln_awal, $bln_akhir]);
        $rev_seragam = $q_srg->fetch()['total'] ?? 0;
    }

    // Pemasukan Eskul
    $rev_eskul = 0;
    if($inc_eskul){
        $q_esk = $pdo->prepare("SELECT SUM(jumlah) as total FROM transaksi_kas 
            WHERE YEAR(tanggal)=? AND MONTH(tanggal) BETWEEN ? AND ? 
            AND kategori='penjualan_eskul' AND arus='masuk'");
        $q_esk->execute([$tahun, $bln_awal, $bln_akhir]);
        $rev_eskul = $q_esk->fetch()['total'] ?? 0;
    }

    // Total Pemasukan
    $total_pendapatan = $rev_koperasi + $rev_seragam + $rev_eskul;

    // Pengeluaran (Dirinci agar jelas uangnya buat apa)
    $q_beban = $pdo->prepare("SELECT kategori, SUM(jumlah) as total FROM transaksi_kas 
        WHERE YEAR(tanggal)=? AND MONTH(tanggal) BETWEEN ? AND ? 
        AND arus='keluar' GROUP BY kategori");
    $q_beban->execute([$tahun, $bln_awal, $bln_akhir]);
    $list_beban = $q_beban->fetchAll(PDO::FETCH_KEY_PAIR);
    $total_beban = array_sum($list_beban);

    // Keuntungan Bersih (SHU)
    $shu_periode = $total_pendapatan - $total_beban;


    // ==========================================
    // 2. HITUNG KEKAYAAN (POSISI HARTA)
    // ==========================================
    $tgl_akhir_db = "$tahun-$bln_akhir-31";

    // Uang Tunai (Kas)
    $q_kas = $pdo->prepare("SELECT SUM(CASE WHEN arus='masuk' THEN jumlah ELSE -jumlah END) as saldo FROM transaksi_kas WHERE tanggal <= ?");
    $q_kas->execute([$tgl_akhir_db]);
    $uang_tunai = $q_kas->fetch()['saldo'] ?? 0;

    // Stok Barang (Aset Barang)
    $stok_kop = $pdo->query("SELECT SUM(stok * harga_modal) FROM stok_koperasi")->fetchColumn() ?? 0;
    $stok_srg = $inc_seragam ? ($pdo->query("SELECT SUM(stok * harga_modal) FROM stok_sekolah")->fetchColumn() ?? 0) : 0;
    $stok_esk = $inc_eskul   ? ($pdo->query("SELECT SUM(stok * harga_modal) FROM stok_eskul")->fetchColumn() ?? 0)   : 0;
    $total_stok = $stok_kop + $stok_srg + $stok_esk;

    // Piutang (Uang kita di orang lain)
    $q_piutang = $pdo->query("SELECT kategori_barang, SUM(sisa) as total FROM cicilan WHERE status='belum' GROUP BY kategori_barang")->fetchAll(PDO::FETCH_KEY_PAIR);
    $piutang_seragam = $inc_seragam ? ($q_piutang['seragam'] ?? 0) : 0;
    $piutang_eskul   = $inc_eskul   ? ($q_piutang['eskul'] ?? 0)   : 0;
    $total_piutang   = $piutang_seragam + $piutang_eskul;

    $total_harta = $uang_tunai + $total_stok + $total_piutang;


    // ==========================================
    // 3. KEWAJIBAN (HUTANG KOPERASI KE ORANG)
    // ==========================================
    
    // Tabungan Siswa/Guru (Wajib bisa diambil kapan saja)
    $q_simp = $pdo->query("SELECT SUM(CASE WHEN tipe_transaksi='setor' THEN jumlah ELSE 0 END) - SUM(CASE WHEN tipe_transaksi='tarik' THEN jumlah ELSE 0 END) as saldo FROM simpanan")->fetch();
    $total_tabungan = $q_simp['saldo'] ?? 0;

    // Titipan Guru (Barang Konsinyasi)
    $hutang_titipan = $pdo->query("SELECT SUM(stok_terjual * harga_modal) FROM titipan")->fetchColumn() ?? 0;

    $total_kewajiban = $total_tabungan + $hutang_titipan;

    // Modal Bersih (Harta - Kewajiban)
    $modal_bersih = $total_harta - $total_kewajiban;


    // ==========================================
    // 4. ANALISIS KESEHATAN (BAHASA MANUSIA)
    // ==========================================
    
    // Cek Keamanan Uang (Likuiditas)
    // Rumus: Apakah Uang Tunai cukup untuk bayar Tabungan jika ditarik semua?
    $status_uang = "";
    $warna_uang = "";
    
    if($total_kewajiban > 0){
        $rasio_aman = ($uang_tunai / $total_kewajiban) * 100;
        if($rasio_aman >= 100){
            $status_uang = "SANGAT AMAN. Uang tunai yang tersedia (Rp ".number_format($uang_tunai).") lebih dari cukup untuk mengembalikan seluruh tabungan anggota saat ini.";
            $warna_uang = "#c6efce"; // Hijau
        } elseif($rasio_aman >= 50) {
            $status_uang = "CUKUP AMAN. Sebagian uang sedang tertanam dalam bentuk Stok Barang. Jika anggota menarik tabungan serentak, perlu waktu menjual stok.";
            $warna_uang = "#ffeb9c"; // Kuning
        } else {
            $status_uang = "WASPADA. Uang tunai menipis. Segera tagih piutang atau jual stok untuk mengamankan dana anggota.";
            $warna_uang = "#ffc7ce"; // Merah
        }
    } else {
        $status_uang = "AMAN. Tidak ada tanggungan hutang/tabungan.";
        $warna_uang = "#c6efce";
    }

    // Ambil Data Penunggak
    $q_tunggakan = $pdo->query("SELECT nama_siswa, kelas, sisa FROM cicilan WHERE sisa > 0 ORDER BY sisa DESC LIMIT 5")->fetchAll();

    ?>
    
    <style>
        body { font-family: Arial, sans-serif; }
        .judul-besar { font-size: 20px; font-weight: bold; text-align: center; color: #1f4e78; }
        .periode { font-size: 12px; text-align: center; margin-bottom: 20px; color: #555; }
        
        .box-header { background-color: #1f4e78; color: white; font-weight: bold; padding: 8px; font-size: 14px; }
        .sub-header { background-color: #bdd7ee; font-weight: bold; padding-left: 20px; }
        
        .highlight-row { background-color: #ffffcc; font-weight: bold; border-top: 2px solid #000; }
        .duit { text-align: right; mso-number-format:"\#\,\#\#0"; }
        .center { text-align: center; }
        .ket { font-style: italic; font-size: 11px; color: #444; }
    </style>

    <div class="judul-besar">LAPORAN KINERJA & KESEHATAN KOPERASI</div>
    <div class="periode">Periode Laporan: <?= $periode_teks ?></div>
    <br>

    <table border="1" width="100%">
        <tr>
            <th colspan="2" class="box-header" style="background-color: #2e7d32;">I. RINGKASAN EKSEKUTIF (POIN PENTING)</th>
        </tr>
        <tr>
            <td width="70%" style="padding: 10px;">
                <b>1. Apakah Koperasi Untung?</b><br>
                <span class="ket">Selisih antara total pemasukan dikurangi pengeluaran operasional & belanja.</span>
            </td>
            <td width="30%" class="duit" style="font-size: 16px; font-weight: bold; color: <?= $shu_periode >=0 ? 'green':'red' ?>;">
                <?= $shu_periode >= 0 ? "UNTUNG ".formatRp($shu_periode) : "RUGI ".formatRp(abs($shu_periode)) ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 10px;">
                <b>2. Berapa Uang Tunai yang Kita Pegang?</b><br>
                <span class="ket">Uang real yang ada di laci kasir atau rekening bendahara saat ini.</span>
            </td>
            <td class="duit" style="font-size: 16px; font-weight: bold;">
                <?= formatRp($uang_tunai) ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 10px;">
                <b>3. Kekayaan Bersih Kita (Modal)?</b><br>
                <span class="ket">Total Harta dikurangi titipan orang lain (Tabungan Siswa/Guru).</span>
            </td>
            <td class="duit" style="font-size: 16px; font-weight: bold; color: blue;">
                <?= formatRp($modal_bersih) ?>
            </td>
        </tr>
    </table>

    <br><br>

    <table border="1" width="100%">
        <tr><th colspan="3" class="box-header">II. RINCIAN PEMASUKAN & PENGELUARAN</th></tr>
        
        <tr><td colspan="3" class="sub-header">A. SUMBER PEMASUKAN (UANG MASUK)</td></tr>
        <tr>
            <td width="5%" class="center">1.</td>
            <td width="60%">Penjualan Koperasi Harian (Jajan/ATK)</td>
            <td width="35%" class="duit"><?= formatRp($rev_koperasi) ?></td>
        </tr>
        <?php if($inc_seragam): ?>
        <tr>
            <td class="center">2.</td>
            <td>Penjualan Seragam Sekolah</td>
            <td class="duit"><?= formatRp($rev_seragam) ?></td>
        </tr>
        <?php endif; ?>
        <?php if($inc_eskul): ?>
        <tr>
            <td class="center">3.</td>
            <td>Penjualan Atribut Eskul</td>
            <td class="duit"><?= formatRp($rev_eskul) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="highlight-row">
            <td colspan="2" align="right">TOTAL UANG MASUK</td>
            <td class="duit"><?= formatRp($total_pendapatan) ?></td>
        </tr>

        <tr><td colspan="3"></td></tr>

        <tr><td colspan="3" class="sub-header">B. PENGGUNAAN DANA (PENGELUARAN)</td></tr>
        <?php 
        $no=1;
        $map_beban = [
            'belanja_stok' => 'Belanja Barang Dagangan (Kulakan)',
            'gaji_staff' => 'Gaji Penjaga / Staff',
            'honor_pengurus' => 'Honor Pengurus Koperasi',
            'dana_sosial' => 'Sumbangan / Dana Sosial',
            'operasional_lain' => 'Listrik, Air, & ATK Kantor'
        ];

        foreach($list_beban as $kat => $val): 
            $nama_beban = isset($map_beban[$kat]) ? $map_beban[$kat] : ucwords(str_replace('_', ' ', $kat));
        ?>
        <tr>
            <td class="center"><?= $no++ ?>.</td>
            <td><?= $nama_beban ?></td>
            <td class="duit"><?= formatRp($val) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="highlight-row">
            <td colspan="2" align="right">TOTAL PENGELUARAN</td>
            <td class="duit" style="color:red;"><?= formatRp($total_beban) ?></td>
        </tr>
    </table>

    <br><br>

    <table border="1" width="100%">
        <tr><th colspan="3" class="box-header">III. POSISI KEKAYAAN KOPERASI (HARTA & HUTANG)</th></tr>
        
        <tr><td colspan="3" class="sub-header">A. HARTA KITA (APA YANG KITA PUNYA)</td></tr>
        <tr>
            <td class="center">1.</td>
            <td><b>Uang Tunai</b> (Siap Pakai)</td>
            <td class="duit"><?= formatRp($uang_tunai) ?></td>
        </tr>
        <tr>
            <td class="center">2.</td>
            <td><b>Stok Barang</b> (Nilai Modal Barang di Gudang)</td>
            <td class="duit"><?= formatRp($total_stok) ?></td>
        </tr>
        <tr>
            <td class="center">3.</td>
            <td><b>Piutang</b> (Uang Kita yang Belum Dibayar Siswa)</td>
            <td class="duit"><?= formatRp($total_piutang) ?></td>
        </tr>
        <tr class="highlight-row">
            <td colspan="2" align="right">TOTAL NILAI HARTA</td>
            <td class="duit"><?= formatRp($total_harta) ?></td>
        </tr>

        <tr><td colspan="3"></td></tr>

        <tr><td colspan="3" class="sub-header">B. KEWAJIBAN (DANA MILIK ORANG LAIN)</td></tr>
        <tr>
            <td class="center">1.</td>
            <td><b>Tabungan Anggota</b> (Wajib dikembalikan jika diminta)</td>
            <td class="duit"><?= formatRp($total_tabungan) ?></td>
        </tr>
        <tr>
            <td class="center">2.</td>
            <td><b>Titipan Guru</b> (Barang konsinyasi yang laku)</td>
            <td class="duit"><?= formatRp($hutang_titipan) ?></td>
        </tr>
        <tr class="highlight-row">
            <td colspan="2" align="right">TOTAL DANA ORANG LAIN</td>
            <td class="duit"><?= formatRp($total_kewajiban) ?></td>
        </tr>
    </table>

    <br><br>

    <table border="1" width="100%">
        <tr><th colspan="2" class="box-header" style="background-color: #c00000;">IV. ANALISIS KESEHATAN (PENTING)</th></tr>
        
        <tr>
            <td width="30%" style="background-color: #f2f2f2; padding: 10px;">
                <b>1. KONDISI KEAMANAN DANA</b>
            </td>
            <td width="70%" style="background-color: <?= $warna_uang ?>; padding: 10px; font-weight: bold;">
                <?= $status_uang ?>
            </td>
        </tr>

        <tr>
            <td valign="top" style="padding: 10px; background-color: #f2f2f2;">
                <b>2. TAGIHAN MACET TERBESAR</b><br>
                <span class="ket">Mohon bantuan Wali Kelas untuk menagih.</span>
            </td>
            <td style="padding: 10px;">
                <?php if(!empty($q_tunggakan)): ?>
                    <table width="100%" border="0">
                    <?php foreach($q_tunggakan as $t): ?>
                        <tr>
                            <td>- <?= $t['nama_siswa'] ?> (<?= $t['kelas'] ?>)</td>
                            <td align="right" style="color:red; font-weight:bold;"><?= formatRp($t['sisa']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <i>Tidak ada tunggakan siswa.</i>
                <?php endif; ?>
            </td>
        </tr>

        <?php 
        // Hitung Alokasi Sederhana
        $dana_sekolah = $shu_periode * 0.40;
        $dana_pengurus = $shu_periode * 0.30;
        $dana_sosial = $shu_periode * 0.30;
        ?>
        <tr>
            <td valign="top" style="padding: 10px; background-color: #f2f2f2;">
                <b>3. ESTIMASI PEMBAGIAN KEUNTUNGAN</b><br>
                <span class="ket">Jika dibagi sekarang (Sesuai kesepakatan).</span>
            </td>
            <td style="padding: 10px;">
                <table width="100%" border="0">
                    <tr><td>Untuk Kas Sekolah/Cadangan (40%)</td><td align="right"><?= formatRp($dana_sekolah) ?></td></tr>
                    <tr><td>Jasa Pengurus & Karyawan (30%)</td><td align="right"><?= formatRp($dana_pengurus) ?></td></tr>
                    <tr><td>Dana Sosial & Kegiatan (30%)</td><td align="right"><?= formatRp($dana_sosial) ?></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <br><br>
    <table border="0" width="100%">
        <tr>
            <td align="center" width="30%">
                Mengetahui,<br>Kepala Sekolah
                <br><br><br><br>
                (___________________)
            </td>
            <td width="40%"></td>
            <td align="center" width="30%">
                <?= date('d F Y') ?><br>Bendahara Koperasi
                <br><br><br><br>
                (___________________)
            </td>
        </tr>
    </table>

    <?php
    exit;
} else {
    echo "Metode request tidak valid.";
}
?>