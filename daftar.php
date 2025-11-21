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
    <style>
        .sertifikat-section {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f4ff 100%);
            border-radius: 15px;
            border: 2px dashed #007bff;
        }
        .sertifikat-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .sertifikat-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
            color: white;
        }
        .sertifikat-info {
            margin-top: 10px;
            font-size: 14px;
            color: #6c757d;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0;
            color: #6c757d;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        .divider::before {
            margin-right: 10px;
        }
        .divider::after {
            margin-left: 10px;
        }
    </style>
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
                    <div class="help-text">Nomor WhatsApp aktif untuk informasi penting dan jangan gunakan nomor palsu</div>
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

            <!-- Tambahkan bagian ini untuk tombol Lihat Sertifikat Saya -->
            <div class="divider">
                <span>ATAU</span>
            </div>

            <div class="sertifikat-section">
                <h4>📄 Sudah Punya Sertifikat?</h4>
                <p class="sertifikat-info">
                    Jika Anda sudah terdaftar dan melalui verifikasi token, lihat sertifikat Anda di sini
                </p>
                <br>
                <a href="lihat_sertifikat.php?no_hp=<?= urlencode($data_peserta['no_hp']) ?>" 
   class="btn btn-success" target="_blank">
   Lihat Sertifikat
</a>

                <div class="sertifikat-info">
                    <small>Fitur untuk peserta yang sudah menyelesaikan pendaftaran dan verifikasi</small>
                </div>
            </div>

        </div>
    </div>
</body>

</html>