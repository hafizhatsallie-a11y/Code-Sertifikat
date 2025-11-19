<?php
include 'koneksi.php';

$email = $_POST['email'];
$nama = $_POST['nama'];
$deskripsi = $_POST['deskripsi'];
$gambar = $_FILES['gambar']['name'];
$tmp = $_FILES['gambar']['tmp_name'];
$folder = "uploads/";

move_uploaded_file($tmp, $folder . $gambar);

$query = "INSERT INTO kursus (email, nama, deskripsi, gambar) VALUES ('$email', '$nama', '$deskripsi', '$gambar')";
mysqli_query($koneksi, $query);

header("Location: index.php");
exit();
?>