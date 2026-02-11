<?php
session_start();
require_once '../config/database.php';

// Proteksi: Hanya Admin
if ($_SESSION['user']['role'] !== 'admin') {
    die("Akses ditolak!");
}

$host = "localhost"; // Sesuaikan jika berbeda
$user = "root";      // Sesuaikan
$pass = "";          // Sesuaikan
$db   = "db_koperasi_sekolah";

$tables = [];
$result = $pdo->query("SHOW TABLES");
while ($row = $result->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

$return = "-- BACKUP DATABASE KOPERASI SEKOLAH --\n";
$return .= "-- Tanggal: " . date('d-M-Y H:i:s') . "\n\n";

foreach ($tables as $table) {
    $result = $pdo->query("SELECT * FROM $table");
    $num_fields = $result->columnCount();

    $return .= "DROP TABLE IF EXISTS $table;";
    $row2 = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
    $return .= "\n\n" . $row2[1] . ";\n\n";

    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $return .= "INSERT INTO $table VALUES(";
        for ($j = 0; $j < $num_fields; $j++) {
            $row[$j] = addslashes($row[$j]);
            $row[$j] = str_replace("\n", "\\n", $row[$j]);
            if (isset($row[$j])) { $return .= '"' . $row[$j] . '"'; } else { $return .= '""'; }
            if ($j < ($num_fields - 1)) { $return .= ','; }
        }
        $return .= ");\n";
    }
    $return .= "\n\n\n";
}

// HEADER DOWNLOAD (Diproses di file bersih tanpa HTML)
$filename = 'backup_koperasi_' . date('Y-m-d_His') . '.sql';
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"" . $filename . "\"");
echo $return;
exit;