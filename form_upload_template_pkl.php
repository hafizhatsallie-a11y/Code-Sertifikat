<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Upload Template Sertifikat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/upload_template_pkl.css?v=<?php echo time(); ?>">
</head>

<body class="bg-light">

    <div class="container mt-5" style="max-width: 650px;">
        <div class="card shadow-sm border-0 p-4">
            <h4 class="fw-bold mb-3">Upload Template Sertifikat</h4>

            <form action="proses_upload_template_pkl.php" method="post" enctype="multipart/form-data">

                <label class="form-label">File Depan (jpg atau png)</label>
                <input type="file" name="file_depan" accept=".png,.jpg,.jpeg" class="form-control mb-3" required>

                <label class="form-label">File Belakang (jpg atau png)</label>
                <input type="file" name="file_belakang" accept=".png,.jpg,.jpeg" class="form-control mb-4" required>

                <button type="submit" name="upload" class="btn btn-primary w-100">Upload Template</button>
            </form>

        </div>
    </div>

</body>

</html>