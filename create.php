<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$connection = mysqli_connect("localhost", "root", "", "maps_perpustakaan", 3306);

// Get all used IDs
$usedIdsQuery = "SELECT id FROM perpustakaan_tb ORDER BY id";
$usedIdsResult = mysqli_query($connection, $usedIdsQuery);
$usedIds = [];
while($row = mysqli_fetch_assoc($usedIdsResult)) {
    $usedIds[] = $row['id'];
}

// Find gaps in IDs
$maxId = empty($usedIds) ? 0 : max($usedIds);
$allIds = range(1, $maxId + 1);
$emptyIds = array_diff($allIds, $usedIds);
$nextAvailableId = empty($emptyIds) ? $maxId + 1 : min($emptyIds);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['inputID'];
    $name = $_POST['inputName'];
    $address = $_POST['inputAddress'];
    $latitude = $_POST['inputLatitude'];
    $longitude = $_POST['inputLongitude'];
    $jumlah_koleksi = $_POST['inputJumlahKoleksi'];
    $kepemilikan = $_POST['inputKepemilikan'];

    $query = "INSERT INTO perpustakaan_tb (id, name, address, coordinate, jumlah_koleksi, kepemilikan)
    VALUES ('$id', '$name', '$address', 
    ST_PointFromText('POINT($longitude $latitude)'), 
    '$jumlah_koleksi', '$kepemilikan')";

    if (mysqli_query($connection, $query)) {
        header("location: read.php");
    } else {
        echo "Error: " . $query . "<br>" . mysqli_error($connection);
    }
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Lokasi Perpustakaan</title>
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

        #map {
            height: 400px;
            margin-bottom: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(7, 35, 38, 0.25);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .custom-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .custom-btn:hover {
            background-color: #0a3538;
            transform: translateY(-2px);
            color: white;
        }

        .welcome-section {
            background: linear-gradient(to right, var(--primary-color), #0f4c4c);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-in-out;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
                    <a class="nav-link" href="lokasi.php">
                        <i class="bi bi-geo-alt"></i> Lokasi Perpustakaan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="create.php">
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
            <h2>Tambah Lokasi Perpustakaan</h2>
            <p class="mb-0">Tambahkan data perpustakaan baru dengan mengisi form berikut</p>
        </div>

        <!-- ID Status Card -->
        <div class="card mb-4 animate__animated animate__fadeIn">
            <div class="card-body">
                <h5 class="card-title">Status ID Perpustakaan</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">ID yang Sudah Digunakan</h6>
                            <p class="mb-0">
                                <?php echo !empty($usedIds) ? implode(', ', $usedIds) : "Belum ada ID yang digunakan"; ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">ID yang Masih Kosong</h6>
                            <p class="mb-0">
                                <?php echo !empty($emptyIds) ? implode(', ', $emptyIds) : "Tidak ada ID yang kosong"; ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success">
                            <h6 class="alert-heading">ID Berikutnya yang Tersedia</h6>
                            <p class="mb-0"><?php echo $nextAvailableId; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="container">
            <div id="map" class="animate__animated animate__fadeIn"></div>
            
            <div class="card animate__animated animate__fadeInUp">
                <div class="card-body p-4">
                    <form action="create.php" method="post">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ID Perpustakaan</label>
                                    <input type="text" class="form-control" name="inputID" placeholder="ID yang disarankan: <?php echo $nextAvailableId; ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Perpustakaan</label>
                                    <input type="text" class="form-control" name="inputName" required>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea class="form-control" name="inputAddress" rows="3" required></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Latitude</label>
                                    <input type="text" class="form-control" name="inputLatitude" id="inputLatitude" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Longitude</label>
                                    <input type="text" class="form-control" name="inputLongitude" id="inputLongitude" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Koleksi</label>
                                    <input type="number" class="form-control" name="inputJumlahKoleksi" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kepemilikan</label>
                                    <select class="form-select" name="inputKepemilikan" required>
                                        <option value="instansi_pemerintah">Instansi Pemerintah</option>
                                        <option value="pribadi">Pribadi</option>
                                        <option value="swasta">Swasta</option>
                                        <option value="perguruan_tinggi">Perguruan Tinggi</option>
                                        <option value="organisasi">Organisasi</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn custom-btn me-md-2">
                                <i class="bi bi-save me-2"></i>Simpan
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        var map = L.map('map').setView([-0.05362918468785933, 109.3478664012471], 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 17,
        }).addTo(map);

        var marker;
        map.on('click', function(e) {
            if(marker) { map.removeLayer(marker); }
            marker = L.marker(e.latlng).addTo(map);
            document.getElementById('inputLatitude').value = e.latlng.lng;
            document.getElementById('inputLongitude').value = e.latlng.lat;
        });
    </script>
</body>
</html>