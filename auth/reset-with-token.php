<?php
require '../configdb-main.php';
session_start();
date_default_timezone_set('Asia/Jakarta');

$token = $_GET['token'] ?? '';
$error_message = '';
$show_form = false;

if (empty($token)) {
    $error_message = "Token tidak ditemukan. Silakan ulangi proses lupa password.";
} else {
    // Verifikasi token dari database
    $query = "SELECT email FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()";
    $stmt = mysqli_prepare($mysqli, $query);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $show_form = true;
    } else {
        $error_message = "Token tidak valid atau telah kedaluwarsa. Silakan ulangi proses lupa password.";
    }
}

// Proses form jika disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi input
    if ($password !== $confirm_password) {
        $_SESSION['reset_error'] = "Password dan konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 8) {
        $_SESSION['reset_error'] = "Password minimal harus 8 karakter.";
    } else {
        // Hash password baru dengan metode aman
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update password dan nonaktifkan token
        $query_update = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE reset_token = ?";
        $stmt_update = mysqli_prepare($mysqli, $query_update);
        mysqli_stmt_bind_param($stmt_update, "ss", $hashed_password, $post_token);

        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['login_sukses'] = "Password berhasil diubah! Silakan login dengan password baru Anda.";
            header("Location: ./"); // Redirect ke halaman login
            exit();
        } else {
            $_SESSION['reset_error'] = "Terjadi kesalahan saat memperbarui password.";
        }
    }
    // Refresh halaman dengan token untuk menampilkan error
    header("Location: reset-with-token.php?token=" . urlencode($post_token));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Reset Password | BS Paste</title>
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
                            <div class="col-lg-6 d-none d-lg-block bg-password-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-2">Buat Password Baru</h1>
                                    </div>

                                    <?php if ($show_form): ?>
                                        <p class="mb-4">Silakan masukkan password baru Anda di bawah ini.</p>
                                        <form class="user" method="POST" action="reset-with-token.php">
                                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                            <div class="form-group">
                                                <input type="password" name="password" class="form-control form-control-user" placeholder="Password Baru" required>
                                            </div>
                                            <div class="form-group">
                                                <input type="password" name="confirm_password" class="form-control form-control-user" placeholder="Konfirmasi Password Baru" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-user btn-block">
                                                Simpan Password Baru
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-danger text-center">
                                            <?php echo $error_message; ?>
                                        </div>
                                        <hr>
                                        <div class="text-center">
                                            <a class="small" href="forgot-password.php">Kembali ke Lupa Password</a>
                                        </div>
                                        <div class="text-center">
                                            <a class="small" href="index.php">Login</a>
                                        </div>
                                    <?php endif; ?>

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

    <?php if (isset($_SESSION['reset_error'])): ?>
        <script>
            swal("Gagal!", "<?php echo $_SESSION['reset_error']; ?>", "error");
        </script>
        <?php unset($_SESSION['reset_error']); ?>
    <?php endif; ?>
</body>

</html>