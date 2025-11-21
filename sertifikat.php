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
    $nomor_batch = 3;
    $tanggal_batch = '2025-09-27';
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

// CEK TEMPLATE DEPAN (WAJIB)
if (!file_exists($depan)) {
    die("Template depan tidak ditemukan: " . $depan);
}

// CEK TEMPLATE BELAKANG OPSIONAL
$belakang_ada = false;
if (!empty($current_template['file_belakang'])) {
    if (file_exists($belakang)) {
        $belakang_ada = true;
    }
}

// Fungsi untuk format tanggal Indonesia dengan hari
function tanggal_indo($tanggal)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
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

// Fungsi konversi angka ke Romawi
function angkaKeRomawi($angka)
{
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

// Fungsi untuk generate nomor sertifikat
function generate_nomor_sertifikat_by_hp($koneksi, $no_hp, $nomor_batch, $tahun)
{
    $check_existing = mysqli_query($koneksi, "SELECT nomor_sertifikat FROM daftar WHERE no_hp = '$no_hp' AND nomor_sertifikat IS NOT NULL");

    if (mysqli_num_rows($check_existing) > 0) {
        $existing_data = mysqli_fetch_assoc($check_existing);
        return $existing_data['nomor_sertifikat'];
    } else {
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

// Generate nomor sertifikat
$tahun_sertifikat = date('Y', strtotime($tanggal_batch));
$nomor_sertifikat = generate_nomor_sertifikat_by_hp($koneksi, $no_hp, $nomor_batch, $tahun_sertifikat);

// Simpan jika belum ada
if (empty($nomor_sertifikat_existing)) {
    mysqli_query($koneksi, "UPDATE daftar SET nomor_sertifikat = '$nomor_sertifikat' WHERE no_hp = '$no_hp'");
}

// buat PDF
$pdf = new FPDF('L', 'mm', [330, 210]);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);

// font
$pdf->AddFont('Pacifico', '', 'Pacifico.php');

// Halaman Depan
$pdf->AddPage();
$pdf->Image($depan, 0, 0, 330, 210);

// NOMOR SERTIFIKAT
$pdf->SetFont('helvetica', '', 16);
$pdf->SetTextColor(0, 0, 0);

$text_width = $pdf->GetStringWidth($nomor_sertifikat);
$spacing = 1;
$total_width = $text_width + ($spacing * (strlen($nomor_sertifikat) - 1));
$start_x = 80 + (170 - $total_width) / 2;
$current_x = $start_x;

for ($i = 0; $i < strlen($nomor_sertifikat); $i++) {
    $char = substr($nomor_sertifikat, $i, 1);
    $pdf->SetXY($current_x, 45);
    $pdf->Cell($pdf->GetStringWidth($char), 10, $char, 0, 0, 'L');
    $current_x += $pdf->GetStringWidth($char) + $spacing;
}

// Nama Peserta
$pdf->SetFont('Pacifico', '', 42);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(0, 85);
$pdf->Cell(330, 10, $nama, 0, 1, 'C');

$pdf->SetFont('helvetica', '', 18);

// Format tanggal pecah tahun
$ts = strtotime($tanggal_batch);
$tahun = date('Y', $ts);
$tanggal_tanpa_tahun = str_replace(" " . $tahun, "", $tanggal_sertifikat);

$base_x = 160;
$base_y = 143;

$pdf->SetXY($base_x, $base_y);
$lebar_tahun = $pdf->GetStringWidth($tahun);

$lebar_depan = $pdf->GetStringWidth($tanggal_tanpa_tahun);

$x_depan = $base_x - $lebar_depan - 2;

$pdf->SetXY($x_depan, $base_y);
$pdf->Cell($lebar_depan, 10, $tanggal_tanpa_tahun, 0, 0, 'L');

$pdf->SetXY($base_x, $base_y);
$pdf->Cell($lebar_tahun, 10, $tahun, 0, 0, 'L');

// ANGKA BATCH DEPAN
$pdf->SetFont('helvetica', 'B', 19);
$pdf->SetTextColor(202, 96, 22);
$pdf->SetXY(173, 135);
$nomor_batch_padded = str_pad($nomor_batch, 2, " ", STR_PAD_LEFT);
$pdf->Cell(30, 10, $nomor_batch_padded, 0, 1, 'C');

// Halaman Belakang HANYA JIKA ADA
if ($belakang_ada) {

    $pdf->AddPage();
    $pdf->Image($belakang, 0, 0, 330, 210);

    $pdf->SetFont('helvetica', 'B', 19);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(69, 29);
    $pdf->Cell(330, 10, $nomor_batch_padded, 0, 1, 'C');
}

// output pdf
$pdf->Output('I', "sertifikat_" . str_replace(' ', '_', $nama) . ".pdf");
exit;
