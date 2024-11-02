<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$connection = mysqli_connect("localhost", "root", "", "maps_perpustakaan", 3306);

// Queries for statistics
$totalLibrariesQuery = "SELECT COUNT(*) as total FROM perpustakaan_tb";
$totalCollectionsQuery = "SELECT SUM(jumlah_koleksi) as total FROM perpustakaan_tb";
$query = "SELECT name, jumlah_koleksi FROM perpustakaan_tb ORDER BY jumlah_koleksi DESC LIMIT 20";

// Updated ownership query to include percentage calculation
$ownershipQuery = "SELECT 
    kepemilikan, 
    COUNT(*) as total,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM perpustakaan_tb), 1) as percentage 
    FROM perpustakaan_tb 
    GROUP BY kepemilikan";

$collectionSizeQuery = "SELECT 
    CASE 
        WHEN jumlah_koleksi >= 10000 THEN 'Koleksi > 10000'
        WHEN jumlah_koleksi >= 5000 THEN 'Koleksi 5000-10000'
        WHEN jumlah_koleksi >= 1000 THEN 'Koleksi 1000-5000'
        ELSE 'Koleksi < 1000'
    END as size_category,
    COUNT(*) as total
    FROM perpustakaan_tb
    GROUP BY size_category";

$totalLibraries = mysqli_fetch_assoc(mysqli_query($connection, $totalLibrariesQuery))['total'];
$totalCollections = mysqli_fetch_assoc(mysqli_query($connection, $totalCollectionsQuery))['total'];
$result = mysqli_query($connection, $query);
$ownershipResult = mysqli_query($connection, $ownershipQuery);
$collectionSizeResult = mysqli_query($connection, $collectionSizeQuery);

$labels = [];
$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $labels[] = $row['name'];
    $data[] = $row['jumlah_koleksi'];
}

$ownershipLabels = [];
$ownershipData = [];
$ownershipPercentages = [];
while($row = mysqli_fetch_assoc($ownershipResult)) {
    $ownershipLabels[] = ucwords(str_replace('_', ' ', $row['kepemilikan']));
    $ownershipData[] = $row['total'];
    $ownershipPercentages[] = $row['percentage'];
}

$collectionDistQuery = "SELECT name, jumlah_koleksi 
    FROM perpustakaan_tb 
    ORDER BY jumlah_koleksi DESC";

$collectionDistResult = mysqli_query($connection, $collectionDistQuery);

$libraryNames = [];
$collectionCounts = [];
while($row = mysqli_fetch_assoc($collectionDistResult)) {
    $libraryNames[] = $row['name'];
    $collectionCounts[] = $row['jumlah_koleksi'];
}

// Get all used IDs
$usedIdsQuery = "SELECT id FROM perpustakaan_tb ORDER BY id";
$usedIdsResult = mysqli_query($connection, $usedIdsQuery);
$usedIds = [];
while($row = mysqli_fetch_assoc($usedIdsResult)) {
    $usedIds[] = $row['id'];
}

// Find gaps in IDs
$maxId = end($usedIds);
$allIds = range(1, $maxId);
$emptyIds = array_diff($allIds, $usedIds);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Perpustakaan</title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
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

        .stats-card {
            border-radius: 15px;
            border: none;
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .chart-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .welcome-section {
            background: linear-gradient(to right, var(--primary-color), #0f4c4c);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .action-card {
            border-radius: 15px;
            border: none;
            transition: all 0.3s;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="lokasi.php">
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
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>Selamat datang Di Departemen Informatika, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p class="mb-0">Kelola Data Perpustakaan dengan mudah dan efisien</p>
        </div>

         <!-- ID Tracking Section -->
         <div class="row mb-4">
            <div class="col-12">
                <div class="card chart-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Status ID Perpustakaan</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">ID yang Sudah Digunakan</h6>
                                    <p class="mb-0">
                                        <?php
                                        if (!empty($usedIds)) {
                                            echo implode(', ', $usedIds);
                                        } else {
                                            echo "Belum ada ID yang digunakan";
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <h6 class="alert-heading">ID yang Masih Kosong</h6>
                                    <p class="mb-0">
                                        <?php
                                        if (!empty($emptyIds)) {
                                            echo implode(', ', $emptyIds);
                                        } else {
                                            echo "Tidak ada ID yang kosong";
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-success">
                            <h6 class="alert-heading">ID Berikutnya yang Tersedia</h6>
                            <p class="mb-0">
                                <?php
                                if (!empty($emptyIds)) {
                                    echo min($emptyIds);
                                } else {
                                    echo $maxId + 1;
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-building text-primary mb-3" style="font-size: 2rem;"></i>
                        <h3 class="display-4 fw-bold"><?php echo number_format($totalLibraries); ?></h3>
                        <p class="text-muted">Total Perpustakaan</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stats-card">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-book text-success mb-3" style="font-size: 2rem;"></i>
                        <h3 class="display-4 fw-bold"><?php echo number_format($totalCollections); ?></h3>
                        <p class="text-muted">Total Koleksi Buku</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mb-4">
            <div class="col-12 mb-4">
                <div class="card chart-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Distribusi Kepemilikan Perpustakaan Di Kota Pontianak</h5>
                        <div style="height: 400px;">
                            <canvas id="ownershipChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card chart-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Distribusi Sebaran Jumlah Koleksi Buku Di Perpustakaan Kota Pontianak</h5>
                        <div style="height: 400px;">
                            <canvas id="collectionSizeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card chart-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">List Perpustakaan Di Kota Pontianak </h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">No</th>
                                        <th scope="col">Nama Perpustakaan</th>
                                        <th scope="col">Jumlah Koleksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    foreach($labels as $key => $name): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= $name ?></td>
                                            <td><?= number_format($data[$key]) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       
  <!-- Action Cards -->
<div class="row justify-content-center">
    <div class="col-md-4 mb-4">
        <div class="card action-card h-100">
            <div class="card-body text-center p-4 d-flex flex-column">
                <i class="bi bi-plus-circle text-primary mb-3" style="font-size: 2rem;"></i>
                <h5 class="card-title">Tambah Lokasi Baru</h5>
                <p class="card-text flex-grow-1">Tambahkan data perpustakaan baru ke dalam sistem</p>
                <a href="create.php" class="btn custom-btn mt-auto">Tambah Lokasi</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card action-card h-100">
            <div class="card-body text-center p-4 d-flex flex-column">
                <i class="bi bi-pencil-square text-success mb-3" style="font-size: 2rem;"></i>
                <h5 class="card-title">Kelola Data</h5>
                <p class="card-text flex-grow-1">Perbarui atau hapus data perpustakaan yang ada</p>
                <a href="read.php" class="btn custom-btn mt-auto">Kelola Data</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card action-card h-100">
            <div class="card-body text-center p-4 d-flex flex-column">
                <i class="bi bi-geo-alt text-warning mb-3" style="font-size: 2rem;"></i>
                <h5 class="card-title">Lokasi Perpustakaan</h5>
                <p class="card-text flex-grow-1">Lihat peta sebaran lokasi perpustakaan</p>
                <a href="lokasi.php" class="btn custom-btn mt-auto">Lihat Lokasi</a>
            </div>
        </div>
    </div>
</div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const pieCtx = document.getElementById('ownershipChart');
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($ownershipLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($ownershipData); ?>,
                    backgroundColor: [
                        '#072326', '#2E86AB', '#4FB0C6', '#57f7f7', '#45B7D1'
                    ],
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        align: 'center',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                const percentages = <?php echo json_encode($ownershipPercentages); ?>;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        return {
                                            text: `${label} (${percentages[i]}%)`,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                const percentages = <?php echo json_encode($ownershipPercentages); ?>;
                                const value = context.raw;
                                return `${value} perpustakaan (${percentages[context.dataIndex]}%)`;
                            }
                        }
                    }
                }
            }
        });

        const collectionSizeCtx = document.getElementById('collectionSizeChart');
        new Chart(collectionSizeCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($libraryNames); ?>,
                datasets: [{
                    label: 'Jumlah Koleksi',
                    data: <?php echo json_encode($collectionCounts); ?>,
                    backgroundColor: [
                        '#2E86AB', '#4FB0C6', '#072326', '#57f7f7', '#45B7D1',
                        '#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6',
                        '#1abc9c', '#d35400', '#34495e', '#16a085', '#c0392b'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.raw || 0;
                                return `${value.toLocaleString()} koleksi`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Jumlah Koleksi'
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <?php if(isset($_SESSION['success_message'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo $_SESSION['success_message']; ?>',
            confirmButtonColor: '#072326'
        });
    </script>
    <?php unset($_SESSION['success_message']); endif; ?>

</body>
</html>

