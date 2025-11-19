<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit('Invalid request.');

$no_hp = trim($_POST['no_hp'] ?? '');
if ($no_hp === '') exit('Nomor HP wajib diisi.');

// cek peserta
$stmt = mysqli_prepare($koneksi, "SELECT id FROM daftar WHERE no_hp = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $no_hp);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res || mysqli_num_rows($res) === 0) {
    exit('Nomor HP tidak terdaftar.');
}

// buat token
$token = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
$expired_at = date('Y-m-d H:i:s', time() + 3600);

// hapus token lama
$stmt = mysqli_prepare($koneksi, "DELETE FROM sertifikat_token WHERE no_hp = ?");
mysqli_stmt_bind_param($stmt, "s", $no_hp);
mysqli_stmt_execute($stmt);

// simpan token baru
$stmt = mysqli_prepare($koneksi, "INSERT INTO sertifikat_token (no_hp, token, expired_at) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $no_hp, $token, $expired_at);
mysqli_stmt_execute($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Diterbitkan - SAE Digital Akademi</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/minta_token_proses.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <div class="token-card">
            <div class="token-header">
                <h1>Token Anda</h1>
            </div>
            
            <div class="token-content">
                <div class="info-group">
                    <span class="label">Nomor HP</span>
                    <span class="value"><?=htmlspecialchars($no_hp)?></span>
                </div>
                
                <div class="token-display">
                    <span class="token-label">Token</span>
                    <div class="token-value"><?=htmlspecialchars($token)?></div>
                </div>
                
                <div class="info-group">
                    <span class="label">Berlaku sampai</span>
                    <span class="value"><?=htmlspecialchars($expired_at)?></span>
                </div>
            </div>
            
            <div class="token-footer">
                <a href="input_token.php" class="action-btn">Masukkan Token</a>
            </div>
        </div>
    </div>
</body>
</html>