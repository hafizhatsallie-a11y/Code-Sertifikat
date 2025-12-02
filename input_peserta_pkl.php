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

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nama = trim($_POST['nama']);
    $sekolah = trim($_POST['sekolah']);
    $keterangan = trim($_POST['keterangan']);

    if ($nama == '' || $sekolah == '' || $keterangan == '') {
        $alert = 'Semua kolom wajib diisi.';
        $alert_type = 'warning';
    } else {
        // Gunakan prepared statement untuk keamanan
        $stmt = mysqli_prepare($koneksi, "
            INSERT INTO peserta_pkl (nama, sekolah, keterangan)
            VALUES (?, ?, ?)
        ");
        
        mysqli_stmt_bind_param($stmt, "sss", $nama, $sekolah, $keterangan);
        $q = mysqli_stmt_execute($stmt);

        if ($q) {
            $alert = 'Data berhasil disimpan.';
            $alert_type = 'success';
            // Clear form
            $_POST = array();
        } else {
            $alert = 'Gagal menyimpan data: ' . mysqli_error($koneksi);
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
    <title>Input Peserta PKL</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/input_peserta.css?v=<?php echo time(); ?>">
</head>

<body>

<div class="container mt-5" style="max-width: 600px;">
    <div class="card p-4 shadow-sm">
        <h4 class="mb-3 text-center">
            <i class="fas fa-user-plus"></i> Input Data Peserta PKL
        </h4>

        <?php if ($alert != ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> p-3 text-center alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($alert); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-user"></i> Nama Peserta <span class="text-danger">*</span>
                </label>
                <input type="text" name="nama" class="form-control" placeholder="Masukkan nama peserta" value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-school"></i> Sekolah <span class="text-danger">*</span>
                </label>
                <input type="text" name="sekolah" class="form-control" placeholder="Masukkan nama sekolah" value="<?php echo isset($_POST['sekolah']) ? htmlspecialchars($_POST['sekolah']) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-note-sticky"></i> Keterangan <span class="text-danger">*</span>
                </label>
                <textarea name="keterangan" class="form-control" rows="3" placeholder="Masukkan keterangan" required><?php echo isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : ''; ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save me-1"></i> Simpan Data
            </button>
        </form>

        <a href="dashboard_sertifikat_pkl.php" class="btn btn-outline-secondary w-100 mt-3">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
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
</script>

</body>
</html>