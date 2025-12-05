<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login_admin.php');
    exit;
}

$id = $_GET['id'] ?? 0;
$from_search = $_GET['from_search'] ?? 0;

// Cari sertifikat di tabel sertifikat_pkl terlebih dahulu
$stmt = mysqli_prepare($koneksi, "
    SELECT * FROM sertifikat_pkl WHERE id = ?
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sertifikat = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Jika tidak ada di sertifikat_pkl, coba cari dari peserta_pkl
if (!$sertifikat) {
    $stmt = mysqli_prepare($koneksi, "
        SELECT 
            p.*,
            s.nomor_sertifikat,
            s.tanggal_generate,
            s.download_count
        FROM peserta_pkl p
        LEFT JOIN sertifikat_pkl s ON p.id = s.peserta_id
        WHERE p.id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $peserta = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($peserta && $peserta['sertifikat_generated'] == 1) {
        // Konversi data peserta ke format sertifikat
        $sertifikat = [
            'id' => $peserta['id'],
            'peserta_id' => $peserta['id'],
            'nomor_sertifikat' => $peserta['sertifikat_number'] ?? 'SAE-PKL-' . date('Ymd') . '-' . str_pad($peserta['id'], 4, '0', STR_PAD_LEFT),
            'nama_peserta' => $peserta['nama'],
            'sekolah_peserta' => $peserta['sekolah'],
            'keterangan' => $peserta['keterangan'],
            'tanggal_generate' => $peserta['generated_at'] ? date('Y-m-d', strtotime($peserta['generated_at'])) : date('Y-m-d'),
            'download_count' => $peserta['download_count'] ?? 0
        ];
    }
}

if (!$sertifikat) {
    die("<div style='text-align: center; padding: 50px;'>
            <h4>Sertifikat tidak ditemukan</h4>
            <p>Sertifikat dengan ID tersebut tidak ditemukan di database.</p>
            <a href='dashboard_sertifikat_pkl.php' class='btn btn-primary'>Kembali ke Dashboard</a>
        </div>");
}

// Update download count
if (isset($sertifikat['id'])) {
    $update_stmt = mysqli_prepare($koneksi, "
        UPDATE sertifikat_pkl 
        SET download_count = download_count + 1,
            last_downloaded = NOW()
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($update_stmt, "i", $sertifikat['id']);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
}

// Format tanggal untuk tampilan
$tanggal_generate = date('d F Y', strtotime($sertifikat['tanggal_generate']));
$download_count = $sertifikat['download_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat PKL - <?php echo htmlspecialchars($sertifikat['nama_peserta']); ?></title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/view_sertifikat.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- Stats Badge -->
<div class="stats-badge">
    <i class="fas fa-download"></i>
    <span>Download: <?php echo $download_count; ?>x</span>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <button onclick="window.print()" class="btn-action btn-print">
        <i class="fas fa-print"></i> Cetak Sertifikat
    </button>
    <a href="download_sertifikat.php?id=<?php echo $id; ?>" class="btn-action btn-download">
        <i class="fas fa-download"></i> Download PDF
    </a>
    <?php if ($from_search): ?>
        <a href="cari_sertifikat.php" class="btn-action btn-back">
            <i class="fas fa-search"></i> Cari Lagi
        </a>
    <?php else: ?>
        <a href="dashboard_sertifikat_pkl.php" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    <?php endif; ?>
</div>

<!-- Access Notice -->
<?php if ($from_search): ?>
<div class="access-notice">
    <i class="fas fa-database"></i>
    <strong>Sertifikat diakses dari database permanen</strong> - Data ini tersimpan sejak pertama digenerate dan bisa diakses kapan saja.
</div>
<?php endif; ?>

<!-- Certificate Container -->
<div class="certificate-container">
    
    <!-- Corner Decorations -->
    <div class="corner-decoration corner-top-left"></div>
    <div class="corner-decoration corner-top-right"></div>
    <div class="corner-decoration corner-bottom-left"></div>
    <div class="corner-decoration corner-bottom-right"></div>
    
    <!-- Certificate Header -->
    <div class="certificate-header">
        <img src="foto/logo sae.png" alt="Logo SAE" class="certificate-logo">
        <h1 class="certificate-title">SERTIFIKAT PKL</h1>
        <div class="certificate-subtitle">SAE Digital Akademi</div>
    </div>
    
    <!-- Certificate Body -->
    <div class="certificate-body">
        <p class="certificate-text">Dengan ini menyatakan bahwa:</p>
        
        <div class="recipient-name">
            <?php echo htmlspecialchars($sertifikat['nama_peserta']); ?>
        </div>
        
        <div class="details-section">
            <p><strong><i class="fas fa-school"></i> Sekolah:</strong> <?php echo htmlspecialchars($sertifikat['sekolah_peserta']); ?></p>
            <?php if (!empty($sertifikat['keterangan'])): ?>
                <p><strong><i class="fas fa-note-sticky"></i> Keterangan:</strong> <?php echo htmlspecialchars($sertifikat['keterangan']); ?></p>
            <?php endif; ?>
            <p><strong><i class="fas fa-calendar-alt"></i> Tanggal Terbit:</strong> <?php echo $tanggal_generate; ?></p>
        </div>
        
        <p class="certificate-text">
            Telah menyelesaikan Program Praktik Kerja Lapangan (PKL)<br>
            dengan baik dan memuaskan.
        </p>
        
        <!-- Certificate Footer -->
        <div class="certificate-footer">
            <div class="signature">
                <div class="signature-line"></div>
                <p>Direktur SAE Digital</p>
            </div>
            
            <div class="signature">
                <div class="signature-line"></div>
                <p>Pembimbing PKL</p>
            </div>
            
            <div class="signature">
                <div class="signature-line"></div>
                <p>Koordinator Program</p>
            </div>
        </div>
        
        <!-- Certificate Number -->
        <div class="certificate-number">
            <i class="fas fa-hashtag"></i>
            <?php echo htmlspecialchars($sertifikat['nomor_sertifikat']); ?>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto print jika ada parameter print
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('print')) {
    setTimeout(() => {
        window.print();
    }, 1000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + P untuk print
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
    
    // Ctrl/Cmd + D untuk download
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        window.location.href = 'download_sertifikat.php?id=<?php echo $id; ?>';
    }
    
    // Escape untuk kembali
    if (e.key === 'Escape') {
        <?php if ($from_search): ?>
            window.location.href = 'cari_sertifikat.php';
        <?php else: ?>
            window.location.href = 'dashboard_sertifikat_pkl.php';
        <?php endif; ?>
    }
});

// Auto focus print button for accessibility
window.onload = function() {
    const printBtn = document.querySelector('.btn-print');
    if (printBtn) {
        setTimeout(() => {
            printBtn.focus();
        }, 500);
    }
};
</script>

</body>
</html>