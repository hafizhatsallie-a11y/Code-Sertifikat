<?php
session_start();
include 'koneksi.php';

// Cek apakah user sudah login / selesai daftar
if (!isset($_SESSION['no_hp'])) {
    die("Anda belum terdaftar.");
}

$no_hp = $_SESSION['no_hp'];

// Ambil data peserta
$q_peserta = mysqli_query($koneksi, "SELECT * FROM daftar WHERE no_hp = '$no_hp' LIMIT 1");
if (mysqli_num_rows($q_peserta) == 0) {
    die("Data peserta tidak ditemukan.");
}

$d = mysqli_fetch_assoc($q_peserta);

// Ambil batch aktif
$q_batch = mysqli_query($koneksi, "SELECT tanggal_mulai FROM batch WHERE aktif = 1 LIMIT 1");
$batch = mysqli_fetch_assoc($q_batch);

if (!$batch) {
    die("Batch belum diatur oleh admin.");
}

$tanggal_mulai = $batch['tanggal_mulai'];
$hari_ini = date('Y-m-d');

// Jika hari ini masih sebelum tanggal pelatihan
if ($hari_ini < $tanggal_mulai) {
    echo "
        <h2>Program pelatihan belum dimulai</h2>
        <p>Jadwal pelatihan anda: <b>" . $tanggal_mulai . "</b></p>
        <p>Anda dapat mengakses token setelah program pelatihan dimulai.</p>
    ";
    exit;
}

// Jika sudah melewati tanggal mulai, tampilkan halaman input token
?>

<!DOCTYPE html>
<html>
<head>
    <title>Input Token Sertifikat</title>
</head>
<body>

<h2>Input Token</h2>
<form method="POST" action="proses_token.php">
    <label>Masukkan Token:</label><br>
    <input type="text" name="token" required><br><br>
    <button type="submit">Verifikasi</button>
</form>

</body>
</html>
