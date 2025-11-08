<?php
// data_inventaris.php - Halaman Tampil Data Inventaris (Lihat Data)
session_start();
// WAJIB: Sertakan file koneksi database Anda
include 'db_config.php';

// Pengecekan Keamanan
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}
$level = htmlspecialchars($_SESSION['level'] ?? 'USER');

// Inisialisasi variabel filter
$filter_jenis = $_GET['jenis'] ?? '';
$filter_building = $_GET['building'] ?? ''; 
$search_query = $_GET['search'] ?? '';
$error_message = '';
$notification = '';

// Ambil notifikasi sukses (jika ada)
if (isset($_SESSION['notif'])) {
    $notification = '<div class="notification success">' . $_SESSION['notif'] . '</div>';
    unset($_SESSION['notif']);
}

// --- AMBIL DATA MASTER UNTUK DROPDOWN FILTER ---

// 1. Ambil daftar Jenis Barang (FIXED: Menggunakan 'jenis_barang' untuk menghindari Fatal Error)
$jenis_list = [];
$query_jenis = "SELECT id_jenis, nama_jenis FROM jenis_barang ORDER BY nama_jenis ASC";
$result_jenis = mysqli_query($koneksi, $query_jenis);
if ($result_jenis) {
    $jenis_list = mysqli_fetch_all($result_jenis, MYSQLI_ASSOC);
}

// 2. Ambil daftar Grup Building (FIXED: Menggunakan 'master_building' sesuai database Anda)
$building_list = [];
$query_building = "SELECT id_building, nama_building FROM master_building ORDER BY nama_building ASC"; 
$result_building = mysqli_query($koneksi, $query_building);
if ($result_building) {
    $building_list = mysqli_fetch_all($result_building, MYSQLI_ASSOC);
} else {
    // Tampilkan pesan error jika tabel master_building bermasalah
    $error_message .= '<div class="notification error">❌ Gagal mengambil daftar Grup Building. Pastikan tabel **master_building** ada dan namanya benar.</div>';
}


// --- QUERY UTAMA DATA INVENTARIS ---
$sql = "
    SELECT 
        brg.id_barang, brg.nama_pengguna, brg.merek, brg.processor, brg.ram, brg.hdd, brg.ssd, brg.vga, 
        brg.tanggal_beli, jns.nama_jenis, bag.nama_bagian, brg.gen, bld.nama_building 
    FROM 
        barang brg
    LEFT JOIN 
        jenis_barang jns ON brg.id_jenis = jns.id_jenis 
    LEFT JOIN 
        bagian bag ON brg.id_bagian = bag.id_bagian 
    LEFT JOIN 
        master_building bld ON bag.id_building = bld.id_building /* FIXED: Menggunakan master_building */
    WHERE 
        1=1
";

// BARIS DUPLIKAT INI DIHAPUS KARENA TIDAK DIGUNAKAN DAN MEMBINGUNGKAN
// $query_data = "SELECT * FROM barang INNER JOIN ... ORDER BY barang.id_barang ASC"; 

// Tambahkan Filter Jenis Barang
if (!empty($filter_jenis)) {
    $sql .= " AND brg.id_jenis = " . (int)$filter_jenis;
}

// Tambahkan Filter Grup Building
if (!empty($filter_building)) {
    $sql .= " AND bld.id_building = " . (int)$filter_building;
}

// Tambahkan Pencarian
if (!empty($search_query)) {
    $search = mysqli_real_escape_string($koneksi, $search_query);
    $sql .= " AND (
        brg.nama_pengguna LIKE '%$search%' OR 
        brg.merek LIKE '%$search%' OR 
        brg.processor LIKE '%$search%' OR 
        bag.nama_bagian LIKE '%$search%' OR 
        jns.nama_jenis LIKE '%$search%' OR
        bld.nama_building LIKE '%$search%'
    )";
}

// PERBAIKAN UTAMA: Menggunakan ASC (Ascending) agar ID 1 muncul di baris paling atas.
// Ganti dari DESC ke ASC:
$sql .= " ORDER BY brg.id_barang ASC";

$result_barang = mysqli_query($koneksi, $sql);
$barang_list = [];
$total_data = 0;

if ($result_barang) {
    $barang_list = mysqli_fetch_all($result_barang, MYSQLI_ASSOC);
    $total_data = count($barang_list);
} else {
    // Tampilkan error jika query gagal
    $error_message .= '<div class="notification error">❌ Gagal mengambil data inventaris. Error SQL: ' . mysqli_error($koneksi) . '</div>';
}

mysqli_close($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Inventaris</title>
    <style>
        /* CSS yang konsisten */
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f0f0f0; }
        .main-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: #000; color: white; padding: 20px 0; box-shadow: 2px 0 5px rgba(0,0,0,0.5); }
        .sidebar h2 { margin: 0 0 5px 20px; color: #fff; font-size: 24px; font-weight: bold; }
        .menu-item { display: flex; align-items: center; padding: 15px 20px; color: #fff; text-decoration: none; font-size: 16px; border-left: 5px solid transparent; transition: background-color 0.3s, border-left-color 0.3s; }
        .menu-item:hover { background-color: #333; border-left-color: #ffc107; }
        .menu-item.active { background-color: #1a1a1a; border-left-color: #007bff; }
        .content-area { flex-grow: 1; padding: 40px; background-color: #fff; }
        .header-pt { font-size: 30px; color: #495057; margin-bottom: 30px; }
        .search-filter-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .filter-group { display: flex; align-items: center; gap: 10px; } /* Grouping filters */
        .search-filter-area select, .search-filter-area input[type="text"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .search-filter-area button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #e9ecef; }
        .action-link { margin-right: 10px; text-decoration: none; font-weight: bold; }
        .edit { color: #ffc107; }
        .delete { color: #dc3545; }
        .notification { padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid; }
        .success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .submenu { background-color: #1a1a1a; padding-left: 35px; display: none; }
        .submenu a { padding: 8px 20px; display: block; color: #ccc; text-decoration: none; font-size: 14px; border-left: 5px solid transparent; }
    </style>
</head>
<body>

    <div class="main-layout">
        
        <div class="sidebar">
            <h2>INVENTARIS</h2>
            <a href="dashboard.php" class="menu-item">🏠 Dashboard</a>
            
            <a href="javascript:void(0);" class="menu-item master-menu">⚙️ Master ▼</a>
            <div class="submenu" id="submenu-master">
                <a href="master_bagian.php">🏢 Bagian</a>
                <a href="master_building.php">🏭 Grup Building</a>
                <a href="master_jenis_barang.php">🖥️ Jenis barang</a>
                <?php if ($level === 'ADMIN' || $level === 'SUPERADMIN'): ?>
                    <a href="master_user.php">👤 User</a>
                <?php endif; ?>
            </div>
            
            <a href="input_barang.php" class="menu-item">➕ Input Barang</a>
            <a href="data_inventaris.php" class="menu-item active">📋 **Lihat Data**</a>
            <a href="laporan.php" class="menu-item">📄 Report</a>
        </div>


        <div class="content-area">
            
            <div class="header-pt">
                DATA INVENTARIS PERANGKAT KERAS
                <span style="font-size: 14px; display: block; margin-top: 5px;">PT.VERONIQUE INDONESIA</span>
            </div>

            <?php echo $notification; ?>
            <?php echo $error_message; ?>

            <div class="search-filter-area">
                <form action="data_inventaris.php" method="GET" class="filter-group">
                    
                    <label style="font-weight: bold; color: #555;">Filter:</label>
                    
                    <select id="jenis" name="jenis">
                        <option value="">-- Jenis --</option>
                        <?php foreach ($jenis_list as $jenis): ?>
                            <option value="<?php echo htmlspecialchars($jenis['id_jenis']); ?>" 
                                <?php echo ($filter_jenis == $jenis['id_jenis']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($jenis['nama_jenis']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select id="building" name="building">
                        <option value="">-- Grup Building --</option>
                        <?php foreach ($building_list as $bld): ?>
                            <option value="<?php echo htmlspecialchars($bld['id_building']); ?>" 
                                <?php echo ($filter_building == $bld['id_building']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bld['nama_building']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit">Terapkan Filter</button>
                </form>

                <form action="data_inventaris.php" method="GET" style="display: flex; align-items: center;">
                    🔍 <input type="text" name="search" placeholder="Cari Pengguna/Merek/Bagian..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <input type="hidden" name="jenis" value="<?php echo htmlspecialchars($filter_jenis); ?>">
                    <input type="hidden" name="building" value="<?php echo htmlspecialchars($filter_building); ?>">
                    <button type="submit">Cari</button>
                </form>
            </div>
            
            <p style="font-weight: bold;">Total Data Ditemukan: "<?php echo $total_data; ?>"</p>

            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Pengguna</th>
                        <th>Bagian</th>
                        <th>**Grup Building**</th> <th>Jenis</th>
                        <th>Merek</th>
                        <th>Spesifikasi</th>
                        <th>Tanggal Beli</th>
                        <th style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($barang_list)): ?>
                        <?php foreach ($barang_list as $brg): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($brg['id_barang']); ?></td>
                            <td><?php echo htmlspecialchars($brg['nama_pengguna']); ?></td>
                            <td><?php echo htmlspecialchars($brg['nama_bagian']); ?></td>
                            <td>**<?php echo htmlspecialchars($brg['nama_building']); ?>**</td> <td><?php echo htmlspecialchars($brg['nama_jenis']); ?></td>
                            <td><?php echo htmlspecialchars($brg['merek']); ?></td>
                            <td>
                                Proc: **<?php echo htmlspecialchars($brg['processor']); ?>** (Gen: <?php echo htmlspecialchars($brg['gen']); ?>)<br>
                                RAM: <?php echo htmlspecialchars($brg['ram']); ?><br>
                                Storage: SSD: <?php echo htmlspecialchars($brg['ssd'] ?: '-'); ?> / HDD: <?php echo htmlspecialchars($brg['hdd'] ?: '-'); ?>
                            </td>
                            <td><?php echo date('d M Y', strtotime($brg['tanggal_beli'])); ?></td>
                            <td>
                                <a href="edit_inventaris.php?id=<?php echo $brg['id_barang']; ?>" class="action-link edit">✏️ Edit</a>
                                <a href="delete_inventaris.php?id=<?php echo $brg['id_barang']; ?>" class="action-link delete" 
                                    onclick="return confirm('Yakin hapus data ini?');">🗑️ Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">Tidak ada data inventaris yang ditemukan. Silakan **Input Barang** terlebih dahulu.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>

<script>
    // Script sederhana untuk toggle submenu
    document.addEventListener('DOMContentLoaded', function() {
        const masterMenu = document.querySelector('.master-menu');
        const subMenu = document.getElementById('submenu-master');
        
        if (masterMenu && subMenu) {
            masterMenu.addEventListener('click', function(e) {
                // Toggle tampilan submenu
                if (subMenu.style.display === 'block') {
                    subMenu.style.display = 'none';
                } else {
                    subMenu.style.display = 'block';
                }
            });
        }
    });
</script>

</body>
</html>