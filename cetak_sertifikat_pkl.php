<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['peserta'])) {
    die("❌ Data peserta belum diisi.");
}

$template_path = 'uploads/template.jpg';
if (!file_exists($template_path)) {
    die("❌ Template tidak ditemukan.");
}

require_once 'fpdf186/fpdf.php';
$data = $_SESSION['peserta'];

/* ===============================
   PDF A4 LANDSCAPE (RASIO ASLI)
   =============================== */
$pdf = new FPDF('L', 'mm', 'A4'); // 297 x 210
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);

/* ===============================
   REGISTER FONT (WAJIB)
   =============================== */
$pdf->AddFont('Pacifico', '', 'Pacifico.php');

$pdf->AddPage();

/* ===============================
   TEMPLATE
   =============================== */
$pdf->Image($template_path, 0, 0, 297, 210);

/* ===============================
   FONT SIZE
   =============================== */
$fs_nomor   = 14;
$fs_nama    = 38;
$fs_kecil   = 15;
$fs_isi     = 17;
$fs_ttd     = 14;

/* ===============================
   ISI SERTIFIKAT
   =============================== */

// Nomor Sertifikat
$pdf->SetFont('Arial', '', $fs_nomor);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(2, 70);
$pdf->Cell(297, 10, $data['nomor'], 0, 0, 'C');

// Nama Peserta (PACIFICO – TANPA BOLD)
$pdf->SetFont('Pacifico', '', $fs_nama);
$pdf->SetTextColor(0, 0, 139);
$pdf->SetXY(0, 90);
$pdf->Cell(297, 18, strtoupper($data['nama']), 0, 0, 'C');

// Institusi
$pdf->SetFont('Arial', '', $fs_kecil);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(0, 106);
$pdf->Cell(297, 10, '( STMIK Kristen Neumann Indonesia )', 0, 0, 'C');

// Kalimat utama
$pdf->SetFont('Arial', '', $fs_isi);
$pdf->SetXY(0, 120);
$pdf->Cell(
    297,
    10,
    'Telah Melaksanakan Magang di ' . $data['lembaga'],
    0,
    0,
    'C'
);

// Periode
$pdf->SetXY(0, 138);
$pdf->Cell(
    297,
    10,
    'Selama ' . $data['lama'] . ', Mulai ' . $data['periode'],
    0,
    0,
    'C'
);

/* ===============================
   TANDA TANGAN
   =============================== */
$y_ttd = 165;

$pdf->SetFont('Arial', '', $fs_ttd);

// Lokasi & tanggal
$pdf->SetXY(170, $y_ttd);
$pdf->Cell(80, 10, $data['lokasi'], 0, 0, 'C');

// Garis tanda tangan
$pdf->SetLineWidth(0.7);
$pdf->Line(40, $y_ttd + 18, 130, $y_ttd + 18);
$pdf->Line(165, $y_ttd + 18, 255, $y_ttd + 18);

/* ===============================
   OUTPUT
   =============================== */
$filename = 'Sertifikat_' . preg_replace('/[^A-Za-z0-9]/', '_', $data['nama']) . '.pdf';
$pdf->Output('I', $filename);
exit;
