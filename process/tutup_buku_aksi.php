<?php
// process/tutup_buku_aksi.php
session_start();
require_once '../config/database.php';
require_once '../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bulan = (int)$_POST['bulan'];
    $tahun = (int)$_POST['tahun'];
    $user_id = $_SESSION['user']['id'];

    // 1. VALIDASI: Tutup buku bulan berjalan hanya bisa dilakukan mulai tanggal 25
    $tgl_sekarang = (int)date('d');
    if ($tgl_sekarang < 25 && $bulan == (int)date('m') && $tahun == (int)date('Y')) {
        setFlash('danger', 'Gagal: Tutup buku bulan berjalan hanya bisa dilakukan mulai tanggal 25 ke atas.');
        header("Location: ../index.php?page=utilitas/riwayat_tutup_buku");
        exit;
    }

    try {
        // 2. VALIDASI: Cek apakah periode ini sudah pernah ditutup
        $cek = $pdo->prepare("SELECT id FROM tutup_buku WHERE bulan = ? AND tahun = ?");
        $cek->execute([$bulan, $tahun]);
        
        if ($cek->rowCount() > 0) {
            setFlash('danger', "Gagal: Periode tersebut sudah pernah ditutup sebelumnya.");
            header("Location: ../index.php?page=utilitas/riwayat_tutup_buku");
            exit;
        }

        $pdo->beginTransaction();

        // 3. HITUNG SALDO UNTUK KAS FISIK (Semua uang masuk termasuk modal awal)
        $st_masuk = $pdo->prepare("SELECT SUM(jumlah) FROM transaksi_kas WHERE arus = 'masuk' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
        $st_masuk->execute([$bulan, $tahun]);
        $total_masuk_semua = $st_masuk->fetchColumn() ?: 0;

        // 4. HITUNG LABA MURNI (Kecualikan kategori 'modal_awal')
        $st_laba = $pdo->prepare("SELECT SUM(jumlah) FROM transaksi_kas WHERE arus = 'masuk' AND kategori != 'modal_awal' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
        $st_laba->execute([$bulan, $tahun]);
        $total_pendapatan_murni = $st_laba->fetchColumn() ?: 0;

        $st_keluar = $pdo->prepare("SELECT SUM(jumlah) FROM transaksi_kas WHERE arus = 'keluar' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
        $st_keluar->execute([$bulan, $tahun]);
        $total_keluar = $st_keluar->fetchColumn() ?: 0;

        $saldo_akhir = $total_masuk_semua - $total_keluar;
        $laba_bersih = $total_pendapatan_murni - $total_keluar;

        // 5. SIMPAN DATA TUTUP BUKU
        $sql = "INSERT INTO tutup_buku (bulan, tahun, total_masuk, total_keluar, saldo_akhir, user_id) VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$bulan, $tahun, $total_masuk_semua, $total_keluar, $saldo_akhir, $user_id]);

        // 6. CATAT LOG
        catatLog($pdo, $user_id, 'Tutup Buku', "Tutup buku $bulan-$tahun. Saldo: " . formatRp($saldo_akhir) . " | Laba Murni: " . formatRp($laba_bersih));

        $pdo->commit();
        setFlash('success', "Berhasil: Tutup buku selesai. Laba murni periode ini: " . formatRp($laba_bersih));

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', 'Error Sistem: ' . $e->getMessage());
    }
}

header("Location: ../index.php?page=utilitas/riwayat_tutup_buku");
exit;