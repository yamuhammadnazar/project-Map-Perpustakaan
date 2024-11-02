<?php

// menerima id dari spbu yang mau dihapus
$id = $_GET['id'];

// membuat query untuk menghapus data berdasarkan id
$query = "DELETE FROM perpustakaan_tb WHERE id = $id";

$connection = mysqli_connect(
    "localhost",
    "root",
    "",
    "maps_perpustakaan",
    "3306"
);

// mengirim query

mysqli_query($connection, $query);

header('Location: read.php');
exit();

?>