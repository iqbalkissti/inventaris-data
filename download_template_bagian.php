<?php
// download_template_bagian.php
// File ini berfungsi untuk menghasilkan dan memaksa unduhan (download) template CSV 
// untuk master bagian.

// 1. Definisikan Nama File yang Akan Diunduh
$filename = "template_master_bagian_" . date('Ymd') . ".csv";

// 2. Definisikan Header Kolom CSV
// Pastikan header ini sesuai dengan kolom yang Anda harapkan untuk diimpor.
$header = ['NAMA_BAGIAN']; 

// 3. Set Header HTTP untuk Memaksa Unduhan File CSV
header('Content-Type: text/csv');
// Memberitahu browser untuk mengunduh file dengan nama yang telah ditentukan
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// 4. Buka Output Stream dan Tulis Data
$output = fopen('php://output', 'w');

// Tulis Header CSV (baris pertama)
// fputcsv akan memformat array menjadi baris CSV yang benar
fputcsv($output, $header);

// Anda bisa menambahkan baris contoh data di sini (opsional)
// Contoh: fputcsv($output, ['IT']); 
// Contoh: fputcsv($output, ['HRD']);

// Tutup file stream
fclose($output);

// Hentikan eksekusi script
exit();
?>