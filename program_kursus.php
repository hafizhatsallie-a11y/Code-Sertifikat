<?php
include 'koneksi.php';
$result = mysqli_query($koneksi, "SELECT * FROM kursus ORDER BY email DESC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Program Kursus</title>
  <link rel="icon" type="image/x-icon" href="foto/logo sae.png">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

  <!-- CSS -->
  <link rel="stylesheet" href="css/program_kursus.css?v=1">
</head>

<body>
  <div class="container py-5">
    <h2 class="text-center mb-5 fw-bold text-primary">PROGRAM KURSUS</h2>

    <div class="row g-4 justify-content-center">
      <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="col-12 col-md-6 col-lg-4 d-flex">
          <div class="card shadow-sm flex-fill">
            <img src="uploads/<?= htmlspecialchars($row['gambar']) ?>"
              class="card-img-top"
              alt="<?= htmlspecialchars($row['nama']) ?>">

            <div class="card-body d-flex flex-column text-center">
              <h5 class="card-title fw-semibold text-primary mb-2"><?= htmlspecialchars($row['nama']) ?></h5>
              <p class="card-text flex-grow-1"><?= htmlspecialchars($row['deskripsi']) ?></p>

              <div class="mt-auto">
                <a href="daftar.php" class="btn btn-primary px-4 fw-semibold">Daftar Sekarang</a>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</body>

</html>