<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// Inisialisasi variabel
$success_message = '';
$error_message = '';

// Handle tambah batch
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_batch'])) {
    $nomor_batch = mysqli_real_escape_string($koneksi, $_POST['nomor_batch']);
    $tanggal_mulai = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai']);
    $tanggal_selesai = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai']);
    
    // Cek apakah nomor_batch sudah ada
    $check = mysqli_query($koneksi, "SELECT id FROM batch WHERE nomor_batch = '$nomor_batch'");
    if (mysqli_num_rows($check) > 0) {
        $error_message = "Nomor batch '$nomor_batch' sudah digunakan! Silakan gunakan nomor lain.";
    } else {
        // Nonaktifkan batch lama
        mysqli_query($koneksi, "UPDATE batch SET aktif = 0");
        
        // Tambahkan batch baru
        $query = "INSERT INTO batch (nomor_batch, tanggal_mulai, tanggal_selesai, aktif) 
                  VALUES ('$nomor_batch', '$tanggal_mulai', '$tanggal_selesai', 1)";
        
        if (mysqli_query($koneksi, $query)) {
            $success_message = "Batch berhasil ditambahkan!";
        } else {
            $error_message = "Error: " . mysqli_error($koneksi);
        }
    }
}

// Handle ubah username dan password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_credentials'])) {
    $new_username = mysqli_real_escape_string($koneksi, $_POST['new_username']);
    $new_password = mysqli_real_escape_string($koneksi, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($koneksi, $_POST['confirm_password']);
    $admin_id = $_SESSION['admin_id'];
    
    // Validasi username tidak kosong
    if (empty($new_username)) {
        $error_message = "Username tidak boleh kosong!";
    } else {
        // Cek apakah username sudah digunakan oleh admin lain
        $check_username = mysqli_query($koneksi, "SELECT * FROM admin WHERE username = '$new_username' AND id != '$admin_id'");
        if (mysqli_num_rows($check_username) > 0) {
            $error_message = "Username sudah digunakan!";
        } else {
            // Update username
            $update_username_query = "UPDATE admin SET username = '$new_username' WHERE id = '$admin_id'";
            if (mysqli_query($koneksi, $update_username_query)) {
                $_SESSION['admin_username'] = $new_username; // Update session
                $success_message = "Username berhasil diubah!";
                
                // Update password jika diisi
                if (!empty($new_password)) {
                    if ($new_password === $confirm_password) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_password_query = "UPDATE admin SET password = '$hashed_password' WHERE id = '$admin_id'";
                        if (mysqli_query($koneksi, $update_password_query)) {
                            $success_message .= " Password berhasil diubah!";
                        } else {
                            $error_message = "Error mengubah password!";
                        }
                    } else {
                        $error_message = "Password tidak cocok!";
                    }
                }
            } else {
                $error_message = "Error mengubah username!";
            }
        }
    }
}

// Handle upload template
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_template'])) {
    $nama_template = mysqli_real_escape_string($koneksi, $_POST['nama_template']);
    
    // Validasi file depan
    if ($_FILES['file_depan']['error'] === 0) {
        $file_depan = $_FILES['file_depan'];
        $depan_filename = 'sertifikat_depan_' . time() . '.jpg';
        $depan_path = 'foto/' . $depan_filename;
        
        // Handle file belakang (opsional)
        $belakang_filename = null;
        if ($_FILES['file_belakang']['error'] === 0) {
            $file_belakang = $_FILES['file_belakang'];
            $belakang_filename = 'sertifikat_belakang_' . time() . '.jpg';
            $belakang_path = 'foto/' . $belakang_filename;
        }
        
        // Upload file depan
        if (move_uploaded_file($file_depan['tmp_name'], $depan_path)) {
            // Upload file belakang jika ada
            if ($belakang_filename && !move_uploaded_file($file_belakang['tmp_name'], $belakang_path)) {
                $error_message = "Error upload file belakang!";
            } else {
                mysqli_query($koneksi, "UPDATE template SET aktif = 0");
                
                if ($belakang_filename) {
                    $query = "INSERT INTO template (nama_template, file_depan, file_belakang, aktif) VALUES ('$nama_template', '$depan_filename', '$belakang_filename', 1)";
                } else {
                    $query = "INSERT INTO template (nama_template, file_depan, aktif) VALUES ('$nama_template', '$depan_filename', 1)";
                }
                
                if (mysqli_query($koneksi, $query)) {
                    $success_message = "Template berhasil diupload!";
                } else {
                    $error_message = "Error menyimpan template!";
                }
            }
        } else {
            $error_message = "Error upload file depan!";
        }
    } else {
        $error_message = "File depan wajib diupload!";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login_admin_batch.php');
    exit;
}

// Ambil data
$batches_query = "SELECT * FROM batch ORDER BY nomor_batch DESC";
$batches_result = mysqli_query($koneksi, $batches_query);

$templates_query = "SELECT * FROM template ORDER BY dibuat_pada DESC";
$templates_result = mysqli_query($koneksi, $templates_query);

$active_batch_query = "SELECT * FROM batch WHERE aktif = 1 LIMIT 1";
$active_batch_result = mysqli_query($koneksi, $active_batch_query);
$active_batch = mysqli_fetch_assoc($active_batch_result);

$active_template_query = "SELECT * FROM template WHERE aktif = 1 LIMIT 1";
$active_template_result = mysqli_query($koneksi, $active_template_query);
$active_template = mysqli_fetch_assoc($active_template_result);

// Ambil data admin saat ini
$admin_id = $_SESSION['admin_id'];
$admin_query = mysqli_query($koneksi, "SELECT username FROM admin WHERE id = '$admin_id'");
$admin_data = mysqli_fetch_assoc($admin_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Batch & Template</title>
<link rel="icon" type="image/x-icon" href="foto/logo sae.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="css/admin_batch.css?<?php echo time(); ?>">
<style>
.status-active { color: green; font-weight: 600; }
.batch-status, .template-status { color: #888; }
.notification { padding: 10px; margin: 10px 0; border-radius: 6px; position: relative; }
.notification.success { background: #d4edda; color: #155724; }
.notification.error { background: #f8d7da; color: #721c24; }
.btn-close { position: absolute; top: 5px; right: 5px; background: none; border: none; font-size: 16px; cursor: pointer; }
</style>
</head>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle visibility password
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const inputId = this.getAttribute('data-target');
            const input = document.getElementById(inputId);
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notifs = document.querySelectorAll('.notification');
    notifs.forEach(el => {
        el.classList.add('show');
        setTimeout(() => {
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 300);
        }, 5000); // 5 detik
    });
});
</script>
<body>

<!-- Navbar Atas -->
<nav class="navbar navbar-expand-lg navbar-light" style="background-color: #fce4ec; box-shadow: 0 2px 10px rgba(233, 30, 99, 0.1);">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="admin_batch.php">
            <img src="foto/logo sae.png" alt="Logo" width="36" height="36" class="me-2 rounded-circle">
            <span class="fw-bold" style="color: #d81b60; font-size: 1.4rem;">Admin Batch</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item me-3">
                    <span class="d-none d-lg-inline" style="color: #888;">Halo,</span>
                    <span class="fw-semibold" style="color: #f83080ff;">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm logout-btn" href="logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Notifikasi -->
<?php if (!empty($success_message)): ?>
    <div class="notification success"><?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="notification error"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

    <div class="row">
        <!-- Card 1: Kelola Batch -->
        <div class="col-md-6">
            <div class="card p-3 mb-3">
                <div class="card-header">Tambah Batch</div>
                <form method="POST">
                    <div class="form-group mb-2">
                        <label>Nomor Batch</label>
                        <input type="number" name="nomor_batch" class="form-control" min="1" required>
                    </div>
                    <div class="form-group mb-2">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control" required>
                    </div>
                    <div class="form-group mb-2">
                        <label>Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control" required>
                    </div>
                    <button type="submit" name="add_batch" class="btn btn-primary w-100">Tambah Batch Aktif</button>
                </form>
                <ul class="batch-list mt-3">
                    <?php mysqli_data_seek($batches_result, 0);
                    while ($batch = mysqli_fetch_assoc($batches_result)): ?>
                        <li class="batch-item <?php echo $batch['aktif'] ? 'active' : ''; ?>">
                            <div>Batch <?php echo $batch['nomor_batch']; ?> (<?php echo date('d M Y', strtotime($batch['tanggal_mulai'])); ?> - <?php echo date('d M Y', strtotime($batch['tanggal_selesai'])); ?>)</div>
                            <div class="<?php echo $batch['aktif'] ? 'status-active' : 'batch-status'; ?>"><?php echo $batch['aktif'] ? 'Aktif' : 'Nonaktif'; ?></div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>

        <!-- Card 2: Kelola Template -->
        <div class="col-md-6">
            <div class="card p-3 mb-3">
                <div class="card-header">Kelola Template</div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group mb-2">
                        <input type="text" name="nama_template" placeholder="Nama Template" class="form-control" required>
                    </div>
                    <div class="form-group mb-2">
                        <label>File Depan (JPG) *</label>
                        <input type="file" name="file_depan" class="form-control" accept=".jpg,.jpeg" required>
                    </div>
                    <div class="form-group mb-2">
                        <label>File Belakang (Opsional)</label>
                        <input type="file" name="file_belakang" class="form-control" accept=".jpg,.jpeg">
                    </div>
                    <button type="submit" name="upload_template" class="btn btn-primary w-100">Set Template Aktif</button>
                </form>
                <ul class="template-list mt-3">
                    <?php mysqli_data_seek($templates_result, 0);
                    while ($template = mysqli_fetch_assoc($templates_result)): ?>
                        <li class="template-item <?php echo $template['aktif'] ? 'active' : ''; ?>">
                            <div><?php echo $template['nama_template']; ?> (<?php echo $template['file_depan']; ?><?php echo $template['file_belakang'] ? ' / ' . $template['file_belakang'] : ''; ?>)</div>
                            <div class="<?php echo $template['aktif'] ? 'status-active' : 'template-status'; ?>"><?php echo $template['aktif'] ? 'Aktif' : 'Nonaktif'; ?></div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
<br>
<br>
        <!-- Card 3: Ubah Username & Password -->
<div class="col-md-12">
    <div class="card p-3 mb-3">
        <div class="card-header">Ubah Username & Password</div>
        <form method="POST">
            <div class="form-group mb-2">
                <label>Username Baru</label>
                <input type="text" name="new_username" value="<?php echo htmlspecialchars($admin_data['username']); ?>" class="form-control" required>
            </div>
            <div class="form-group mb-2 position-relative">
                <label>Password Baru</label>
                <div class="input-with-icon">
                    <input type="password" name="new_password" id="new_password" class="form-control pe-5" placeholder="Kosongkan jika tidak diubah">
                    <button type="button" class="btn btn-sm toggle-password" data-target="new_password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6c757d;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
            </div>
            <div class="form-group mb-2 position-relative">
                <label>Konfirmasi Password</label>
                <div class="input-with-icon">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control pe-5" placeholder="Ulangi password baru">
                    <button type="button" class="btn btn-sm toggle-password" data-target="confirm_password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6c757d;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" name="change_credentials" class="btn btn-success w-100">Perbarui Data</button>
        </form>
    </div>
</div>
</div>
</body>
</html>
