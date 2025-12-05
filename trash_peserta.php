<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login_admin.php');
    exit;
}

$alert = '';
$alert_type = '';

// Handle restore tanpa konfirmasi
if (isset($_GET['restore_id'])) {
    $restore_id = intval($_GET['restore_id']);

    // Ambil data dari trash
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM trash_peserta_pkl WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $restore_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($data) {
        // Restore ke tabel utama
        $stmt = mysqli_prepare($koneksi, "
            INSERT INTO peserta_pkl (nama, sekolah, keterangan)
            VALUES (?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "sss", $data['nama'], $data['sekolah'], $data['keterangan']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Hapus dari trash
        $stmt = mysqli_prepare($koneksi, "DELETE FROM trash_peserta_pkl WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $restore_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $alert = 'Data berhasil dikembalikan ke dashboard';
        $alert_type = 'success';
    } else {
        $alert = 'Data tidak ditemukan';
        $alert_type = 'danger';
    }
}

// Handle hapus permanen tanpa konfirmasi
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Hapus permanen dari trash
    $stmt = mysqli_prepare($koneksi, "DELETE FROM trash_peserta_pkl WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $alert = 'Data berhasil dihapus permanen';
    $alert_type = 'success';
}

// Empty trash tanpa konfirmasi
if (isset($_GET['empty_trash'])) {
    $stmt = mysqli_prepare($koneksi, "DELETE FROM trash_peserta_pkl");
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $alert = 'Trash berhasil dikosongkan';
    $alert_type = 'success';
}

// Ambil data dari trash
$stmt = mysqli_prepare($koneksi, "
    SELECT * FROM trash_peserta_pkl 
    ORDER BY dihapus_pada DESC
");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$trash_items = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trash Peserta - SAE Digital Akademi</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/trash_peserta.css?v=<?php echo time(); ?>">

</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="trash_peserta.php">
                <i class="fas fa-trash-alt me-2"></i>
                <span class="fw-bold">Trash Peserta</span>
            </a>

            <div class="navbar-nav">
                <a href="dashboard_sertifikat_pkl.php" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if ($alert): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $alert_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($alert); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <div class="d-flex align-items-center">
                    <i class="fas fa-trash me-3" style="font-size: 24px; color: #dc3545;"></i>
                    <div>
                        <h5 class="mb-0 fw-bold">Data di Trash</h5>
                        <small class="text-muted">Data yang telah dihapus</small>
                    </div>
                    <span class="badge bg-danger ms-3" style="font-size: 14px;"><?php echo count($trash_items); ?> data</span>
                </div>
                <?php if (count($trash_items) > 0): ?>
                    <a href="?empty_trash=1" class="btn btn-sm btn-outline-danger" id="emptyTrashBtn">
                        <i class="fas fa-broom me-1"></i> Kosongkan Trash
                    </a>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <?php if (count($trash_items) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless">
                            <thead>
                                <tr class="table-light">
                                    <th width="5%">#</th>
                                    <th width="25%">Nama</th>
                                    <th width="25%">Sekolah</th>
                                    <th width="20%">Keterangan</th>
                                    <th width="15%">Dihapus Pada</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($trash_items as $item): ?>
                                    <tr class="trash-item">
                                        <td class="fw-bold text-secondary"><?php echo $no++; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['nama']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['sekolah']); ?></td>
                                        <td>
                                            <small><?php echo htmlspecialchars($item['keterangan']); ?></small>
                                        </td>
                                        <td>
                                            <span class="deleted-time">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($item['dihapus_pada'])); ?><br>
                                                <small>
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('H:i', strtotime($item['dihapus_pada'])); ?>
                                                    <br>
                                                    oleh: <?php echo htmlspecialchars($item['dihapus_oleh']); ?>
                                                </small>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <!-- TOMBOL RESTORE - Tanpa Konfirmasi -->
                                                <a href="?restore_id=<?php echo $item['id']; ?>"
                                                    class="btn btn-restore"
                                                    title="Kembalikan ke Dashboard">
                                                    <i class="fas fa-undo"></i>
                                                </a>
                                                <!-- TOMBOL DELETE - Tanpa Konfirmasi -->
                                                <a href="?delete_id=<?php echo $item['id']; ?>"
                                                    class="btn btn-delete"
                                                    title="Hapus Permanen">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-trash">
                        <i class="fas fa-trash-alt"></i>
                        <h4 class="text-muted">Trash Kosong</h4>
                        <p class="text-muted">Tidak ada data yang dihapus</p>
                        <a href="dashboard_sertifikat_pkl.php" class="btn btn-primary mt-3">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide alerts after 3 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 3000);

        // Confirmation for empty trash (optional - bisa dihapus jika tidak mau konfirmasi)
        document.getElementById('emptyTrashBtn')?.addEventListener('click', function(e) {
            // Jika ingin tetap tanpa konfirmasi, hapus event listener ini
            // return true; // Biarkan link bekerja langsung

            // Jika ingin konfirmasi sederhana tanpa modal:
            if (!confirm('Kosongkan semua data di trash?')) {
                e.preventDefault();
            }
        });

        // Auto focus alert close button
        document.addEventListener('DOMContentLoaded', function() {
            const alertCloseBtn = document.querySelector('.alert .btn-close');
            if (alertCloseBtn) {
                setTimeout(() => {
                    alertCloseBtn.focus();
                }, 100);
            }
        });
    </script>

</body>

</html>