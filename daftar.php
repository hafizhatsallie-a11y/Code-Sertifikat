<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email  = mysqli_real_escape_string($koneksi, $_POST['email']);
    $no_hp  = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $kursus = mysqli_real_escape_string($koneksi, $_POST['kursus']);

    // Cek apakah nomor HP sudah terdaftar
    $check_query = mysqli_query($koneksi, "SELECT * FROM daftar WHERE no_hp = '$no_hp'");
    
    if (mysqli_num_rows($check_query) > 0) {
        // Jika sudah terdaftar, redirect ke halaman minta token
        header("Location: minta_token.php?hp=$no_hp&message=already_registered");
        exit;
    }

    // Ambil batch aktif
    $qbatch = mysqli_query($koneksi, "SELECT * FROM batch WHERE aktif=1 LIMIT 1");

    if (mysqli_num_rows($qbatch) == 0) {
        die("Tidak ada batch aktif.");
    }

    $b = mysqli_fetch_assoc($qbatch);
    $batch = $b['nomor_batch'];
    $tanggal = $b['tanggal_mulai'];

    // Generate token otomatis
    $token = strtoupper(bin2hex(random_bytes(3)));
    $token_exp = date('Y-m-d H:i:s', strtotime('+3 days'));

    // Insert data baru
    $insert_query = mysqli_query($koneksi, "
        INSERT INTO daftar (nama_peserta, email, no_hp, kursus)
        VALUES('$nama', '$email', '$no_hp', '$kursus')
    ");

    if ($insert_query) {
        // Redirect ke halaman minta token
        header("Location: minta_token.php?hp=$no_hp&message=success");
        exit;
    } else {
        // Jika ada error lain
        die("Terjadi kesalahan: " . mysqli_error($koneksi));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kursus - SAE Digital Akademi</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/daftar.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <div class="form-wrapper">
            <div class="header-section">
                <h1>SAE Digital Akademi</h1>
                <h2>Form Pendaftaran Kursus</h2>
                <p class="subtitle">Isi data diri dengan lengkap dan benar</p>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text">Nama Lengkap</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" name="nama" placeholder="Tulis nama lengkap Anda di sini" required class="large-input">
                    <div class="help-text">Contoh: Budi Santoso</div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text">Alamat Email</span>
                        <span class="required">*</span>
                    </label>
                    <input type="email" name="email" placeholder="nama@contoh.com" required class="large-input">
                    <div class="help-text">Kami akan mengirim konfirmasi ke email ini</div>
                </div>
<br>
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text">Nomor Handphone</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" name="no_hp" placeholder="08123456789" required class="large-input">
                    <div class="help-text">Nomor WhatsApp aktif untuk informasi penting</div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text">Pilihan Kursus</span>
                        <span class="required">*</span>
                    </label>
                    <select name="kursus" required class="large-select">
                        <option value="">-- Silakan Pilih Kursus --</option>
                        <option value="Shopee Affiliate">Shopee Affiliate</option>
                        <option value="Canva Design">Canva Design</option>
                        <option value="Digital Marketing">Digital Marketing</option>
                    </select>
                    <div class="help-text">Pilih kursus yang ingin Anda ikuti</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <span class="btn-text">DAFTAR SEKARANG</span>
                        <span class="btn-subtext">Klik untuk melanjutkan pendaftaran</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</body>
</html>