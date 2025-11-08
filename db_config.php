<?php
// db_config.php - File Konfigurasi Database

// Pengaturan Server
$host = "localhost"; 
$user = "root";      
// Dikosongkan karena Anda sudah mengatur user root MySQL tanpa password.
$pass = ""; 
// PASTIKAN NAMA DATABASE SESUAI DENGAN YANG DI IMPORT
$db = "inventaris_db"; 

// Buat koneksi MySQLi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (mysqli_connect_errno()) {
    // Jika koneksi gagal (misalnya MySQL server mati), tampilkan pesan error
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Jika koneksi berhasil, variabel $koneksi siap digunakan di semua file PHP
?>