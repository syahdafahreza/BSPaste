<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
include 'configdb-login.php'; // Menggunakan koneksi dari configdb-login.php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (isset($_SESSION['username'])) {
    header("Location: ../index.php"); // Redirect ke dashboard jika sudah login
    exit();
}
 
if (isset($_POST['submit'])) {
    // Gunakan koneksi dari configdb-login.php yaitu $conn
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
 
    if ($password == $cpassword) {
        if (strlen($password) >= 8) {
            // Cek apakah email sudah ada (dengan prepared statement)
            $sql_check = "SELECT id FROM users WHERE email = ?";
            $stmt_check = mysqli_prepare($conn, $sql_check);
            mysqli_stmt_bind_param($stmt_check, "s", $email);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);

            if (mysqli_num_rows($result_check) == 0) {
                // Hashing password dengan metode aman
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $verification_token = bin2hex(random_bytes(50));
                
                // Insert user baru dengan status belum diverifikasi
                $sql_insert = "INSERT INTO users (username, email, password, verification_token, is_verified) VALUES (?, ?, ?, ?, 0)";
                $stmt_insert = mysqli_prepare($conn, $sql_insert);
                mysqli_stmt_bind_param($stmt_insert, "ssss", $username, $email, $hashed_password, $verification_token);
                
                if (mysqli_stmt_execute($stmt_insert)) {
                    // Kirim email verifikasi
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'maileragent.syahdafahreza@gmail.com';
                        $mail->Password   = 'ludf wlar ptyc mvyf';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('maileragent.syahdafahreza@gmail.com', 'BS Paste No-Reply');
                        $mail->addAddress($email, $username);

                        $mail->isHTML(true);
                        $mail->Subject = 'Verifikasi Akun BS Paste Anda';
                        $verify_link = 'http://localhost/bspaste/auth/verify.php?token=' . $verification_token; // GANTI DENGAN DOMAIN ANDA
                        $mail->Body    = "Halo {$username},<br><br>Terima kasih telah mendaftar. Silakan klik link di bawah ini untuk mengaktifkan akun Anda:<br><br><a href='{$verify_link}'>{$verify_link}</a><br><br>Terima kasih,<br>Tim BS Paste";
                        
                        $mail->send();
                        $_SESSION['register_info'] = 'Registrasi hampir selesai! Silakan periksa email Anda untuk link verifikasi.';
                        header("Location: ./"); // Arahkan ke login untuk melihat pesan
                        exit();
                    } catch (Exception $e) {
                        echo "<script>alert('Registrasi berhasil, namun email verifikasi gagal dikirim. Mailer Error: {$mail->ErrorInfo}')</script>";
                    }
                } else {
                    echo "<script>alert('Woops! Terjadi kesalahan saat mendaftar.')</script>";
                }
            } else {
                echo "<script>alert('Woops! Email sudah terdaftar.')</script>";
            }
        } else {
            echo "<script>alert('Password minimal harus 8 karakter.')</script>";
        }
    } else {
        echo "<script>alert('Password tidak sesuai.')</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Register | Bootstrap Paste</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="card o-hidden border-0 shadow-lg my-5">
            <div class="card-body p-0">
                <div class="row">
                    <div class="col-lg-5 d-none d-lg-block bg-register-image"></div>
                    <div class="col-lg-7">
                        <div class="p-5">
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4">Buat Akun Baru!</h1>
                            </div>
                            <form action="" method="POST" class="user">
                                <div class="form-group">
                                    <input type="text" name="username" class="form-control form-control-user" placeholder="Nama User" required>
                                </div>
                                <div class="form-group">
                                    <input type="email" name="email" class="form-control form-control-user" placeholder="Alamat Email" required>
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <input type="password" name="password" class="form-control form-control-user" placeholder="Password (min. 8 karakter)" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="password" name="cpassword" class="form-control form-control-user" placeholder="Ulangi Password" required>
                                    </div>
                                </div>
                                <button name="submit" class="btn btn-primary btn-user btn-block">Daftar</button>
                            </form>
                            <hr>
                            <div class="text-center">
                                <a class="small" href="forgot-password.php">Lupa Password?</a>
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
    <script src="/bspaste/vendor/jquery/jquery.min.js"></script>
    <script src="/bspaste/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/bspaste/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="/bspaste/js/sb-admin-2.min.js"></script>
</body>
</html>