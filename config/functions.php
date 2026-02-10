<?php
// Format Rupiah
function formatRp($angka){
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Format Tanggal Indo
function tglIndo($tanggal){
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $pecahkan = explode('-', $tanggal);
    // Validasi agar tidak error jika format salah
    if(count($pecahkan) < 3) return $tanggal;
    
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

// Cek Login
function cekLogin(){
    if(!isset($_SESSION['user'])){
        header("Location: login.php");
        exit;
    }
}

// FUNGSI CEK TUTUP BUKU
function cekStatusPeriode($pdo, $tanggal){
    $tgl = explode('-', $tanggal);
    $bulan = (int)$tgl[1];
    $tahun = (int)$tgl[0];

    $stmt = $pdo->prepare("SELECT id FROM tutup_buku WHERE bulan = ? AND tahun = ?");
    $stmt->execute([$bulan, $tahun]);
    
    if($stmt->rowCount() > 0){
        return true; // SUDAH DITUTUP (TERKUNCI)
    }
    return false; // MASIH BUKA
}

// --- [BARU] FUNGSI FLASH MESSAGE (PENGGANTI ALERT) ---
function setFlash($type, $message){
    // type: success, danger, warning, info
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function displayFlash(){
    if(isset($_SESSION['flash'])){
        $type = $_SESSION['flash']['type'];
        $msg  = $_SESSION['flash']['message'];
        unset($_SESSION['flash']); // Hapus setelah ditampilkan
        
        // Icon mapping
        $icon = 'info-circle';
        if($type == 'success') $icon = 'check-circle';
        if($type == 'danger') $icon = 'exclamation-triangle';
        
        echo "
        <div class='alert alert-$type alert-dismissible fade show shadow-sm border-0 mb-4' role='alert'>
            <div class='d-flex align-items-center'>
                <i class='fas fa-$icon me-2 fs-5'></i>
                <div>$msg</div>
            </div>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
        ";
    }
}
?>