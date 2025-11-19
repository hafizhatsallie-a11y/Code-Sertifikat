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
    
    mysqli_query($koneksi, "UPDATE batch SET aktif = 0");
    $query = "INSERT INTO batch (nomor_batch, tanggal_mulai, tanggal_selesai, aktif) VALUES ('$nomor_batch', '$tanggal_mulai', '$tanggal_selesai', 1)";
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
    <title>Admin Dashboard - SAE Digital Akademi</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/admin_batch.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>SAE Digital Akademi</h1>
            <div class="admin-info">
                <span>Halo, <?php echo $_SESSION['admin_username']; ?></span>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($success_message)): ?>
            <div class="alert success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="dashboard-cards">
            <div class="card">
                <div class="card-icon">📅</div>
                <h3>Batch Aktif</h3>
                <p class="card-value"><?php echo $active_batch ? 'Batch ' . $active_batch['nomor_batch'] : '-'; ?></p>
                <p class="card-desc"><?php echo $active_batch ? date('d M Y', strtotime($active_batch['tanggal_mulai'])) . ' - ' . date('d M Y', strtotime($active_batch['tanggal_selesai'])) : 'Tidak ada batch aktif'; ?></p>
            </div>
            
            <div class="card">
                <div class="card-icon">🎨</div>
                <h3>Template Aktif</h3>
                <p class="card-value"><?php echo $active_template ? $active_template['nama_template'] : '-'; ?></p>
                <p class="card-desc">Template sertifikat saat ini</p>
            </div>
        </div>

        <div class="content-grid">
            <!-- Kelola Batch -->
            <div class="content-card">
                <h2>📦 Kelola Batch</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="number" name="nomor_batch" placeholder="Nomor Batch" min="1" required>
                    </div>
                    <div class="form-group">
                        <input type="date" name="tanggal_mulai" required>
                    </div>
                    <div class="form-group">
                        <input type="date" name="tanggal_selesai" required>
                    </div>
                    <button type="submit" name="add_batch" class="btn primary">Set Batch Aktif</button>
                </form>

                <div class="table-section">
                    <h3>Daftar Batch</h3>
                    <div class="table">
                        <?php while ($batch = mysqli_fetch_assoc($batches_result)): ?>
                        <div class="table-row <?php echo $batch['aktif'] ? 'active' : ''; ?>">
                            <div class="table-cell">Batch <?php echo $batch['nomor_batch']; ?></div>
                            <div class="table-cell"><?php echo date('d M Y', strtotime($batch['tanggal_mulai'])); ?></div>
                            <div class="table-cell"><?php echo date('d M Y', strtotime($batch['tanggal_selesai'])); ?></div>
                            <div class="table-cell status"><?php echo $batch['aktif'] ? 'Aktif' : 'Nonaktif'; ?></div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Kelola Template -->
            <div class="content-card">
                <h2>🎨 Kelola Template</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <input type="text" name="nama_template" placeholder="Nama Template" required>
                    </div>
                    <div class="form-group">
                        <label>File Depan (JPG) *</label>
                        <input type="file" name="file_depan" accept=".jpg,.jpeg" required>
                    </div>
                    <div class="form-group">
                        <label>File Belakang (JPG) - Opsional</label>
                        <input type="file" name="file_belakang" accept=".jpg,.jpeg">
                    </div>
                    <button type="submit" name="upload_template" class="btn primary">Set Template Aktif</button>
                </form>

                <div class="table-section">
                    <h3>Daftar Template</h3>
                    <div class="table">
                        <?php 
                        // Reset pointer result
                        mysqli_data_seek($templates_result, 0);
                        while ($template = mysqli_fetch_assoc($templates_result)): 
                        ?>
                        <div class="table-row <?php echo $template['aktif'] ? 'active' : ''; ?>">
                            <div class="table-cell"><?php echo $template['nama_template']; ?></div>
                            <div class="table-cell"><?php echo $template['file_depan']; ?></div>
                            <div class="table-cell"><?php echo $template['file_belakang'] ?: '-'; ?></div>
                            <div class="table-cell status"><?php echo $template['aktif'] ? 'Aktif' : 'Nonaktif'; ?></div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Ubah Username & Password -->
        <div class="content-card">
    <h2>🔐 Ubah Username & Password</h2>
    
    <!-- Tampilkan Data Saat Ini -->
    <div class="current-data">
        <h4>Data Saat Ini:</h4>
        <div class="data-info">
            <div class="data-item">
                <strong>Username:</strong> 
                <span><?php echo htmlspecialchars($admin_data['username']); ?></span>
            </div>
            <div class="data-item">
                <strong>Status:</strong> 
                <span class="status active">Aktif</span>
            </div>
            <div class="data-item">
                <strong>Terakhir Login:</strong> 
                <span><?php echo date('d M Y H:i:s'); ?></span>
            </div>
        </div>
    </div>

    <form method="POST" action="">
        <div class="form-group">
            <label>Username Baru</label>
            <input type="text" name="new_username" placeholder="Username Baru" value="<?php echo htmlspecialchars($admin_data['username']); ?>" required>
        </div>
        <div class="form-group">
            <label>Password Baru</label>
            <input type="password" name="new_password" placeholder="Password Baru (kosongkan jika tidak ingin mengubah)">
            <small class="form-text">Biarkan kosong jika tidak ingin mengubah password</small>
        </div>
        <div class="form-group">
            <label>Konfirmasi Password</label>
            <input type="password" name="confirm_password" placeholder="Konfirmasi Password">
        </div>
        <button type="submit" name="change_credentials" class="btn primary">Ubah Data</button>
    </form>
</div>
        </div>
    </div>
</body>
</html>