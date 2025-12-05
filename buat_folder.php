<?php
session_start();
include 'koneksi.php';

// Cek login admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login_admin.php');
    exit;
}

$alert = '';
$alert_type = '';

// Handle tambah folder
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_folder'])) {

    $nama_folder = trim($_POST['nama_folder']);
    $deskripsi = trim($_POST['deskripsi']);

    // Validasi input
    if ($nama_folder == '') {
        $alert = 'Nama folder wajib diisi.';
        $alert_type = 'warning';
    } else {
        // Cek apakah folder sudah ada
        $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM folder_peserta WHERE nama_folder = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $nama_folder);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        mysqli_stmt_close($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            $alert = 'Nama folder sudah ada. Gunakan nama yang berbeda.';
            $alert_type = 'warning';
        } else {
            // Insert folder baru
            $stmt = mysqli_prepare($koneksi, "
                INSERT INTO folder_peserta (nama_folder, deskripsi, dibuat_pada)
                VALUES (?, ?, NOW())
            ");

            mysqli_stmt_bind_param($stmt, "ss", $nama_folder, $deskripsi);
            $q = mysqli_stmt_execute($stmt);

            if ($q) {
                $alert = 'Folder berhasil dibuat.';
                $alert_type = 'success';
                $_POST = array();
            } else {
                $alert = 'Gagal membuat folder: ' . mysqli_error($koneksi);
                $alert_type = 'danger';
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Handle edit folder
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_folder'])) {

    $folder_id = intval($_POST['folder_id']);
    $nama_folder = trim($_POST['nama_folder']);
    $deskripsi = trim($_POST['deskripsi']);

    // Validasi input
    if ($nama_folder == '') {
        $alert = 'Nama folder wajib diisi.';
        $alert_type = 'warning';
    } else {
        // Cek apakah nama folder sudah ada (selain folder yang sedang diedit)
        $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM folder_peserta WHERE nama_folder = ? AND id != ?");
        mysqli_stmt_bind_param($stmt_check, "si", $nama_folder, $folder_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        mysqli_stmt_close($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            $alert = 'Nama folder sudah ada. Gunakan nama yang berbeda.';
            $alert_type = 'warning';
        } else {
            // Update folder
            $stmt = mysqli_prepare($koneksi, "
                UPDATE folder_peserta 
                SET nama_folder = ?, deskripsi = ?
                WHERE id = ?
            ");

            mysqli_stmt_bind_param($stmt, "ssi", $nama_folder, $deskripsi, $folder_id);
            $q = mysqli_stmt_execute($stmt);

            if ($q) {
                $alert = 'Folder berhasil diperbarui.';
                $alert_type = 'success';
                $_POST = array();
            } else {
                $alert = 'Gagal memperbarui folder: ' . mysqli_error($koneksi);
                $alert_type = 'danger';
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Handle hapus folder
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Hapus folder
    $stmt = mysqli_prepare($koneksi, "DELETE FROM folder_peserta WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $delete_id);

    if (mysqli_stmt_execute($stmt)) {
        $alert = 'Folder berhasil dihapus.';
        $alert_type = 'success';
    } else {
        $alert = 'Gagal menghapus folder: ' . mysqli_error($koneksi);
        $alert_type = 'danger';
    }

    mysqli_stmt_close($stmt);
}

// Handle drag and drop (AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_peserta_to_folder') {
    header('Content-Type: application/json');

    $folder_id = intval($_POST['folder_id']);
    $peserta_id = intval($_POST['peserta_id']);

    // Cek apakah peserta sudah ada di folder
    $stmt_check = mysqli_prepare($koneksi, "
        SELECT id FROM folder_peserta_mapping 
        WHERE folder_id = ? AND peserta_id = ?
    ");
    mysqli_stmt_bind_param($stmt_check, "ii", $folder_id, $peserta_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    mysqli_stmt_close($stmt_check);

    if (mysqli_num_rows($result_check) > 0) {
        echo json_encode(['success' => false, 'message' => 'Peserta sudah ada di folder ini']);
        exit;
    }

    // Insert peserta ke folder
    $stmt = mysqli_prepare($koneksi, "
        INSERT INTO folder_peserta_mapping (folder_id, peserta_id, ditambahkan_pada)
        VALUES (?, ?, NOW())
    ");

    mysqli_stmt_bind_param($stmt, "ii", $folder_id, $peserta_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Peserta berhasil ditambahkan ke folder']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan peserta: ' . mysqli_error($koneksi)]);
    }

    mysqli_stmt_close($stmt);
    exit;
}

// Handle remove peserta dari folder (AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'remove_peserta_from_folder') {
    header('Content-Type: application/json');

    $mapping_id = intval($_POST['mapping_id']);

    $stmt = mysqli_prepare($koneksi, "DELETE FROM folder_peserta_mapping WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $mapping_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Peserta berhasil dihapus dari folder']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus peserta']);
    }

    mysqli_stmt_close($stmt);
    exit;
}

// Ambil semua folder dengan peserta
$stmt = mysqli_prepare($koneksi, "
    SELECT 
        f.id, 
        f.nama_folder, 
        f.deskripsi, 
        f.dibuat_pada,
        COUNT(fp.id) as jumlah_peserta
    FROM folder_peserta f
    LEFT JOIN folder_peserta_mapping fp ON f.id = fp.folder_id
    GROUP BY f.id
    ORDER BY f.dibuat_pada DESC
");
mysqli_stmt_execute($stmt);
$result_folders = mysqli_stmt_get_result($stmt);
$folders = mysqli_fetch_all($result_folders, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Ambil semua peserta dengan asal sekolah
$stmt = mysqli_prepare($koneksi, "
    SELECT DISTINCT 
        p.id, 
        p.nama,
        p.sekolah
    FROM peserta_pkl p
    ORDER BY p.nama ASC
");
mysqli_stmt_execute($stmt);
$result_peserta = mysqli_stmt_get_result($stmt);
$peserta_list = mysqli_fetch_all($result_peserta, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Ambil peserta per folder dengan asal sekolah
$peserta_per_folder = [];
$stmt = mysqli_prepare($koneksi, "
    SELECT 
        fpm.id as mapping_id,
        fpm.folder_id,
        p.id,
        p.nama,
        p.sekolah
    FROM folder_peserta_mapping fpm
    JOIN peserta_pkl p ON fpm.peserta_id = p.id
    ORDER BY fpm.folder_id, p.nama ASC
");
mysqli_stmt_execute($stmt);
$result_peserta_folder = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result_peserta_folder)) {
    $peserta_per_folder[$row['folder_id']][] = $row;
}
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Folder Peserta</title>
    <link rel="icon" type="image/x-icon" href="foto/logo sae.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/buat_folder.css?v=<?php echo time(); ?>">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="buat_folder.php">
                <i class="fas fa-folder-open me-2"></i> Kelola Folder Peserta
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="btn btn-sm btn-light" href="dashboard_sertifikat_pkl.php">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-5 pb-5">

        <!-- Alert -->
        <?php if ($alert != ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo htmlspecialchars($alert); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Form Tambah Folder -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-plus-circle me-2"></i> Buat Folder Baru
                    </div>
                    <div class="card-body">
                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-folder me-1"></i> Nama Folder <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="nama_folder"
                                    class="form-control"
                                    placeholder="Contoh: Batch 1, SMK Negeri 1, dll"
                                    value="<?php echo isset($_POST['nama_folder']) ? htmlspecialchars($_POST['nama_folder']) : ''; ?>"
                                    required>
                                <small class="text-muted">Nama unik untuk pengelompokan peserta</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-align-left me-1"></i> Deskripsi (Opsional)
                                </label>
                                <textarea
                                    name="deskripsi"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Masukkan deskripsi folder..."><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
                                <small class="text-muted">Deskripsi untuk memudahkan identifikasi folder</small>
                            </div>

                            <button type="submit" name="add_folder" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Buat Folder
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Daftar Folder -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list me-2"></i> Daftar Folder (<?php echo count($folders); ?>)
                    </div>
                    <div class="card-body p-0">
                        <div class="folder-list-scroll" style="max-height: 400px; overflow-y: auto;">
                            <?php if (count($folders) > 0): ?>
                                <?php foreach ($folders as $folder): ?>
                                    <div class="folder-item m-3 mb-2">
                                        <h6>
                                            <i class="fas fa-folder me-2"></i> <?php echo htmlspecialchars($folder['nama_folder']); ?>
                                        </h6>
                                        <p class="small mb-2">
                                            <?php echo $folder['deskripsi'] ? htmlspecialchars(substr($folder['deskripsi'], 0, 50)) . '...' : '<em class="text-muted">Tanpa deskripsi</em>'; ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-primary"><?php echo $folder['jumlah_peserta']; ?> Peserta</span>
                                            <div class="folder-actions">
                                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal" onclick="editFolder(<?php echo $folder['id']; ?>, '<?php echo htmlspecialchars(addslashes($folder['nama_folder'])); ?>', '<?php echo htmlspecialchars(addslashes($folder['deskripsi'])); ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?delete_id=<?php echo $folder['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus folder ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>Belum ada folder</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Drag and Drop Section -->
            <div class="col-lg-8">
                <div class="row">
                    <!-- Peserta Available -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-users me-2"></i> Daftar Peserta
                            </div>
                            <div class="card-body">
                                <!-- Fitur Pencarian -->
                                <div class="search-container mb-3">
                                    <i class="fas fa-search search-icon"></i>
                                    <input
                                        type="text"
                                        class="form-control search-input"
                                        id="searchPeserta"
                                        placeholder="Cari nama atau sekolah..."
                                        onkeyup="filterPeserta()">
                                    <button
                                        class="clear-search"
                                        type="button"
                                        onclick="clearSearch()"
                                        style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                <p class="small text-muted mb-3">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Drag peserta ke folder untuk menambahkannya
                                </p>

                                <div class="peserta-source custom-scrollbar" id="pesertaSource">
                                    <?php if (count($peserta_list) > 0): ?>
                                        <?php foreach ($peserta_list as $peserta): ?>
                                            <div
                                                class="peserta-item"
                                                draggable="true"
                                                data-peserta-id="<?php echo $peserta['id']; ?>"
                                                data-nama="<?php echo htmlspecialchars(strtolower($peserta['nama'])); ?>"
                                                data-sekolah="<?php echo htmlspecialchars(strtolower($peserta['sekolah'] ?? '')); ?>"
                                                ondragstart="dragStart(event)"
                                                ondragend="dragEnd(event)">
                                                <div class="peserta-avatar">
                                                    <i class="fas fa-user-circle"></i>
                                                </div>
                                                <div class="peserta-info">
                                                    <div class="peserta-nama"><?php echo htmlspecialchars($peserta['nama']); ?></div>
                                                    <div class="peserta-sekolah">
                                                        <i class="fas fa-school school-icon"></i>
                                                        <?php echo htmlspecialchars($peserta['sekolah'] ?? 'Tidak diketahui'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p>Tidak ada peserta</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Counter Peserta -->
                                <div class="peserta-counter">
                                    <span id="pesertaCount"><?php echo count($peserta_list); ?></span> peserta ditemukan
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Folder Drop Zones -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-folder-plus me-2"></i> Drop Peserta ke Folder
                            </div>
                            <div class="card-body">
                                <?php if (count($folders) > 0): ?>
                                    <div class="folder-scroll-container" style="max-height: 500px; overflow-y: auto;">

                                        <?php foreach ($folders as $folder): ?>
                                            <div class="mb-4">
                                                <h6 class="mb-3">
                                                    <i class="fas fa-folder me-2"></i>
                                                    <?php echo htmlspecialchars($folder['nama_folder']); ?>
                                                    <span class="badge bg-info"><?php echo $folder['jumlah_peserta']; ?></span>
                                                </h6>

                                                <div
                                                    class="folder-drop-zone"
                                                    data-folder-id="<?php echo $folder['id']; ?>"
                                                    ondragover="dragOver(event)"
                                                    ondragleave="dragLeave(event)"
                                                    ondrop="dropPeserta(event)">

                                                    <?php if (isset($peserta_per_folder[$folder['id']]) && count($peserta_per_folder[$folder['id']]) > 0): ?>
                                                        <?php foreach ($peserta_per_folder[$folder['id']] as $p): ?>
                                                            <div class="peserta-in-folder">
                                                                <div class="peserta-info">
                                                                    <div class="peserta-nama">
                                                                        <i class="fas fa-check-circle text-success me-2"></i>
                                                                        <?php echo htmlspecialchars($p['nama']); ?>
                                                                    </div>
                                                                    <div class="peserta-sekolah">
                                                                        <i class="fas fa-school school-icon"></i>
                                                                        <?php echo htmlspecialchars($p['sekolah'] ?? 'Tidak diketahui'); ?>
                                                                    </div>
                                                                </div>
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-danger"
                                                                    onclick="removePesertaFromFolder(<?php echo $p['mapping_id']; ?>)">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="empty-state">
                                                            <i class="fas fa-inbox me-2"></i>
                                                            Drag peserta ke sini
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-folder-open fa-2x mb-2"></i>
                                        <p>Belum ada folder. Buat folder terlebih dahulu.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Edit Folder -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i> Edit Folder
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="folder_id" id="folder_id" value="">

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-folder me-1"></i> Nama Folder <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                name="nama_folder"
                                id="nama_folder"
                                class="form-control"
                                placeholder="Nama folder"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-align-left me-1"></i> Deskripsi
                            </label>
                            <textarea
                                name="deskripsi"
                                id="deskripsi"
                                class="form-control"
                                rows="3"
                                placeholder="Deskripsi folder..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_folder" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let draggedPeserta = null;

        // Fungsi untuk filter peserta
        function filterPeserta() {
            const searchTerm = document.getElementById('searchPeserta').value.toLowerCase();
            const pesertaItems = document.querySelectorAll('#pesertaSource .peserta-item');
            const clearBtn = document.querySelector('.clear-search');
            let visibleCount = 0;

            pesertaItems.forEach(item => {
                const nama = item.getAttribute('data-nama').toLowerCase();
                const sekolah = item.getAttribute('data-sekolah').toLowerCase();

                if (nama.includes(searchTerm) || sekolah.includes(searchTerm)) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Update counter
            document.getElementById('pesertaCount').textContent = visibleCount;

            // Show/hide clear button
            if (clearBtn) {
                clearBtn.style.display = searchTerm ? 'block' : 'none';
            }

            // Tampilkan pesan jika tidak ada hasil
            const emptyState = document.querySelector('#pesertaSource .empty-state');
            if (emptyState) {
                if (visibleCount === 0 && searchTerm) {
                    emptyState.innerHTML = '<i class="fas fa-search fa-2x mb-2"></i><p>Tidak ditemukan peserta dengan kata kunci "' + searchTerm + '"</p>';
                    emptyState.style.display = 'block';
                } else if (visibleCount === 0) {
                    emptyState.innerHTML = '<i class="fas fa-inbox fa-2x mb-2"></i><p>Tidak ada peserta</p>';
                    emptyState.style.display = 'block';
                } else {
                    emptyState.style.display = 'none';
                }
            }
        }

        // Fungsi untuk clear search
        function clearSearch() {
            document.getElementById('searchPeserta').value = '';
            filterPeserta();
        }

        // Auto-focus pada input search saat page load
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchPeserta');
            if (searchInput) {
                // Tambahkan event listener untuk ESC key
                searchInput.addEventListener('keyup', function(e) {
                    if (e.key === 'Escape') {
                        clearSearch();
                        this.blur();
                    }
                });
            }

            // Auto-hide alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Drag Start
        function dragStart(e) {
            draggedPeserta = e.target;
            e.target.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', e.target.innerHTML);
        }

        // Drag End
        function dragEnd(e) {
            if (draggedPeserta) {
                draggedPeserta.classList.remove('dragging');
            }
        }

        // Drag Over
        function dragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            e.currentTarget.classList.add('drag-over');
        }

        // Drag Leave
        function dragLeave(e) {
            e.currentTarget.classList.remove('drag-over');
        }

        // Drop Peserta
        function dropPeserta(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('drag-over');

            if (!draggedPeserta) return;

            const pesertaId = draggedPeserta.getAttribute('data-peserta-id');
            const folderId = e.currentTarget.getAttribute('data-folder-id');

            if (!pesertaId || !folderId) return;

            // Send AJAX request
            fetch('buat_folder.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=add_peserta_to_folder&folder_id=' + folderId + '&peserta_id=' + pesertaId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload page
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan');
                });
        }

        // Remove Peserta from Folder
        function removePesertaFromFolder(mappingId) {
            if (!confirm('Hapus peserta dari folder ini?')) return;

            fetch('buat_folder.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=remove_peserta_from_folder&mapping_id=' + mappingId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload page
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan');
                });
        }

        // Edit folder function
        function editFolder(id, nama, deskripsi) {
            document.getElementById('folder_id').value = id;
            document.getElementById('nama_folder').value = nama;
            document.getElementById('deskripsi').value = deskripsi;
        }
    </script>

</body>

</html>