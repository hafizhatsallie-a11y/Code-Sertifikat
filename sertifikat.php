<?php
session_start();
require 'fpdf186/fpdf.php';
include 'koneksi.php';

// pastikan user sudah lolos verifikasi token
if (!isset($_SESSION['akses_sertifikat'])) {
    die("Akses tidak sah. Silakan input token.");
}

// Hapus semua output buffer
while (ob_get_level()) {
    ob_end_clean();
}

$no_hp = mysqli_real_escape_string($koneksi, $_SESSION['akses_sertifikat']);

// ambil data peserta dari tabel daftar
$q = mysqli_prepare($koneksi, "
    SELECT nama_peserta, kursus, id, nomor_sertifikat
    FROM daftar
    WHERE no_hp = ?
    LIMIT 1
");
mysqli_stmt_bind_param($q, "s", $no_hp);
mysqli_stmt_execute($q);
$res = mysqli_stmt_get_result($q);

if (mysqli_num_rows($res) == 0) {
    die("Data peserta tidak ditemukan.");
}

$d = mysqli_fetch_assoc($res);

$nama = $d['nama_peserta'];
$kursus = $d['kursus'];
$id_peserta = $d['id'];
$nomor_sertifikat_existing = $d['nomor_sertifikat'];

// AMBIL DATA BATCH AKTIF DARI DATABASE
$batch_query = "SELECT * FROM batch WHERE aktif = 1 ORDER BY id DESC LIMIT 1";
$batch_result = mysqli_query($koneksi, $batch_query);
$current_batch = mysqli_fetch_assoc($batch_result);

// Jika tidak ada batch aktif, gunakan default
if (!$current_batch) {
    $nomor_batch = 3; // Default fallback
    $tanggal_batch = '2025-09-27'; // Default fallback
} else {
    $nomor_batch = $current_batch['nomor_batch'];
    $tanggal_batch = $current_batch['tanggal_mulai'];
}

// AMBIL DATA TEMPLATE AKTIF DARI DATABASE
$template_query = "SELECT * FROM template WHERE aktif = 1 ORDER BY id DESC LIMIT 1";
$template_result = mysqli_query($koneksi, $template_query);
$current_template = mysqli_fetch_assoc($template_result);

// Jika tidak ada template aktif, gunakan default
if (!$current_template) {
    $depan = "foto/sertifikat_kursus_shopee (depan).jpg";
    $belakang = "foto/sertifikat_kursus_shopee (belakang).jpg";
} else {
    $depan = "foto/" . $current_template['file_depan'];
    $belakang = "foto/" . $current_template['file_belakang'];
}

// cek template
if (!file_exists($depan)) die("Template depan tidak ditemukan: " . $depan);
if (!file_exists($belakang)) die("Template belakang tidak ditemukan: " . $belakang);

// Fungsi untuk format tanggal Indonesia dengan hari
function tanggal_indo($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $hari = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $timestamp = strtotime($tanggal);
    $hari_inggris = date('l', $timestamp);
    $tanggal_num = date('j', $timestamp);
    $bulan_num = date('n', $timestamp);
    $tahun = date('Y', $timestamp);
    
    return $hari[$hari_inggris] . ', ' . $tanggal_num . ' ' . $bulan[$bulan_num] . ' ' . $tahun;
}

// Fungsi untuk konversi angka ke Romawi
function angkaKeRomawi($angka) {
    $romawi = '';
    $nilai_romawi = [
        'M' => 1000,
        'CM' => 900,
        'D' => 500,
        'CD' => 400,
        'C' => 100,
        'XC' => 90,
        'L' => 50,
        'XL' => 40,
        'X' => 10,
        'IX' => 9,
        'V' => 5,
        'IV' => 4,
        'I' => 1
    ];
    
    foreach ($nilai_romawi as $simbol => $nilai) {
        $jumlah = intval($angka / $nilai);
        $romawi .= str_repeat($simbol, $jumlah);
        $angka = $angka % $nilai;
    }
    
    return $romawi;
}

// Fungsi untuk generate nomor sertifikat berdasarkan no_hp dengan format 0980/SAE/X/2025
function generate_nomor_sertifikat_by_hp($koneksi, $no_hp, $nomor_batch, $tahun) {
    // Cek apakah user ini sudah punya nomor sertifikat
    $check_existing = mysqli_query($koneksi, "SELECT nomor_sertifikat FROM daftar WHERE no_hp = '$no_hp' AND nomor_sertifikat IS NOT NULL");
    
    if (mysqli_num_rows($check_existing) > 0) {
        // Jika sudah ada, gunakan nomor yang sama
        $existing_data = mysqli_fetch_assoc($check_existing);
        return $existing_data['nomor_sertifikat'];
    } else {
        // Jika belum ada, generate nomor baru berdasarkan urutan pendaftaran
        $get_urutan_query = mysqli_query($koneksi, "
            SELECT COUNT(*) as urutan 
            FROM daftar 
            WHERE id <= (SELECT id FROM daftar WHERE no_hp = '$no_hp' LIMIT 1)
            AND nomor_sertifikat IS NOT NULL
        ");
        $urutan_data = mysqli_fetch_assoc($get_urutan_query);
        $counter = $urutan_data['urutan'] + 1;
        
        $nomor_urut = str_pad($counter, 4, "0", STR_PAD_LEFT);
        $romawi_batch = angkaKeRomawi($nomor_batch);
        
        return $nomor_urut . "/SAE/" . $romawi_batch . "/" . $tahun;
    }
}

// Format tanggal untuk sertifikat
$tanggal_sertifikat = tanggal_indo($tanggal_batch);

// GENERATE NOMOR SERTIFIKAT BERDASARKAN no_hp DENGAN FORMAT 0980/SAE/X/2025
$tahun_sertifikat = date('Y', strtotime($tanggal_batch));
$nomor_sertifikat = generate_nomor_sertifikat_by_hp($koneksi, $no_hp, $nomor_batch, $tahun_sertifikat);

// Simpan nomor sertifikat ke database jika belum ada
if (empty($nomor_sertifikat_existing)) {
    mysqli_query($koneksi, "UPDATE daftar SET nomor_sertifikat = '$nomor_sertifikat' WHERE no_hp = '$no_hp'");
}

// buat PDF - TANPA MENGUBAH UKURAN TEMPLATE
$pdf = new FPDF('L', 'mm', [330, 210]);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);

// Daftarkan font Pacifico terlebih dahulu
$pdf->AddFont('Pacifico','','Pacifico.php');

// Halaman Depan
$pdf->AddPage();
$pdf->Image($depan, 0, 0, 330, 210);

// NOMOR SERTIFIKAT - format: 0980/SAE/X/2025 - DENGAN LETTER SPACING TANPA MENGUBAH TEMPLATE
$pdf->SetFont('helvetica', '', 16);
$pdf->SetTextColor(0, 0, 0);

// Hitung lebar teks untuk posisi yang tepat
$text_width = $pdf->GetStringWidth($nomor_sertifikat);
$spacing = 1; // Jarak antar huruf dalam mm
$total_width = $text_width + ($spacing * (strlen($nomor_sertifikat) - 1));

// Hitung posisi X agar tetap di tengah area 170mm
$start_x = 80 + (170 - $total_width) / 2;
$current_x = $start_x;

// Tulis setiap karakter dengan spacing
for ($i = 0; $i < strlen($nomor_sertifikat); $i++) {
    $char = substr($nomor_sertifikat, $i, 1);
    $pdf->SetXY($current_x, 45);
    $pdf->Cell($pdf->GetStringWidth($char), 10, $char, 0, 0, 'L');
    $current_x += $pdf->GetStringWidth($char) + $spacing;
}

// Nama Peserta - di tengah halaman TANPA strtoupper()
$pdf->SetFont('Pacifico', '', 42);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(0, 85);
$pdf->Cell(330, 10, $nama, 0, 1, 'C'); // HAPUS strtoupper() di sini

// TANGGAL - DENGAN FONT MONOSPACE UNTUK POSISI TETAP
$pdf->SetFont('helvetica', '', 17);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(104, 143);
$pdf->Cell(100, 10, $tanggal_sertifikat, 0, 1, 'L');

// ANGKKA BATCH DI HALAMAN DEPAN (tetap angka biasa)
$pdf->SetFont('helvetica', 'B', 19);
// Halaman Belakang - hanya angka batch (tetap angka biasa)
$pdf->AddPage();
$pdf->Image($belakang, 0, 0, 330, 210);

// ANGKKA BATCH DI HALAMAN BELAKANG (tetap angka biasa)
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(68, 29);
$nomor_batch_padded = str_pad($nomor_batch, 2, " ", STR_PAD_LEFT);
$pdf->Cell(330, 10, $nomor_batch_padded, 0, 1, 'C');

// tampilkan ke browser
$pdf->Output('I', "sertifikat_" . str_replace(' ', '_', $nama) . ".pdf");
exit;
?>