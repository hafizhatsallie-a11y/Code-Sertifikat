<?php
include 'koneksi.php';

$nama   = $_POST['nama_peserta'];
$email  = $_POST['email'];
$nohp   = $_POST['no_hp'];
$kursus = $_POST['kursus'];

// Cek duplikasi berdasarkan email dan kursus
$qcek = mysqli_query($koneksi, 
    "SELECT id FROM daftar WHERE email='$email' AND kursus='$kursus'"
);

if (mysqli_num_rows($qcek) > 0) {
    echo "Email ini sudah terdaftar untuk kursus yang sama.";
    exit;
}

// Simpan peserta
$q = mysqli_query($koneksi, "
    INSERT INTO daftar (nama_peserta, email, no_hp, kursus)
    VALUES ('$nama','$email','$nohp','$kursus')
");

if ($q) {
    echo "Pendaftaran berhasil. Silakan minta token.";
} else {
    echo "Gagal: " . mysqli_error($koneksi);
}
