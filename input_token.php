<?php
session_start();
include 'koneksi.php';

// jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $no_hp = trim($_POST['no_hp'] ?? '');
    $token = trim($_POST['token'] ?? '');

    if ($no_hp === '' || $token === '') {
        $error = "Nomor HP dan token wajib diisi.";
    } else {
        // cek token terakhir
        $stmt = mysqli_prepare($koneksi,
            "SELECT token, expired_at FROM sertifikat_token 
             WHERE no_hp = ? ORDER BY no_hp DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "s", $no_hp);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if (!$res || mysqli_num_rows($res) === 0) {
            $error = "Token tidak ditemukan.";
        } else {
            $row = mysqli_fetch_assoc($res);

            if (strtotime($row['expired_at']) < time()) {
                $error = "Token kedaluwarsa.";
            } elseif ($row['token'] !== $token) {
                $error = "Token salah.";
            } else {
                // sukses
                $_SESSION['akses_sertifikat'] = $no_hp;

                header("Location: sertifikat.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Token - SAE Digital Akademi</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/input_token.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Verifikasi Token</h1>
                <p>Masukkan token untuk mengakses sertifikat</p>
            </div>
            
            <form action="" method="post" class="token-form">
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?=htmlspecialchars($error)?>
                </div>
                <?php endif; ?>

                <div class="input-group">
                    <label for="no_hp">Nomor HP</label>
                    <input type="text" name="no_hp" id="no_hp" placeholder="08xxxxxxxxxx" required value="<?=htmlspecialchars($_POST['no_hp'] ?? '')?>">
                </div>
                
                <div class="input-group">
                    <label for="token">Token (4 digit)</label>
                    <input type="text" name="token" id="token" maxlength="4" pattern="\d{4}" placeholder="1234" required>
                </div>
                
                <button type="submit" class="submit-btn">
                    <span class="btn-text">Verifikasi</span>
                </button>
            </form>
            
            <div class="card-footer">
                <a href="minta_token.php" class="link">Minta Token Baru</a>
            </div>
        </div>
    </div>
</body>
</html>