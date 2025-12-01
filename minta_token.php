<?php
session_start();
include 'koneksi.php';

// Cek apakah user sudah daftar
if (!isset($_SESSION['no_hp'])) {
    die("Anda belum terdaftar.");
}

$no_hp = $_SESSION['no_hp'];

// Ambil batch aktif
$q_batch = mysqli_query($koneksi, "SELECT tanggal_mulai FROM batch WHERE aktif = 1 LIMIT 1");
$batch = mysqli_fetch_assoc($q_batch);

if (!$batch) {
    die("Batch belum diatur oleh admin.");
}

$tanggal_mulai = $batch['tanggal_mulai'];
$hari_ini = date('Y-m-d');

// Cek apakah pelatihan sudah boleh diakses
if ($hari_ini < $tanggal_mulai) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Program Belum Dimulai - Sertifikat</title>
        <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <link rel="stylesheet" href="css/minta_token.css?<?php echo time(); ?>">
    </head>
    <body>
        <div class="token-container fade-in">
            <div class="logo">
                <img src="foto/logo sae.png" alt="Logo SAE">
            </div>
            
            <h1 class="page-title">Program Pelatihan</h1>
            <p class="page-subtitle">Sistem Sertifikat Digital</p>
            
            <div class="status-card warning">
                <div class="status-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h2 class="status-title">Program Belum Dimulai</h2>
                <p class="status-message">Silahkan ikuti pelatihan sesuai jadwal untuk mengetahui info lebih lanjut hubungi admin kami <br> +62 812-6462-0119.</p>
                
                <div class="info-box">
                    <div class="info-title">
                        <i class="fas fa-calendar-alt"></i>
                        Jadwal Pelatihan Anda
                    </div>
                    <div class="info-content">
                        Program akan dimulai pada: <strong><?php echo date('d F Y', strtotime($tanggal_mulai)); ?></strong>
                    </div>
                </div>
             
            
            <a href="daftar.php" class="btn btn-outline">
                <i class="fas fa-sign-out-alt"></i>Keluar
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// SELALU BUAT TOKEN BARU SETIAP KALI DIAKSES - TOKEN BERUBAH-UBAH
$token = rand(1000, 9999); // Angka random 4 digit 1000-9999
$expired_at = date('Y-m-d H:i:s', time() + 3600); // Berlaku 1 jam

// Hapus token lama jika ada
mysqli_query($koneksi, "DELETE FROM sertifikat_token WHERE no_hp = '$no_hp'");

// Simpan token baru
mysqli_query($koneksi, "
    INSERT INTO sertifikat_token (no_hp, token, expired_at)
    VALUES ('$no_hp', '$token', '$expired_at')
");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Baru - Sertifikat</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/minta_token.css?<?php echo time(); ?>">
</head>
<body>
    <div class="token-container fade-in">
        <div class="logo">
            <img src="foto/logo sae.png" alt="Logo SAE">
        </div>
        
        <h1 class="page-title">Token Sertifikat</h1>
        <p class="page-subtitle">Token Berhasil Dibuat</p>
        
        <div class="status-card">
            <div class="status-icon">
                <i class="fas fa-key"></i>
            </div>
            <h2 class="status-title">Token Baru Dibuat</h2>
            <p class="status-message">Token Anda telah berhasil dibuat dan siap digunakan.</p>
        </div>
        
        <div class="token-display token-copyable" id="tokenDisplay">
            <div class="token-label">KODE TOKEN ANDA</div>
            <div class="token-value"><?php echo $token; ?></div>
            <div class="token-expiry">
                <i class="fas fa-clock me-1"></i>
                Berlaku 1 jam
            </div>
            <button class="copy-btn" onclick="copyToken()">
                <i class="far fa-copy"></i> Copy
            </button>
            <div class="copy-message" id="copyMessage">Token berhasil disalin!</div>
        </div>
        
        <div class="info-box">
            <div class="info-title">
                <i class="fas fa-exclamation-triangle"></i>
                Penting!
            </div>
            <div class="info-content">
                • Token hanya berlaku 1 jam<br>
                • Simpan token dengan aman<br>
                • Jangan bagikan kepada siapapun<br>
                • Token akan expired: <?php echo date('H:i', strtotime($expired_at)); ?>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <a href="input_token.php" class="btn btn-primary">
                <i class="fas fa-arrow-right"></i>Lanjutkan ke Sertifikat
</a>
        </div>
        
        <div style="margin-top: 15px; font-size: 0.8rem; color: #666; text-align: center;">
            <i class="fas fa-info-circle"></i> Token akan berubah setiap kali diminta
        </div>
    </div>

    <script>
    function copyToken() {
        const token = "<?php echo $token; ?>";
        const tokenDisplay = document.getElementById('tokenDisplay');
        const copyMessage = document.getElementById('copyMessage');
        
        // Copy token ke clipboard
        navigator.clipboard.writeText(token).then(() => {
            // Tampilkan pesan sukses
            copyMessage.classList.add('show');
            
            // Efek visual
            tokenDisplay.classList.add('copied');
            
            // Sembunyikan pesan setelah 2 detik
            setTimeout(() => {
                copyMessage.classList.remove('show');
                tokenDisplay.classList.remove('copied');
            }, 2000);
        }).catch(err => {
            console.error('Gagal menyalin token: ', err);
            alert('Gagal menyalin token. Silakan salin manual.');
        });
    }

    // Klik pada token display juga bisa copy
    document.getElementById('tokenDisplay').addEventListener('click', function(e) {
        if (!e.target.classList.contains('copy-btn')) {
            copyToken();
        }
    });
    
    // Auto refresh token setiap 30 detik (opsional)
    // setTimeout(() => {
    //     window.location.reload();
    // }, 30000);
    </script>
</body>
</html>