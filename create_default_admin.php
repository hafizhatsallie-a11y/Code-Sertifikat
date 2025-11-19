<?php
// Jalankan sekali untuk membuat akun default adminsae (profit11m!)
include 'koneksi.php';

$username = 'adminsae';
$password_plain = 'profit11m!';

// Cek apakah user sudah ada
$q = mysqli_query($koneksi, "SELECT id FROM admin WHERE username='" . mysqli_real_escape_string($koneksi, $username) . "' LIMIT 1");
if (mysqli_num_rows($q) > 0) {
    echo "User sudah ada. Kalau mau reset password, hapus baris dan jalankan lagi.";
    exit;
}

// Hash password dan insert
$hash = password_hash($password_plain, PASSWORD_DEFAULT);
$ins = mysqli_query($koneksi, "INSERT INTO admin (username, password) VALUES ('" . mysqli_real_escape_string($koneksi, $username) . "', '" . mysqli_real_escape_string($koneksi, $hash) . "')");
if ($ins) echo "Berhasil membuat admin default. Username: $username, Password: $password_plain. Hapus file ini setelah selesai.";
else echo "Gagal: " . mysqli_error($koneksi);
