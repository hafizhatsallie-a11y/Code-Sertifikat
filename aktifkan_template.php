<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin_kursus.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Validasi bahwa template ada
    $check = mysqli_query($koneksi, "SELECT id FROM template_pkl WHERE id = $id");
    
    if (mysqli_num_rows($check) == 0) {
        $_SESSION['error'] = "Template tidak ditemukan";
        header("Location: upload_template_pkl.php");
        exit;
    }
    
    // Nonaktifkan semua template terlebih dahulu
    mysqli_query($koneksi, "UPDATE template_pkl SET aktif = 0");
    
    // Aktifkan template yang dipilih
    $update = mysqli_query($koneksi, "UPDATE template_pkl SET aktif = 1 WHERE id = $id");
    
    if ($update) {
        $_SESSION['success'] = "Template berhasil diaktifkan";
    } else {
        $_SESSION['error'] = "Gagal mengaktifkan template";
    }
    
} else {
    $_SESSION['error'] = "ID template tidak valid";
}

header("Location: upload_template_pkl.php");
exit;
?>
            $_SESSION['error'] = "Format file belakang tidak didukung! Gunakan JPG, PNG, atau PDF";
            header('Location: upload_template_pkl.php');
            exit;
        }
    }
    
    // Cek apakah template dijadikan aktif
    $aktif = isset($_POST['aktif']) && $_POST['aktif'] == '1' ? 1 : 0;
    
    // Buat folder upload jika belum ada
    $upload_dir = 'uploads/templates/'; // Perbaiki path
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Upload file depan
    $ext_depan = strtolower(pathinfo($file_depan['name'], PATHINFO_EXTENSION));
    $filename_depan = 'template_depan_' . time() . '_' . rand(1000, 9999) . '.' . $ext_depan;
    $path_depan = $upload_dir . $filename_depan;
    
    if (!move_uploaded_file($file_depan['tmp_name'], $path_depan)) {
        $_SESSION['error'] = "Gagal mengupload file depan!";
        header('Location: upload_template_pkl.php');
        exit;
    }
    
    // Upload file belakang jika ada
    if ($file_belakang_data) {
        $ext_belakang = strtolower(pathinfo($file_belakang_data['name'], PATHINFO_EXTENSION));
        $filename_belakang = 'template_belakang_' . time() . '_' . rand(1000, 9999) . '.' . $ext_belakang;
        $path_belakang = $upload_dir . $filename_belakang;
        
        if (!move_uploaded_file($file_belakang_data['tmp_name'], $path_belakang)) {
            $_SESSION['error'] = "Gagal mengupload file belakang!";
            header('Location: upload_template_pkl.php');
            exit;
        }
    }
    
    // Jika template dijadikan aktif, nonaktifkan semua template lain
    if ($aktif == 1) {
        // Ganti 'template' dengan nama tabel yang benar
        $cek_tabel = mysqli_query($koneksi, "SHOW TABLES LIKE 'template'");
        if (mysqli_num_rows($cek_tabel) > 0) {
            mysqli_query($koneksi, "UPDATE template SET aktif = 0");
        } else {
            // Coba tabel template_pkl
            mysqli_query($koneksi, "UPDATE template_pkl SET aktif = 0");
        }
    }
    
    // Simpan ke database
    // Cek nama tabel yang benar
    $cek_tabel = mysqli_query($koneksi, "SHOW TABLES LIKE 'template'");
    if (mysqli_num_rows($cek_tabel) > 0) {
        // Gunakan tabel 'template'
        $stmt = mysqli_prepare($koneksi, 
            "INSERT INTO template (file_depan, file_belakang, aktif, created_at) 
             VALUES (?, ?, ?, NOW())");
    } else {
        // Gunakan tabel 'template_pkl'
        $stmt = mysqli_prepare($koneksi, 
            "INSERT INTO template_pkl (file_depan, file_belakang, aktif, created_at) 
             VALUES (?, ?, ?, NOW())");
    }
    
    if (!$stmt) {
        $_SESSION['error'] = "Kesalahan database: " . mysqli_error($koneksi);
        header('Location: upload_template_pkl.php');
        exit;
    }
    
    // Gunakan null jika file_belakang kosong
    $file_belakang_value = $filename_belakang !== null ? $filename_belakang : null;
    
    mysqli_stmt_bind_param($stmt, "ssi", $filename_depan, $file_belakang_value, $aktif);
    
    if (mysqli_stmt_execute($stmt)) {
        $template_id = mysqli_stmt_insert_id($stmt);
        $_SESSION['success'] = "Template berhasil diupload!" . 
                              ($aktif == 1 ? " Template telah diaktifkan." : "");
        
        // Simpan id template untuk editor field (jika ada)
        $_SESSION['new_template_id'] = $template_id;
        
        // Redirect ke halaman edit atau list
        header('Location: list_template.php');
        exit;
        
    } else {
        $_SESSION['error'] = "Gagal menyimpan ke database: " . mysqli_stmt_error($stmt);
        header('Location: upload_template_pkl.php');
        exit;
    }
    
    mysqli_stmt_close($stmt);
    
} else {
    echo "Akses Tidak Diperbolehkan";
    exit;
}
?>