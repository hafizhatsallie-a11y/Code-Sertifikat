<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin.php");
    exit;
}

if (isset($_POST['upload'])) {

    $targetDir = "uploads/template_pkl/";

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileDepan = basename($_FILES["file_depan"]["name"]);
    $fileBelakang = basename($_FILES["file_belakang"]["name"]);

    $pathDepan = $targetDir . $fileDepan;
    $pathBelakang = $targetDir . $fileBelakang;

    $allowed = array("jpg", "jpeg", "png");
    $extDepan = strtolower(pathinfo($pathDepan, PATHINFO_EXTENSION));
    $extBelakang = strtolower(pathinfo($pathBelakang, PATHINFO_EXTENSION));

    if (!in_array($extDepan, $allowed) || !in_array($extBelakang, $allowed)) {
        $_SESSION['error'] = "File harus JPG PNG atau JPEG";
        header("Location: upload_template_pkl.php");
        exit;
    }

    if (move_uploaded_file($_FILES["file_depan"]["tmp_name"], $pathDepan)
        && move_uploaded_file($_FILES["file_belakang"]["tmp_name"], $pathBelakang)) {

        mysqli_query($koneksi, "UPDATE template_pkl SET aktif = 0");

        $stmt = mysqli_prepare($koneksi, "INSERT INTO template_pkl (file_depan, file_belakang, aktif) VALUES (?, ?, 1)");
        mysqli_stmt_bind_param($stmt, "ss", $fileDepan, $fileBelakang);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['success'] = "Template berhasil diupload dan diaktifkan";
        header("Location: upload_template_pkl.php");
        exit;

    } else {
        $_SESSION['error'] = "Upload gagal";
        header("Location: upload_template_pkl.php");
        exit;
    }
} else {
    echo "Akses Tidak Diperbolehkan";
    exit;
}
