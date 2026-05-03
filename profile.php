<?php
include 'configdb-main.php';
include 'auth/configdb-login.php'; // Using $conn for user operations
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: auth/");
    exit();
}

$user_session = $_SESSION['username'];
$success_msg = "";
$error_msg = "";

// Fetch current user data
$query_user = "SELECT * FROM users WHERE username = ?";
$stmt_user = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt_user, "s", $user_session);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user_data = mysqli_fetch_assoc($result_user);

if (!$user_data) {
    die("User not found.");
}

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $new_username = mysqli_real_escape_string($conn, $_POST['username']);
    $new_email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if username/email already exists for other users
    $check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $stmt_check = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt_check, "ssi", $new_username, $new_email, $user_data['id']);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) > 0) {
        $error_msg = "Username atau Email sudah digunakan oleh pengguna lain.";
    } else {
        $update_query = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt_update, "ssi", $new_username, $new_email, $user_data['id']);
        
        if (mysqli_stmt_execute($stmt_update)) {
            // Update session if username changed
            $_SESSION['username'] = $new_username;
            $user_session = $new_username;
            $success_msg = "Profil berhasil diperbarui!";
            logActivity($user_session, "Update Profil", "Username: $new_username, Email: $new_email");
            
            // Refresh user data
            $user_data['username'] = $new_username;
            $user_data['email'] = $new_email;
        } else {
            $error_msg = "Terjadi kesalahan saat memperbarui profil.";
        }
    }
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (password_verify($old_pass, $user_data['password'])) {
        if ($new_pass === $confirm_pass) {
            if (strlen($new_pass) >= 8) {
                $hashed_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                $update_pass_query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt_pass = mysqli_prepare($conn, $update_pass_query);
                mysqli_stmt_bind_param($stmt_pass, "si", $hashed_new_pass, $user_data['id']);
                
                if (mysqli_stmt_execute($stmt_pass)) {
                    // --- HYBRID VAULT: Re-wrap MVK ---
                    if (isset($_SESSION['master_key'])) {
                        $mvk_raw = base64_decode($_SESSION['master_key']);
                        $new_pdk_raw = hash_pbkdf2("sha256", $new_pass, $user_data['encryption_salt'], 100000, 32, true);
                        $new_mvk_enc_pass = wrap_key($mvk_raw, $new_pdk_raw);
                        
                        $upd_mvk = "UPDATE users SET master_vault_key_enc_pass = ? WHERE id = ?";
                        $stmt_mvk = mysqli_prepare($conn, $upd_mvk);
                        mysqli_stmt_bind_param($stmt_mvk, "si", $new_mvk_enc_pass, $user_data['id']);
                        mysqli_stmt_execute($stmt_mvk);
                        
                        // Update session PDK just in case
                        $_SESSION['user_key'] = base64_encode($new_pdk_raw);
                    }
                    // -------------------------------

                    $success_msg = "Password berhasil diubah! Kunci akses data Anda telah diperbarui secara aman.";
                    $user_data['password'] = $hashed_new_pass;
                    logActivity($user_session, "Ganti Password", "User ganti password (MVK Re-wrapped)");
                } else {
                    $error_msg = "Terjadi kesalahan saat mengubah password.";
                }
            } else {
                $error_msg = "Password baru minimal 8 karakter.";
            }
        } else {
            $error_msg = "Konfirmasi password baru tidak cocok.";
        }
    } else {
        $error_msg = "Password lama salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Profil Pengguna - BS Paste">
    <meta name="author" content="Syahda Fahreza">

    <title>Profil | BS Paste</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.css" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php include 'sidebar.php'; include 'topbar.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Profil Saya</h1>
                    </div>

                    <div class="row">
                        <!-- Profile Info Card -->
                        <div class="col-lg-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Informasi Akun</h6>
                                </div>
                                <div class="card-body text-center">
                                    <img class="img-profile rounded-circle mb-3" src="img/undraw_profile.svg" style="width: 150px;">
                                    <h4 class="font-weight-bold text-gray-800"><?php echo htmlspecialchars($user_data['username']); ?></h4>
                                    <p class="text-gray-500 mb-4"><?php echo htmlspecialchars($user_data['email']); ?></p>
                                    <?php if ($user_data['is_verified']): ?>
                                    <div class="badge badge-success p-2">
                                        <i class="fas fa-check-circle mr-1"></i> Akun Terverifikasi
                                    </div>
                                    <?php else: ?>
                                    <div class="badge badge-warning p-2">
                                        <i class="fas fa-exclamation-circle mr-1"></i> Belum Terverifikasi
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Update Profile Form -->
                        <div class="col-lg-8">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Edit Profil</h6>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <label for="username">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                        </div>
                                        <button type="submit" name="update_profile" class="btn btn-primary">Simpan Perubahan</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Change Password Card -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Ganti Password</h6>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <label for="old_password">Password Lama</label>
                                            <input type="password" class="form-control" id="old_password" name="old_password" required>
                                        </div>
                                        <div class="hr-divider mb-3 mt-3"></div>
                                        <div class="form-group">
                                            <label for="new_password">Password Baru</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Min. 8 karakter" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="confirm_password">Konfirmasi Password Baru</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        <button type="submit" name="change_password" class="btn btn-danger">Ganti Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; BS Paste <?php echo date("Y"); ?></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Yakin ingin Logout?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Pilih "Logout" di bawah jika Anda siap untuk mengakhiri sesi Anda saat ini.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                    <a class="btn btn-primary" href="auth/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Sweet Alert -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.js"></script>

    <?php if ($success_msg): ?>
    <script>
        swal("Berhasil!", "<?php echo $success_msg; ?>", "success");
    </script>
    <?php endif; ?>

    <?php if ($error_msg): ?>
    <script>
        swal("Gagal!", "<?php echo $error_msg; ?>", "error");
    </script>
    <?php endif; ?>

</body>

</html>
