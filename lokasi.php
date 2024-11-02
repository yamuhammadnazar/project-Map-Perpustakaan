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

$query = "SELECT id, name, address, ST_X(coordinate) AS lng, ST_Y(coordinate) AS lat, jumlah_koleksi, kepemilikan FROM perpustakaan_tb";
$locations = send_query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lokasi Perpustakaan</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    
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

        #map {
            height: 500px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .search-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .library-card {
            border-radius: 15px;
            border: none;
            transition: transform 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .library-card:hover {
            transform: translateY(-5px);
        }

        .custom-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .custom-btn:hover {
            background-color: #0a3538;
            transform: translateY(-2px);
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
        <div class="welcome-section mb-4">
            <h2>Lokasi Perpustakaan</h2>
            <p class="mb-0">Peta dan daftar perpustakaan di Pontianak</p>
        </div>

        <!-- Map -->
        <div class="card mb-4">
            <div class="card-body p-0">
                <div id="map"></div>
            </div>
        </div>

        <!-- Library List -->
        <div class="row" id="locationList">
            <?php foreach($locations as $location): ?>
            <div class="col-md-6 mb-4 location-item" data-ownership="<?= $location['kepemilikan'] ?>">
                <div class="card library-card">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($location['name']) ?></h5>
                        <p class="card-text">
                            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($location['address']) ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary"><?= htmlspecialchars($location['kepemilikan']) ?></span>
                            <span class="badge bg-secondary"><?= number_format($location['jumlah_koleksi']) ?> Koleksi</span>
                            <button class="btn custom-btn btn-sm" onclick="map.setView([<?= $location['lng'] ?>, <?= $location['lat'] ?>], 16)">
                                <i class="bi bi-geo"></i> Lihat di Peta
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        var map = L.map('map').setView([-0.04007654778875747, 109.36107982779419], 12);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var locations = <?php echo json_encode($locations); ?>;
        locations.forEach(function(location) {
            L.marker([location.lng, location.lat])
                .addTo(map)
                .bindPopup(`
                    <div class="popup-content">
                        <h6>${location.name}</h6>
                        <p class="mb-1"><small>${location.address}</small></p>
                        <p class="mb-0"><small><strong>${location.jumlah_koleksi}</strong> Koleksi</small></p>
                    </div>
                `);
        });

        function filterLocations() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const ownershipValue = document.getElementById('ownershipFilter').value;
            const items = document.querySelectorAll('.location-item');

            items.forEach(function(item) {
                const name = item.querySelector('.card-title').textContent.toLowerCase();
                const ownership = item.dataset.ownership;
                
                const matchesSearch = name.includes(searchValue);
                const matchesOwnership = ownershipValue === '' || ownership === ownershipValue;

                item.style.display = matchesSearch && matchesOwnership ? '' : 'none';
            });
        }

        document.getElementById('searchInput').addEventListener('input', filterLocations);
        document.getElementById('ownershipFilter').addEventListener('change', filterLocations);
    </script>
</body>
</html>
