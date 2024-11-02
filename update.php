<?php 
// Menerima id dari read.html
$id = intval($_GET['id']);

// Query untuk menemukan data dengan id yang sesuai
$query = "
    SELECT
        id,
        name,
        address,
        jumlah_koleksi,
        kepemilikan,
        ST_Y(coordinate) AS lat, ST_X(coordinate) AS lng
    FROM perpustakaan_tb
    WHERE id = $id
";

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

// Mengirim query untuk mengambil data
$library = send_query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Library</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous"/>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        #peta { 
            height: 500px; 
            margin-top: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .custom-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }
        .btn-update {
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            transition: transform 0.2s;
        }
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }
        .page-header {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 30px 0;
            margin-bottom: 40px;
            border-radius: 0 0 50px 50px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="page-header">
        <div class="container">
            <h2 class="text-center mb-0"><i class="fas fa-edit me-2"></i>Update Library</h2>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card custom-card">
                    <div class="card-body p-4">
                        <form action="process-update.php" method="post">
                            <input type="hidden" name="id" value="<?= $library[0]['id']; ?>">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-book-reader me-2"></i>Nama Perpustakaan</label>
                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($library[0]['name']); ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-map-marker-alt me-2"></i>Alamat</label>
                                <textarea class="form-control" name="address" rows="3" required><?= htmlspecialchars($library[0]['address']); ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-books me-2"></i>Total Koleksi</label>
                                <input type="number" class="form-control" name="jumlah_koleksi" value="<?= $library[0]['jumlah_koleksi']; ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-building me-2"></i>Kepemilikan</label>
                                <select class="form-select" name="kepemilikan" required>
                                    <option value="instansi_pemerintah" <?= $library[0]['kepemilikan'] == 'instansi_pemerintah' ? 'selected' : ''; ?>>Instansi Pemerintah</option>
                                    <option value="pribadi" <?= $library[0]['kepemilikan'] == 'pribadi' ? 'selected' : ''; ?>>Pribadi</option>
                                    <option value="swasta" <?= $library[0]['kepemilikan'] == 'swasta' ? 'selected' : ''; ?>>Swasta</option>
                                    <option value="perguruan_tinggi" <?= $library[0]['kepemilikan'] == 'perguruan_tinggi' ? 'selected' : ''; ?>>Perguruan Tinggi</option>
                                    <option value="organisasi" <?= $library[0]['kepemilikan'] == 'organisasi' ? 'selected' : ''; ?>>Organisasi</option>
                                </select>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold"><i class="fas fa-latitude me-2"></i>Latitude</label>
                                    <input type="text" id="latitude" class="form-control" name="latitude" value="<?= $library[0]['lat']; ?>" readonly>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold"><i class="fas fa-longitude me-2"></i>Longitude</label>
                                    <input type="text" id="longitude" class="form-control" name="longitude" value="<?= $library[0]['lng']; ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-3 mb-3">
                                <button type="submit" class="btn btn-primary btn-update w-50">
                                    <i class="fas fa-save me-2"></i>Perbarui
                                </button>
                                <a href="read.php" class="btn btn-dark btn-update w-50">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </a>
                            </div>

                        </form>
                    </div>
                </div>

                <div id="peta" class="mt-4"></div>
            </div>
        </div>
    </div>

    <script>
        var map = L.map("peta").setView([-0.05362918468785933, 109.3478664012471], 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            minZoom: 1,
            maxzoom:60,
        }).addTo(map);
        var marker = L.marker([-0.05362918468785933, 109.3478664012471]).addTo(map);
        var inputLat = document.getElementById('latitude');
        var inputLng = document.getElementById('longitude');
        map.on('click', function(e) {
            if (marker) { map.removeLayer(marker); }
            marker = L.marker(e.latlng).addTo(map);
            inputLat.value = e.latlng.lat;
            inputLng.value = e.latlng.lng;
        });
    </script>
</body>
</html>
