<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login_admin.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login_admin.php');
    exit;
}

// Ambil data peserta dengan prepared statement
$stmt = mysqli_prepare($koneksi, "SELECT id, nama, sekolah, keterangan FROM peserta_pkl ORDER BY id DESC");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$peserta = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Hitung total
$total = count($peserta);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sertifikat PKL - SAE Digital Akademi</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/dashboard_sertifikat_pkl.css?v=<?php echo time(); ?>">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard_sertifikat_pkl.php">
                <img src="foto/logo sae.png" alt="Logo" width="40" height="40" class="me-2 rounded-circle" style="object-fit: cover;">
                <span class="fw-bold">Dashboard PKL</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item me-2">
                        <a class="btn btn-sm btn-light" href="admin_batch.php">
                            <i class="fas fa-certificate me-1"></i> Sertifikat Shopee
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-danger" href="?logout=1">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">

        <!-- Menu Action -->
        <div class="menu-action mb-4">
            <a class="btn btn-primary" href="input_peserta_pkl.php">
                <i class="fas fa-plus me-1"></i> Input Peserta
            </a>
            <a class="btn btn-secondary" href="buat_folder.php">
                <i class="fas fa-folder me-1"></i> Buat Folder
            </a>
           
            <a class="btn btn-secondary" href="upload_template_pkl.php">
                <i class="fas fa-pen me-1"></i> Edit Template PDF
            </a>
        </div>

        <!-- Ringkasan Stats -->
        <div class="row mb-4">
            <div class="col-md-4 col-lg-3">
                <div class="card card-stat">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h4 class="stat-number"><?php echo $total; ?></h4>
                            <p class="stat-label">Total Peserta PKL</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="card card-table">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-list me-2"></i> Data Peserta PKL
                </span>
                <span class="badge bg-primary"><?php echo $total; ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 5%">No</th>
                                <th style="width: 25%">Nama</th>
                                <th style="width: 25%">Sekolah</th>
                                <th style="width: 30%">Keterangan</th>
                                <th style="width: 15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (count($peserta) > 0) {
                                $no = 1;
                                foreach ($peserta as $p):
                            ?>
                                    <tr>
                                        <td class="fw-bold text-primary"><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($p['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($p['sekolah']); ?></td>
                                        <td>
                                            <small><?php echo htmlspecialchars(strlen($p['keterangan']) > 50 ? substr($p['keterangan'], 0, 50) . '...' : $p['keterangan']); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit_peserta.php?id=<?php echo urlencode($p['id']); ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="hapus_peserta.php?id=<?php echo urlencode($p['id']); ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                endforeach;
                            } else {
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p>Tidak ada data peserta</p>
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Show alert untuk fitur yang sedang dikembangkan
        function showAlert() {
            alert('Fitur ini sedang dikembangkan');
        }
    </script>

</body>

</html>