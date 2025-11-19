<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION["admin_logged_in"])) {
  header("Location: program_kursus.php");
  exit();
}

if (isset($_GET['logout'])) {
  session_destroy();
  header("Location: program_kursus.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["hapus_id"])) {
  $email = mysqli_real_escape_string($koneksi, $_POST["hapus_id"]);
  mysqli_query($koneksi, "DELETE FROM kursus WHERE email = '$email'");
  header("Location: index.php");
  exit();
}

$result = mysqli_query($koneksi, "SELECT * FROM kursus ORDER BY email DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Kursus</title>
  <link rel="icon" type="image/x-icon" href="foto/admin.png">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

  <!-- CSS Utama -->
  <link rel="stylesheet" href="css/index.css?v=1">
</head>

<body class="container py-5">
  <!-- Judul -->
  <h2 class="mb-4 text-center fw-bold text-primary">Tambah Program Kursus</h2>

  <!-- Form Tambah Kursus -->
  <form method="POST" action="simpan_program.php" enctype="multipart/form-data" class="mx-auto shadow p-4 rounded bg-white" style="max-width: 600px;">
    <input type="email" name="email" placeholder="Email" required class="form-control mb-3">
    <input type="text" name="nama" placeholder="Nama Program" required class="form-control mb-3">
    <textarea name="deskripsi" placeholder="Deskripsi Program" required class="form-control mb-3"></textarea>
    <input type="file" name="gambar" accept="image/*" required class="form-control mb-3">

    <div class="text-center mt-3">
      <button type="submit" class="btn btn-success px-4">Simpan</button>
    </div>
  </form>

  <!-- Tombol Logout -->
  <div class="text-end mt-4 mb-3">
    <a href="kursus.php?logout=true" class="btn btn-outline-danger px-4">Logout</a>
  </div>

  <hr>

  <!-- Daftar Kursus -->
  <h3 class="text-center text-primary mt-5 mb-4 fw-semibold">Daftar Program Kursus</h3>

  <div class="row">
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
      <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
          <img src="uploads/<?= htmlspecialchars($row['gambar']) ?>" 
               class="card-img-top rounded-top"
               alt="<?= htmlspecialchars($row['nama']) ?>"
               style="height: 180px; object-fit: cover;">
          <div class="card-body d-flex flex-column justify-content-between">
            <div>
              <h5 class="card-title text-primary fw-semibold"><?= htmlspecialchars($row['nama']) ?></h5>
              <p class="card-text"><?= htmlspecialchars($row['deskripsi']) ?></p>
            </div>
            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus program ini?');">
              <input type="hidden" name="hapus_id" value="<?= $row['email'] ?>">
              <div class="text-center mt-3">
                <button type="submit" class="btn btn-danger px-4">Hapus</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</body>
</html>
