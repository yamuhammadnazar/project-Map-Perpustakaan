<?php
// Database connection
$connection = mysqli_connect("localhost", "root", "", "maps_perpustakaan", 3306);

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $jumlah_koleksi = $_POST['jumlah_koleksi'];
    $kepemilikan = $_POST['kepemilikan'];

    // Update query
    $query = "UPDATE perpustakaan_tb
              SET name = '$name',
                  address = '$address',
                  coordinate = ST_GeomFromText('POINT($latitude $longitude)', 4326),
                  jumlah_koleksi = '$jumlah_koleksi',
                  kepemilikan = '$kepemilikan'
              WHERE id = $id";

    // Execute the query and check if it was successful
    if (mysqli_query($connection, $query)) {
        header("Location: read.php?modal=success&message=Data berhasil diperbarui");
    } else {
        header("Location: read.php?modal=error&message=Gagal memperbarui data");
    }
}

mysqli_close($connection);
?>