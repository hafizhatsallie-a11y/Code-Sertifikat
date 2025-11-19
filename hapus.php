<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION["admin_logged_in"])) {
  header("Location: program_kursus.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["email"])) {
  $email = mysqli_real_escape_string($koneksi, $_POST["email"]);
  mysqli_query($koneksi, "DELETE FROM kursus WHERE email = '$email'");
  header("Location: index.php");
  exit();
} else {
  echo "Permintaan tidak valid.";
}
?>