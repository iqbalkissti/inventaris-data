<?php
// master_jenis.php - CRUD untuk Master Jenis Barang
session_start();
include 'db_config.php';

// Pengecekan Keamanan
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$message = '';

// --- LOGIKA FORM ---

// 1. TAMBAH/EDIT DATA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_jenis'])) {
    $nama_jenis = mysqli_real_escape_string($koneksi, trim(strtoupper($_POST['nama_jenis'])));
    $id_edit = isset($_POST['id_jenis']) ? (int)$_POST['id_jenis'] : 0;

    if (empty($nama_jenis)) {
        $message = '<div class="notification error">Nama jenis tidak boleh kosong!</div>';
    } else {
        if ($id_edit > 0) {
            // Logika EDIT (UPDATE)
            $query = "UPDATE jenis_barang SET nama_jenis = '$nama_jenis' WHERE id_jenis = $id_edit";
            $sukses_msg = '✅ Jenis barang berhasil diubah!';
        } else {
            // Logika TAMBAH (INSERT)
            $query = "INSERT INTO jenis_barang (nama_jenis) VALUES ('$nama_jenis')";
            $sukses_msg = '✅ Jenis barang berhasil ditambahkan!';
        }

        if (mysqli_query($koneksi, $query)) {
            $_SESSION['notif'] = $sukses_msg;
            header('Location: master_jenis_barang.php');
            exit();
        } else {
            // Error Duplicate Entry (MySQL Error 1062)
            if (mysqli_errno($koneksi) == 1062) {
                 $message = '<div class="notification error">Nama jenis **' . $nama_jenis . '** sudah ada!</div>';
            } else {
                 $message = '<div class="notification error">❌ Gagal menyimpan data: ' . mysqli_error($koneksi) . '</div>';
            }
        }
    }
}

// 2. HAPUS DATA
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_hapus = (int)$_GET['id'];
    $query = "DELETE FROM jenis_barang WHERE id_jenis = $id_hapus";
    
    if (mysqli_query($koneksi, $query)) {
        $_SESSION['notif'] = '🗑️ Jenis barang berhasil dihapus!';
    } else {
        $_SESSION['notif'] = '❌ Gagal menghapus data. Pastikan tidak ada barang yang menggunakan jenis ini.';
    }
    header('Location: master_jenis_barang.php');
    exit();
}

// 3. AMBIL DATA UNTUK DITAMPILKAN
$query_select = "SELECT * FROM jenis_barang ORDER BY nama_jenis ASC";
$result_select = mysqli_query($koneksi, $query_select);
$jenis_barang_list = mysqli_fetch_all($result_select, MYSQLI_ASSOC);

// 4. AMBIL DATA UNTUK FORM EDIT
$edit_data = ['id_jenis' => 0, 'nama_jenis' => ''];
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id_edit_form = (int)$_GET['id'];
    $query_edit = "SELECT * FROM jenis_barang WHERE id_jenis = $id_edit_form";
    $result_edit = mysqli_query($koneksi, $query_edit);
    if ($result_edit && $row = mysqli_fetch_assoc($result_edit)) {
        $edit_data = $row;
    } else {
        $message = '<div class="notification error">Data jenis barang tidak ditemukan.</div>';
    }
}

// Ambil notifikasi sukses (jika ada)
$notification = '';
if (isset($_SESSION['notif'])) {
    $notification = '<div class="notification success">' . $_SESSION['notif'] . '</div>';
    unset($_SESSION['notif']);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Jenis Barang</title>
    <style>
        /* CSS Umum (Sidebar dan Layout) */
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f0f0f0; }
        .main-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: #000; color: white; padding: 20px 0; box-shadow: 2px 0 5px rgba(0,0,0,0.5); position: relative; }
        .sidebar h2 { text-align: center; color: #fff; margin: 0 0 40px 0; font-size: 24px; text-transform: uppercase; }
        .menu-item { display: block; padding: 15px 20px; color: #fff; text-decoration: none; font-size: 16px; border-left: 5px solid transparent; transition: background-color 0.3s, border-left-color 0.3s; }
        .menu-item:hover, .menu-item.active { background-color: #333; border-left-color: #ffc107; } /* Ganti warna active */
        .logout-link { display: block; padding: 15px 20px; color: #dc3545; text-decoration: none; position: absolute; bottom: 0; width: 100%; box-sizing: border-box; }
        
        /* CSS Konten Utama */
        .content-area { flex-grow: 1; padding: 40px; background-color: #fff; }
        .header-pt { font-size: 30px; color: #495057; margin-bottom: 30px; }
        .header-pt span { color: #007bff; }
        
        /* Form dan Tabel */
        .form-master { background: #f8f8f8; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .form-master input[type="text"] { padding: 10px; border: 1px solid #ccc; border-radius: 4px; width: 300px; margin-right: 10px; }
        .btn-submit { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-reset { background-color: #6c757d; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        
        table { width: 400px; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .action-link { margin-right: 10px; text-decoration: none; }
        .edit { color: #ffc107; }
        .delete { color: #dc3545; }
        
        /* Notifikasi */
        .notification { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <div class="main-layout">
        
        <div class="sidebar">
            <h2 style="color: #ffc107;">MASTER</h2>
            
            <a href="master_data.php" class="menu-item" style="color: #ffc107; border-left-color: #ffc107;">
                ← Kembali
            </a>
            
            <div style="margin-top: 20px;">
                <a href="master_bagian.php" class="menu-item">
                    🏢 Bagian
                </a>
                
                <a href="master_jenis_barang.php" class="menu-item active">
                    🖥️ Jenis barang
                </a>
                
                <a href="master_user.php" class="menu-item">
                    👤 User
                </a>
            </div>
            
            <a href="logout.php" class="logout-link">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
        </div>

        <div class="content-area">
            
            <div class="header-pt">
                KELOLA JENIS BARANG (Master)
                <span style="font-size: 14px; display: block; margin-top: 5px;">PT.VERONIQUE INDONESIA</span>
            </div>

            <?php echo $notification; // Tampilkan notifikasi dari session ?>
            <?php echo $message; // Tampilkan notifikasi error form ?>

            <div class="form-master">
                <h3>
                    <?php echo ($edit_data['id_jenis'] > 0) ? '✏️ Edit Jenis Barang (ID: ' . $edit_data['id_jenis'] . ')' : '➕ Tambah Jenis Barang Baru'; ?>
                </h3>
                
                <form action="master_jenis_barang.php" method="POST">
                    <input type="hidden" name="id_jenis" value="<?php echo htmlspecialchars($edit_data['id_jenis']); ?>">
                    <input type="text" name="nama_jenis" placeholder="Contoh: LAPTOP" 
                           value="<?php echo htmlspecialchars($edit_data['nama_jenis']); ?>" required>
                    
                    <button type="submit" name="submit_jenis" class="btn-submit">
                        <?php echo ($edit_data['id_jenis'] > 0) ? 'Simpan Perubahan' : 'Tambah Jenis'; ?>
                    </button>
                    
                    <?php if ($edit_data['id_jenis'] > 0): ?>
                        <a href="master_jenis_barang.php" class="btn-reset">Batal Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <h3>Daftar Jenis Barang (<?php echo count($jenis_barang_list); ?> total)</h3>
            <?php if (!empty($jenis_barang_list)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Jenis</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jenis_barang_list as $jenis): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($jenis['id_jenis']); ?></td>
                        <td><?php echo htmlspecialchars($jenis['nama_jenis']); ?></td>
                        <td>
                            <a href="master_jenis.php?action=edit&id=<?php echo $jenis['id_jenis']; ?>" class="action-link edit">Edit</a>
                            <a href="master_jenis.php?action=delete&id=<?php echo $jenis['id_jenis']; ?>" class="action-link delete" onclick="return confirm('Yakin hapus jenis <?php echo $jenis['nama_jenis']; ?>?');">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Belum ada jenis barang yang ditambahkan.</p>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>