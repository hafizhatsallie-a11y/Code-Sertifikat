<?php
include 'koneksi.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $no_hp = mysqli_real_escape_string($koneksi, $_POST['no_hp']);

    $q = mysqli_query($koneksi, "SELECT email FROM daftar WHERE no_hp = '$no_hp' LIMIT 1");

    if (mysqli_num_rows($q) > 0) {
        $d = mysqli_fetch_assoc($q);
        $email = urlencode($d['email']);
        header("Location: cetak_sertifikat.php?email=$email");
        exit;
    } else {
        $error = "Nomor HP tidak ditemukan. Silakan daftar.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Sertifikat - SAE Digital</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/lihat_sertifikat.css?php echo time(); ?>">
</head>

<body>
    <!-- Floating Decorations -->
    <div class="floating-certificate cert-1">
        <i class="fas fa-certificate"></i>
    </div>
    <div class="floating-certificate cert-2">
        <i class="fas fa-award"></i>
    </div>
    <div class="floating-certificate cert-3">
        <i class="fas fa-star"></i>
    </div>

    <div class="container">
        <div class="header">
            <div class="certificate-icon">
                <i class="fas fa-certificate"></i>
            </div>
            <h3>Lihat Sertifikat Peserta</h3>
            <p class="subtitle">Masukkan nomor HP untuk melihat sertifikat Anda</p>
        </div>

        <form method="POST" class="form">
            <div class="form-group">
                <label for="no_hp"><i class="fas fa-mobile-alt"></i> Nomor HP</label>
                <div class="input-wrapper">
                    <i class="fas fa-phone"></i>
                    <input type="text"
                        id="no_hp"
                        name="no_hp"
                        class="form-control"
                        placeholder=""
                        required
                        pattern="[0-9]{10,13}">
                </div>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-search"></i>
                Lihat Sertifikat Saya
            </button>
        </form>
 

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="success-preview">
            <i class="fas fa-gift"></i>
            Sertifikat nya jangan lupa di download yaa 🎉
        </div>
       <a href="daftar.php" class="btn btn-outline mt-3">
    <i class="fas fa-home"></i> Kembali ke Beranda
</a>
        <div class="footer">
            <p>SAE Digital Akademi &copy; 2025</p>
        </div>
    </div>

    <script>
        // Tambahan interaksi JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.form');
            const button = document.querySelector('.btn');

            form.addEventListener('submit', function() {
                button.innerHTML = '<div class="loading"></div> Memproses...';
                button.disabled = true;
            });

            // Efek hover pada input
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>

</html>