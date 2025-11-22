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

// ambil data peserta
$q = mysqli_prepare($koneksi, "
    SELECT nama_peserta, kursus, id, nomor_sertifikat, tanggal_akses_pertama
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
$tanggal_akses_pertama = $d['tanggal_akses_pertama'];

// jika pertama kali akses sertifikat, simpan tanggal akses hari ini
if (empty($tanggal_akses_pertama)) {
    $tanggal_akses_pertama = date('Y-m-d');
    mysqli_query($koneksi, "
        UPDATE daftar SET tanggal_akses_pertama = '$tanggal_akses_pertama'
        WHERE id = '$id_peserta'
    ");
}

// AMBIL TEMPLATE AKTIF
$template_query = "SELECT * FROM template WHERE aktif = 1 ORDER BY id DESC LIMIT 1";
$template_result = mysqli_query($koneksi, $template_query);
$current_template = mysqli_fetch_assoc($template_result);

if (!$current_template) {
    $depan = "foto/sertifikat_kursus_shopee (depan).jpg";
    $belakang = "foto/sertifikat_kursus_shopee (belakang).jpg";
} else {
    $depan = "foto/" . $current_template['file_depan'];
    $belakang = "foto/" . $current_template['file_belakang'];
}

// CEK DEPAN
if (!file_exists($depan)) {
    die("Template depan tidak ditemukan: " . $depan);
}

// CEK BELAKANG OPSIONAL
$belakang_ada = false;
if (!empty($current_template['file_belakang']) && file_exists($belakang)) {
    $belakang_ada = true;
}

// FUNGSI TANGGAL INDO
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
    return $hari[date('l', $timestamp)] . ', ' . date('j', $timestamp) . ' ' . $bulan[date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

// KONVERSI ROMAWI
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
        while ($angka >= $nilai) {
            $angka -= $nilai;
            $romawi .= $simbol;
        }
    }

    return $romawi;
}

// AMBIL BULAN & TAHUN DARI TANGGAL AKSES PERTAMA
$bulan = date('n', strtotime($tanggal_akses_pertama));
$bulan_romawi = angkaKeRomawi($bulan);
$tahun_sertifikat = date('Y', strtotime($tanggal_akses_pertama));

// GENERATE NOMOR SERTIFIKAT
function generate_nomor_sertifikat_by_hp($koneksi, $no_hp, $bulan_romawi, $tahun)
{
    // jika sudah ada, langsung pakai
    $cek = mysqli_query($koneksi, "
        SELECT nomor_sertifikat FROM daftar
        WHERE no_hp = '$no_hp' AND nomor_sertifikat IS NOT NULL
    ");

    if (mysqli_num_rows($cek) > 0) {
        $row = mysqli_fetch_assoc($cek);
        return $row['nomor_sertifikat'];
    }

    // jika belum ada, generate baru berdasarkan jumlah sertifikat sebelumnya
    $hit = mysqli_query($koneksi, "
        SELECT COUNT(*) AS jml FROM daftar
        WHERE nomor_sertifikat IS NOT NULL
    ");
    $num = mysqli_fetch_assoc($hit)['jml'] + 1;

    $urut = str_pad($num, 4, "0", STR_PAD_LEFT);

    return $urut . "/SAE/" . $bulan_romawi . "/" . $tahun;
}

$nomor_sertifikat = generate_nomor_sertifikat_by_hp(
    $koneksi,
    $no_hp,
    $bulan_romawi,
    $tahun_sertifikat
);

// simpan jika baru
if (empty($nomor_sertifikat_existing)) {
    mysqli_query($koneksi, "
        UPDATE daftar SET nomor_sertifikat = '$nomor_sertifikat'
        WHERE no_hp = '$no_hp'
    ");
}

// SET TANGGAL CETAK
$tanggal_sertifikat = tanggal_indo($tanggal_akses_pertama);

// BUAT PDF
$pdf = new FPDF('L', 'mm', [330, 210]);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);

$pdf->AddFont('Pacifico', '', 'Pacifico.php');

// HALAMAN DEPAN
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

// NAMA PESERTA
$pdf->SetFont('Pacifico', '', 42);
$pdf->SetXY(0, 85);
$pdf->Cell(330, 10, $nama, 0, 1, 'C');

// TANGGAL
$pdf->SetFont('helvetica', '', 18);

$ts = strtotime($tanggal_akses_pertama);
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

// ANGKA DI TENGAH (TETAP MENGGUNAKAN NOMOR BATCH UNTUK DESAIN)
$pdf->SetFont('helvetica', 'B', 19);
$pdf->SetTextColor(202, 96, 22);
$pdf->SetXY(173, 135);

// ambil nomor batch lama karena ini hanya tampilan visual, bukan logika sertifikat
$nomor_batch_padded = str_pad(1, 2, " ", STR_PAD_LEFT);
$pdf->Cell(30, 10, $nomor_batch_padded, 0, 1, 'C');

// HALAMAN BELAKANG
if ($belakang_ada) {
    $pdf->AddPage();
    $pdf->Image($belakang, 0, 0, 330, 210);

    $pdf->SetFont('helvetica', 'B', 19);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(69, 29);
    $pdf->Cell(330, 10, $nomor_batch_padded, 0, 1, 'C');
}

$pdf->Output('I', "sertifikat_" . str_replace(' ', '_', $nama) . ".pdf");
exit;
