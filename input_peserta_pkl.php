<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login_admin.php');
    exit;
}

$alert = '';
$alert_type = '';
$peserta_data = null;
$nomor_sertifikat = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $sekolah = trim($_POST['sekolah']);
    $keterangan = trim($_POST['keterangan']);

    if ($nama == "" || $sekolah == "") {
        $alert = 'Nama dan Sekolah wajib diisi.';
        $alert_type = 'warning';
    } else {
        $stmt = mysqli_prepare($koneksi,"
            INSERT INTO peserta_pkl (nama, sekolah, keterangan)
            VALUES (?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt,"sss",$nama,$sekolah,$keterangan);
        $q = mysqli_stmt_execute($stmt);

        if ($q) {
            $peserta_id = mysqli_insert_id($koneksi);
            $alert = "Data berhasil disimpan.";
            $alert_type = "success";

            if (isset($_POST['generate_sertifikat'])) {
                $peserta_data = [
                    'id' => $peserta_id,
                    'nama' => $nama,
                    'sekolah' => $sekolah,
                    'keterangan' => $keterangan
                ];

                $nomor_sertifikat = simpanSertifikatDatabase($koneksi,$peserta_data);
            } else {
                $_POST = [];
            }

        } else {
            $alert = "Gagal menyimpan data: " . mysqli_error($koneksi);
            $alert_type = "danger";
        }

        mysqli_stmt_close($stmt);
    }
}

function simpanSertifikatDatabase($koneksi,$peserta_data){
    mysqli_query($koneksi,"
        CREATE TABLE IF NOT EXISTS sertifikat_pkl (
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
    ");

    $nomor_sertifikat = generateNomorSertifikat($koneksi);

    $stmt = mysqli_prepare($koneksi,"
        INSERT INTO sertifikat_pkl (
            peserta_id, nomor_sertifikat, nama_peserta, sekolah_peserta, keterangan, tanggal_generate
        ) VALUES (?, ?, ?, ?, ?, CURDATE())
    ");
    mysqli_stmt_bind_param($stmt,"issss",$peserta_data['id'],$nomor_sertifikat,$peserta_data['nama'],$peserta_data['sekolah'],$peserta_data['keterangan']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt2 = mysqli_prepare($koneksi,"
        UPDATE peserta_pkl SET sertifikat_generated=1, sertifikat_number=?, generated_at=NOW()
        WHERE id=?
    ");
    mysqli_stmt_bind_param($stmt2,"si",$nomor_sertifikat,$peserta_data['id']);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    return $nomor_sertifikat;
}

function generateNomorSertifikat($koneksi){
    $prefix = "SAE-PKL";
    $year = date("Y");
    $month = date("m");

    $q = mysqli_query($koneksi,"SELECT COUNT(*) AS total FROM sertifikat_pkl WHERE YEAR(created_at)=YEAR(CURDATE())");
    $r = mysqli_fetch_assoc($q);
    $sequence = ($r['total'] ?? 0) + 1;

    return $prefix . "-" . $year . $month . "-" . str_pad($sequence,4,"0",STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Input & Generate Sertifikat PKL</title>
<link rel="icon" type="image/x-icon" href="foto/logo sae.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="css/input_peserta.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="container mt-4 mb-5">
    <a href="dashboard_sertifikat_pkl.php" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>

    <?php if ($alert != ''): ?>
        <div class="alert alert-<?php echo $alert_type; ?> text-center">
            <?php echo $alert; ?>
        </div>
    <?php endif; ?>

    <?php if ($nomor_sertifikat != ''): ?>
        <div class="card p-4 shadow-sm mb-4">
            <h4 class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> Sertifikat Berhasil Dibuat</h4>
            <p>Nomor Sertifikat:</p>
            <h5 class="fw-bold text-primary"><?php echo $nomor_sertifikat; ?></h5>

            <div class="mt-4 text-center">
                <a href="cetak_sertifikat_pkl.php?id=<?php echo $peserta_data['id']; ?>" class="btn btn-primary" target="_blank">
                    <i class="fas fa-print me-2"></i> Cetak Sertifikat PDF
                </a>
                <a href="input_peserta_pkl.php" class="btn btn-outline-primary ms-2">
                    <i class="fas fa-user-plus me-2"></i> Input Baru
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="card p-4 shadow-sm">
        <h4 class="text-center mb-3">Input Data Peserta PKL</h4>

        <form method="POST" class="needs-validation" novalidate>
            <label class="form-label">Nama Peserta</label>
            <input type="text" name="nama" class="form-control mb-3" required>

            <label class="form-label">Sekolah</label>
            <input type="text" name="sekolah" class="form-control mb-3" required>

            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control mb-4"></textarea>

            <div class="d-flex gap-3">
                <button type="submit" name="simpan_saja" class="btn btn-outline-primary flex-fill">
                    <i class="fas fa-save me-1"></i> Simpan
                </button>
                <button type="submit" name="generate_sertifikat" class="btn btn-primary flex-fill">
                    <i class="fas fa-certificate me-1"></i> Simpan & Generate
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
