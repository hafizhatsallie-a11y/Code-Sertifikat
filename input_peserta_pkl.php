<?php
// input_peserta.php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Simpan data ke session
    $_SESSION['peserta'] = [
        'nomor' => $_POST['nomor'],
        'nama' => $_POST['nama'],
        'lembaga' => $_POST['lembaga'],
        'lama' => $_POST['lama'],
        'periode' => $_POST['periode'],
        'pimpinan' => $_POST['pimpinan'],
        'lokasi' => $_POST['lokasi']
    ];
    
    header('Location: cetak_sertifikat_pkl.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Input Data Peserta</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 500px; margin: 0 auto; }
        input { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #28a745; color: white; padding: 10px; border: none; width: 100%; }
    </style>
</head>
<body>
    <h1>Input Data Peserta</h1>
    
    <div style="margin: 20px 0;">
        <a href="upload_template.php">Upload Template</a> |
        <a href="input_peserta.php">Input Data</a> |
        <a href="cetak_sertifikat.php">Cetak PDF</a>
    </div>
    
    <?php if (!file_exists('uploads/template.jpg')): ?>
        <div style="background: #ffc107; padding: 10px;">
            ⚠️ Template belum diupload. <a href="upload_template.php">Upload dulu</a>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <p>Nomor Sertifikat:</p>
        <input type="text" name="nomor" value="0964/SAE/IX/2025" required>
        
        <p>Nama Peserta:</p>
        <input type="text" name="nama" value="Elman Giawa" required>
        
        <p>Lembaga Magang:</p>
        <input type="text" name="lembaga" value="SAE Digital Akademi" required>
        
        <p>Lama Magang:</p>
        <input type="text" name="lama" value="1 Bulan" required>
        
        <p>Periode Magang:</p>
        <input type="text" name="periode" value="12 Agustus s.d 16 September 2025" required>
        
        <p>Pimpinan:</p>
        <input type="text" name="pimpinan" value="Sugianto, S.T., M.Kom">
        
        <p>Lokasi & Tanggal:</p>
        <input type="text" name="lokasi" value="Medan, 16 September 2025">
        
        <br><br>
        <button type="submit">Simpan & Cetak</button>
    </form>
</body>
</html>