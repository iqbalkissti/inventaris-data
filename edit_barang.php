<?php
// edit_barang.php - Halaman Edit dan Pindah Bagian (Tracking)
session_start();
include 'db_config.php';

// Amankan dan cek ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: data_inventaris.php');
    exit();
}
$id_barang = (int)$_GET['id'];

// Ambil data barang yang akan diedit
$q_barang = "SELECT * FROM barang WHERE id_barang = $id_barang";
$r_barang = mysqli_query($koneksi, $q_barang);
$data_barang = mysqli_fetch_assoc($r_barang);

if (!$data_barang) {
    $_SESSION['notif'] = '❌ Data inventaris tidak ditemukan.';
    header('Location: data_inventaris.php');
    exit();
}

// Ambil Daftar Master untuk Dropdown
$bagian_list = mysqli_fetch_all(mysqli_query($koneksi, "SELECT id_bagian, nama_bagian FROM bagian ORDER BY nama_bagian ASC"), MYSQLI_ASSOC);
$jenis_list = mysqli_fetch_all(mysqli_query($koneksi, "SELECT id_jenis, nama_jenis FROM jenis_barang ORDER BY nama_jenis ASC"), MYSQLI_ASSOC);

$message = '';

// --- LOGIKA UPDATE DATA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_data'])) {
    
    // Ambil dan amankan semua data dari form
    $new_nama_pengguna = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $new_id_bagian = (int)$_POST['id_bagian']; // Ini penting untuk tracking pindah divisi
    $new_id_jenis = (int)$_POST['id_jenis'];
    $new_merek = mysqli_real_escape_string($koneksi, $_POST['merek']);
    $new_processor = mysqli_real_escape_string($koneksi, $_POST['processor']);
    $new_gen = mysqli_real_escape_string($koneksi, $_POST['gen']);
    $new_ram = mysqli_real_escape_string($koneksi, $_POST['ram']);
    $new_hdd = mysqli_real_escape_string($koneksi, $_POST['hdd']);
    $new_ssd = mysqli_real_escape_string($koneksi, $_POST['ssd']);
    $new_vga = mysqli_real_escape_string($koneksi, $_POST['vga']);
    $new_tanggal_beli = mysqli_real_escape_string($koneksi, $_POST['tanggal_beli']);


    $update_query = "
        UPDATE barang SET
            nama_pengguna = '$new_nama_pengguna',
            id_bagian = $new_id_bagian,
            id_jenis = $new_id_jenis,
            merek = '$new_merek',
            processor = '$new_processor',
            gen = '$new_gen',
            ram = '$new_ram',
            hdd = '$new_hdd',
            ssd = '$new_ssd',
            vga = '$new_vga',
            tanggal_beli = '$new_tanggal_beli'
        WHERE id_barang = $id_barang
    ";

    if (mysqli_query($koneksi, $update_query)) {
        $_SESSION['notif'] = '✅ Data inventaris untuk **' . $new_nama_pengguna . '** berhasil diperbarui (termasuk tracking pindah divisi jika ada).';
        header('Location: data_inventaris.php');
        exit();
    } else {
        $message = '<div class="notification error">❌ Gagal memperbarui data. Error SQL: ' . mysqli_error($koneksi) . '</div>';
    }
}

// Lanjutkan dengan HTML untuk menampilkan form edit
// ... (Tambahkan bagian HTML untuk form edit di sini, gunakan data dari $data_barang)

// Tutup koneksi
mysqli_close($koneksi);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Data Inventaris</title>
    <style>
        /* Anda bisa meniru atau menggunakan CSS dari input_barang.php */
        body { font-family: Arial, sans-serif; padding: 20px; }
        .form-edit-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h3 { color: #007bff; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="date"], select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background-color: #0056b3; }
        .notification { padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid; }
        .error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <div class="form-edit-container">
        <h3>✍️ Edit Data Inventaris: <?php echo htmlspecialchars($data_barang['nama_pengguna']); ?></h3>
        <?php echo $message; ?>

        <form action="edit_barang.php?id=<?php echo $id_barang; ?>" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                
                <div>
                    <div class="form-group">
                        <label for="nama">Nama Pengguna:</label>
                        <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($data_barang['nama_pengguna']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="id_bagian">Bagian (Tracking Pindah Divisi):</label>
                        <select id="id_bagian" name="id_bagian" required>
                            <?php foreach ($bagian_list as $bagian): ?>
                                <option value="<?php echo htmlspecialchars($bagian['id_bagian']); ?>" 
                                    <?php echo ($data_barang['id_bagian'] == $bagian['id_bagian']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($bagian['nama_bagian']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_jenis">Jenis Perangkat:</label>
                        <select id="id_jenis" name="id_jenis" required>
                            <?php foreach ($jenis_list as $jenis): ?>
                                <option value="<?php echo htmlspecialchars($jenis['id_jenis']); ?>" 
                                    <?php echo ($data_barang['id_jenis'] == $jenis['id_jenis']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($jenis['nama_jenis']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tanggal_beli">Tanggal Pengadaan:</label>
                        <input type="date" id="tanggal_beli" name="tanggal_beli" value="<?php echo htmlspecialchars($data_barang['tanggal_beli']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="merek">Merek:</label>
                        <input type="text" id="merek" name="merek" value="<?php echo htmlspecialchars($data_barang['merek']); ?>" required>
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label for="processor">Processor:</label>
                        <input type="text" id="processor" name="processor" value="<?php echo htmlspecialchars($data_barang['processor']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gen">Generasi (GEN):</label>
                        <input type="text" id="gen" name="gen" value="<?php echo htmlspecialchars($data_barang['gen']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="ram">RAM (contoh: 8 GB):</label>
                        <input type="text" id="ram" name="ram" value="<?php echo htmlspecialchars($data_barang['ram']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="hdd">HDD (contoh: 1000 GB atau -):</label>
                        <input type="text" id="hdd" name="hdd" value="<?php echo htmlspecialchars($data_barang['hdd']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="ssd">SSD (contoh: 256 GB atau -):</label>
                        <input type="text" id="ssd" name="ssd" value="<?php echo htmlspecialchars($data_barang['ssd']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="vga">VGA:</label>
                        <input type="text" id="vga" name="vga" value="<?php echo htmlspecialchars($data_barang['vga']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group" style="text-align: right; margin-top: 20px;">
                <button type="submit" name="update_data" class="btn-submit">💾 Simpan Perubahan Data</button>
                <a href="data_inventaris.php" class="btn-submit" style="background-color: #6c757d;">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>