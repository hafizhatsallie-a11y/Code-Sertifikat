<?php
include 'koneksi.php';

if (!isset($_GET['id'])) {
    echo "ID tidak ditemukan.";
    exit;
}

$id = intval($_GET['id']);

// ambil data file untuk dihapus dari folder
$q = mysqli_query($koneksi, "SELECT file_depan, file_belakang FROM template_pkl WHERE id = $id");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    echo "Data tidak ditemukan.";
    exit;
}

// hapus file fisik
if (!empty($data['file_depan']) && file_exists('uploads/template_pkl/' . $data['file_depan'])) {
    unlink('uploads/template_pkl/' . $data['file_depan']);
}

if (!empty($data['file_belakang']) && file_exists('uploads/template_pkl/' . $data['file_belakang'])) {
    unlink('uploads/template_pkl/' . $data['file_belakang']);
}

// hapus data dari database
mysqli_query($koneksi, "DELETE FROM template_pkl WHERE id = $id");

header("Location: upload_template_pkl.php?msg=deleted");
exit;
