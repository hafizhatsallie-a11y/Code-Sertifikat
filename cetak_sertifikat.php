<?php
require 'fpdf186/fpdf.php';
include 'koneksi.php';

// Hapus output buffer supaya PDF tidak error
while (ob_get_level()) {
    ob_end_clean();
}

if (empty($_GET['email'])) {
    die("Email tidak diberikan.");
}

$email = mysqli_real_escape_string($koneksi, $_GET['email']);

// Ambil data peserta
$q = mysqli_query($koneksi, "
    SELECT nama_peserta, kursus, id, nomor_sertifikat, no_hp
    FROM daftar 
    WHERE email = '$email' 
    LIMIT 1
");

if (mysqli_num_rows($q) == 0) {
    die("Data peserta tidak ditemukan.");
}

$d = mysqli_fetch_assoc($q);

$nama = $d['nama_peserta'];
$kursus = $d['kursus'];
$nomor_sertifikat = $d['nomor_sertifikat'];
$no_hp = $d['no_hp'];

// Ambil batch aktif
$batch = mysqli_query($koneksi, "SELECT * FROM batch WHERE aktif = 1 ORDER BY id DESC LIMIT 1");
$batch_data = mysqli_fetch_assoc($batch);

$nomor_batch = $batch_data ? $batch_data['nomor_batch'] : 3;
$tanggal_batch = $batch_data ? $batch_data['tanggal_mulai'] : '2025-09-27';

// Ambil template aktif
$temp = mysqli_query($koneksi, "SELECT * FROM template WHERE aktif = 1 ORDER BY id DESC LIMIT 1");
$t = mysqli_fetch_assoc($temp);

$depan = $t ? "foto/" . $t['file_depan'] : "foto/sertifikat_kursus_shopee (depan).jpg";
$belakang = $t ? "foto/" . $t['file_belakang'] : "foto/sertifikat_kursus_shopee (belakang).jpg";

$belakang_ada = file_exists($belakang);

// Fungsi tanggal Indo
function tanggal_indo_lengkap($tgl) {
    $bulan = [
        1=>'Januari','Februari','Maret','April','Mei','Juni',
        'Juli','Agustus','September','Oktober','November','Desember'
    ];
    $hari = [
        'Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa',
        'Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'
    ];
    $ts = strtotime($tgl);
    return $hari[date('l',$ts)] . ", " . date('j',$ts) . " " . $bulan[date('n',$ts)] . " " . date('Y',$ts);
}

$tanggal_sertifikat = tanggal_indo_lengkap($tanggal_batch);

// Buat PDF
$pdf = new FPDF('L','mm',[330,210]);
$pdf->SetMargins(0,0,0);
$pdf->SetAutoPageBreak(false);

$pdf->AddFont('Pacifico','','Pacifico.php');

// Halaman Depan
$pdf->AddPage();
$pdf->Image($depan,0,0,330,210);

// Nomor sertifikat
$pdf->SetFont('helvetica','',16);
$pdf->SetTextColor(0,0,0);

$text_width = $pdf->GetStringWidth($nomor_sertifikat);
$spacing = 1;
$total_width = $text_width + ($spacing * (strlen($nomor_sertifikat)-1));
$start_x = 80 + (170 - $total_width) / 2;
$current_x = $start_x;

for($i=0;$i<strlen($nomor_sertifikat);$i++){
    $char = $nomor_sertifikat[$i];
    $pdf->SetXY($current_x,45);
    $pdf->Cell($pdf->GetStringWidth($char),10,$char,0,0,'L');
    $current_x += $pdf->GetStringWidth($char) + $spacing;
}

// Nama
$pdf->SetFont('Pacifico','',42);
$pdf->SetXY(0,85);
$pdf->Cell(330,10,$nama,0,1,'C');

// Tanggal
$pdf->SetFont('helvetica','',18);

$ts = strtotime($tanggal_batch);
$tahun = date('Y',$ts);
$tanggal_tanpa_tahun = str_replace(" ".$tahun,"",$tanggal_sertifikat);

$base_x = 160;
$base_y = 143;

$pdf->SetXY($base_x - $pdf->GetStringWidth($tanggal_tanpa_tahun) - 2, $base_y);
$pdf->Cell(0,10,$tanggal_tanpa_tahun,0,0,'L');

$pdf->SetXY($base_x, $base_y);
$pdf->Cell(0,10,$tahun,0,0,'L');

// Batch
$pdf->SetFont('helvetica','B',19);
$pdf->SetTextColor(202,96,22);
$pdf->SetXY(173,135);
$pdf->Cell(30,10,str_pad($nomor_batch,2," ",STR_PAD_LEFT),0,1,'C');

// Halaman Belakang jika ada
if ($belakang_ada) {
    $pdf->AddPage();
    $pdf->Image($belakang,0,0,330,210);

    $pdf->SetFont('helvetica','B',19);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(69,29);
    $pdf->Cell(330,10,str_pad($nomor_batch,2," ",STR_PAD_LEFT),0,1,'C');
}

$pdf->Output('I', "sertifikat_".$nama.".pdf");
exit;
