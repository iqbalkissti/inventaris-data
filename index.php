<?php
// index.php - Halaman Login dengan tampilan yang diperbarui
session_start();

// Cek apakah pengguna sudah login. Jika iya, langsung arahkan ke dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';

// Proses Form Login (menggunakan hardcode)
// *** CATATAN: KODE INI MENGGUNAKAN HARDCODE, BUKAN DATABASE ***
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kredensial Hardcode (Sederhana) - Ganti nanti dengan koneksi ke tabel user
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['level'] = 'ADMIN'; 
        $_SESSION['id_user'] = 1; 
        
        header('Location: dashboard.php');
        exit();
    } else if ($username === 'user' && $password === 'user') {
        // User non-admin (opsional)
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['level'] = 'USER';
        $_SESSION['id_user'] = 2;
        
        header('Location: dashboard.php');
        exit();
    } else {
        $error_message = 'Username atau password salah!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PT. VERONIQUE INDONESIA - Login Inventaris</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f0f2f5; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            flex-direction: column;
        }
        .login-box { 
            background: #fff; 
            padding: 40px 30px; 
            border-radius: 8px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            width: 350px; 
            text-align: center; 
            box-sizing: border-box;
        }
        
        /* Gaya Judul Utama (Sesuai Desain) */
        .title-container {
            margin-bottom: 30px;
        }
        h1 {
            color: #000;
            font-size: 24px;
            margin: 0;
        }
        h2 {
            color: #4848d1; /* Warna Biru Ungu/Gelap */
            font-size: 18px;
            margin: 5px 0 0 0;
            font-weight: normal;
        }

        /* Input Fields */
        input[type="text"], 
        input[type="password"] { 
            width: 100%; 
            padding: 12px; 
            margin-bottom: 15px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            box-sizing: border-box; 
            font-size: 16px;
            text-align: left; /* Teks Placeholder/Input rata kiri */
        }
        
        /* Tombol Login */
        input[type="submit"] { 
            background-color: #1877f2; 
            color: white; 
            padding: 12px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            width: 100%; 
            font-size: 18px; 
            font-weight: bold;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover { 
            background-color: #166fe5; 
        }
        
        /* Error Message */
        .error { 
            background-color: #f8d7da;
            color: #721c24; 
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px; 
            border: 1px solid #f5c6cb;
            font-size: 14px;
        }
        
        /* Footer SINCE 2025 */
        .footer-since {
            margin-top: 50px;
            font-size: 14px;
            color: #6c757d;
        }
        .footer-since span {
            color: #4848d1;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="login-box">
        <div class="title-container">
            <h1>PT.VERONIQUE INDONESIA</h1>
            <h2>INVENTARIS SYSTEM</h2>
        </div>

        <?php
        if (!empty($error_message)) {
            echo '<div class="error">' . htmlspecialchars($error_message) . '</div>';
        }
        ?>

        <form action="index.php" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Login">
        </form>
    </div>
    
    <div class="footer-since">
        SINCE <span>2025</span>
    </div>

</body>
</html>