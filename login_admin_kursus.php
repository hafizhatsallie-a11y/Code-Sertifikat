<?php
session_start();
include 'koneksi.php';

$admin_email = "adminsae@gmail.com";
$admin_password = "Profit11m!";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if ($_POST["email"] === $admin_email && $_POST["password"] === $admin_password) {
    $_SESSION["admin_logged_in"] = true;
    header("Location: index.php");
    exit();
  } else {
    $error = "Email atau password salah!";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin</title>
  <link rel="icon" type="image/x-icon" href="foto/admin.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="css/login_admin_kursus.css?v=5">
  <style>
   
  </style>
</head>

<body>
  <div class="login-container">
    <form method="POST">
      <h2>Login</h2>

      <?php if (isset($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <input type="email" name="email" placeholder="Email" required>

      <div class="password-wrapper">
        <input type="password" id="password" name="password" placeholder="Password" required>
        <button type="button" class="toggle-password" onclick="togglePassword()">
          👁️
        </button>
      </div>

      <button type="submit">Login</button>

      <p>© SAE Digital Akademi</p>
    </form>
  </div>

  <script>
    function togglePassword() {
      const passwordField = document.getElementById("password");
      const toggleBtn = document.querySelector(".toggle-password");
      if (passwordField.type === "password") {
        passwordField.type = "text";
        toggleBtn.textContent = "🙈";
      } else {
        passwordField.type = "password";
        toggleBtn.textContent = "👁️";
      }
    }
  </script>
</body>
</html>
