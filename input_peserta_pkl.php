<?php

session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login_admin.php');
    exit;
}

$alert = '';
$alert_type = '';
$peserta_data = null;
$nomor_sertifikat = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $sekolah = trim($_POST['sekolah']);
    $keterangan = trim($_POST['keterangan']);

    if ($nama == "" || $sekolah == "") {
        $alert = 'Nama dan Sekolah wajib diisi.';
        $alert_type = 'warning';
    } else {
        $stmt = mysqli_prepare($koneksi,"
            INSERT INTO peserta_pkl (nama, sekolah, keterangan)
            VALUES (?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt,"sss",$nama,$sekolah,$keterangan);
        $q = mysqli_stmt_execute($stmt);

        if ($q) {
            $peserta_id = mysqli_insert_id($koneksi);
            $alert = "Data berhasil disimpan.";
            $alert_type = "success";

            if (isset($_POST['generate_sertifikat'])) {
                $peserta_data = [
                    'id' => $peserta_id,
                    'nama' => $nama,
                    'sekolah' => $sekolah,
                    'keterangan' => $keterangan
                ];

                $nomor_sertifikat = simpanSertifikatDatabase($koneksi,$peserta_data);
            } else {
                $_POST = [];
            }

        } else {
            $alert = "Gagal menyimpan data: " . mysqli_error($koneksi);
            $alert_type = "danger";
        }

        mysqli_stmt_close($stmt);
    }
}

function simpanSertifikatDatabase($koneksi,$peserta_data){
    mysqli_query($koneksi,"
        CREATE TABLE IF NOT EXISTS sertifikat_pkl (
            id INT PRIMARY KEY AUTO_INCREMENT,
            peserta_id INT NOT NULL,
            nomor_sertifikat VARCHAR(50) UNIQUE NOT NULL,
            nama_peserta VARCHAR(100) NOT NULL,
            sekolah_peserta VARCHAR(100) NOT NULL,
            keterangan TEXT,
            tanggal_generate DATE NOT NULL,
            download_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $nomor_sertifikat = generateNomorSertifikat($koneksi);

    $stmt = mysqli_prepare($koneksi,"
        INSERT INTO sertifikat_pkl (
            peserta_id, nomor_sertifikat, nama_peserta, sekolah_peserta, keterangan, tanggal_generate
        ) VALUES (?, ?, ?, ?, ?, CURDATE())
    ");
    mysqli_stmt_bind_param($stmt,"issss",$peserta_data['id'],$nomor_sertifikat,$peserta_data['nama'],$peserta_data['sekolah'],$peserta_data['keterangan']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt2 = mysqli_prepare($koneksi,"
        UPDATE peserta_pkl SET sertifikat_generated=1, sertifikat_number=?, generated_at=NOW()
        WHERE id=?
    ");
    mysqli_stmt_bind_param($stmt2,"si",$nomor_sertifikat,$peserta_data['id']);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    return $nomor_sertifikat;
}

function generateNomorSertifikat($koneksi){
    $prefix = "SAE-PKL";
    $year = date("Y");
    $month = date("m");

    $q = mysqli_query($koneksi,"SELECT COUNT(*) AS total FROM sertifikat_pkl WHERE YEAR(created_at)=YEAR(CURDATE())");
    $r = mysqli_fetch_assoc($q);
    $sequence = ($r['total'] ?? 0) + 1;

    return $prefix . "-" . $year . $month . "-" . str_pad($sequence,4,"0",STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input & Generate Sertifikat PKL</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/input_peserta.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="container mt-4 mb-5">
        <!-- Tombol Kembali ke Dashboard -->
        <a href="dashboard_sertifikat_pkl.php" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
        </a>

        <!-- Alert Message -->
        <?php if ($alert != ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo ($alert_type === 'success' ? 'check-circle' : 'exclamation-circle'); ?> me-2"></i>
                <?php echo htmlspecialchars($alert); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Success Card dengan Tombol Kembali ke Dashboard -->
        <?php if ($nomor_sertifikat != ''): ?>
            <div class="card card-success mb-4 shadow-sm border-0 border-top border-success border-5">
                <div class="card-body">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="text-success mb-3">
                            <i class="fas fa-certificate me-2"></i> Sertifikat Berhasil Dibuat
                        </h4>
                        
                        <div class="bg-light p-4 rounded mb-4">
                            <p class="text-muted mb-2">Nomor Sertifikat:</p>
                            <h5 class="fw-bold text-primary" style="word-break: break-all;">
                                <?php echo htmlspecialchars($nomor_sertifikat); ?>
                            </h5>
                        </div>

                        <p class="text-muted mb-4">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                Data peserta: <strong><?php echo htmlspecialchars($peserta_data['nama']); ?></strong>
                            </small>
                        </p>

                        <!-- Button Group -->
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <a href="cetak_sertifikat_pkl.php?id=<?php echo intval($peserta_data['id']); ?>" 
                               class="btn btn-primary" 
                               target="_blank">
                                <i class="fas fa-print me-2"></i> Cetak Sertifikat PDF
                            </a>
                            <a href="input_peserta_pkl.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i> Input Peserta Baru
                            </a>
                            <a href="dashboard_sertifikat_pkl.php" class="btn btn-success">
                                <i class="fas fa-home me-2"></i> Kembali ke Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Input Peserta -->
        <div class="card card-table shadow-sm border-0">
            <div class="card-header">
                <i class="fas fa-user-plus me-2"></i> Input Data Peserta PKL
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <!-- Nama Peserta -->
                    <div class="mb-3">
                        <label for="nama" class="form-label">
                            <i class="fas fa-user me-1"></i> Nama Peserta
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               id="nama" 
                               name="nama" 
                               class="form-control" 
                               placeholder="Masukkan nama peserta"
                               value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>"
                               required>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i> Nama peserta harus diisi
                        </div>
                    </div>

                    <!-- Sekolah -->
                    <div class="mb-3">
                        <label for="sekolah" class="form-label">
                            <i class="fas fa-school me-1"></i> Sekolah / Institusi
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               id="sekolah" 
                               name="sekolah" 
                               class="form-control" 
                               placeholder="Masukkan nama sekolah"
                               value="<?php echo isset($_POST['sekolah']) ? htmlspecialchars($_POST['sekolah']) : ''; ?>"
                               required>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i> Sekolah harus diisi
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div class="mb-4">
                        <label for="keterangan" class="form-label">
                            <i class="fas fa-sticky-note me-1"></i> Keterangan (Opsional)
                        </label>
                        <textarea id="keterangan" 
                                  name="keterangan" 
                                  class="form-control" 
                                  rows="4" 
                                  placeholder="Masukkan keterangan tambahan (opsional)"><?php echo isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : ''; ?></textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i> Isi keterangan jika ada informasi tambahan
                        </div>
                    </div>

                    <!-- Button Group -->
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <button type="submit" 
                                name="simpan_saja" 
                                class="btn btn-outline-primary flex-grow-1"
                                style="min-width: 200px;">
                            <i class="fas fa-save me-1"></i> Hanya Simpan
                        </button>
                        <button type="submit" 
                                name="generate_sertifikat" 
                                class="btn btn-primary flex-grow-1"
                                style="min-width: 200px;">
                            <i class="fas fa-certificate me-1"></i> Simpan & Generate Sertifikat
                        </button>
                    </div>

                    <!-- Info Text -->
                    <div class="alert alert-info mt-4" role="alert">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Tip:</strong> 
                        <ul class="mb-0 mt-2">
                            <li>Pilih "Hanya Simpan" jika ingin menambah data tanpa membuat sertifikat</li>
                            <li>Pilih "Simpan & Generate Sertifikat" untuk langsung membuat sertifikat</li>
                        </ul>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });

            // Focus input nama
            const namaInput = document.getElementById('nama');
            if (namaInput) {
                namaInput.focus();
            }
        });

        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                let forms = document.querySelectorAll('.needs-validation');
                Array.prototype.slice.call(forms).forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>

</body>
</html>