<?php

session_start();
include 'koneksi.php';

// Cek login admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login_admin.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login_admin.php');
    exit;
}

// Ambil pesan dari session
$alert = '';
$alert_type = '';
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    $alert_type = $_SESSION['alert_type'];
    unset($_SESSION['alert']);
    unset($_SESSION['alert_type']);
}

try {
    // QUERY PESERTA DENGAN PREPARED STATEMENT
    $stmt = mysqli_prepare($koneksi, "
        SELECT 
            p.id, 
            p.nama, 
            p.sekolah, 
            p.keterangan,
            IFNULL(p.sertifikat_generated, 0) as sertifikat_generated,
            p.sertifikat_number,
            p.generated_at,
            s.file_path,
            IFNULL(s.download_count, 0) as download_count,
            s.tanggal_generate
        FROM peserta_pkl p
        LEFT JOIN sertifikat_pkl s ON p.id = s.peserta_id
        ORDER BY p.id DESC
    ");

    if (!$stmt) {
        throw new Exception('Prepare statement gagal: ' . mysqli_error($koneksi));
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $peserta = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    $total = count($peserta);

    // QUERY STATISTIK
    $stmt = mysqli_prepare($koneksi, "
        SELECT 
            COUNT(*) as total_peserta,
            COALESCE(SUM(CASE WHEN IFNULL(p.sertifikat_generated, 0) = 1 THEN 1 ELSE 0 END), 0) as total_sertifikat,
            COALESCE(SUM(CASE WHEN IFNULL(p.sertifikat_generated, 0) = 0 THEN 1 ELSE 0 END), 0) as belum_sertifikat
        FROM peserta_pkl p
    ");

    if (!$stmt) {
        throw new Exception('Prepare statement statistik gagal: ' . mysqli_error($koneksi));
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // QUERY TOTAL DOWNLOAD
    $total_download = 0;
    if ($stats['total_sertifikat'] > 0) {
        $stmt = mysqli_prepare($koneksi, "
            SELECT SUM(COALESCE(download_count, 0)) as total_download 
            FROM sertifikat_pkl
        ");

        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $download_stats = mysqli_fetch_assoc($result);
            $total_download = $download_stats['total_download'] ?? 0;
            mysqli_stmt_close($stmt);
        }
    }

    // QUERY FOLDER
    $stmt = mysqli_prepare($koneksi, "
        SELECT 
            f.id, 
            f.nama_folder, 
            f.deskripsi, 
            COUNT(fpm.id) as jumlah_peserta
        FROM folder_peserta f
        LEFT JOIN folder_peserta_mapping fpm ON f.id = fpm.folder_id
        GROUP BY f.id
        ORDER BY f.dibuat_pada DESC
    ");

    if (!$stmt) {
        throw new Exception('Prepare statement folder gagal: ' . mysqli_error($koneksi));
    }

    mysqli_stmt_execute($stmt);
    $result_folders = mysqli_stmt_get_result($stmt);
    $folders = mysqli_fetch_all($result_folders, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    // QUERY DETAIL PESERTA PER FOLDER (FIXED SQL INJECTION)
    $folder_details = [];
    if (count($folders) > 0) {
        $folder_ids = array_column($folders, 'id');
        $placeholders = implode(',', array_fill(0, count($folder_ids), '?'));

        $stmt = mysqli_prepare($koneksi, "
            SELECT 
                fpm.folder_id,
                p.id as peserta_id,
                p.nama,
                p.sekolah
            FROM folder_peserta_mapping fpm
            JOIN peserta_pkl p ON fpm.peserta_id = p.id
            WHERE fpm.folder_id IN ($placeholders)
            ORDER BY fpm.folder_id, p.nama
        ");

        if ($stmt) {
            $types = str_repeat('i', count($folder_ids));
            mysqli_stmt_bind_param($stmt, $types, ...$folder_ids);
            mysqli_stmt_execute($stmt);
            $result_details = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($result_details)) {
                $folder_details[$row['folder_id']][] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    }

    // QUERY TRASH COUNT
    $trash_count = 0;
    $stmt = mysqli_prepare($koneksi, "SHOW TABLES LIKE 'trash_peserta_pkl'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) as total FROM trash_peserta_pkl");
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $trash_data = mysqli_fetch_assoc($result);
        $trash_count = $trash_data['total'] ?? 0;
        mysqli_stmt_close($stmt);
    }

    // AUTO CREATE TABLE SERTIFIKAT
    $stmt = mysqli_prepare($koneksi, "SHOW TABLES LIKE 'sertifikat_pkl'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        $create_table = "
            CREATE TABLE IF NOT EXISTS sertifikat_pkl (
                id INT PRIMARY KEY AUTO_INCREMENT,
                peserta_id INT NOT NULL,
                nomor_sertifikat VARCHAR(50),
                file_path VARCHAR(255),
                tanggal_generate DATE,
                download_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (peserta_id) REFERENCES peserta_pkl(id) ON DELETE CASCADE
            )
        ";
        mysqli_query($koneksi, $create_table);
    }

    // AUTO ADD COLUMNS
    $stmt = mysqli_prepare($koneksi, "SHOW COLUMNS FROM peserta_pkl LIKE 'sertifikat_generated'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        $add_columns = "
            ALTER TABLE peserta_pkl 
            ADD COLUMN sertifikat_generated BOOLEAN DEFAULT FALSE,
            ADD COLUMN sertifikat_number VARCHAR(50),
            ADD COLUMN generated_at DATETIME
        ";
        mysqli_query($koneksi, $add_columns);
    }
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    $alert = 'Error: ' . $e->getMessage();
    $alert_type = 'danger';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sertifikat PKL - SAE Digital Akademi</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard_sertifikat_pkl.css?v=<?php echo time(); ?>">
    <style>
        mark.highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard_sertifikat_pkl.php">
                <img src="foto/logo sae.png" alt="Logo SAE" width="40" height="40" class="me-2 rounded-circle" style="object-fit: cover;">
                <span class="fw-bold">Dashboard PKL Sertifikat</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item me-2">
                        <a class="btn btn-sm btn-light" href="admin_batch.php">
                            <i class="fas fa-certificate me-1"></i> Sertifikat Shopee
                        </a>
                    </li>
                    <li class="nav-item me-2">
                        <a class="btn btn-sm btn-warning" href="trash_peserta.php">
                            <i class="fas fa-trash-alt me-1"></i> Trash
                            <?php if ($trash_count > 0): ?>
                                <span class="badge bg-danger"><?php echo htmlspecialchars($trash_count); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-danger" href="?logout=1">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">

        <!-- Alert -->
        <?php if ($alert != ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo htmlspecialchars($alert); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Menu Action -->
        <div class="menu-action mb-4">
            <a class="btn btn-primary" href="input_peserta_pkl.php">
                <i class="fas fa-plus me-1"></i> Input Peserta
            </a>
            <a class="btn btn-secondary" href="buat_folder.php">
                <i class="fas fa-folder me-1"></i> Buat Folder
            </a>
            <a class="btn btn-secondary" href="upload_template_pkl.php">
                <i class="fas fa-pen me-1"></i> Edit Template PDF
            </a>
        </div>

        <!-- Statistik Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-lg-3 mb-3">
                <div class="card card-stat">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h4 class="stat-number"><?php echo htmlspecialchars($stats['total_peserta']); ?></h4>
                            <p class="stat-label">Total Peserta</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-lg-3 mb-3">
                <div class="card card-stat bg-success text-white">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div class="stat-content">
                            <h4 class="stat-number"><?php echo htmlspecialchars($stats['total_sertifikat']); ?></h4>
                            <p class="stat-label">Sertifikat Aktif</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-lg-3 mb-3">
                <div class="card card-stat bg-info text-white">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <div class="stat-content">
                            <h4 class="stat-number"><?php echo htmlspecialchars($total_download); ?></h4>
                            <p class="stat-label">Total Download</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-lg-3 mb-3">
                <div class="card card-stat">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="stat-content">
                            <h4 class="stat-number mb-2">Cari Sertifikat</h4>
                            <form method="GET" action="cari_sertifikat.php" class="d-flex">
                                <input type="text" name="q" class="form-control me-2"
                                    placeholder="Nama peserta..." required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Folder Section -->
        <?php if (count($folders) > 0): ?>
            <div class="card card-table mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-folder-open me-2"></i> Kelompok Folder Peserta
                    </span>
                    <span class="badge bg-primary"><?php echo count($folders); ?> Folder</span>
                </div>

                <div class="card-body">
                    <div class="row">
                        <?php foreach ($folders as $folder): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="folder-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title mb-1">
                                                    <i class="fas fa-folder text-warning me-2"></i>
                                                    <?php echo htmlspecialchars($folder['nama_folder']); ?>
                                                </h5>
                                                <?php if (!empty($folder['deskripsi'])): ?>
                                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($folder['deskripsi']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge bg-primary folder-badge">
                                                <?php echo htmlspecialchars($folder['jumlah_peserta']); ?> peserta
                                            </span>
                                        </div>

                                        <div class="folder-list">
                                            <?php if (isset($folder_details[$folder['id']]) && count($folder_details[$folder['id']]) > 0): ?>
                                                <?php foreach ($folder_details[$folder['id']] as $index => $peserta_folder):
                                                    if ($index < 5): ?>
                                                        <div class="folder-list-item">
                                                            <div class="d-flex justify-content-between">
                                                                <div class="fw-medium"><?php echo htmlspecialchars($peserta_folder['nama']); ?></div>
                                                                <small><?php echo htmlspecialchars($peserta_folder['sekolah']); ?></small>
                                                            </div>
                                                        </div>
                                                <?php endif;
                                                endforeach; ?>

                                                <?php if (count($folder_details[$folder['id']]) > 5): ?>
                                                    <div class="folder-list-item text-center">
                                                        <small class="text-muted">
                                                            ... dan <?php echo count($folder_details[$folder['id']]) - 5; ?> peserta lainnya
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="empty-folder">
                                                    <i class="fas fa-inbox fa-lg mb-2"></i>
                                                    <p class="mb-0">Belum ada peserta di folder ini</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex gap-2 mt-3 flex-wrap">
                                            <a href="buat_folder.php#folder<?php echo htmlspecialchars($folder['id']); ?>"
                                                class="btn btn-sm btn-outline-primary flex-grow-1">
                                                <i class="fas fa-eye me-1"></i> Lihat Detail
                                            </a>
                                            <a href="buat_folder.php?delete_id=<?php echo htmlspecialchars($folder['id']); ?>"
                                                class="btn btn-sm btn-outline-danger flex-grow-1"
                                                onclick="return confirm('Hapus folder ini?')">
                                                <i class="fas fa-trash me-1"></i> Hapus
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Peserta Table -->
        <div class="card card-table">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="fas fa-list me-2"></i> Data Peserta PKL
                    <span class="badge bg-secondary ms-2">
                        <i class="fas fa-certificate"></i>
                        <?php echo htmlspecialchars($stats['total_sertifikat']); ?> sertifikat aktif
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshTable()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <span class="badge bg-primary peserta-count"><?php echo htmlspecialchars($total); ?> peserta</span>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="p-3">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control"
                        placeholder="Cari nama peserta, sekolah, atau nomor sertifikat...">
                    <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <small class="text-muted mt-1 d-block">
                    <i class="fas fa-info-circle me-1"></i>
                    Klik ESC untuk menghapus pencarian
                </small>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" id="tabelPeserta">
                        <thead>
                            <tr>
                                <th style="width: 5%">No</th>
                                <th style="width: 25%">Nama Peserta</th>
                                <th style="width: 25%">Sekolah</th>
                                <th style="width: 25%">Keterangan</th>
                                <th style="width: 10%">Download</th>
                                <th style="width: 20%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total > 0) {
                                $no = 1;
                                foreach ($peserta as $p):
                                    $has_cert = $p['sertifikat_generated'] == 1;
                                    $cert_date = !empty($p['generated_at']) ? date('d/m/Y', strtotime($p['generated_at'])) : '';
                                    $download_count = $p['download_count'] ?? 0;
                                    $peserta_id = htmlspecialchars($p['id']);
                                    $nama = htmlspecialchars($p['nama']);
                                    $sekolah = htmlspecialchars($p['sekolah']);
                                    $cert_number = htmlspecialchars($p['sertifikat_number'] ?? '');
                            ?>
                                    <tr id="peserta-<?php echo $peserta_id; ?>">
                                        <td class="fw-bold text-primary"><?php echo $no++; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <strong class="peserta-nama"><?php echo $nama; ?></strong>
                                                    <?php if ($has_cert && !empty($cert_number)): ?>
                                                        <br>
                                                        <small class="text-muted cert-number">
                                                            <i class="fas fa-hashtag me-1"></i>
                                                            <?php echo $cert_number; ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="peserta-sekolah"><?php echo $sekolah; ?></td>
                                        <td>
                                            <?php if (!empty($p['keterangan'])): ?>
                                                <small><?php echo htmlspecialchars($p['keterangan']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($has_cert): ?>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-info me-2">
                                                        <i class="fas fa-download"></i> <?php echo htmlspecialchars($download_count); ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm flex-wrap gap-1">
                                                <?php if ($has_cert): ?>
                                                    <a href="view_sertifikat.php?id=<?php echo $peserta_id; ?>"
                                                        class="btn btn-success btn-sm"
                                                        target="_blank"
                                                        title="Lihat Sertifikat">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="download_sertifikat.php?id=<?php echo $peserta_id; ?>"
                                                        class="btn btn-primary btn-sm"
                                                        title="Download Sertifikat">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small">
                                                        <i class="fas fa-info-circle me-1"></i> Belum ada sertifikat
                                                    </span>
                                                <?php endif; ?>

                                                <a href="edit_peserta.php?id=<?php echo $peserta_id; ?>"
                                                    class="btn btn-warning btn-sm"
                                                    title="Edit Data">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="hapus_peserta.php?id=<?php echo $peserta_id; ?>"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Pindahkan ke trash?')"
                                                    title="Hapus Data">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <h5>Tidak ada data peserta</h5>
                                        <p class="mb-4">Mulai dengan menambahkan peserta baru</p>
                                        <a href="input_peserta_pkl.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i> Tambah Peserta Pertama
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>

    <script>
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });

            // Focus search input
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.focus();
            }
        });

        // Search functionality (SAFE)
        function performSearch() {
            const searchInput = document.getElementById('searchInput');
            const rows = document.querySelectorAll("#tabelPeserta tbody tr");
            const counterBadge = document.querySelector('.peserta-count');

            let filter = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            rows.forEach(row => {
                // Skip empty row
                if (row.querySelector('.text-center')) {
                    return;
                }

                let namaElement = row.querySelector('.peserta-nama');
                let sekolahElement = row.querySelector('.peserta-sekolah');
                let certNumberElement = row.querySelector('.cert-number');
                let keteranganCell = row.cells[3];

                if (!namaElement || !sekolahElement) return;

                let nama = namaElement.textContent.toLowerCase();
                let sekolah = sekolahElement.textContent.toLowerCase();
                let keterangan = keteranganCell ? keteranganCell.textContent.toLowerCase() : '';
                let certNumber = certNumberElement ? certNumberElement.textContent.toLowerCase() : '';

                let match = false;

                if (filter) {
                    match = nama.includes(filter) ||
                        sekolah.includes(filter) ||
                        keterangan.includes(filter) ||
                        certNumber.includes(filter);
                } else {
                    match = true;
                }

                if (match) {
                    row.style.display = "";
                    visibleCount++;

                    // Highlight text (SAFE)
                    if (filter) {
                        highlightText(namaElement, filter);
                        highlightText(sekolahElement, filter);
                    } else {
                        removeHighlight(namaElement);
                        removeHighlight(sekolahElement);
                    }
                } else {
                    row.style.display = "none";
                    removeHighlight(namaElement);
                    removeHighlight(sekolahElement);
                }
            });

            // Update counter
            if (counterBadge) {
                let counterText = visibleCount + ' peserta';
                if (filter) {
                    counterText += ' ditemukan';
                }
                counterBadge.textContent = counterText;
            }
        }

        function highlightText(element, term) {
            if (!element) return;

            let originalText = element.textContent;
            let regex = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');

            let fragment = document.createDocumentFragment();
            let lastIndex = 0;

            originalText.replace(regex, function(match, p1, offset) {
                // Add text node
                if (offset > lastIndex) {
                    fragment.appendChild(
                        document.createTextNode(originalText.substring(lastIndex, offset))
                    );
                }

                // Add mark element
                let mark = document.createElement('mark');
                mark.className = 'highlight';
                mark.textContent = match;
                fragment.appendChild(mark);

                lastIndex = offset + match.length;
            });

            // Add remaining text
            if (lastIndex < originalText.length) {
                fragment.appendChild(
                    document.createTextNode(originalText.substring(lastIndex))
                );
            }

            element.textContent = '';
            element.appendChild(fragment);
        }

        function removeHighlight(element) {
            if (!element) return;
            let text = element.textContent;
            element.textContent = text;
        }

        function clearSearch() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = '';
                performSearch();
                searchInput.focus();
            }
        }

        function refreshTable() {
            location.reload();
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');

            if (searchInput) {
                searchInput.addEventListener('input', performSearch);
                searchInput.addEventListener('keyup', function(e) {
                    if (e.key === 'Escape') {
                        clearSearch();
                    }
                });
            }
        });
    </script>

</body>

</html>