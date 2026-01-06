<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email  = mysqli_real_escape_string($koneksi, $_POST['email']);
    $no_hp  = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $kursus = mysqli_real_escape_string($koneksi, $_POST['kursus']);

    /*
    LOGIKA MODIFIKASI:
    - Cek apakah sudah ada data dengan email ATAU no_hp yang sama untuk kursus yang sama
    - Jika sudah ada, langsung arahkan ke halaman lihat sertifikat
    */

    $cek = mysqli_query(
        $koneksi,
        "SELECT no_hp 
         FROM daftar 
         WHERE (email = '$email' OR no_hp = '$no_hp') 
           AND kursus = '$kursus'
         LIMIT 1"
    );

    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        // Simpan ke session untuk digunakan di halaman lihat sertifikat
        $_SESSION['no_hp'] = $data['no_hp'];
        $_SESSION['pesan_info'] = "Data Anda sudah terdaftar sebelumnya. Silakan lihat sertifikat Anda.";
        header("Location: lihat_sertifikat.php?hp=" . urlencode($data['no_hp']));
        exit;
    }

    // INSERT hanya jika benar-benar data baru
    try {
        $insert = mysqli_query(
            $koneksi,
            "INSERT INTO daftar (nama_peserta, email, no_hp, kursus)
             VALUES ('$nama', '$email', '$no_hp', '$kursus')"
        );

        if ($insert) {
            $_SESSION['no_hp'] = $no_hp;
            $_SESSION['pesan_sukses'] = "Pendaftaran berhasil! Silakan lanjutkan untuk mendapatkan token.";
            header("Location: minta_token.php");
            exit;
        } else {
            // Jika insert gagal karena constraint lain
            throw new Exception("Gagal melakukan pendaftaran.");
        }
        
    } catch (Exception $e) {
        // Jika terjadi error (termasuk duplicate entry), redirect ke lihat sertifikat
        $_SESSION['pesan_error'] = "Terjadi kesalahan. Silakan cek data Anda atau lihat sertifikat jika sudah terdaftar.";
        header("Location: lihat_sertifikat.php?hp=" . urlencode($no_hp));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Kursus - SAE Digital Akademi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/daftar.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="container">
    <div class="form-wrapper">

        <div class="header-section text-center">
            <h1>SAE Digital Akademi</h1>
            <h2>Form Pendaftaran Kursus</h2>
            <p class="subtitle">Isi data dengan benar</p>
        </div>

        <?php
        // Tampilkan pesan jika ada
        if (isset($_SESSION['pesan_sukses'])) {
            echo '<div class="alert alert-success">' . $_SESSION['pesan_sukses'] . '</div>';
            unset($_SESSION['pesan_sukses']);
        }
        
        if (isset($_SESSION['pesan_error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['pesan_error'] . '</div>';
            unset($_SESSION['pesan_error']);
        }
        ?>

        <form method="POST">
            <div class="mb-3">
                <label>Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control" required 
                       placeholder="Masukkan nama lengkap Anda">
            </div>

            <div class="mb-3">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" required 
                       placeholder="contoh@email.com">
            </div>

            <div class="mb-3">
                <label>Nomor HP *</label>
                <input type="text" name="no_hp" class="form-control" required 
                       placeholder="Contoh: 081234567890" 
                       pattern="[0-9]{10,13}" 
                       title="Masukkan nomor HP yang valid (10-13 digit)">
            </div>

            <div class="mb-3">
                <label>Kursus *</label>
                <select name="kursus" class="form-select" required>
                    <option value="">-- Pilih Kursus --</option>
                    <option value="Shopee Affiliate">Shopee Affiliate</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Daftar Sekarang
            </button>
        </form>

        <div class="text-center mt-4">
            <div class="divider">ATAU</div>
            <h5>Sudah Punya Sertifikat?</h5>
            <a href="lihat_sertifikat.php" class="btn btn-success mt-2">
                Lihat Sertifikat
            </a>
        </div>

        <!-- Info tambahan -->
        <div class="mt-4 text-center" style="font-size: 0.8rem; color: #7f8c8d;">
            <p>Jika data Anda sudah terdaftar (email atau nomor HP), Anda akan langsung diarahkan ke halaman sertifikat.</p>
        </div>

    </div>
</div>

</body>
</html>