<?php
// download_template_barang.php

// Tentukan nama file
$filename = 'template_inventaris_barang.csv';

// Data header kolom CSV
$header_fields = [
    'nama_pengguna',
    'nama_bagian',
    'tanggal_beli (YYYY-MM-DD)', // Penting: informasikan format tanggal
    'ram',
    'processor',
    'gen',
    'merek',
    'hdd',
    'ssd',
    'vga',
    'nama_jenis'
];

// Data contoh baris pertama (Opsional, tapi sangat disarankan sebagai panduan)
$sample_data = [
    'ANDI PRATAMA',
    'IT',
    '2024-05-10',
    '16 GB',
    'Core i7',
    'Gen 12',
    'DELL OptiPlex',
    '-',
    '512 GB',
    'Intel UHD Graphics',
    'PC DESKTOP'
];

// --- Pengaturan Header untuk Download CSV ---
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Buka output stream
$output = fopen('php://output', 'w');

// Tulis Header Kolom
fputcsv($output, $header_fields);

// Tulis Baris Contoh (Opsional: hapus baris ini jika Anda hanya ingin header)
fputcsv($output, $sample_data);

// Tutup output stream
fclose($output);

exit();
?>