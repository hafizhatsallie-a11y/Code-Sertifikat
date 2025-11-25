<?php
session_start();
include 'koneksi.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $no_hp = trim($_POST['no_hp']);

    // Validasi minimal 11 digit
    if (!preg_match('/^[0-9]{11,13}$/', $no_hp)) {
        $error = "Nomor HP harus 11 sampai 13 digit.";
    } else {

        // Cek nomor HP di database
        $q = mysqli_query($koneksi, "
            SELECT no_hp FROM daftar 
            WHERE no_hp = '$no_hp'
            LIMIT 1
        ");

        if (mysqli_num_rows($q) > 0) {

            // Set session untuk sertifikat.php
            $_SESSION['akses_sertifikat'] = $no_hp;

            header("Location: sertifikat.php");
            exit;

        } else {
            $error = "Nomor HP tidak ditemukan. Silakan daftar.";
        }
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
    <link rel="stylesheet" href="css/lihat_sertifikat.css">
</head>

<body>

    <div class="container mt-5" style="max-width: 450px;">
        <h3 class="text-center mb-3">Lihat Sertifikat Peserta</h3>

        <form method="POST">

            <label class="form-label">Nomor HP</label>
            <input type="text"
                name="no_hp"
                class="form-control"
                required
                pattern="[0-9]{11,13}"
                placeholder="Minimal 11 digit">

            <button type="submit" class="btn btn-primary w-100 mt-3">
                Lihat Sertifikat Saya
            </button>
        </form>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mt-3">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <a href="daftar.php" class="btn btn-outline-secondary w-100 mt-3">Kembali ke Beranda</a>
    </div>

</body>
</html>
