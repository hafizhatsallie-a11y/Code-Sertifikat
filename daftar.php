<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email  = mysqli_real_escape_string($koneksi, $_POST['email']);
    $no_hp  = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $kursus = mysqli_real_escape_string($koneksi, $_POST['kursus']);

    // cek no hp sudah terdaftar
    $cek = mysqli_query($koneksi, "SELECT * FROM daftar WHERE no_hp='$no_hp' LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        header("Location: lihat_sertifikat.php?hp=$no_hp");
        exit;
    }

    // simpan data peserta baru
    $q = mysqli_query($koneksi, "
        INSERT INTO daftar (nama_peserta,email,no_hp,kursus)
        VALUES('$nama','$email','$no_hp','$kursus')
    ");

    if ($q) {
        $_SESSION['no_hp'] = $no_hp;
        header("Location: minta_token.php");
        exit;
    } else {
        die("Gagal menyimpan data: " . mysqli_error($koneksi));
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
                    <input type="text" name="nama" required class="large-input">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text">Alamat Email</span>
                        <span class="required">*</span>
                    </label>
                    <input type="email" name="email" required class="large-input">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text">Nomor Handphone</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" name="no_hp" required class="large-input">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text">Pilihan Kursus</span>
                        <span class="required">*</span>
                    </label>
                    <select name="kursus" required class="large-select">
                        <option value="">-- Pilih Kursus --</option>
                        <option value="Shopee Affiliate">Shopee Affiliate</option>
                        <option value="Canva Design">Canva Design</option>
                        <option value="Digital Marketing">Digital Marketing</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <span class="btn-text">DAFTAR SEKARANG</span>
                    </button>
                </div>
            </form>
<div class="container">
    <div class="justify`-content-center text-center mt-4">
            <div class="divider"><span>ATAU</span></div>

            <div class="sertifikat-section">
                <h4>Sudah Punya Sertifikat?</h4>
                <p class="sertifikat-info">Lihat sertifikat Anda di sini</p>

                     <a href="lihat_sertifikat.php" class="btn btn-success mt-3">
    <i class="fas fa-home"></i> lihat sertifikat saya
</a>
            </div>
</div>
        </div>
    </div>
</body>

</html>
