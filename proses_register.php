<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id       = mysqli_real_escape_string($koneksi, $_POST['id']);
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama_peserta']);
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $no_hp    = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $alamat   = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $kursus   = mysqli_real_escape_string($koneksi, $_POST['kursus']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Cek apakah email atau no_hp sudah ada
    $cek = mysqli_query($koneksi, "SELECT * FROM daftar WHERE no_hp='$no_hp' OR email='$email'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>
                alert('Email atau No HP sudah terdaftar. Silakan login.');
                window.location='login.php';
              </script>";
        exit;
    }

    // Simpan data peserta baru
    $q = "INSERT INTO daftar (nama_peserta, email, no_hp, alamat, kursus, password)
          VALUES ('$nama', '$email', '$no_hp', '$alamat', '$kursus', '$password')";

    if (mysqli_query($koneksi, $q)) {
        echo "<script>
                alert('Pendaftaran berhasil! Silakan login.');
                window.location='login.php';
              </script>";
    } else {
        echo "<script>
                alert('Gagal mendaftar. Silakan coba lagi.');
                window.location='register.php';
              </script>";
    }
}
?>
