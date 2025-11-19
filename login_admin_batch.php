<?php
session_start();
include 'koneksi.php';

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_batch.php');
    exit;
}

$error = '';

// Buat tabel dan admin default jika belum ada
function createDefaultAdmin($koneksi) {
    // Buat tabel admin jika belum ada
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS admin (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Cek apakah admin sudah ada
    $check_admin = mysqli_query($koneksi, "SELECT * FROM admin WHERE username = 'adminsae'");
    if (mysqli_num_rows($check_admin) == 0) {
        // Insert admin default dengan password '123'
        $hashed_password = password_hash('123', PASSWORD_DEFAULT);
        mysqli_query($koneksi, "INSERT INTO admin (username, password) VALUES ('adminsae', '$hashed_password')");
    }
}

// Panggil fungsi untuk buat admin default
createDefaultAdmin($koneksi);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM admin WHERE username = '$username'";
    $result = mysqli_query($koneksi, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_id'] = $admin['id'];
            header('Location: admin_batch.php');
            exit;
        } else {
            $error = "Password salah!";
        }
        
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SAE Digital Akademi</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/login_admin_sertifikat.css?v=<?php echo time(); ?>">
  
</head>
<body>
    <div class="login-container">
        <h2>Login Admin</h2>
        
        <div class="default-info">
            <strong>Default Login:</strong><br>
            Username: <strong>adminsae</strong><br>
            Password: <strong>123</strong>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error">
                <?php echo $error; ?>
                <br><small>Pastikan username dan password sudah benar.</small>
            </div>
        <?php endif; ?>

        <?php
        // Debug info
        $check_admin = mysqli_query($koneksi, "SELECT * FROM admin WHERE username = 'adminsae'");
        if (mysqli_num_rows($check_admin) > 0) {
            $admin_data = mysqli_fetch_assoc($check_admin);
            echo '<div class="debug-info">';
            echo 'Admin ditemukan di database.<br>';
            echo 'Hash password: ' . substr($admin_data['password'], 0, 20) . '...';
            echo '</div>';
        } else {
            echo '<div class="debug-info">';
            echo 'Admin tidak ditemukan di database.';
            echo '</div>';
        }
        ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="adminsae" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <div class="password-input">
                    <input type="password" id="password" name="password" value="123" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        👁️
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>

        <div class="help-text">
            <p>Jika masih gagal, coba:</p>
            <p>1. Hapus tabel admin dan biarkan sistem buat ulang</p>
            <p>2. Cek koneksi database</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.textContent = '🔒';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = '👁️';
            }
        }
    </script>
</body>
</html>