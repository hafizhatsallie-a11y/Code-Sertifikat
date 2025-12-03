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

// Ambil data peserta berdasarkan ID
$stmt = mysqli_prepare($koneksi, "SELECT id, nama, sekolah, keterangan FROM peserta_pkl WHERE id = ?");
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

// Handle form submit (update data)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nama = trim($_POST['nama']);
    $sekolah = trim($_POST['sekolah']);
    $keterangan = trim($_POST['keterangan']);

    // Validasi input
    if ($nama == '' || $sekolah == '' || $keterangan == '') {
        $alert = 'Semua kolom wajib diisi.';
        $alert_type = 'warning';
    } else {
        // Update data dengan prepared statement
        $stmt = mysqli_prepare($koneksi, "
            UPDATE peserta_pkl 
            SET nama = ?, sekolah = ?, keterangan = ? 
            WHERE id = ?
        ");
        
        mysqli_stmt_bind_param($stmt, "sssi", $nama, $sekolah, $keterangan, $id);
        $q = mysqli_stmt_execute($stmt);

        if ($q) {
            $alert = 'Data berhasil diperbarui.';
            $alert_type = 'success';
            
            // Update variabel peserta untuk menampilkan data terbaru
            $peserta['nama'] = $nama;
            $peserta['sekolah'] = $sekolah;
            $peserta['keterangan'] = $keterangan;
            
            // Redirect setelah 2 detik
            echo '<meta http-equiv="refresh" content="2;url=dashboard_sertifikat_pkl.php">';
        } else {
            $alert = 'Gagal memperbarui data: ' . mysqli_error($koneksi);
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
    <title>Edit Peserta PKL</title>
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
            <i class="fas fa-edit"></i> Edit Data Peserta PKL
        </h4>

        <?php if ($alert != ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> p-3 text-center alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($alert); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($peserta): ?>
            <form method="POST" novalidate>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Nama Peserta <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nama" class="form-control" placeholder="Masukkan nama peserta" value="<?php echo htmlspecialchars($peserta['nama']); ?>" required>
                    <small class="text-muted">Contoh: Budi Santoso</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-school"></i> Sekolah <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="sekolah" class="form-control" placeholder="Masukkan nama sekolah" value="<?php echo htmlspecialchars($peserta['sekolah']); ?>" required>
                    <small class="text-muted">Contoh: SMK Negeri 1 Jakarta</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-note-sticky"></i> Keterangan <span class="text-danger">*</span>
                    </label>
                    <textarea name="keterangan" class="form-control" rows="3" placeholder="Masukkan keterangan" required><?php echo htmlspecialchars($peserta['keterangan']); ?></textarea>
                    <small class="text-muted">Masukkan informasi tambahan tentang peserta</small>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Perbarui Data
                    </button>
                </div>
            </form>

            <a href="dashboard_sertifikat_pkl.php" class="btn btn-outline-secondary w-100 mt-3">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        <?php else: ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> Data peserta tidak ditemukan.
            </div>
            <a href="dashboard_sertifikat_pkl.php" class="btn btn-outline-secondary w-100">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        <?php endif; ?>
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