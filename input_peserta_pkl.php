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
$show_preview = false;
$peserta_data = null;
$nomor_sertifikat = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $sekolah = trim($_POST['sekolah']);
    $keterangan = trim($_POST['keterangan']);

    if (empty($nama) || empty($sekolah)) {
        $alert = 'Nama dan Sekolah wajib diisi.';
        $alert_type = 'warning';
    } else {
        // Gunakan prepared statement untuk keamanan
        $stmt = mysqli_prepare($koneksi, "
            INSERT INTO peserta_pkl (nama, sekolah, keterangan)
            VALUES (?, ?, ?)
        ");
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $nama, $sekolah, $keterangan);
            $q = mysqli_stmt_execute($stmt);
            
            if ($q) {
                $peserta_id = mysqli_insert_id($koneksi);
                $alert = 'Data berhasil disimpan!';
                $alert_type = 'success';
                
                // Jika tombol "Simpan & Generate" diklik, tampilkan preview
                if (isset($_POST['generate_sertifikat'])) {
                    $show_preview = true;
                    $peserta_data = [
                        'id' => $peserta_id,
                        'nama' => $nama,
                        'sekolah' => $sekolah,
                        'keterangan' => $keterangan
                    ];
                    
                    // SIMPAN SERTIFIKAT KE DATABASE UNTUK AKSES PERMANEN
                    $nomor_sertifikat = simpanSertifikatDatabase($koneksi, $peserta_data);
                    
                    if ($nomor_sertifikat) {
                        $alert .= ' Sertifikat berhasil disimpan di database.';
                    } else {
                        $alert .= ' (Catatan: Sertifikat gagal disimpan di database)';
                    }
                } else {
                    // Hanya simpan, clear form
                    $_POST = array();
                    $alert .= ' Data telah disimpan ke database.';
                }
            } else {
                $alert = 'Gagal menyimpan data: ' . mysqli_error($koneksi);
                $alert_type = 'danger';
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $alert = 'Error dalam query: ' . mysqli_error($koneksi);
            $alert_type = 'danger';
        }
    }
}

/**
 * Fungsi untuk menyimpan sertifikat ke database (akses permanen)
 */
function simpanSertifikatDatabase($koneksi, $peserta_data) {
    // Pastikan tabel sertifikat_pkl sudah ada
    $check_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'sertifikat_pkl'");
    if (mysqli_num_rows($check_table) == 0) {
        // Buat tabel jika belum ada
        $create_table = "
            CREATE TABLE sertifikat_pkl (
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
        ";
        mysqli_query($koneksi, $create_table);
    }
    
    // Generate nomor sertifikat unik
    $nomor_sertifikat = generateNomorSertifikat($koneksi);
    
    // Simpan ke tabel sertifikat_pkl
    $stmt = mysqli_prepare($koneksi, "
        INSERT INTO sertifikat_pkl (
            peserta_id,
            nomor_sertifikat,
            nama_peserta,
            sekolah_peserta,
            keterangan,
            tanggal_generate
        ) VALUES (?, ?, ?, ?, ?, CURDATE())
    ");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "issss",
            $peserta_data['id'],
            $nomor_sertifikat,
            $peserta_data['nama'],
            $peserta_data['sekolah'],
            $peserta_data['keterangan']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            // Update tabel peserta_pkl dengan status sertifikat
            $update_stmt = mysqli_prepare($koneksi, "
                UPDATE peserta_pkl 
                SET sertifikat_generated = 1,
                    sertifikat_number = ?,
                    generated_at = NOW()
                WHERE id = ?
            ");
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, "si", $nomor_sertifikat, $peserta_data['id']);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            }
            
            mysqli_stmt_close($stmt);
            return $nomor_sertifikat;
        }
        mysqli_stmt_close($stmt);
    }
    
    return false;
}

/**
 * Fungsi generate nomor sertifikat
 */
function generateNomorSertifikat($koneksi) {
    $prefix = 'SAE-PKL';
    $year = date('Y');
    $month = date('m');
    
    // Ambil jumlah sertifikat tahun ini untuk sequence
    $query = "SELECT COUNT(*) as total FROM sertifikat_pkl WHERE YEAR(created_at) = YEAR(CURDATE())";
    $result = mysqli_query($koneksi, $query);
    $row = mysqli_fetch_assoc($result);
    $sequence = ($row['total'] ?? 0) + 1;
    
    return $prefix . '-' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input & Generate Sertifikat PKL - SAE Digital Akademi</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/input_peserta.css"?v=<?php echo time(); ?>">
</head>

<body>

<div class="container mt-4 mb-5">
    <nav class="mb-4 no-print">
        <a href="dashboard_sertifikat_pkl.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </nav>
    
    <?php if ($show_preview): ?>
        <!-- PREVIEW SERTIFIKAT -->
        <div class="card p-4 shadow-sm no-print">
            <h4 class="mb-4">
                <i class="fas fa-eye text-primary me-2"></i> Preview Sertifikat
            </h4>
            
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle me-2"></i>
                Data berhasil disimpan! Berikut preview sertifikat untuk <?php echo htmlspecialchars($peserta_data['nama']); ?>
            </div>
            
            <!-- Info Sertifikat Tersimpan -->
            <?php if ($nomor_sertifikat): ?>
            <div class="certificate-info-card">
                <h5 class="mb-3">
                    <i class="fas fa-database me-2"></i> Sertifikat Tersimpan di Database
                </h5>
                <p class="mb-2">
                    <i class="fas fa-check-circle me-2"></i>
                    Sertifikat telah disimpan secara permanen di database.
                </p>
                <p class="mb-2">
                    <i class="fas fa-search me-2"></i>
                    Bisa diakses kapan saja melalui fitur "Cari Sertifikat" di dashboard.
                </p>
                <div class="certificate-number-badge">
                    <i class="fas fa-hashtag me-1"></i>
                    <?php echo htmlspecialchars($nomor_sertifikat); ?>
                </div>
            </div>
            
            <div class="access-info">
                <h6><i class="fas fa-info-circle me-2"></i> Cara Akses Ulang Sertifikat:</h6>
                <ol class="mb-0">
                    <li>Pergi ke dashboard</li>
                    <li>Klik "Cari Sertifikat"</li>
                    <li>Masukkan nama peserta: <strong><?php echo htmlspecialchars($peserta_data['nama']); ?></strong></li>
                    <li>Atau masukkan nomor sertifikat: <strong><?php echo htmlspecialchars($nomor_sertifikat); ?></strong></li>
                    <li>Klik tombol akses untuk melihat atau download ulang</li>
                </ol>
            </div>
            <?php endif; ?>
            
            <div class="text-center mb-4 mt-4">
                <button onclick="printCertificate()" class="btn btn-primary">
                    <i class="fas fa-print me-2"></i> Cetak Sertifikat Sekarang
                </button>
                <a href="cari_sertifikat.php?q=<?php echo urlencode($peserta_data['nama']); ?>" class="btn btn-success ms-2">
                    <i class="fas fa-search me-2"></i> Coba Akses Sertifikat
                </a>
                <button onclick="window.location.href='input_peserta_pkl.php'" class="btn btn-outline-primary ms-2">
                    <i class="fas fa-user-plus me-2"></i> Input Data Baru
                </button>
            </div>
        </div>
        
        <!-- TAMPILAN SERTIFIKAT -->
        <div class="certificate-preview">
            <div class="certificate-border">
                <div class="certificate-header">
                    <img src="foto/logo sae.png" alt="Logo SAE" width="60" class="mb-2">
                    <div class="certificate-title">SERTIFIKAT PKL</div>
                    <div class="certificate-subtitle">SAE Digital Akademi</div>
                </div>
                
                <div class="text-center">
                    <p class="certificate-text">Diberikan kepada:</p>
                    <div class="recipient-name"><?php echo htmlspecialchars($peserta_data['nama']); ?></div>
                    <p class="certificate-text">
                        <?php echo htmlspecialchars($peserta_data['sekolah']); ?><br><br>
                        <?php echo htmlspecialchars($peserta_data['keterangan']); ?>
                    </p>
                    <p class="certificate-text">
                        Telah menyelesaikan Program Praktik Kerja Lapangan (PKL)<br>
                        dengan baik dan memuaskan.
                    </p>
                </div>
                
                <div class="certificate-footer">
                    <div class="signature">
                        <div class="signature-line"></div>
                        <p>Direktur SAE Digital</p>
                    </div>
                    
                    <div class="signature">
                        <div class="signature-line"></div>
                        <p>Tanggal: <?php echo date('d F Y'); ?></p>
                    </div>
                </div>
                
                <!-- Nomor Sertifikat di footer -->
                <?php if ($nomor_sertifikat): ?>
                <div style="position: absolute; bottom: 10px; right: 20px; font-size: 12px; color: #666;">
                    No. Sertifikat: <?php echo htmlspecialchars($nomor_sertifikat); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
    <?php else: ?>
        <!-- FORM INPUT DATA -->
        <div class="card p-4 shadow-sm">
            <div class="text-center mb-4">
                <h4 class="mb-2">
                    <i class="fas fa-user-plus text-primary"></i> Input Data & Generate Sertifikat PKL
                </h4>
                <p class="text-muted mb-0">Masukkan data peserta untuk langsung membuat sertifikat</p>
            </div>

            <!-- Alur Progress -->
       

            <?php if ($alert != ''): ?>
                <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> p-3 text-center alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $alert_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($alert); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate class="needs-validation">
                <div class="mb-3">
                    <label class="form-label fw-medium">
                        <i class="fas fa-user text-primary me-1"></i> Nama Peserta <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nama" class="form-control" 
                           placeholder="Masukkan nama lengkap peserta" 
                           value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" 
                           required>
                    <div class="invalid-feedback">Harap isi nama peserta</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">
                        <i class="fas fa-school text-primary me-1"></i> Sekolah <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="sekolah" class="form-control" 
                           placeholder="Masukkan nama sekolah" 
                           value="<?php echo isset($_POST['sekolah']) ? htmlspecialchars($_POST['sekolah']) : ''; ?>" 
                           required>
                    <div class="invalid-feedback">Harap isi nama sekolah</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">
                        <i class="fas fa-note-sticky text-primary me-1"></i> Keterangan
                    </label>
                    <textarea name="keterangan" class="form-control" rows="3" 
                              placeholder="Deskripsi tugas atau keterangan tambahan..."><?php echo isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : ''; ?></textarea>
                </div>

                <div class="d-flex gap-3 mt-4">
                    <button type="submit" name="simpan_saja" class="btn btn-outline-primary flex-fill">
                        <i class="fas fa-save me-1"></i> Simpan Saja
                    </button>
                    <button type="submit" name="generate_sertifikat" class="btn btn-primary flex-fill">
                        <i class="fas fa-certificate me-1"></i> Simpan & Generate Sertifikat
                    </button>
                </div>
            </form>
        </div>

        <!-- Info Box -->
        <div class="card mt-4 border-info">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-info-circle text-info me-2"></i> Cara Penggunaan
                </h6>
                <ul class="list-unstyled mb-0">
                    <li><i class="fas fa-check-circle text-success me-2"></i> <strong>Simpan Saja</strong> - Hanya menyimpan data ke database</li>
                    <li><i class="fas fa-check-circle text-success me-2"></i> <strong>Simpan & Generate</strong> - Simpan data dan langsung tampilkan sertifikat</li>
                    <li><i class="fas fa-check-circle text-success me-2"></i> <strong>Sertifikat Tersimpan Permanen</strong> - Bisa diakses kapan saja</li>
                    <li><i class="fas fa-check-circle text-success me-2"></i> <strong>Akses Ulang</strong> - Cari melalui "Cari Sertifikat" di dashboard</li>
                    <li><i class="fas fa-check-circle text-success me-2"></i> <strong>Data Tidak Berubah</strong> - Nama, sekolah, keterangan tetap sama</li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function printCertificate() {
    window.print();
}

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Focus on nama field
    const namaField = document.querySelector('input[name="nama"]');
    if (namaField) {
        namaField.focus();
    }
});
</script>

</body>
</html>