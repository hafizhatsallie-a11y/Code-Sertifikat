<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login_admin.php");
    exit;
}

$query = mysqli_query($koneksi, "SELECT * FROM template_pkl ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Upload Template Sertifikat PKL</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/upload_template_pkl.css?v=<?php echo time(); ?>">
</head>

<body>

    <div class="container py-4">

        <div class="header-box d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">Template Sertifikat PKL</h2>
                <p>Kelola template depan dan belakang</p>
            </div>
            <a href="dashboard_sertifikat_pkl.php" class="btn btn-primary px-1 py-2"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard Sertifikat PKL</a>

            <a href="form_upload_template_pkl.php" class="btn btn-primary px-5 py-2">+ Upload Template Baru</a>


        </div>
    </div>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>File Depan</th>
                        <th>File Belakang</th>
                        <th>Status</th>
                        <th>Tanggal Upload</th>
                        <th style="width: 150px">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($query)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['file_depan']); ?></td>
                            <td><?php echo htmlspecialchars($row['file_belakang']); ?></td>
                            <td>
                                <?php if ($row['aktif'] == 1) { ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary">Tidak aktif</span>
                                <?php } ?>
                            </td>
                            <td><?php echo $row['created_at']; ?></td>

                            <td class="d-flex gap-2">
                                <?php if ($row['aktif'] != 1) { ?>
                                    <a href="aktifkan_template.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary btn-sm">Aktifkan</a>
                                <?php } ?>

                                <a href="hapus_template.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('Hapus template ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>

            </table>
        </div>
    </div>

    </div>

</body>

</html>