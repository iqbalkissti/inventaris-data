<?php
// delete_inventaris.php - Proses Penghapusan Data Inventaris
session_start();
include 'db_config.php'; // Sertakan file koneksi database

// 1. Pengecekan Keamanan (Wajib)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php'); // Arahkan ke halaman login jika belum login
    exit();
}

// 2. Mendapatkan ID Barang yang akan dihapus
$id_barang = $_GET['id'] ?? null;

if (empty($id_barang) || !is_numeric($id_barang)) {
    $_SESSION['notif'] = "🚫 ID barang tidak valid.";
    header('Location: data_inventaris.php');
    exit();
}

// 3. Proses Penghapusan Data
// Gunakan Prepared Statement untuk keamanan dari SQL Injection
$query_delete = "DELETE FROM barang WHERE id_barang = ?";
$stmt = mysqli_prepare($koneksi, $query_delete);

if ($stmt) {
    // Bind parameter (i = integer)
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    
    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Sukses
        $_SESSION['notif'] = "✅ Data inventaris dengan ID **" . htmlspecialchars($id_barang) . "** berhasil dihapus.";
    } else {
        // Gagal Eksekusi
        $_SESSION['notif'] = "❌ Gagal menghapus data inventaris. Error: " . mysqli_error($koneksi);
    }

    mysqli_stmt_close($stmt);
} else {
    // Gagal Prepare Statement
    $_SESSION['notif'] = "❌ Gagal menyiapkan proses penghapusan data.";
}

mysqli_close($koneksi);

// 4. Redirect kembali ke halaman tampil data
header('Location: data_inventaris.php');
exit();
?>