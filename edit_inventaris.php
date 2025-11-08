<?php
// edit_inventaris.php - Halaman untuk Mengedit Data Inventaris
session_start();
include 'db_config.php'; // Pastikan file koneksi database Anda sudah benar

// 1. Amankan halaman: Redirect ke halaman login jika belum login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}
$level = htmlspecialchars($_SESSION['level'] ?? 'USER');

$message = ''; // Untuk menampilkan pesan sukses atau error
$data_barang = null; // Variabel untuk menyimpan data barang yang akan diedit
$id_barang = (int)($_GET['id'] ?? 0); // Ambil ID barang dari URL

// Jika ID barang tidak valid (0 atau tidak ada), redirect kembali
if ($id_barang === 0) {
    $_SESSION['notif'] = '❌ ID Barang tidak valid untuk pengeditan.';
    header('Location: data_inventaris.php');
    exit();
}

// --- FUNGSI PENDUKUNG: Mengambil data Master (Bagian, Jenis Barang) ---

// A. Ambil Daftar Bagian
$bagian_list = [];
$query_bagian = "SELECT id_bagian, nama_bagian FROM bagian ORDER BY nama_bagian ASC";
$result_bagian = mysqli_query($koneksi, $query_bagian);
if ($result_bagian) {
    $bagian_list = mysqli_fetch_all($result_bagian, MYSQLI_ASSOC);
} else {
    $message .= '<div class="notification error">❌ Gagal mengambil daftar Bagian: ' . mysqli_error($koneksi) . '</div>';
}

// B. Ambil Daftar Jenis Barang
$jenis_list = [];
$query_jenis = "SELECT id_jenis, nama_jenis FROM jenis_barang ORDER BY nama_jenis ASC";
$result_jenis = mysqli_query($koneksi, $query_jenis);
if ($result_jenis) {
    $jenis_list = mysqli_fetch_all($result_jenis, MYSQLI_ASSOC);
} else {
    $message .= '<div class="notification error">❌ Gagal mengambil daftar Jenis Barang: ' . mysqli_error($koneksi) . '</div>';
}


// --- LOGIKA 1: Mengambil Data Inventaris yang Akan Diedit (saat halaman pertama kali dibuka) ---
// Perhatian: Ini akan dijalankan juga setelah POST jika terjadi error, 
// namun di-override oleh data POST di Logika 2 jika ada error.
$query_fetch = "SELECT * FROM barang WHERE id_barang = $id_barang";
$result_fetch = mysqli_query($koneksi, $query_fetch);

if ($result_fetch && mysqli_num_rows($result_fetch) > 0) {
    $data_barang = mysqli_fetch_assoc($result_fetch);
} else {
    // Data tidak ditemukan, mungkin ID salah atau data sudah dihapus
    $_SESSION['notif'] = '❌ Data inventaris dengan ID **' . htmlspecialchars($id_barang) . '** tidak ditemukan.';
    header('Location: data_inventaris.php');
    exit();
}


// --- LOGIKA 2: MEMPROSES UPDATE DATA (Jika formulir disubmit) MENGGUNAKAN PREPARED STATEMENTS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_update'])) {
    
    // Ambil Data dari Formulir
    // ID Barang sudah ada dari atas: $id_barang
    $nama_pengguna = $_POST['nama'] ?? '';
    $id_bagian = (int)($_POST['id_bagian'] ?? 0);
    $tanggal_beli = $_POST['tanggal_beli'] ?? ''; 
    $ram = $_POST['ram'] ?? '';
    $processor = $_POST['processor'] ?? '';
    $gen = $_POST['gen'] ?? '';
    $merek = $_POST['merek'] ?? '';
    $hdd = $_POST['hdd'] ?? '';
    $ssd = $_POST['ssd'] ?? '';
    $vga = $_POST['vga'] ?? '';
    $id_jenis = (int)($_POST['id_jenis'] ?? 0);
    $status = $_POST['status'] ?? ''; 
    // Kita TIDAK mengikat (bind) tanggal_update, tetapi menggunakannya langsung sebagai fungsi NOW() di query.

    // Query UPDATE menggunakan placeholder (?)
    // CATATAN: Kolom 'tanggal_update' DISET OTOMATIS menggunakan fungsi NOW() tanpa placeholder
    $query_update = "
        UPDATE barang SET 
            nama_pengguna = ?, 
            id_bagian = ?, 
            tanggal_beli = ?, 
            ram = ?, 
            processor = ?, 
            gen = ?, 
            merek = ?, 
            hdd = ?, 
            ssd = ?, 
            vga = ?, 
            id_jenis = ?,
            status = ?,
            tanggal_update = NOW() /* <-- DISET OTOMATIS */
        WHERE id_barang = ?
    ";

    // 1. Persiapkan Statement
    $stmt = mysqli_prepare($koneksi, $query_update);

    if ($stmt) {
        // 2. Bind Parameter: 12 placeholders (?) yang tersisa
        // Tipe Data: (nama:s, bagian:i, tgl_beli:s, ram:s, proc:s, gen:s, merek:s, hdd:s, ssd:s, vga:s, jenis:i, status:s, id_barang:i)
        // String Tipe Data: s i s s s s s s s s i s i (Total 13 karakter)
        
        $bind_success = mysqli_stmt_bind_param(
            $stmt, 
            "siisssssssisi", // String Tipe Data yang BENAR: 13 Karakter
            $nama_pengguna, 
            $id_bagian, 
            $tanggal_beli, 
            $ram, 
            $processor, 
            $gen, 
            $merek, 
            $hdd, 
            $ssd, 
            $vga, 
            $id_jenis,
            $status,
            $id_barang // <-- Parameter ke-13
        );

        if ($bind_success) {
            // 3. Jalankan Statement
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['notif'] = '✅ Data inventaris ID **' . htmlspecialchars($id_barang) . '** berhasil diupdate!';
                mysqli_stmt_close($stmt);
                header('Location: data_inventaris.php'); 
                exit();
            } else {
                // Tampilkan error SQL yang lebih detail
                $message = '<div class="notification error">❌ Gagal mengupdate data. Error SQL: ' . mysqli_stmt_error($stmt) . '</div>';
                mysqli_stmt_close($stmt);
                
                // Isi kembali formulir dengan data POST agar user tidak kehilangan input
                $data_barang = $_POST; 
                $data_barang['id_barang'] = $id_barang;
                $data_barang['nama_pengguna'] = $_POST['nama'] ?? ''; 
            }
        } else {
             // Perbaiki pesan error untuk mengindikasikan masalah bind
             $message = '<div class="notification error">❌ Gagal bind parameter. Jumlah data yang diikat: 13. Tipe data: siisssssssisi.</div>';
             // Tetap tutup statement meski bind gagal (jika stmt berhasil dibuat)
             mysqli_stmt_close($stmt); 
        }

    } else {
        $message = '<div class="notification error">❌ Gagal mempersiapkan query. Error SQL: ' . mysqli_error($koneksi) . '</div>';
    }
}
// END OF LOGIKA 2

mysqli_close($koneksi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data Inventaris</title>
    <style>
        /* --- TATA LETAK UTAMA --- */
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #e9eef2; }
        .main-layout { display: flex; min-height: 100vh; }
        .sidebar { 
            width: 250px; 
            background-color: #212529; /* Darker sidebar */
            color: white; 
            padding: 20px 0; 
            box-shadow: 2px 0 5px rgba(0,0,0,0.5); 
        }
        .sidebar h2 { margin: 0 0 15px 20px; color: #fff; font-size: 24px; font-weight: 600; }
        .menu-item { 
            display: flex; 
            align-items: center; 
            padding: 15px 20px; 
            color: #ccc; 
            text-decoration: none; 
            font-size: 16px; 
            border-left: 5px solid transparent; 
            transition: background-color 0.3s, border-left-color 0.3s, color 0.3s; 
        }
        .menu-item:hover { background-color: #343a40; color: #fff; border-left-color: #ffc107; }
        .menu-item.active { background-color: #007bff; color: #fff; border-left-color: #fff; font-weight: bold;} /* Penanda aktif */
        .content-area { flex-grow: 1; padding: 40px; background-color: #fff; }

        /* --- HEADER KONTEN --- */
        .header-pt { 
            font-size: 28px; 
            color: #343a40; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        /* --- FORMULIR --- */
        .form-container { 
            background-color: #f8f9fa; /* Light grey background for container */
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
        }
        .form-title { 
            display: block;
            font-size: 18px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 20px;
        }
        .form-group { 
            margin-bottom: 15px; 
            display: flex; 
            gap: 30px; 
        }
        .form-column { flex: 1; }
        
        .form-column label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #343a40; 
            font-size: 14px;
        }
        .form-column input[type="text"], 
        .form-column input[type="date"], 
        .form-column select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da; /* Light border */
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-column input:focus, .form-column select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }

        /* --- TOMBOL --- */
        .action-buttons {
            margin-top: 30px;
            text-align: right;
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
        }

        .btn-submit {
            padding: 10px 20px;
            background-color: #28a745; /* Green for Submit */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-submit:hover { background-color: #218838; }

        .btn-cancel {
            padding: 10px 20px;
            background-color: #6c757d; /* Grey for Cancel */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            margin-right: 10px;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .btn-cancel:hover { background-color: #5a6268; }

        /* --- NOTIFIKASI --- */
        .notification { padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid; }
        .error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .submenu { background-color: #343a40; padding-left: 35px; display: none; }
        .submenu a { padding: 8px 20px; display: block; color: #ccc; text-decoration: none; font-size: 14px; border-left: 5px solid transparent; }
        .submenu a:hover { color: #fff; background-color: #495057; border-left-color: #ffc107; }
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
                EDIT DATA INVENTARIS 
                <span style="font-size: 14px; display: block; margin-top: 5px; color: #6c757d;">ID Perangkat: #<?php echo htmlspecialchars($id_barang); ?></span>
            </div>

            <?php echo $message; // Menampilkan pesan error/sukses ?>

            <div class="form-container">
                <strong class="form-title">Ubah Detail Perangkat Keras</strong>

                <form action="edit_inventaris.php?id=<?php echo $id_barang; ?>" method="POST">
                    
                    <h3>Detail Pengguna & Perangkat</h3>
                    <hr style="border: 0; border-top: 1px solid #ddd; margin-bottom: 20px;">
                    
                    <div class="form-group">
                        <div class="form-column">
                            <label for="nama">Nama Pengguna:</label>
                            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($data_barang['nama_pengguna'] ?? ''); ?>" required>
                        </div>
                        <div class="form-column">
                            <label for="merek">Merek Perangkat:</label>
                            <input type="text" id="merek" name="merek" value="<?php echo htmlspecialchars($data_barang['merek'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-column">
                            <label for="id_bagian">Bagian/Departemen:</label>
                            <select id="id_bagian" name="id_bagian" required>
                                <option value="">-- Pilih Bagian --</option>
                                <?php foreach ($bagian_list as $bagian): ?>
                                    <option value="<?php echo htmlspecialchars($bagian['id_bagian']); ?>"
                                        <?php echo ((int)($data_barang['id_bagian'] ?? 0) === (int)$bagian['id_bagian']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($bagian['nama_bagian']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-column">
                            <label for="id_jenis">Jenis Perangkat:</label>
                            <select id="id_jenis" name="id_jenis" required>
                                <option value="">-- Pilih Jenis --</option>
                                <?php foreach ($jenis_list as $jenis): ?>
                                    <option value="<?php echo htmlspecialchars($jenis['id_jenis']); ?>"
                                        <?php echo ((int)($data_barang['id_jenis'] ?? 0) === (int)$jenis['id_jenis']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($jenis['nama_jenis']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                         <div class="form-column">
                            <label for="tanggal_beli">Tanggal Pengadaan:</label>
                            <input type="date" id="tanggal_beli" name="tanggal_beli" value="<?php echo htmlspecialchars($data_barang['tanggal_beli'] ?? ''); ?>" required>
                        </div>
                        <div class="form-column">
                            <label for="status">Status Perangkat:</label>
                            <select id="status" name="status" required>
                                <option value="Aktif" <?php echo (($data_barang['status'] ?? '') === 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                                <option value="Rusak" <?php echo (($data_barang['status'] ?? '') === 'Rusak') ? 'selected' : ''; ?>>Rusak</option>
                                <option value="Disposal" <?php echo (($data_barang['status'] ?? '') === 'Disposal') ? 'selected' : ''; ?>>Disposal</option>
                            </select>
                        </div>
                    </div>
                    
                    
                    <h3 style="margin-top: 30px;">Spesifikasi Hardware</h3>
                    <hr style="border: 0; border-top: 1px solid #ddd; margin-bottom: 20px;">

                    <div class="form-group">
                        <div class="form-column">
                            <label for="processor">Processor (contoh: Core i5):</label>
                            <input type="text" id="processor" name="processor" value="<?php echo htmlspecialchars($data_barang['processor'] ?? ''); ?>">
                        </div>
                        <div class="form-column">
                            <label for="gen">Generasi (GEN, contoh: Gen 10):</label>
                            <input type="text" id="gen" name="gen" value="<?php echo htmlspecialchars($data_barang['gen'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                         <div class="form-column">
                            <label for="ram">RAM (contoh: 8 GB):</label>
                            <input type="text" id="ram" name="ram" value="<?php echo htmlspecialchars($data_barang['ram'] ?? ''); ?>" required>
                        </div>
                        <div class="form-column">
                            <label for="vga">VGA (contoh: NVIDIA RTX 3050 atau -):</label>
                            <input type="text" id="vga" name="vga" value="<?php echo htmlspecialchars($data_barang['vga'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-column">
                            <label for="ssd">SSD (contoh: 256 GB atau -):</label>
                            <input type="text" id="ssd" name="ssd" value="<?php echo htmlspecialchars($data_barang['ssd'] ?? ''); ?>">
                        </div>
                        <div class="form-column">
                            <label for="hdd">HDD (contoh: 1000 GB atau -):</label>
                            <input type="text" id="hdd" name="hdd" value="<?php echo htmlspecialchars($data_barang['hdd'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="action-buttons">
                        <a href="data_inventaris.php" class="btn-cancel">Batal</a>
                        <button type="submit" name="submit_update" class="btn-submit">Simpan Perubahan</button>
                    </div>

                </form>
            </div>

        </div>
    </div>
    <script>
        // Script sederhana untuk toggle submenu
        document.addEventListener('DOMContentLoaded', function() {
            const masterMenu = document.querySelector('.master-menu');
            const subMenu = document.getElementById('submenu-master');
            
            // Logika untuk menampilkan submenu jika salah satu link di dalamnya adalah halaman saat ini (tidak dilakukan di sini)
            // Namun, kita akan tetap menampilkan submenu jika tombol diklik.

            if (masterMenu && subMenu) {
                masterMenu.addEventListener('click', function(e) {
                    // Toggle tampilan submenu
                    if (subMenu.style.display === 'block') {
                        subMenu.style.display = 'none';
                        masterMenu.innerHTML = '⚙️ Master ▼';
                    } else {
                        subMenu.style.display = 'block';
                        masterMenu.innerHTML = '⚙️ Master ▲';
                    }
                });
            }
        });
    </script>
</body>
</html>