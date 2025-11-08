<?php
// inventaris.php (atau api.php) - API Tampil Data Inventaris (JSON)

// 1. Konfigurasi Header
// Beritahu browser bahwa respons adalah JSON
header('Content-Type: application/json');
// Izinkan akses dari domain manapun (penting untuk tes di localhost atau domain berbeda)
header('Access-Control-Allow-Origin: *'); 

// 2. Sertakan Koneksi Database
include 'db_config.php'; 

// 3. Query Pengambilan Data
// Menggabungkan data dari tabel barang, bagian, master_building, dan jenis_barang
$sql = "
    SELECT 
        brg.id_barang AS ID, 
        brg.merek AS nama_pc, 
        brg.nama_pengguna AS user, 
        
        -- Menggabungkan nama building dan bagian untuk kolom 'Lokasi'
        CONCAT(bld.nama_building, ' - ', bag.nama_bagian) AS lokasi, 
        
        -- Menggabungkan detail spesifikasi untuk kolom 'Spesifikasi'
        CONCAT('Proc: ', brg.processor, ' (Gen:', brg.gen, ') | RAM: ', brg.ram, ' | Str: SSD:', brg.ssd, '/HDD:', brg.hdd) AS spesifikasi
        
    FROM 
        barang brg
    LEFT JOIN 
        bagian bag ON brg.id_bagian = bag.id_bagian 
    LEFT JOIN 
        master_building bld ON bag.id_building = bld.id_building
    LEFT JOIN 
        jenis_barang jns ON brg.id_jenis = jns.id_jenis
    ORDER BY 
        brg.id_barang DESC
";

$result = mysqli_query($koneksi, $sql);

if ($result) {
    // Ambil semua hasil query dan ubah ke array asosiatif
    $data_inventaris = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // 4. Keluarkan data dalam format JSON
    echo json_encode($data_inventaris);
} else {
    // 5. Tangani Error
    // Set response code menjadi 500 (Internal Server Error)
    http_response_code(500);
    echo json_encode(["error" => "Gagal mengambil data inventaris: " . mysqli_error($koneksi)]);
}

mysqli_close($koneksi);
?>