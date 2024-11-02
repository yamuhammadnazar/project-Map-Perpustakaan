<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$connection = mysqli_connect("localhost", "root", "", "maps_perpustakaan", 3306);

function send_query($query) {
    global $connection;
    $result = mysqli_query($connection, $query);
    $rows = [];
    while($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

$query = "SELECT id, name, address, ST_X(coordinate) AS lat, ST_Y(coordinate) AS lng, jumlah_koleksi, kepemilikan FROM perpustakaan_tb";
$locations = send_query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Lokasi Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --primary-color: #072326;
            --accent-color: #57f7f7;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        .sidebar {
            width: 280px;
            background: var(--primary-color);
            min-height: 100vh;
            position: fixed;
            transition: all 0.3s;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: #fff;
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: var(--accent-color);
        }

        .sidebar .nav-link i {
            margin-right: 10px;
        }

        .main-content {
            margin-left: 280px;
            padding: 20px;
        }

        .welcome-section {
            background: linear-gradient(to right, var(--primary-color), #0f4c4c);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .map-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }

        #map {
            height: 400px;
            border-radius: 15px;
        }

        .search-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .search-container:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .location-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
            transition: all 0.3s ease;
        }

        .location-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }

        .card-header {
            border-bottom: none;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.875rem;
        }

        .btn-warning {
            background-color: var(--primary-color);
            border: none;
            color: white;
        }

        .btn-warning:hover {
            background-color: #0a3538;
            color: white;
        }

        .location-details i {
            width: 24px;
            text-align: center;
        }

        .fw-medium {
            font-weight: 500;
        }

        .animate__animated {
            animation-duration: 0.8s;
        }

        .search-info {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 5px;
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .modal-header {
            padding: 1.5rem 1.5rem 0.5rem;
        }

        .modal-footer {
            padding: 0.5rem 1.5rem 1.5rem;
        }

        .modal .btn {
            min-width: 100px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-3">
            <h4 class="text-white mb-4">Dashboard Admin</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="lokasi.php">
                        <i class="bi bi-geo-alt"></i> Lokasi Perpustakaan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="create.php">
                        <i class="bi bi-plus-circle"></i> Tambah Lokasi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="read.php">
                        <i class="bi bi-pencil-square"></i> Perbarui Data
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="welcome-section mb-4">
                <h2>Perbarui Data</h2>
                <p class="mb-0">Ubah, Edit dan Hapus Data Lokasi Perpustakaan</p>
            </div>

            <div class="map-container animate__animated animate__fadeIn">
                <div id="map"></div>
            </div>

            <div class="search-container animate__animated animate__fadeInUp">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" 
                           id="searchInput" 
                           class="form-control form-control-lg border-0" 
                           placeholder="Cari berdasarkan nama atau kepemilikan...">
                </div>
                <div class="search-info">
                    Contoh pencarian: nama perpustakaan atau jenis kepemilikan (Instansi Pemerintah/Swasta/Pribadi/Perguruan Tinggi/Organisasi)
                </div>
            </div>

            <div id="locationsList" class="row g-4 py-3">
                <?php foreach($locations as $location): ?>
                    <div class="col-md-6 col-lg-4 location-item animate__animated animate__fadeInUp">
                        <div class="card h-100 shadow-sm location-card">
                            <div class="card-header bg-gradient text-white py-3" style="background: var(--primary-color)">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-building me-2"></i>
                                    <?= $location['name']; ?>
                                </h5>
                            </div>
                            
                            <div class="card-body p-4">
                                <div class="location-info">
                                    <div class="location-details">
                                        <div class="d-flex align-items-start mb-3">
                                            <i class="bi bi-geo-alt-fill text-danger me-3 fs-5"></i>
                                            <p class="mb-0 text-muted">
                                                <?= $location['address']; ?>
                                            </p>
                                        </div>
                                        
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="bi bi-person-badge me-3 fs-5" style="color: var(--primary-color)"></i>
                                            <p class="mb-0 text-muted">
                                                Kepemilikan: <span class="fw-medium"><?= ucwords(str_replace('_', ' ', $location['kepemilikan'])); ?></span>
                                            </p>
                                        </div>
                                        
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-book me-3 fs-5" style="color: var(--primary-color)"></i>
                                            <p class="mb-0 text-muted">
                                                Jumlah Koleksi: <span class="fw-medium"><?= number_format($location['jumlah_koleksi']); ?></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer bg-light border-0 p-3">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="update.php?id=<?= $location['id']; ?>" 
                                       class="btn btn-warning btn-sm d-flex align-items-center">
                                        <i class="bi bi-pencil-square me-2"></i>
                                        Update
                                    </a>
                                    <button type="button" 
                                            class="btn btn-danger btn-sm d-flex align-items-center delete-btn" 
                                            data-id="<?= $location['id']; ?>">
                                        <i class="bi bi-trash me-2"></i>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>Konfirmasi Hapus
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="mb-0">Apakah Anda yakin ingin menghapus data perpustakaan ini?</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger px-4">Hapus</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-success">
                        <i class="bi bi-check-circle-fill me-2"></i>Selamat, Pembaruan Anda Telah Berhasil
                    </h5>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="mb-0" id="successMessage"></p>
                 </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Gagal
                    </h5>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="mb-0" id="errorMessage"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        var map = L.map('map').setView([-0.04007654778875747, 109.36107982779419], 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {}).addTo(map);

        var locations = <?php echo json_encode($locations); ?>;
        locations.forEach(function(row) {
            L.marker([row.lat, row.lng])
             .addTo(map)
             .bindPopup("<b>" + row.name + "</b><br>" + row.address);
        });

        document.getElementById('searchInput').addEventListener('input', function() {
            var filter = this.value.toLowerCase();
            var items = document.querySelectorAll('.location-item');
            
            items.forEach(function(item) {
                var name = item.querySelector('h5').textContent.toLowerCase();
                var ownership = item.querySelector('.location-details span').textContent.toLowerCase();
                
                if (name.includes(filter) || ownership.includes(filter)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Get all delete buttons
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

            // Add click event to all delete buttons
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    confirmDeleteBtn.href = 'delete.php?id=' + id;
                    deleteModal.show();
                });
            });

            // Check for modal parameters and show appropriate modal
            <?php if(isset($_GET['modal'])): ?>
                <?php if($_GET['modal'] == 'success'): ?>
                    document.getElementById('successMessage').textContent = "<?= htmlspecialchars($_GET['message']) ?>";
                    new bootstrap.Modal(document.getElementById('successModal')).show();
                <?php elseif($_GET['modal'] == 'error'): ?>
                    document.getElementById('errorMessage').textContent = "<?= htmlspecialchars($_GET['message']) ?>";
                    new bootstrap.Modal(document.getElementById('errorModal')).show();
                <?php endif; ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>
