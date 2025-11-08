<?php
// input_barang.php - Formulir Penambahan Data Inventaris (Dengan Koneksi DB, Import CSV, dan Kalender Manual)
session_start();

// 1. Amankan halaman
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php'); // Arahkan ke login jika belum login
    exit();
}

// WAJIB: Sertakan file koneksi database Anda
include 'db_config.php'; 

// Ambil data user yang sedang login
$username = htmlspecialchars($_SESSION['username'] ?? 'Guest');
$level = htmlspecialchars($_SESSION['level'] ?? 'USER');

$message = '';
$notification = '';

// Ambil notifikasi sukses (jika ada)
if (isset($_SESSION['notif'])) {
    $notification = '<div class="notification success">' . $_SESSION['notif'] . '</div>';
    unset($_SESSION['notif']);
}

// --- FUNGSI PENDUKUNG: Mengambil data dari Master ---

// Ambil Daftar Bagian
$bagian_list = [];
$query_bagian = "SELECT id_bagian, nama_bagian FROM bagian ORDER BY nama_bagian ASC";
$result_bagian = mysqli_query($koneksi, $query_bagian);
if ($result_bagian) {
    $bagian_list = mysqli_fetch_all($result_bagian, MYSQLI_ASSOC);
}

// Ambil Daftar Jenis Barang
$jenis_list = [];
$query_jenis = "SELECT id_jenis, nama_jenis FROM jenis_barang ORDER BY nama_jenis ASC";
$result_jenis = mysqli_query($koneksi, $query_jenis);
if ($result_jenis) {
    $jenis_list = mysqli_fetch_all($result_jenis, MYSQLI_ASSOC);
}


// -----------------------------------------------------------
// --- LOGIKA 1: PENAMBAHAN DATA BARU (Formulir Manual) ---
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_input'])) {
    
    // Ambil dan Amankan Data dari Formulir menggunakan mysqli_real_escape_string
    $nama_pengguna = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $id_bagian = (int)$_POST['id_bagian'];
    
    // PERUBAHAN 1: Mengambil Tanggal Pengadaan dari inputan user
    $tanggal_beli = mysqli_real_escape_string($koneksi, $_POST['tanggal_beli']); 
    
    // Mengambil tanggal input data manual
    $tanggal_input = mysqli_real_escape_string($koneksi, $_POST['tanggal_input']); 
    
    $ram = mysqli_real_escape_string($koneksi, $_POST['ram']);
    $processor = mysqli_real_escape_string($koneksi, $_POST['processor']);
    $gen = mysqli_real_escape_string($koneksi, $_POST['gen']);
    $merek = mysqli_real_escape_string($koneksi, $_POST['merek']);
    $hdd = mysqli_real_escape_string($koneksi, $_POST['hdd']);
    $ssd = mysqli_real_escape_string($koneksi, $_POST['ssd']);
    $vga = mysqli_real_escape_string($koneksi, $_POST['vga']);
    $id_jenis = (int)$_POST['id_jenis'];
    
    // 3. Buat Query INSERT
    // PASTIKAN KOLOM DI DB ANDA MENGGUNAKAN NAMA 'tanggal_beli' JIKA TIDAK ADA,
    // ATAU GANTI NAMA KOLOM `tahun_beli` DI DB MENJADI `tanggal_beli` DENGAN TIPE DATE.
    $query = "INSERT INTO barang (
                nama_pengguna, id_bagian, tanggal_beli, tanggal_input, ram, processor, 
                gen, merek, hdd, ssd, vga, id_jenis
              ) VALUES (
                '$nama_pengguna', $id_bagian, '$tanggal_beli', '$tanggal_input', '$ram', '$processor', 
                '$gen', '$merek', '$hdd', '$ssd', '$vga', $id_jenis
              )";

    // 4. Jalankan Query dan Cek Hasil
    if (mysqli_query($koneksi, $query)) {
        $_SESSION['notif'] = '✅ Data inventaris berhasil ditambahkan!';
        header('Location: input_barang.php');
        exit();
    } else {
        $message = '<div class="notification error">❌ Gagal menambahkan data. Error SQL: ' . mysqli_error($koneksi) . '</div>';
    }
}


// -----------------------------------------------------------
// --- LOGIKA 2: IMPORT CSV BARANG ---
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_csv'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['csv_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

        if ($file_ext != 'csv') {
            $message = '<div class="notification error">❌ Format file harus **CSV**!</div>';
        } else {
            $handle = fopen($file_tmp, "r");
            $success_count = 0;
            $failed_count = 0;
            // Tanggal untuk impor tetap otomatis tanggal hari ini
            $tanggal_input_import = date('Y-m-d');

            // Skip header (Baris pertama diasumsikan sebagai header)
            fgetcsv($handle, 1000, ","); 

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Pastikan format CSV sesuai (11 kolom):
                // Kolom 2 sekarang diasumsikan adalah Tanggal Beli/Pengadaan dalam format YYYY-MM-DD
                if (count($data) < 11 || empty($data[0]) || empty($data[1]) || empty($data[10])) {
                    $failed_count++;
                    continue; 
                }

                // Amankan dan konversi data
                $nama_pengguna = mysqli_real_escape_string($koneksi, trim($data[0]));
                $nama_bagian_csv = mysqli_real_escape_string($koneksi, trim(strtoupper($data[1])));
                // PERUBAHAN 2: Mengambil Tanggal Beli/Pengadaan dari CSV (Kolom ke-3)
                $tanggal_beli_csv = mysqli_real_escape_string($koneksi, trim($data[2])); 
                
                $ram = mysqli_real_escape_string($koneksi, trim($data[3]));
                $processor = mysqli_real_escape_string($koneksi, trim($data[4]));
                $gen = mysqli_real_escape_string($koneksi, trim($data[5]));
                $merek = mysqli_real_escape_string($koneksi, trim($data[6]));
                $hdd = mysqli_real_escape_string($koneksi, trim($data[7]));
                $ssd = mysqli_real_escape_string($koneksi, trim($data[8]));
                $vga = mysqli_real_escape_string($koneksi, trim($data[9]));
                $nama_jenis_csv = mysqli_real_escape_string($koneksi, trim(strtoupper($data[10])));

                // Dapatkan ID Bagian (Foreign Key)
                $q_bag = "SELECT id_bagian FROM bagian WHERE nama_bagian = '$nama_bagian_csv'";
                $r_bag = mysqli_query($koneksi, $q_bag);
                $d_bag = mysqli_fetch_assoc($r_bag);
                $id_bagian_fk = $d_bag['id_bagian'] ?? 0;

                // Dapatkan ID Jenis (Foreign Key)
                $q_jen = "SELECT id_jenis FROM jenis_barang WHERE nama_jenis = '$nama_jenis_csv'";
                $r_jen = mysqli_query($koneksi, $q_jen);
                $d_jen = mysqli_fetch_assoc($r_jen);
                $id_jenis_fk = $d_jen['id_jenis'] ?? 0;


                if ($id_bagian_fk > 0 && $id_jenis_fk > 0) {
                    $insert_query = "INSERT INTO barang (
                        nama_pengguna, id_bagian, tanggal_beli, tanggal_input, ram, processor, 
                        gen, merek, hdd, ssd, vga, id_jenis
                      ) VALUES (
                        '$nama_pengguna', $id_bagian_fk, '$tanggal_beli_csv', '$tanggal_input_import', '$ram', '$processor', 
                        '$gen', '$merek', '$hdd', '$ssd', '$vga', $id_jenis_fk
                      )";

                    if (mysqli_query($koneksi, $insert_query)) {
                        $success_count++;
                    } else {
                        $failed_count++; // Gagal SQL
                    }
                } else {
                    $failed_count++; // Gagal menemukan ID Bagian/Jenis
                }
            }
            fclose($handle);

            if ($success_count > 0) {
                $_SESSION['notif'] = '🎉 Impor berhasil! **' . $success_count . '** data Inventaris baru ditambahkan. (' . $failed_count . ' data dilewati/gagal).';
            } else {
                $_SESSION['notif'] = '⚠️ Impor selesai. Tidak ada data baru yang berhasil ditambahkan. (' . $failed_count . ' data dilewati/gagal).';
            }
            
            header('Location: input_barang.php');
            exit();
        }
    } elseif (isset($_POST['import_csv'])) {
        $message = '<div class="notification error">❌ Gagal mengunggah file. Pastikan Anda memilih file yang benar.</div>';
    }
}

// Penutup blok PHP untuk logika
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Data Inventaris Baru</title>
    <style>
        /* BASE LAYOUT */
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f0f0f0; }
        .main-layout { display: flex; min-height: 100vh; }
        .content-area { flex-grow: 1; padding: 40px; background-color: #fff; position: relative; }
        
        /* SIDEBAR (Konsisten) */
        .sidebar { width: 250px; background-color: #000; color: white; padding: 20px 0; box-shadow: 2px 0 5px rgba(0,0,0,0.5); }
        .sidebar h2 { text-align: left; color: #fff; margin: 0 0 5px 20px; font-size: 24px; font-weight: bold; text-transform: uppercase; }
        .user-info { text-align: left; margin-bottom: 20px; padding: 0 20px; }
        .user-info .level { font-size: 14px; font-weight: bold; color: #d8cfff; display: inline; margin-right: 5px; text-transform: uppercase; }
        .user-info .logout-link-sidebar { font-size: 14px; font-weight: bold; color: #dc3545; text-decoration: none; }
        .menu-item { display: flex; align-items: center; padding: 15px 20px; color: #fff; text-decoration: none; font-size: 16px; border-left: 5px solid transparent; transition: background-color 0.3s, border-left-color 0.3s; }
        .menu-item:hover { background-color: #333; border-left-color: #ffc107; }
        .menu-item.active { background-color: #1a1a1a; border-left-color: #007bff; }
        .menu-item .icon { margin-right: 15px; font-size: 18px; width: 20px; text-align: center; }
        
        /* FORM DAN IMPORT AREA */
        .header-pt { font-size: 30px; color: #495057; margin-bottom: 30px; }
        .form-container { display: flex; gap: 30px; margin-bottom: 30px; }
        .form-input-area { flex: 2; background: #f8f8f8; padding: 25px; border-radius: 8px; border: 1px solid #eee; }
        .import-box { flex: 1; background-color: #f0f8ff; border: 1px solid #cce5ff; padding: 20px; border-radius: 8px; height: fit-content; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        /* Perubahan CSS untuk menyertakan input[type="date"] */
        input[type="text"], input[type="number"], select, input[type="date"] { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        
        .btn-submit { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: background-color 0.2s; }
        .btn-submit:hover { background-color: #1e7e34; }
        .btn-download-format {
            display: inline-block;
            background-color: #007bff; 
            color: white;
            padding: 8px 15px; 
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            margin-top: 10px;
            transition: background-color 0.2s;
        }
        .btn-download-format:hover { background-color: #0056b3; }
        .btn-import { 
            background-color: #007bff; 
            color: white; 
            padding: 8px 15px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            transition: background-color 0.2s; 
            margin-top: 10px;
        } 
        .btn-import:hover { background-color: #0056b3; }

        /* Notifikasi */
        .notification { padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid; }
        .success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    </style>
</head>
<body>

    <div class="main-layout">
        
        <div class="sidebar">
            
            <h2>INVENTARIS</h2>
            
            <div class="user-info">
                <span class="level"><?php echo htmlspecialchars($_SESSION['level']); ?></span> / 
                <a href="logout.php" class="logout-link-sidebar">Log out</a>
            </div>
            
            <a href="dashboard.php" class="menu-item">
                <span class="icon">🏠</span> Dashboard
            </a>
            
            <a href="javascript:void(0);" class="menu-item">
                <span class="icon">⚙️</span> Master <span style="margin-left: auto;">▼</span>
            </a>
            <div class="submenu" style="display: none;">
                <a href="master_bagian.php">🏢 Bagian</a>
                <a href="master_jenis_barang.php">🖥️ Jenis barang</a>
                <?php if ($level === 'ADMIN' || $level === 'SUPERADMIN'): ?>
                    <a href="master_user.php">👤 User</a>
                <?php endif; ?>
            </div>
            
            <a href="input_barang.php" class="menu-item active">
                <span class="icon">➕</span> **Input Barang**
            </a>

            <a href="data_inventaris.php" class="menu-item">
                <span class="icon">📋</span> Lihat Data
            </a>
            
            <a href="laporan.php" class="menu-item">
                <span class="icon">📄</span> Report
            </a>
        </div>
        <a href="javascript:void(0);" class="menu-item active">
                <span class="icon">⚙️</span> **Master** <span style="margin-left: auto;">▼</span>
            </a>
            
            <div class="submenu">
                <a href="master_bagian.php" class="active-sub">
                    🏢 Bagian
                </a>
                <a href="master_jenis_barang.php">
                    🖥️ Jenis barang
                </a>
                <?php if ($level === 'ADMIN' || $level === 'SUPERADMIN'): ?>
                    <a href="master_user.php">
                        👤 User
                    </a>
                <?php endif; ?>
            </div>
            
            <a href="tambah_barang.php" class="menu-item">
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
                INPUT DATA INVENTARIS BARU
                <span style="font-size: 14px; display: block; margin-top: 5px;">PT.VERONIQUE INDONESIA</span>
            </div>

            <?php echo $notification; // Tampilkan notifikasi sukses dari session ?>
            <?php echo $message; // Tampilkan pesan error SQL/Import ?>

            <div class="form-container">
                
                <div class="form-input-area">
                    <h3>➕ Tambah Data Manual</h3>
                    
                    <form action="input_barang.php" method="POST">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            
                            <div>
                                <div class="form-group">
                                    <label for="nama">Nama Pengguna:</label>
                                    <input type="text" id="nama" name="nama" required>
                                </div>

                                <div class="form-group">
                                    <label for="id_bagian">Bagian:</label>
                                    <select id="id_bagian" name="id_bagian" required>
                                        <option value="">-- Pilih Bagian --</option>
                                        <?php foreach ($bagian_list as $bagian): ?>
                                            <option value="<?php echo htmlspecialchars($bagian['id_bagian']); ?>">
                                                <?php echo htmlspecialchars($bagian['nama_bagian']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(empty($bagian_list)): ?><p style="color: red; font-size: 12px;">❗ Master Bagian kosong.</p><?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="id_jenis">Jenis Perangkat:</label>
                                    <select id="id_jenis" name="id_jenis" required>
                                        <option value="">-- Pilih Jenis --</option>
                                        <?php foreach ($jenis_list as $jenis): ?>
                                            <option value="<?php echo htmlspecialchars($jenis['id_jenis']); ?>">
                                                <?php echo htmlspecialchars($jenis['nama_jenis']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(empty($jenis_list)): ?><p style="color: red; font-size: 12px;">❗ Master Jenis Barang kosong.</p><?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="tanggal_beli">Tanggal Pengadaan:</label>
                                    <input type="date" id="tanggal_beli" name="tanggal_beli" required>
                                </div>
                                <div class="form-group">
                                    <label for="tanggal_input">Tanggal Input Data:</label>
                                    <input type="date" id="tanggal_input" name="tanggal_input" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="merek">Merek:</label>
                                    <input type="text" id="merek" name="merek" required>
                                </div>
                            </div>

                            <div>
                                <div class="form-group">
                                    <label for="processor">Processor:</label>
                                    <input type="text" id="processor" name="processor" placeholder="Contoh: Core i5" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="gen">Generasi (GEN):</label>
                                    <input type="text" id="gen" name="gen" placeholder="Contoh: Gen 10">
                                </div>
                                
                                <div class="form-group">
                                    <label for="ram">RAM (contoh: 8 GB):</label>
                                    <input type="text" id="ram" name="ram" required>
                                </div>

                                <div class="form-group">
                                    <label for="hdd">HDD (contoh: 1000 GB atau -):</label>
                                    <input type="text" id="hdd" name="hdd" placeholder="Contoh: 1000 GB atau -">
                                </div>

                                <div class="form-group">
                                    <label for="ssd">SSD (contoh: 256 GB atau -):</label>
                                    <input type="text" id="ssd" name="ssd" placeholder="Contoh: 256 GB atau -">
                                </div>

                                <div class="form-group">
                                    <label for="vga">VGA:</label>
                                    <input type="text" id="vga" name="vga" placeholder="Contoh: NVIDIA RTX 3050 atau -">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group" style="text-align: right; margin-top: 20px;">
                            <button type="submit" name="submit_input" class="btn-submit">Simpan Data Inventaris</button>
                        </div>
                    </form>
                </div>
                
                <div class="import-box">
                    <h4>📥 Impor Data Inventaris Masal (CSV)</h4>
                    <p style="font-size: 13px; margin-top: -10px; color: #0056b3;">
                        Pastikan kolom **Nama Bagian** dan **Nama Jenis** sudah tersedia di menu Master sebelum impor.
                    </p>
                    
                    <a href="download_template_barang.php" class="btn-download-format">
                        ⬇️ Download Format CSV
                    </a>

                    <form action="input_barang.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                        <input type="file" name="csv_file" accept=".csv" required>
                        <button type="submit" name="import_csv" class="btn-import">Proses Impor</button>
                    </form>
                </div>

            </div>
        </div>
    </div>

<script>
    // Script sederhana untuk toggle submenu (jika perlu)
    document.querySelector('.menu-item:nth-child(3)').addEventListener('click', function() {
        var submenu = this.nextElementSibling;
        if (submenu.style.display === 'none' || submenu.style.display === '') {
            submenu.style.display = 'block';
        } else {
            submenu.style.display = 'none';
        }
    });
</script>
</body>
</html>