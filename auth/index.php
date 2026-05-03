<?php
include 'configdb-login.php';
include '../configdb-main.php';
session_start();

if (isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['submit'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Cek user berdasarkan email (dengan prepared statement)
    $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        // Jika user ditemukan, verifikasi password
        if (password_verify($password, $user['password'])) {
            // Jika password cocok, cek status verifikasi
            if ($user['is_verified'] == 1) {
                // Login berhasil
                $_SESSION['username'] = $user['username'];
                
                // --- SAKTI ENCRYPTION: Key Derivation ---
                $salt = $user['encryption_salt'];
                if (empty($salt)) {
                    $salt = bin2hex(random_bytes(32));
                    $update_salt = "UPDATE users SET encryption_salt = ? WHERE id = ?";
                    $stmt_salt = mysqli_prepare($conn, $update_salt);
                    mysqli_stmt_bind_param($stmt_salt, "si", $salt, $user['id']);
                    mysqli_stmt_execute($stmt_salt);
                }
                
                // Derive PDK: PBKDF2 (100k iterations, SHA256, 32 bytes)
                $derived_key = hash_pbkdf2("sha256", $password, $salt, 100000, 32, true);
                $_SESSION['user_key'] = base64_encode($derived_key);
                
                // --- HYBRID VAULT: Unlock MVK ---
                if ($user['is_vault_active'] == 1 && !empty($user['master_vault_key_enc_pass'])) {
                    $mvk_raw = unwrap_key($user['master_vault_key_enc_pass'], $derived_key);
                    if ($mvk_raw) {
                        $_SESSION['master_key'] = base64_encode($mvk_raw);
                    }
                }
                // -------------------------------

                logActivity($user['username'], "Login", "User login (Vault Status: " . ($user['is_vault_active'] ? 'Active' : 'Pending Migration') . ")");
                header("Location: ../index.php");
                exit();
            } else {
                // Akun belum diverifikasi
                $_SESSION['login_error'] = 'Akun Anda belum diverifikasi. Silakan periksa email Anda.';
            }
        } else {
            // Password salah
            $_SESSION['login_error'] = 'Email atau password Anda salah.';
        }
    } else {
        // Email tidak ditemukan
        $_SESSION['login_error'] = 'Email atau password Anda salah.';
    }
    // Redirect kembali ke halaman login untuk menampilkan error
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login | Bootstrap Paste</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Selamat Datang Kembali!</h1>
                                    </div>
                                    <form action="" method="POST" class="user">
                                        <div class="form-group">
                                            <input type="email" name="email" class="form-control form-control-user" placeholder="Email" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="password" class="form-control form-control-user" placeholder="Kata Sandi" required>
                                        </div>
                                        <button name="submit" class="btn btn-primary btn-user btn-block">Login</button>
                                    </form>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="forgot-password.php">Lupa Password?</a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="register.php">Buat Akun Baru!</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.js"></script>

    <?php if (isset($_SESSION['login_error'])): ?>
        <script> swal("Gagal!", "<?php echo $_SESSION['login_error']; ?>", "error"); </script>
        <?php unset($_SESSION['login_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['login_sukses'])): ?>
        <script> swal("Berhasil!", "<?php echo $_SESSION['login_sukses']; ?>", "success"); </script>
        <?php unset($_SESSION['login_sukses']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['register_info'])): ?>
        <script> swal("Info", "<?php echo $_SESSION['register_info']; ?>", "info"); </script>
        <?php unset($_SESSION['register_info']); ?>
    <?php endif; ?>
</body>
</html>