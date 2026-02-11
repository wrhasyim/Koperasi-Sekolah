<?php
// process/auth_logout.php

// Cek status session, hanya start jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_destroy();

// Redirect ke halaman login (path relatif terhadap index.php)
header("Location: login.php");
exit;
?>