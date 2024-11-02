<?php
session_start();

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn = new mysqli("localhost", "root", "", "maps_perpustakaan", "3306");

    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $_SESSION['username'] = $username;
        $_SESSION['success_message'] = "Login berhasil! Selamat datang " . $username;
        header("Location: dashboard.php");
        exit;
    } else {
        $error_message = "Anda tidak terdaftar, silahkan Hubungi Kepala Bagian Departemen Informatika Provinsi Kalimantan Barat";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            background-image: url('https://source.unsplash.com/1600x900/?library');
            background-size: cover;
            background-position: center;
            height: 100vh;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: auto;
            margin-top: 100px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 50px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .form-control:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
        }
        .btn-login {
            background-color: #2c3e50;
            border-color: #2c3e50;
            width: 100%;
            padding: 10px;
        }
        .btn-login:hover {
            background-color: #34495e;
            border-color: #34495e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-book-reader"></i>
                <h2>Departeman IT</h2>
                <p class="text-muted">Silakan login untuk melanjutkan</p>
            </div>

            <form action="login.php" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if(isset($error_message)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Login Gagal',
            text: '<?php echo $error_message; ?>',
            confirmButtonColor: '#2c3e50'
        });
    </script>
    <?php endif; ?>
</body>
</html>
