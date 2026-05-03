<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Sesuaikan path jika berbeda
require '../vendor/autoload.php'; 
require '../configdb-main.php';

session_start();
date_default_timezone_set('Asia/Jakarta');

$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($mysqli, $_POST['email']);

    // 1. Cek apakah email ada di database
    $query_cek_email = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($mysqli, $query_cek_email);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // 2. Buat token reset yang aman dan waktu kedaluwarsa
        $token = bin2hex(random_bytes(50));
        $token_expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // 3. Simpan token ke database
        $query_update_token = "UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?";
        $stmt_update = mysqli_prepare($mysqli, $query_update_token);
        mysqli_stmt_bind_param($stmt_update, "sss", $token, $token_expires, $email);
        mysqli_stmt_execute($stmt_update);

        // 4. Kirim email menggunakan PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Konfigurasi Server SMTP Gmail
            // $mail->SMTPDebug = 2; // Aktifkan untuk debugging
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'maileragent.syahdafahreza@gmail.com'; // Akun Gmail Anda
            $mail->Password   = 'ludf wlar ptyc mvyf'; // Password Aplikasi Gmail Anda
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Penerima dan Pengirim
            $mail->setFrom('maileragent.syahdafahreza@gmail.com', 'BS Paste No-Reply');
            $mail->addAddress($email);

            // Konten Email
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password Akun BS Paste Anda';
            $reset_link = 'http://localhost/bspaste/auth/reset-with-token.php?token=' . $token; // GANTI DENGAN DOMAIN ANDA
            $mail->Body    = "Halo,<br><br>Kami menerima permintaan untuk mereset password akun Anda. Silakan klik link di bawah ini untuk membuat password baru:<br><br><a href='{$reset_link}'>{$reset_link}</a><br><br>Jika Anda tidak merasa melakukan permintaan ini, abaikan saja email ini.<br><br>Terima kasih,<br>Tim BS Paste";
            $mail->AltBody = "Untuk mereset password Anda, kunjungi link berikut: {$reset_link}";

            $mail->send();
            $_SESSION['forgot_sukses'] = 'Link reset password telah dikirim ke email Anda.';
        } catch (Exception $e) {
            $_SESSION['forgot_error'] = "Gagal mengirim email. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
         // Jika email tidak ditemukan, tetap berikan pesan sukses untuk keamanan
         // Ini mencegah orang lain menebak-nebak email yang terdaftar
        $_SESSION['forgot_sukses'] = 'Jika email Anda terdaftar, link reset password telah dikirim.';
    }
    // Refresh halaman untuk menampilkan pesan
    header("Location: forgot-password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Lupa Password | BS Paste</title>
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
                                        <h1 class="h4 text-gray-900 mb-2">Lupa Password Anda?</h1>
                                        <p class="mb-4">Masukkan alamat email Anda di bawah ini dan kami akan mengirimkan link untuk mereset password Anda!</p>
                                    </div>
                                    <form class="user" method="POST" action="forgot-password.php">
                                        <div class="form-group">
                                            <input type="email" name="email" class="form-control form-control-user"
                                                id="exampleInputEmail" aria-describedby="emailHelp"
                                                placeholder="Masukkan Alamat Email..." required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Reset Password
                                        </button>
                                    </form>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="register.php">Buat Akun Baru!</a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="index.php">Sudah punya akun? Login!</a>
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

    <?php if (isset($_SESSION['forgot_sukses'])): ?>
        <script>
            swal("Berhasil!", "<?php echo $_SESSION['forgot_sukses']; ?>", "success");
        </script>
        <?php unset($_SESSION['forgot_sukses']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['forgot_error'])): ?>
        <script>
            swal("Gagal!", "<?php echo $_SESSION['forgot_error']; ?>", "error");
        </script>
        <?php unset($_SESSION['forgot_error']); ?>
    <?php endif; ?>
</body>
</html>