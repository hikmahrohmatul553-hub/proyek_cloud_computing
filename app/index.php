<?php
echo "<h1>Selamat Datang di Website Cloud Computing!</h1>";
echo "<p>Status Server: Aktif dan Berjalan di Docker Container</p>";

// Cek Koneksi ke Database Proxy
$host = 'db-proxy';
$user = 'user_app';
$pass = 'password_app';
$db   = 'db_proyek';

$conn = new mysqli($host, $user, $pass, $db, 6033);

if ($conn->connect_error) {
    echo "<p style='color:red;'>Status Database: Gagal Terhubung (" . $conn->connect_error . ")</p>";
} else {
    echo "<p style='color:green;'>Status Database: Berhasil Terhubung via DB Proxy!</p>";
}
?>
