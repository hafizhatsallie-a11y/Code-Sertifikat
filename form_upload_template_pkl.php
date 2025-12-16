<?php
// upload_template.php
session_start();

// Folder upload
$upload_dir = "uploads/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['template_file'])) {
    $file = $_FILES['template_file'];
    
    if ($file['error'] == 0) {
        // Pindahkan file
        $filename = 'template.jpg'; // Nama tetap
        $destination = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $success = "✅ Template berhasil diupload!";
        } else {
            $error = "❌ Gagal menyimpan file.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Template</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .nav a { background: #007bff; color: white; padding: 10px; text-decoration: none; margin: 5px; }
        form { margin: 20px 0; padding: 20px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Upload Template</h1>
    
    <div class="nav">
        <a href="input_peserta_pkl.php">Input Data</a>
    </div>
    
    <?php if (isset($success)): ?>
        <div style="background: green; color: white; padding: 10px;"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div style="background: red; color: white; padding: 10px;"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <p>Pilih template (JPG/PNG):</p>
        <input type="file" name="template_file" accept=".jpg,.jpeg,.png" required><br><br>
        <button type="submit">Upload</button>
    </form>
    
    <?php if (file_exists('uploads/template.jpg')): ?>
        <h3>Template Saat Ini:</h3>
        <img src="uploads/template.jpg" style="max-width: 300px; border: 1px solid #ccc;">
    <?php endif; ?>
</body>
</html>