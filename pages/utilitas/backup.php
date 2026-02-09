<?php
// Pastikan hanya ADMIN yang bisa akses
if($_SESSION['user']['role'] != 'admin'){
    echo "<script>alert('Akses Ditolak!'); window.location='dashboard';</script>";
    exit;
}

if(isset($_POST['backup_now'])){
    // Konfigurasi Database
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $name = 'db_koperasi_sekolah';
    
    // Nama File Backup
    $filename = $name . "_" . date("Y-m-d_H-i-s") . ".sql";
    
    // Header untuk Download File
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // 1. DUMP STRUKTUR & DATA
    $return = "-- BACKUP DATABASE KOPERASI SEKOLAH\n";
    $return .= "-- Tanggal: " . date("d-M-Y H:i:s") . "\n\n";
    
    $pdo_backup = new PDO("mysql:host=$host;dbname=$name", $user, $pass);
    $tables = array();
    $result = $pdo_backup->query('SHOW TABLES');
    while($row = $result->fetch(PDO::FETCH_NUM)){ $tables[] = $row[0]; }
    
    foreach($tables as $table){
        $result = $pdo_backup->query('SELECT * FROM '.$table);
        $num_fields = $result->columnCount();
        
        $return .= "DROP TABLE IF EXISTS ".$table.";";
        $row2 = $pdo_backup->query('SHOW CREATE TABLE '.$table)->fetch(PDO::FETCH_NUM);
        $return .= "\n\n".$row2[1].";\n\n";
        
        for ($i = 0; $i < $num_fields; $i++){
            while($row = $result->fetch(PDO::FETCH_NUM)){
                $return.= 'INSERT INTO '.$table.' VALUES(';
                for($j=0; $j<$num_fields; $j++){
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n","\\n",$row[$j]);
                    if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                    if ($j<($num_fields-1)) { $return.= ','; }
                }
                $return.= ");\n";
            }
        }
        $return.="\n\n\n";
    }
    
    echo $return;
    exit;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Backup Database</h1>
</div>

<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i> <strong>Penting:</strong> Lakukan backup data secara berkala (misal: seminggu sekali) dan simpan file SQL di Google Drive atau Flashdisk.
</div>

<div class="card shadow-sm">
    <div class="card-body text-center p-5">
        <i class="fas fa-database fa-5x text-primary mb-4"></i>
        <h4>Download Data Koperasi</h4>
        <p class="text-muted mb-4">Semua data anggota, simpanan, kas, dan stok akan didownload dalam format .SQL</p>
        
        <form method="POST">
            <button type="submit" name="backup_now" class="btn btn-lg btn-primary">
                <i class="fas fa-download me-2"></i> Download Backup Sekarang
            </button>
        </form>
    </div>
</div>