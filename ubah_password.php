<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

$username = $_SESSION['admin_user'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['new_username'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Ambil data admin sekarang
    $q = mysqli_query($koneksi, "SELECT * FROM admin WHERE username='" . mysqli_real_escape_string($koneksi, $username) . "' LIMIT 1");
    $data = mysqli_fetch_assoc($q);
    if (!$data) {
        $error = "Data admin tidak ditemukan.";
    } elseif (!password_verify($current_password, $data['password'])) {
        $error = "Password saat ini salah.";
    } else {
        // proses ganti username bila diisi dan berbeda
        if ($new_username !== '' && $new_username !== $username) {
            $nu = mysqli_real_escape_string($koneksi, $new_username);
            // cek unik
            $c = mysqli_query($koneksi, "SELECT id FROM admin WHERE username='$nu' AND username != '" . mysqli_real_escape_string($koneksi, $username) . "' LIMIT 1");
            if (mysqli_num_rows($c) > 0) {
                $error = "Username sudah dipakai. Pilih username lain.";
            } else {
                $ok1 = mysqli_query($koneksi, "UPDATE admin SET username='$nu' WHERE username='" . mysqli_real_escape_string($koneksi, $username) . "'");
                if ($ok1) {
                    $_SESSION['admin_user'] = $new_username;
                    $username = $new_username;
                    $success .= "Username berhasil diubah. ";
                } else {
                    $error = "Gagal mengubah username: " . mysqli_error($koneksi);
                }
            }
        }

        // proses ganti password bila diisi
        if ($error === '' && $new_password !== '') {
            if ($new_password !== $confirm) {
                $error = "Konfirmasi password tidak cocok.";
            } else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $ok2 = mysqli_query($koneksi, "UPDATE admin SET password='" . mysqli_real_escape_string($koneksi, $hash) . "' WHERE username='" . mysqli_real_escape_string($koneksi, $username) . "'");
                if ($ok2) $success .= "Password berhasil diubah.";
                else $error = "Gagal mengubah password: " . mysqli_error($koneksi);
            }
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Ubah Akun Admin</title>
<link rel="icon" type="image/x-icon" href="foto/logo sae.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/ubah_password.css?v=<?php echo time(); ?>">
</head>
<body class="p-4 bg-light">
<div class="container" style="max-width:520px;">
  <h4>Ubah Username / Password</h4>
  <?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <?php if($success): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label>Username baru (kosongkan jika tidak ingin ganti)</label>
      <input type="text" name="new_username" class="form-control" value="<?=htmlspecialchars($username)?>">
    </div>

    <div class="mb-3">
      <label>Password saat ini <small class="text-muted">(wajib untuk perubahan apapun)</small></label>
      <input type="password" name="current_password" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Password baru (kosongkan jika tidak ingin ganti)</label>
      <input type="password" name="new_password" class="form-control">
    </div>

    <div class="mb-3">
      <label>Konfirmasi password baru</label>
      <input type="password" name="confirm_password" class="form-control">
    </div>

    <button class="btn btn-primary w-100">Simpan Perubahan</button>
    <a href="admin_batch.php" class="btn btn-secondary w-100 mt-2">Kembali</a>
  </form>
</div>
</body>
</html>
