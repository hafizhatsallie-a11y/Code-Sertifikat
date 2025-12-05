<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login_admin.php');
    exit;
}

$keyword = trim($_GET['q'] ?? '');
$results = [];
$total_results = 0;
$message = '';

if (!empty($keyword)) {
    // Cari sertifikat berdasarkan nama atau nomor sertifikat
    $stmt = mysqli_prepare($koneksi, "
        SELECT 
            s.*,
            p.nama as nama_asli,
            p.sekolah as sekolah_asli,
            p.keterangan as keterangan_asli
        FROM sertifikat_pkl s
        LEFT JOIN peserta_pkl p ON s.peserta_id = p.id
        WHERE s.nama_peserta LIKE ? 
           OR s.nomor_sertifikat LIKE ?
           OR s.sekolah_peserta LIKE ?
        ORDER BY s.tanggal_generate DESC
    ");
    
    $search_term = "%$keyword%";
    mysqli_stmt_bind_param($stmt, "sss", $search_term, $search_term, $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    $total_results = count($results);
    
    if ($total_results == 0) {
        $message = "Tidak ditemukan sertifikat dengan kata kunci: <strong>'$keyword'</strong>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari & Akses Sertifikat - SAE Digital Akademi</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .search-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .certificate-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .certificate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .certificate-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        
        .certificate-status {
            font-size: 12px;
        }
        
        .btn-access {
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-access:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .highlight {
            background-color: #fff3cd;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-state i {
            font-size: 70px;
            color: #6c757d;
            opacity: 0.3;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="cari_sertifikat.php">
            <i class="fas fa-search me-2"></i>
            <span class="fw-bold">Cari & Akses Sertifikat</span>
        </a>
        
        <div class="navbar-nav">
            <a href="dashboard_sertifikat_pkl.php" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4">
    <div class="search-container">
        <!-- Form Pencarian -->
        <div class="card certificate-card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="fas fa-certificate text-primary me-2"></i>
                    Akses Sertifikat PKL
                </h4>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Fitur ini memungkinkan Anda mengakses sertifikat yang sudah digenerate sebelumnya.<br>
                    <strong>Manfaat:</strong> 
                    <ul class="mb-0 mt-2">
                        <li>✓ Akses sertifikat kapan saja, tanpa perlu generate ulang</li>
                        <li>✓ Barcode/QR Code tetap sama dan bisa discan</li>
                        <li>✓ Data tidak berubah sejak pertama digenerate</li>
                        <li>✓ Berguna jika barcode hilang atau tidak ada internet</li>
                    </ul>
                </div>
                
                <form method="GET" action="cari_sertifikat.php" class="mt-4">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-primary text-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control" 
                               placeholder="Masukkan nama peserta atau nomor sertifikat..." 
                               value="<?php echo htmlspecialchars($keyword); ?>"
                               required autofocus>
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search me-1"></i> Cari
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-lightbulb me-1"></i>
                        Contoh: "Budi Santoso" atau "SAE-PKL-202401-001"
                    </small>
                </form>
            </div>
        </div>
        
        <!-- Hasil Pencarian -->
        <?php if (!empty($keyword)): ?>
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>
                        <i class="fas fa-list me-2"></i>
                        Hasil Pencarian
                        <?php if ($total_results > 0): ?>
                            <span class="badge bg-primary ms-2"><?php echo $total_results; ?> ditemukan</span>
                        <?php endif; ?>
                    </h5>
                    <?php if ($total_results > 0): ?>
                        <small class="text-muted">
                            Kata kunci: <strong>"<?php echo htmlspecialchars($keyword); ?>"</strong>
                        </small>
                    <?php endif; ?>
                </div>
                
                <?php if ($total_results > 0): ?>
                    <?php foreach ($results as $cert): 
                        $download_count = $cert['download_count'] ?? 0;
                        $last_download = !empty($cert['last_downloaded']) ? 
                            date('d/m/Y H:i', strtotime($cert['last_downloaded'])) : 'Belum pernah';
                    ?>
                        <div class="card certificate-card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-2">
                                            <h5 class="mb-0 me-3">
                                                <?php 
                                                // Highlight keyword in nama
                                                $highlighted_nama = str_ireplace(
                                                    $keyword, 
                                                    '<span class="highlight">' . $keyword . '</span>', 
                                                    htmlspecialchars($cert['nama_peserta'])
                                                );
                                                echo $highlighted_nama;
                                                ?>
                                            </h5>
                                            <span class="certificate-number">
                                                <?php 
                                                // Highlight keyword in certificate number
                                                $highlighted_no = str_ireplace(
                                                    $keyword, 
                                                    '<span style="background: #ffc107; padding: 0 3px; border-radius: 3px;">' . $keyword . '</span>', 
                                                    htmlspecialchars($cert['nomor_sertifikat'])
                                                );
                                                echo $highlighted_no;
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <i class="fas fa-school me-1 text-muted"></i>
                                            <strong><?php echo htmlspecialchars($cert['sekolah_peserta']); ?></strong>
                                        </div>
                                        
                                        <?php if (!empty($cert['keterangan'])): ?>
                                            <div class="mb-2">
                                                <i class="fas fa-note-sticky me-1 text-muted"></i>
                                                <small><?php echo htmlspecialchars($cert['keterangan']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="certificate-status">
                                            <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                            <small class="text-muted">
                                                Terbit: <?php echo date('d F Y', strtotime($cert['tanggal_generate'])); ?>
                                                <?php if (!empty($cert['tanggal_berakhir'])): ?>
                                                    • Berakhir: <?php echo date('d F Y', strtotime($cert['tanggal_berakhir'])); ?>
                                                <?php endif; ?>
                                            </small>
                                            <br>
                                            <i class="fas fa-download me-1 text-muted"></i>
                                            <small class="text-muted">
                                                Download: <?php echo $download_count; ?>x • Terakhir: <?php echo $last_download; ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 text-end">
                                        <div class="d-flex flex-column h-100 justify-content-center">
                                            <div class="btn-group-vertical w-100">
                                                <!-- Lihat Sertifikat -->
                                                <a href="view_sertifikat.php?id=<?php echo $cert['id']; ?>&from_search=1" 
                                                   class="btn btn-success btn-access mb-2"
                                                   target="_blank">
                                                    <i class="fas fa-eye me-1"></i> Lihat Sertifikat
                                                </a>
                                                
                                                <!-- Download Sertifikat -->
                                                <a href="download_sertifikat.php?id=<?php echo $cert['id']; ?>" 
                                                   class="btn btn-primary btn-access mb-2">
                                                    <i class="fas fa-download me-1"></i> Download PDF
                                                </a>
                                                
                                                <!-- Regenerate jika perlu -->
                                                <a href="regenerate_sertifikat.php?id=<?php echo $cert['id']; ?>" 
                                                   class="btn btn-warning btn-access"
                                                   onclick="return confirm('Regenerate sertifikat ini? Barcode/QR Code akan tetap sama.')">
                                                    <i class="fas fa-redo me-1"></i> Regenerate
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Info tambahan -->
                    <div class="alert alert-success mt-3">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Sertifikat berhasil ditemukan!</strong> Data sertifikat disimpan permanen di database dan bisa diakses kapan saja.
                    </div>
                    
                <?php else: ?>
                    <!-- Tidak ditemukan -->
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h4 class="text-muted">Sertifikat Tidak Ditemukan</h4>
                        <p class="text-muted"><?php echo $message; ?></p>
                        
                        <div class="mt-4">
                            <h6 class="mb-3">Tips pencarian:</h6>
                            <ul class="text-start" style="max-width: 500px; margin: 0 auto;">
                                <li>Gunakan nama lengkap peserta (contoh: "Budi Santoso")</li>
                                <li>Gunakan nomor sertifikat lengkap (contoh: "SAE-PKL-202401-001")</li>
                                <li>Cek ejaan nama dengan benar</li>
                                <li>Jika masih tidak ditemukan, mungkin sertifikat belum digenerate</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <a href="dashboard_sertifikat_pkl.php" class="btn btn-primary me-2">
                                <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
                            </a>
                            <button onclick="window.location.reload()" class="btn btn-outline-primary">
                                <i class="fas fa-redo me-1"></i> Coba Lagi
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Petunjuk penggunaan -->
        <?php if (empty($keyword)): ?>
            <div class="card certificate-card mt-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-question-circle text-info me-2"></i>
                        Bagaimana Cara Kerjanya?
                    </h5>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <span class="badge bg-primary rounded-circle p-2 me-3">1</span>
                                </div>
                                <div>
                                    <h6>Generate Sertifikat</h6>
                                    <p class="text-muted small mb-0">
                                        Admin membuat sertifikat melalui form input peserta. 
                                        Data otomatis tersimpan permanen di database.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <span class="badge bg-primary rounded-circle p-2 me-3">2</span>
                                </div>
                                <div>
                                    <h6>Data Tersimpan</h6>
                                    <p class="text-muted small mb-0">
                                        Nama, sekolah, keterangan, barcode, QR Code disimpan 
                                        dan tidak akan berubah.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <span class="badge bg-primary rounded-circle p-2 me-3">3</span>
                                </div>
                                <div>
                                    <h6>Akses Kapan Saja</h6>
                                    <p class="text-muted small mb-0">
                                        Cari sertifikat menggunakan nama atau nomor sertifikat. 
                                        Bisa diakses bahkan tanpa internet.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <span class="badge bg-primary rounded-circle p-2 me-3">4</span>
                                </div>
                                <div>
                                    <h6>Download Ulang</h6>
                                    <p class="text-muted small mb-0">
                                        Download sertifikat berkali-kali jika hilang. 
                                        Barcode tetap sama dan bisa discan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Penting:</strong> Setelah sertifikat digenerate, data tidak bisa diubah. 
                        Jika ada kesalahan, harus regenerate sertifikat baru.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-focus search input
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput && searchInput.value === '') {
        searchInput.focus();
    }
});

// Quick search with Enter key
document.querySelector('input[name="q"]')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.form.submit();
    }
});

// Clear search on Escape key
document.querySelector('input[name="q"]')?.addEventListener('keyup', function(e) {
    if (e.key === 'Escape') {
        this.value = '';
    }
});
</script>
</body>
</html>