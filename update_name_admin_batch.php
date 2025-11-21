<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_id'])) exit;

if (!empty($_POST['nama'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $id = $_SESSION['admin_id'];

    mysqli_query($koneksi, "UPDATE admin SET username = '$nama' WHERE id = $id");
    $_SESSION['admin_username'] = $nama;
}
