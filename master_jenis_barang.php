<?php
// master_jenis_barang.php - Mengelola Data Jenis Barang (Master)
session_start();
include 'db_config.php';

// Pengecekan Keamanan dan Level
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['level'] !== 'ADMIN' && $_SESSION['level'] !== 'SUPERADMIN')) {
    header('Location: dashboard.php'); 
    exit();
}
$level = htmlspecialchars($_SESSION['level'] ?? 'USER');

$message = '';
$notification = '';

// Ambil notifikasi dari session
if (isset($_SESSION['notif'])) {
    $notification = '<div class="notification success">' . $_SESSION['notif'] . '</div>';
    unset($_SESSION['notif']);
}

// --- LOGIKA CRUD ---

// A. Tambah Data (Create)
if (isset($_POST['add_jenis'])) {
    $nama_jenis = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['nama_jenis'])));
    
    if (!empty($nama_jenis)) {
        // MENGGUNAKAN NAMA TABEL: 'jenis_barang' (Sesuai database Anda)
        $check = mysqli_query($koneksi, "SELECT id_jenis FROM jenis_barang WHERE nama_jenis = '$nama_jenis'");
        if (mysqli_num_rows($check) > 0) {
            $message = '<div class="notification error">❌ Nama Jenis Barang sudah ada!</div>';
        } else {
            $query = "INSERT INTO jenis_barang (nama_jenis) VALUES ('$nama_jenis')";
            if (mysqli_query($koneksi, $query)) {
                $_SESSION['notif'] = '✅ Jenis Barang **' . htmlspecialchars($nama_jenis) . '** berhasil ditambahkan!';
            } else {
                $message = '<div class="notification error">❌ Gagal menambahkan data: ' . mysqli_error($koneksi) . '</div>';
            }
        }
    } else {
        $message = '<div class="notification error">Nama Jenis Barang tidak boleh kosong.</div>';
    }
    header('Location: master_jenis_barang.php');
    exit();
}

// B. Hapus Data (Delete)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_hapus = (int)$_GET['id'];
    
    // Pengecekan Keterkaitan: Cek apakah ada Barang yang masih terhubung ke Jenis ini
    $check_dependency = mysqli_query($koneksi, "SELECT id_barang FROM barang WHERE id_jenis = $id_hapus");
    
    if (mysqli_num_rows($check_dependency) > 0) {
        $_SESSION['notif'] = '⚠️ Gagal menghapus. Masih ada ' . mysqli_num_rows($check_dependency) . ' Barang yang terkait dengan Jenis ini.';
    } else {
        // MENGGUNAKAN NAMA TABEL: 'jenis_barang'
        $query = "DELETE FROM jenis_barang WHERE id_jenis = $id_hapus";
        if (mysqli_query($koneksi, $query)) {
            $_SESSION['notif'] = '🗑️ Jenis Barang berhasil dihapus!';
        } else {
            $message = '❌ Gagal menghapus data: ' . mysqli_error($koneksi);
        }
    }
    header('Location: master_jenis_barang.php');
    exit();
}

// --- AMBIL DATA JENIS BARANG UNTUK DITAMPILKAN ---
$jenis_list = [];
// MENGGUNAKAN NAMA TABEL: 'jenis_barang'
$query_select = "SELECT id_jenis, nama_jenis FROM jenis_barang ORDER BY nama_jenis ASC";
$result_select = mysqli_query($koneksi, $query_select);

if ($result_select) {
    $jenis_list = mysqli_fetch_all($result_select, MYSQLI_ASSOC);
} else {
     $message = '<div class="notification error">❌ Gagal mengambil data: Tabel **jenis_barang** belum ada atau terjadi kesalahan database: ' . mysqli_error($koneksi) . '</div>';
}

mysqli_close($koneksi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Jenis Barang</title>
    <style>
        /* CSS LENGKAP UNTUK TAMPILAN */
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f0f0f0; }
        .main-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: #000; color: white; padding: 20px 0; box-shadow: 2px 0 5px rgba(0,0,0,0.5); }
        .sidebar h2 { text-align: left; color: #fff; margin: 0 0 5px 20px; font-size: 24px; font-weight: bold; text-transform: uppercase; }
        .menu-item { display: flex; align-items: center; padding: 15px 20px; color: #fff; text-decoration: none; font-size: 16px; border-left: 5px solid transparent; transition: background-color 0.3s, border-left-color 0.3s; }
        .menu-item:hover { background-color: #333; border-left-color: #ffc107; }
        .menu-item.active { background-color: #1a1a1a; border-left-color: #007bff; }
        .submenu { background-color: #1a1a1a; padding-left: 35px; display: block; }
        .submenu a { padding: 8px 20px; display: block; color: #ccc; text-decoration: none; font-size: 14px; border-left: 5px solid transparent; }
        .submenu a.active-sub { color: #fff; font-weight: bold; background-color: #333; }
        .content-area { flex-grow: 1; padding: 40px; background-color: #fff; }
        .header-pt { font-size: 30px; color: #495057; margin-bottom: 30px; }
        .header-pt span { color: #007bff; }
        .add-form-container { background-color: #f9f9f9; padding: 20px; border-radius: 6px; margin-bottom: 30px; }
        .add-form-container input[type="text"] { padding: 10px; width: 300px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px; }
        .btn-add { padding: 10px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #e9ecef; }
        .action-link { margin-right: 10px; text-decoration: none; font-weight: bold; }
        .edit { color: #ffc107; }
        .delete { color: #dc3545; }
        .notification { padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid; }
        .success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .warning { background-color: #fff3cd; color: #856404; border-color: #ffeeba; }
    </style>
</head>
<body>

    <div class="main-layout">
        
        <div class="sidebar">
            <h2>INVENTARIS</h2>
            <a href="dashboard.php" class="menu-item"><span class="icon">🏠</span> Dashboard</a>
            
            <a href="javascript:void(0);" class="menu-item active">
                <span class="icon">⚙️</span> **Master** <span style="margin-left: auto;">▼</span>
            </a>
            
            <div class="submenu">
                <a href="master_bagian.php">🏢 Bagian</a>
                <a href="master_building.php">🏭 Grup Building</a>
                <a href="master_jenis_barang.php" class="active-sub">🖥️ Jenis barang</a>
                <?php if ($level === 'ADMIN' || $level === 'SUPERADMIN'): ?>
                    <a href="master_user.php">👤 User</a>
                <?php endif; ?>
            </div>
            
            <a href="input_barang.php" class="menu-item"><span class="icon">➕</span> Input Barang</a>
            <a href="data_inventaris.php" class="menu-item"><span class="icon">📋</span> Lihat Data</a>
            <a href="laporan.php" class="menu-item"><span class="icon">📄</span> Report</a>
        </div>


        <div class="content-area">
            
            <div class="header-pt">
                MASTER JENIS BARANG
                <span style="font-size: 14px; display: block; margin-top: 5px;">PT.VERONIQUE INDONESIA</span>
            </div>

            <?php echo $notification; ?>
            <?php echo $message; ?>

            <div class="add-form-container">
                <h3>➕ Tambah Jenis Barang Baru</h3>
                <form action="master_jenis_barang.php" method="POST">
                    <input type="text" name="nama_jenis" placeholder="Contoh: PC, LAPTOP, MONITOR" required>
                    <button type="submit" name="add_jenis" class="btn-add">Tambah Jenis</button>
                </form>
            </div>
            
            <h3>Daftar Jenis Barang (<?php echo count($jenis_list); ?> total)</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>NAMA JENIS</th>
                        <th style="width: 150px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($jenis_list)): ?>
                        <?php foreach ($jenis_list as $j): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($j['id_jenis']); ?></td>
                            <td><?php echo htmlspecialchars($j['nama_jenis']); ?></td>
                            <td>
                                <a href="edit_jenis.php?id=<?php echo $j['id_jenis']; ?>" class="action-link edit">✏️ Edit</a>
                                <a href="master_jenis_barang.php?action=delete&id=<?php echo $j['id_jenis']; ?>" class="action-link delete" 
                                    onclick="return confirm('Yakin hapus Jenis Barang <?php echo $j['nama_jenis']; ?>? Semua Barang di dalamnya harus dipindahkan atau dihapus terlebih dahulu.');">🗑️ Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">Tidak ada data Jenis Barang. Pastikan tabel **jenis_barang** sudah ada di database.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</body>
</html>