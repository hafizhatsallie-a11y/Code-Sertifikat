<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minta Token - SAE Digital Akademi</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/minta_token.css?php echo time(); ?>">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Token Sertifikat</h1>
                <p>Masukkan nomor HP untuk mendapatkan token</p>
            </div>
            
            <form action="minta_token_proses.php" method="post" class="token-form">
                <div class="input-group">
                    <label for="no_hp">Nomor HP</label>
                    <input type="text" name="no_hp" id="no_hp" placeholder="08xxxxxxxxxx" required>
                </div>
                
                <button type="submit" class="submit-btn">
                    <span class="btn-text">Kirim Token</span>
                </button>
            </form>
            
            <div class="card-footer">
                <p>Token adalah kunci untuk akses sertifikat</p>
            </div>
        </div>
    </div>
</body>
</html>
