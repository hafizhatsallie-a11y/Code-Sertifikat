<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

$success_message = '';
$error_message = '';

// =========================
// TAMBAH ATAU AKTIFKAN BATCH
// =========================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_batch'])) {

    $nomor_batch = mysqli_real_escape_string($koneksi, $_POST['nomor_batch']);
    $tanggal_mulai = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai']);
    $tanggal_selesai = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai']);

    // Nonaktifkan semua batch lama
    mysqli_query($koneksi, "UPDATE batch SET aktif = 0");

    // Cek apakah batch ini sudah ada sebelumnya
    $cek = mysqli_query($koneksi, "SELECT id FROM batch WHERE nomor_batch = '$nomor_batch' LIMIT 1");

    if (mysqli_num_rows($cek) > 0) {
        // Jika batch sudah ada, aktifkan ulang dan update tanggalnya
        mysqli_query($koneksi, "UPDATE batch 
                                SET tanggal_mulai = '$tanggal_mulai',
                                    tanggal_selesai = '$tanggal_selesai',
                                    aktif = 1
                                WHERE nomor_batch = '$nomor_batch'");
        $success_message = "Batch $nomor_batch berhasil diaktifkan kembali.";
    } else {
        // Tambah batch baru
        mysqli_query($koneksi, "INSERT INTO batch (nomor_batch, tanggal_mulai, tanggal_selesai, aktif)
                                VALUES ('$nomor_batch', '$tanggal_mulai', '$tanggal_selesai', 1)");
        $success_message = "Batch baru berhasil ditambahkan.";
    }
}

// =========================
// UBAH USERNAME & PASSWORD
// =========================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_credentials'])) {
    $new_username = mysqli_real_escape_string($koneksi, $_POST['new_username']);
    $new_password = mysqli_real_escape_string($koneksi, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($koneksi, $_POST['confirm_password']);
    $admin_id = $_SESSION['admin_id'];

    if (empty($new_username)) {
        $error_message = "Username tidak boleh kosong.";
    } else {
        $check_username = mysqli_query($koneksi, "SELECT * FROM admin WHERE username = '$new_username' AND id != '$admin_id'");
        if (mysqli_num_rows($check_username) > 0) {
            $error_message = "Username sudah digunakan admin lain.";
        } else {
            mysqli_query($koneksi, "UPDATE admin SET username = '$new_username' WHERE id = '$admin_id'");
            $_SESSION['admin_username'] = $new_username;
            $success_message = "Username berhasil diperbarui.";

            if (!empty($new_password)) {
                if ($new_password === $confirm_password) {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    mysqli_query($koneksi, "UPDATE admin SET password = '$hashed' WHERE id = '$admin_id'");
                    $success_message .= " Password berhasil diperbarui.";
                } else {
                    $error_message = "Password tidak cocok.";
                }
            }
        }
    }
}

// =========================
// UPLOAD TEMPLATE
// =========================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_template'])) {
    $nama_template = mysqli_real_escape_string($koneksi, $_POST['nama_template']);

    if ($_FILES['file_depan']['error'] === 0) {
        $file_depan = $_FILES['file_depan'];
        $depan_filename = 'sertifikat_depan_' . time() . '.jpg';
        move_uploaded_file($file_depan['tmp_name'], 'foto/' . $depan_filename);

        $belakang_filename = null;
        if ($_FILES['file_belakang']['error'] === 0) {
            $file_belakang = $_FILES['file_belakang'];
            $belakang_filename = 'sertifikat_belakang_' . time() . '.jpg';
            move_uploaded_file($file_belakang['tmp_name'], 'foto/' . $belakang_filename);
        }

        mysqli_query($koneksi, "UPDATE template SET aktif = 0");

        if ($belakang_filename) {
            mysqli_query($koneksi, "INSERT INTO template (nama_template, file_depan, file_belakang, aktif)
                                    VALUES ('$nama_template', '$depan_filename', '$belakang_filename', 1)");
        } else {
            mysqli_query($koneksi, "INSERT INTO template (nama_template, file_depan, aktif)
                                    VALUES ('$nama_template', '$depan_filename', 1)");
        }

        $success_message = "Template berhasil diupload.";
    } else {
        $error_message = "File depan wajib diupload.";
    }
}

// =========================
// LOGOUT
// =========================
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login_admin_batch.php');
    exit;
}

// =========================
// DATA UNTUK TAMPILAN
// =========================
$batches_result = mysqli_query($koneksi, "SELECT * FROM batch ORDER BY nomor_batch DESC");
$templates_result = mysqli_query($koneksi, "SELECT * FROM template ORDER BY dibuat_pada DESC");

$admin_id = $_SESSION['admin_id'];
$admin_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT username FROM admin WHERE id = '$admin_id'"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
<title>Kelola Batch & Template</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/admin_batch.css?<?php echo time(); ?>">
</head>
<body>

<!-- NOTIFIKASI -->
<?php if ($success_message): ?>
<div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>

<?php if ($error_message): ?>
<div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<div class="container mt-4">
    <h2>Kelola Batch</h2>

    <form method="POST">
        <label>Nomor Batch</label>
        <input type="number" name="nomor_batch" class="form-control" required>

        <label class="mt-2">Tanggal Mulai</label>
        <input type="date" name="tanggal_mulai" class="form-control" required>

        <label class="mt-2">Tanggal Selesai</label>
        <input type="date" name="tanggal_selesai" class="form-control" required>

        <button class="btn btn-primary w-100 mt-3" name="add_batch">Simpan Batch</button>
    </form>

    <hr>

    <h4>Daftar Batch</h4>
    <ul class="list-group">
        <?php while ($b = mysqli_fetch_assoc($batches_result)): ?>
            <li class="list-group-item d-flex justify-content-between">
                <span>Batch <?php echo $b['nomor_batch']; ?> (<?php echo $b['tanggal_mulai']; ?> s.d <?php echo $b['tanggal_selesai']; ?>)</span>
                <span><?php echo $b['aktif'] ? 'Aktif' : 'Nonaktif'; ?></span>
            </li>
        <?php endwhile; ?>
    </ul>

</div>

</body>
</html>
