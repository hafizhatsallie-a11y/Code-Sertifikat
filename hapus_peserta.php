<?php
session_start();
include 'koneksi.php';

// Cek login admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login_admin.php');
    exit;
}

$alert = '';
$alert_type = '';
$peserta = null;

// Cek apakah ID ada di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard_sertifikat_pkl.php');
    exit;
}

$id = intval($_GET['id']);

// Ambil data peserta berdasarkan ID untuk konfirmasi
$stmt = mysqli_prepare($koneksi, "SELECT id, nama, sekolah FROM peserta_pkl WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$peserta = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Jika data tidak ditemukan
if (!$peserta) {
    header('Location: dashboard_sertifikat_pkl.php?error=not_found');
    exit;
}

// Handle konfirmasi penghapusan
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete'])) {
    
    $confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';
    
    // Validasi konfirmasi
    if ($confirm !== 'ya') {
        $alert = 'Anda harus mengkonfirmasi penghapusan dengan mengetik "ya".';
        $alert_type = 'warning';
    } else {
        // Hapus data dengan prepared statement
        $stmt = mysqli_prepare($koneksi, "DELETE FROM peserta_pkl WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        $q = mysqli_stmt_execute($stmt);

        if ($q) {
            // Redirect ke dashboard dengan pesan sukses
            header('Location: dashboard_sertifikat_pkl.php?deleted=success');
            exit;
        } else {
            $alert = 'Gagal menghapus data: ' . mysqli_error($koneksi);
            $alert_type = 'danger';
        }
        
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Peserta PKL</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/hapus_peserta.css?v=<?php echo time(); ?>">
   
</head>

<body>

<div class="container mt-5" style="max-width: 600px;">
    <div class="card p-4 shadow-sm">
        <h4 class="mb-3 text-center text-danger">
            <i class="fas fa-trash-alt"></i> Hapus Data Peserta PKL
        </h4>

        <?php if ($alert != ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> p-3 alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($alert); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Warning Box -->
        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Peringatan!</strong> Tindakan ini tidak dapat dibatalkan. Data yang dihapus akan hilang secara permanen.
        </div>

        <!-- Peserta Info -->
        <div class="peserta-info">
            <div class="mb-3">
                <label><i class="fas fa-id-badge me-2"></i>ID Peserta:</label>
                <p><?php echo htmlspecialchars($peserta['id']); ?></p>
            </div>
            <div class="mb-3">
                <label><i class="fas fa-user me-2"></i>Nama Peserta:</label>
                <p><?php echo htmlspecialchars($peserta['nama']); ?></p>
            </div>
            <div>
                <label><i class="fas fa-school me-2"></i>Sekolah:</label>
                <p><?php echo htmlspecialchars($peserta['sekolah']); ?></p>
            </div>
        </div>

        <!-- Confirmation Form -->
        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="form-label">
                    <span class="text-danger">*</span> Ketik <strong>"ya"</strong> untuk mengkonfirmasi penghapusan:
                </label>
                <input 
                    type="text" 
                    name="confirm" 
                    class="form-control form-control-lg" 
                    placeholder="Ketik: ya" 
                    autocomplete="off"
                    required>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle me-1"></i>
                    Ini adalah tindakan verifikasi untuk mencegah penghapusan yang tidak sengaja.
                </small>
            </div>

            <div class="btn-group-custom">
                <button type="submit" name="confirm_delete" class="btn btn-danger btn-lg">
                    <i class="fas fa-trash-alt me-1"></i> Hapus Data
                </button>
                <a href="dashboard_sertifikat_pkl.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times me-1"></i> Batal
                </a>
            </div>
        </form>

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

    // Validasi input konfirmasi (case-insensitive)
    document.querySelector('input[name="confirm"]').addEventListener('input', function(e) {
        this.value = this.value.toLowerCase();
    });
</script>

</body>
</html>