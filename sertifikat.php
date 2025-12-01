<?php
session_start();
require 'fpdf186/fpdf.php';
include 'koneksi.php';

// cek akses
if (!isset($_SESSION['akses_sertifikat'])) {
    die("Akses tidak sah. Silakan input token.");
}

// bersihkan buffer
while (ob_get_level()) {
    ob_end_clean();
}

$no_hp = mysqli_real_escape_string($koneksi, $_SESSION['akses_sertifikat']);

/* ============================================
   AMBIL DATA PESERTA
============================================ */
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

$nama  = $d['nama_peserta'];
$kursus = $d['kursus'];
$id_peserta = $d['id'];
$nomor_sertifikat_existing = $d['nomor_sertifikat'];
$tanggal_akses_pertama = $d['tanggal_akses_pertama'];

/* ============================================
   SIMPAN TANGGAL AKSES PERTAMA
============================================ */
if (empty($tanggal_akses_pertama)) {
    $tanggal_akses_pertama = date('Y-m-d');
    mysqli_query($koneksi, "
        UPDATE daftar SET tanggal_akses_pertama = '$tanggal_akses_pertama'
        WHERE id = '$id_peserta'
    ");
}

/* ============================================
   AMBIL BATCH AKTIF
============================================ */
$qBatch = mysqli_query($koneksi, "
    SELECT nomor_batch, tanggal_mulai
    FROM batch
    WHERE aktif = 1
    LIMIT 1
");

$dBatch = mysqli_fetch_assoc($qBatch);

$nomor_batch = $dBatch ? $dBatch['nomor_batch'] : 1;
$tanggal_mulai_batch = $dBatch ? $dBatch['tanggal_mulai'] : date('Y-m-d');

// angka batch untuk tampilan
$nomor_batch_padded = str_pad($nomor_batch, 2, " ", STR_PAD_LEFT);

/* ============================================
   AMBIL TEMPLATE SERTIFIKAT
============================================ */
$template_result = mysqli_query($koneksi, "
    SELECT * FROM template WHERE aktif = 1 ORDER BY id DESC LIMIT 1
");
$current_template = mysqli_fetch_assoc($template_result);

if (!$current_template) {
    $depan = "foto/sertifikat_kursus_shopee (depan).jpg";
    $belakang = "foto/sertifikat_kursus_shopee (belakang).jpg";
} else {
    $depan = "foto/" . $current_template['file_depan'];
    $belakang = !empty($current_template['file_belakang'])
                ? "foto/" . $current_template['file_belakang']
                : null;
}

if (!file_exists($depan)) {
    die("Template depan tidak ditemukan: " . $depan);
}

$belakang_ada = $belakang && file_exists($belakang);

/* ============================================
   FUNGSI
============================================ */
function tanggal_indo($tanggal)
{
    $bulan = [
        1 => 'Januari','Februari','Maret','April','Mei','Juni','Juli',
        'Agustus','September','Oktober','November','Desember'
    ];

    $hari = [
        'Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
        'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'
    ];

    $ts = strtotime($tanggal);
    return $hari[date('l', $ts)] . ", " . date('j', $ts) . " " . $bulan[date('n', $ts)] . " " . date('Y', $ts);
}

function angkaKeRomawi($angka)
{
    $map = ['M'=>1000,'CM'=>900,'D'=>500,'CD'=>400,'C'=>100,'XC'=>90,'L'=>50,'XL'=>40,'X'=>10,'IX'=>9,'V'=>5,'IV'=>4,'I'=>1];
    $res = '';
    foreach ($map as $rom => $val) {
        while ($angka >= $val) {
            $angka -= $val;
            $res .= $rom;
        }
    }
    return $res;
}

/* ============================================
   NOMOR SERTIFIKAT
============================================ */
$bulan = date('n', strtotime($tanggal_akses_pertama));
$bulan_romawi = angkaKeRomawi($bulan);
$tahun_sertifikat = date('Y', strtotime($tanggal_akses_pertama));

function generate_nomor_sertifikat_by_hp($koneksi, $no_hp, $bulan_romawi, $tahun)
{
    $cek = mysqli_query($koneksi, "
        SELECT nomor_sertifikat 
        FROM daftar 
        WHERE no_hp = '$no_hp' AND nomor_sertifikat IS NOT NULL
    ");

    if (mysqli_num_rows($cek) > 0) {
        return mysqli_fetch_assoc($cek)['nomor_sertifikat'];
    }

    $hit = mysqli_query($koneksi, "
    SELECT COUNT(*) AS jml FROM daftar
    WHERE nomor_sertifikat IS NOT NULL
");

$base = 1;
$num = mysqli_fetch_assoc($hit)['jml'];
$urut = str_pad($base + $num, 4, "0", STR_PAD_LEFT);


    return $urut . "/SAE/" . $bulan_romawi . "/" . $tahun;
}

$nomor_sertifikat = generate_nomor_sertifikat_by_hp(
    $koneksi, $no_hp, $bulan_romawi, $tahun_sertifikat
);

if (empty($nomor_sertifikat_existing)) {
    mysqli_query($koneksi, "
        UPDATE daftar SET nomor_sertifikat = '$nomor_sertifikat'
        WHERE no_hp = '$no_hp'
    ");
}

/* ============================================
   TANGGAL SERTIFIKAT (PAKAI TANGGAL MULAI BATCH)
============================================ */
$tanggal_sertifikat = tanggal_indo($tanggal_mulai_batch);

$ts_tanggal = strtotime($tanggal_mulai_batch);
$tahun_tanggal = date('Y', $ts_tanggal);

// contoh: "Sabtu, 11 Januari 2025"
// hapus tahun agar dipisah seperti template Anda
$tanggal_tanpa_tahun = str_replace(" " . $tahun_tanggal, "", $tanggal_sertifikat);

/* ============================================
   BUAT PDF
============================================ */
$pdf = new FPDF('L', 'mm', [330, 210]);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);

$pdf->AddFont('Pacifico', '', 'Pacifico.php');

/* ============================================
   HALAMAN DEPAN
============================================ */
$pdf->AddPage();
$pdf->Image($depan, 0, 0, 330, 210);

// nomor sertifikat
$pdf->SetFont('helvetica', '', 16);
$pdf->SetTextColor(0, 0, 0);

$spacing = 1;
$text_width = $pdf->GetStringWidth($nomor_sertifikat);
$total_width = $text_width + ($spacing * (strlen($nomor_sertifikat) - 1));

$page_center_x = 330 / 2;
$start_x = $page_center_x - ($total_width / 2); // titik awal di tengah
$current_x = $start_x;
$y_position = 45; // ubah sesuai kebutuhan posisi vertikal

for ($i = 0; $i < strlen($nomor_sertifikat); $i++) {
    $char = substr($nomor_sertifikat, $i, 1);
    $char_width = $pdf->GetStringWidth($char);

    $pdf->SetXY($current_x, $y_position);
    $pdf->Cell($char_width, 10, $char, 0, 0, 'L');

    $current_x += $char_width + $spacing;
}


// nama peserta
$pdf->SetFont('Pacifico', '', 42);
$pdf->SetXY(0, 85);
$pdf->Cell(330, 10, $nama, 0, 1, 'C');

// tanggal depan
$pdf->SetFont('helvetica', '', 18);
$base_x = 160;
$base_y = 143;

$lebar_depan = $pdf->GetStringWidth($tanggal_tanpa_tahun);
$lebar_tahun = $pdf->GetStringWidth($tahun_tanggal);

$pdf->SetXY($base_x - $lebar_depan - 2, $base_y);
$pdf->Cell($lebar_depan, 10, $tanggal_tanpa_tahun, 0, 0, 'L');

$pdf->SetXY($base_x, $base_y);
$pdf->Cell($lebar_tahun, 10, $tahun_tanggal, 0, 0, 'L');

// batch visual
$pdf->SetFont('helvetica', 'B', 19);
$pdf->SetTextColor(202, 96, 22);
$pdf->SetXY(172, 135);
$pdf->Cell(30, 10, $nomor_batch_padded, 0, 1, 'C');

/* ============================================
   HALAMAN BELAKANG
============================================ */
if ($belakang_ada) {
    $pdf->AddPage();
    $pdf->Image($belakang, 0, 0, 330, 210);

    // batch untuk halaman belakang
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(68, 29);
    $pdf->Cell(330, 10, $nomor_batch_padded, 0, 1, 'C');
}

$pdf->Output('I', "sertifikat_" . str_replace(' ', '_', $nama) . ".pdf");
exit;
