<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login_admin.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Ambil data peserta sebelum dihapus
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM peserta_pkl WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $peserta = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($peserta) {
        // Soft delete - pindahkan ke trash
        $admin = $_SESSION['admin_username'] ?? 'admin';
        
        // Simpan ke trash
        $stmt = mysqli_prepare($koneksi, "
            INSERT INTO trash_peserta_pkl (peserta_id, nama, sekolah, keterangan, dihapus_oleh)
            VALUES (?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "issss", $id, $peserta['nama'], $peserta['sekolah'], $peserta['keterangan'], $admin);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Hapus dari tabel utama
        $stmt = mysqli_prepare($koneksi, "DELETE FROM peserta_pkl WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        $_SESSION['alert'] = 'Data berhasil dipindahkan ke trash.';
        $_SESSION['alert_type'] = 'success';
    } else {
        $_SESSION['alert'] = 'Data tidak ditemukan.';
        $_SESSION['alert_type'] = 'danger';
    }
}

header('Location: dashboard_sertifikat_pkl.php');
exit;
?>