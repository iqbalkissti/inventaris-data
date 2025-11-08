<?php
// dashboard.php

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}
// Pastikan db_config.php sudah benar-benar ada dan berisi koneksi database ($koneksi)
include 'db_config.php'; 

// Data user dan inisialisasi
$username = htmlspecialchars($_SESSION['username'] ?? 'Guest');
$level = htmlspecialchars($_SESSION['level'] ?? 'USER');

// --- LOGIKA PERHITUNGAN DATA INVENTARIS ---

// 1. Ambil daftar semua jenis barang dari tabel master
$jenis_query = "SELECT id_jenis, nama_jenis FROM jenis_barang";
$jenis_result = mysqli_query($koneksi, $jenis_query);
$jenis_map = []; // Array untuk menyimpan Nama Jenis => Jumlah

if ($jenis_result) {
    while ($row = mysqli_fetch_assoc($jenis_result)) {
        // Konversi nama jenis ke UPPERCASE agar sesuai dengan key di HTML
        $key = strtoupper($row['nama_jenis']);
        $jenis_map[$key] = 0; 

        // 2. Hitung jumlah barang untuk setiap ID Jenis
        $id_jenis = $row['id_jenis'];
        $count_query = "SELECT COUNT(*) AS total FROM barang WHERE id_jenis = $id_jenis";
        $count_result = mysqli_query($koneksi, $count_query);

        if ($count_result) {
            $count_data = mysqli_fetch_assoc($count_result);
            $jenis_map[$key] = $count_data['total'];
        }
    }
} else {
    // Tampilkan pesan error yang lebih informatif jika tabel master tidak ditemukan
    // Atau jika koneksi gagal
    // Hapus die() jika Anda ingin dashboard tetap tampil walau tanpa data
    // die("Error mengambil jenis barang: " . mysqli_error($koneksi)); 
}

// Tutup koneksi setelah semua data diambil
mysqli_close($koneksi); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Inventaris</title>
    <style>
        /* CSS Umum (Konsisten) */
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f0f0f0; }
        .main-layout { display: flex; min-height: 100vh; }
        
        /* SIDEBAR (Warna Hitam) */
        .sidebar {
            width: 250px;
            background-color: #000;
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.5);
            position: relative;
        }

        /* --- REVISI GAYA HEADER SIDEBAR KHUSUS (START) --- */
        .sidebar h2 {
            text-align: left;
            color: white;
            margin: 0 0 5px 20px;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* BLOK ADMIN / LOGOUT BARU */
        .user-info {
            text-align: left;
            margin-bottom: 20px;
            padding: 0 20px;
        }
        .user-info .level {
            font-size: 14px;
            font-weight: bold;
            color: #d8cfff;
            display: inline;
            margin-right: 5px;
            text-transform: uppercase;
        }
        .user-info .logout-link-sidebar {
            font-size: 14px;
            font-weight: bold;
            color: #dc3545;
            text-decoration: none;
        }
        .user-info .logout-link-sidebar:hover {
            text-decoration: underline;
        }
        /* --- REVISI GAYA HEADER SIDEBAR KHUSUS (END) --- */


        .menu-item {
            display: flex; 
            align-items: center;
            padding: 15px 20px;
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            border-left: 5px solid transparent;
            transition: background-color 0.3s, border-left-color 0.3s;
            cursor: pointer;
        }
        .menu-item:hover {
            background-color: #333;
            border-left-color: #ffc107; 
        }
        .menu-item.active {
            background-color: #1a1a1a;
            border-left-color: #007bff;
        }
        
        /* SUB-MENU MASTER */
        .submenu {
            background-color: #1a1a1a; 
            padding-left: 35px;
            display: none; 
        }
        
        .submenu a {
            padding: 8px 20px; 
            display: block;
            color: #ccc;
            text-decoration: none;
            font-size: 14px;
            border-left: 5px solid transparent;
        }
        .submenu a:hover {
            background-color: #444;
            color: #fff;
            border-left-color: #ffc107;
        }
        
        /* CSS Konten Utama */
        .content-area { 
            flex-grow: 1; 
            padding: 40px; 
            background-color: #fff; 
            position: relative;
        }
        .header-pt { 
            font-size: 30px; 
            color: #495057; 
            margin-bottom: 30px; 
            padding-right: 150px; 
        }
        .header-pt span { color: #007bff; }

        /* Kartu Dashboard */
        .card-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 40px;
        }
        .card {
            background-color: #1a1a3a;
            color: white;
            padding: 20px;
            border-radius: 8px;
            /* Menggunakan grid-column untuk mengatur lebar yang lebih responsif */
            width: calc(25% - 15px); 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .card h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            text-transform: uppercase;
            color: #aaa;
        }
        .card .count {
            font-size: 36px;
            font-weight: bold;
        }

        .summary-info {
            margin-top: 20px;
            padding: 15px;
            border-left: 5px solid #007bff;
            background-color: #f7f7f7;
        }
    </style>
</head>
<body>

    <div class="main-layout">
        
        <div class="sidebar">
            
            <h2>INVENTARIS</h2>
            
            <div class="user-info">
                <span class="level"><?php echo $level; ?></span> / 
                <a href="logout.php" class="logout-link-sidebar">Log out</a>
            </div>
            
            <a href="dashboard.php" class="menu-item active">
                <span class="icon">🏠</span> Dashboard
            </a>
            
            <a href="javascript:void(0);" class="menu-item" id="master-toggle">
                <span class="icon">⚙️</span> Master <span style="margin-left: auto;">▼</span>
            </a>
            
            <div class="submenu" id="master-submenu">
                <a href="master_bagian.php">
                    🏢  Bagian
                </a>
                <a href="master_building.php" class="active-sub">
                    🏭 Grup Building
                </a>
                <a href="master_jenis_barang.php">
                    🖥️  Jenis barang
                </a>
                <?php if ($level === 'ADMIN'): ?>
                    <a href="master_user.php">
                        👤  User
                    </a>
                <?php endif; ?>
            </div>
            
            <a href="input_barang.php" class="menu-item">
                <span class="icon">➕</span> Input Barang
            </a>

            <a href="data_inventaris.php" class="menu-item">
                <span class="icon">📋</span> Lihat Data
            </a>
            
            <a href="laporan.php" class="menu-item">
                <span class="icon">📄</span> Report
            </a>
            
        </div>
        <div class="content-area">
            
            <div class="header-pt">
                SELAMAT DATANG, **<?php echo strtoupper($username); ?>**
                <span style="font-size: 14px; display: block; margin-top: 5px;">PT.VERONIQUE INDONESIA</span>
            </div>

            
            <h3>Ringkasan Inventaris Berdasarkan Jenis</h3>
            <div class="card-grid">
                
                <div class="card">
                    <h4>JUMLAH LAPTOP</h4>
                    <div class="count"><?php echo $jenis_map['LAPTOP'] ?? 0; ?></div>
                </div>
                
                <div class="card">
                    <h4>JUMLAH PC</h4>
                    <div class="count"><?php echo $jenis_map['PC'] ?? 0; ?></div>
                </div>
                
                <div class="card">
                    <h4>JUMLAH AIO</h4>
                    <div class="count"><?php echo $jenis_map['AIO'] ?? 0; ?></div>
                </div>
                
                <div class="card">
                    <h4>JUMLAH MONITOR</h4>
                    <div class="count"><?php echo $jenis_map['MONITOR'] ?? 0; ?></div>
                </div>
                
                <div class="card">
                    <h4>JUMLAH HP</h4>
                    <div class="count"><?php echo $jenis_map['HP'] ?? 0; ?></div>
                </div>
                
                <div class="card">
                    <h4>JUMLAH CAMERA</h4>
                    <div class="count"><?php echo $jenis_map['CAMERA'] ?? 0; ?></div>
                </div>
                
                <div class="card">
                    <h4>JUMLAH ROUTER</h4>
                    <div class="count"><?php echo $jenis_map['ROUTER'] ?? 0; ?></div>
                </div>
                
                <div class="card">
                    <h4>JUMLAH SWITCH</h4>
                    <div class="count"><?php echo $jenis_map['SWITCH'] ?? 0; ?></div>
                </div>
            </div>
            
            <div class="summary-info">
                <p>Angka di atas sudah dihitung dari data yang tersimpan di database **"tabel barang"**.</p>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('master-toggle').addEventListener('click', function() {
            var submenu = document.getElementById('master-submenu');
            if (submenu.style.display === 'block') {
                submenu.style.display = 'none';
            } else {
                submenu.style.display = 'block';
            }
        });
        
        // Opsional: Tetap buka jika user berada di salah satu halaman master
        var path = window.location.pathname;
        if (path.includes('master_bagian.php') || path.includes('master_jenis_barang.php') || path.includes('master_user.php')) {
            document.getElementById('master-submenu').style.display = 'block';
        }
    </script>

</body>
</html>