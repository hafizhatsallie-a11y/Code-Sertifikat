<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login_admin_batch.php');
    exit;
}

// Inisialisasi variabel
$success_message = '';
$error_message = '';

// Handle tambah batch - DIUBAH: Hapus pengecekan duplikasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_batch'])) {
    $nomor_batch = mysqli_real_escape_string($koneksi, $_POST['nomor_batch']);
    $tanggal_mulai = mysqli_real_escape_string($koneksi, $_POST['tanggal_mulai']);
    $tanggal_selesai = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai']);
    
    // DIUBAH: Langsung tambahkan batch tanpa cek duplikasi
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
</head>
<body>

<!-- Navbar Atas -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="admin_batch.php">
            <img src="foto/logo sae.png" alt="Logo" width="36" height="36" class="me-2 rounded-circle">
            <span class="fw-bold">Admin Batch</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item me-3">
                    <span class="fw-semibold">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm logout-btn" href="?logout=1">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <?php
        date_default_timezone_set('Asia/Jakarta'); // set timezone ke Jakarta

        $hour = date('H'); // jam 0-23
        if ($hour >= 5 && $hour < 12) {
            $greeting = "Good Morning";
        } elseif ($hour >= 12 && $hour < 17) {
            $greeting = "Good Afternoon";
        } elseif ($hour >= 17 && $hour < 20) {
            $greeting = "Good Evening";
        } else {
            $greeting = "Good Night";
        }
        ?>
        <h1 class="greeting"><?php echo $greeting . "! " . htmlspecialchars($_SESSION['admin_username']); ?></h1>
        <p class="date"><?php echo date('l, jS F Y'); ?></p>
    </div>
</div>


    <!-- Stats Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-value"><?php echo mysqli_num_rows($batches_result); ?></div>
            <div class="stat-label">Total Batches</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo mysqli_num_rows($templates_result); ?></div>
            <div class="stat-label">Templates Available</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $active_batch ? 'Batch ' . $active_batch['nomor_batch'] : 'None'; ?></div>
            <div class="stat-label">Active Batch</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $active_template ? $active_template['nama_template'] : 'None'; ?></div>
            <div class="stat-label">Active Template</div>
        </div>
    </div>

    <!-- Notifikasi -->
    <?php if (!empty($success_message)): ?>
        <div class="notification success">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="notification error">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Left Column -->
        <div class="left-column">
            <!-- Kelola Batch Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-layer-group me-2"></i>Kelola Batch
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Nomor Batch</label>
                            <input type="number" name="nomor_batch" class="form-control" min="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control" required>
                        </div>
                        <button type="submit" name="add_batch" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle me-1"></i>Tambah Batch Aktif
                        </button>
                    </form>
                    
                    <h6 class="mt-4 mb-3 fw-semibold">Daftar Batch</h6>
                    <ul class="batch-list">
                        <?php mysqli_data_seek($batches_result, 0);
                        while ($batch = mysqli_fetch_assoc($batches_result)): ?>
                            <li class="batch-item <?php echo $batch['aktif'] ? 'active' : ''; ?>">
                                <div>
                                    <strong>Batch <?php echo $batch['nomor_batch']; ?></strong>
                                    <div class="text-muted small">
                                        <?php echo date('d M Y', strtotime($batch['tanggal_mulai'])); ?> - 
                                        <?php echo date('d M Y', strtotime($batch['tanggal_selesai'])); ?>
                                    </div>
                                </div>
                                <div class="<?php echo $batch['aktif'] ? 'status-active' : 'batch-status'; ?>">
                                    <?php echo $batch['aktif'] ? 'Aktif' : 'Nonaktif'; ?>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>

            <!-- Ubah Username & Password Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-cog me-2"></i>Ubah Username & Password
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Username Baru</label>
                            <input type="text" name="new_username" value="<?php echo htmlspecialchars($admin_data['username']); ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password Baru</label>
                            <div class="input-with-icon">
                                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                                <button type="button" class="toggle-password" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Konfirmasi Password</label>
                            <div class="input-with-icon">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Ulangi password baru">
                                <button type="button" class="toggle-password" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" name="change_credentials" class="btn btn-success w-100">
                            <i class="fas fa-save me-1"></i>Perbarui Data
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="right-column">
            <!-- Kelola Template Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-file-image me-2"></i>Kelola Template
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Nama Template</label>
                            <input type="text" name="nama_template" placeholder="Nama Template" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">File Depan (JPG) *</label>
                            <input type="file" name="file_depan" class="form-control" accept=".jpg,.jpeg" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">File Belakang (Opsional)</label>
                            <input type="file" name="file_belakang" class="form-control" accept=".jpg,.jpeg">
                        </div>
                        <button type="submit" name="upload_template" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-1"></i>Set Template Aktif
                        </button>
                    </form>
                    
                    <h6 class="mt-4 mb-3 fw-semibold">Daftar Template</h6>
                    <ul class="template-list">
                        <?php mysqli_data_seek($templates_result, 0);
                        while ($template = mysqli_fetch_assoc($templates_result)): ?>
                            <li class="template-item <?php echo $template['aktif'] ? 'active' : ''; ?>">
                                <div>
                                    <strong><?php echo $template['nama_template']; ?></strong>
                                    <div class="text-muted small">
                                        <?php echo $template['file_depan']; ?>
                                        <?php echo $template['file_belakang'] ? ' / ' . $template['file_belakang'] : ''; ?>
                                    </div>
                                </div>
                                <div class="<?php echo $template['aktif'] ? 'status-active' : 'template-status'; ?>">
                                    <?php echo $template['aktif'] ? 'Aktif' : 'Nonaktif'; ?>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

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

    // Close notifications
    document.querySelectorAll('.btn-close').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.notification').style.display = 'none';
        });
    });

    // Auto-hide notifications after 5 seconds
    const notifs = document.querySelectorAll('.notification');
    notifs.forEach(el => {
        setTimeout(() => {
            if (el.style.display !== 'none') {
                el.style.opacity = '0';
                setTimeout(() => el.style.display = 'none', 300);
            }
        }, 5000);
    });
});
</script>
</body>
</html>