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
    <link rel="stylesheet" href="css/sertifikat_pkl.css?v=<?php echo time(); ?>">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard_sertifikat_pkl.php">
                <img src="foto/logo sae.png" alt="Logo" width="36" height="36" class="me-2 rounded-circle">
                <span class="fw-bold">Dashboard PKL</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item me-2">
                        <a class="btn btn-sm btn-primary" href="admin_batch.php">
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

    <div class="container mt-4">

        <!-- Menu Action -->
        <div class="d-flex gap-3 mb-4 flex-wrap">
            <a class="btn btn-dark" href="input_peserta_pkl.php">
                <i class="fas fa-plus me-1"></i> Input Peserta
            </a>
            <a class="btn btn-secondary" href="#" onclick="alert('Fitur ini sedang dikembangkan')">
                <i class="fas fa-folder me-1"></i> Buat Folder
            </a>
            <a class="btn btn-secondary" href="#" onclick="alert('Fitur ini sedang dikembangkan')">
                <i class="fas fa-upload me-1"></i> Upload File
            </a>
            <a class="btn btn-secondary" href="#" onclick="alert('Fitur ini sedang dikembangkan')">
                <i class="fas fa-pen me-1"></i> Edit Template PDF
            </a>
        </div>

        <!-- Ringkasan Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title fw-bold mb-0"><?php echo $total; ?></h4>
                        <p class="card-text mb-0">Total Peserta PKL</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span>Data Peserta PKL</span>
                <span class="badge bg-primary"><?php echo $total; ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="table-dark">
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
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($p['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($p['sekolah']); ?></td>
                                    <td><?php echo htmlspecialchars($p['keterangan']); ?></td>
                                    <td>
                                        <a href="edit_peserta.php?id=<?php echo urlencode($p['id']); ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="hapus_peserta.php?id=<?php echo urlencode($p['id']); ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php
                                endforeach;
                            } else {
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox me-2"></i> Tidak ada data peserta
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
                    alert.style.display = 'none';
                }, 5000);
            });
        });
    </script>

</body>

</html>