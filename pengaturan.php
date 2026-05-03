<?php
include 'configdb-main.php';
include 'auth/configdb-login.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: auth/");
    exit();
}

$user_session = $_SESSION['username'];
$success_msg = "";
$error_msg = "";

// Fetch current user data (including settings)
$query_user = "SELECT * FROM users WHERE username = ?";
$stmt_user = mysqli_prepare($conn, $query_user);
mysqli_stmt_bind_param($stmt_user, "s", $user_session);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user_data = mysqli_fetch_assoc($result_user);

if (!$user_data) {
    die("User not found.");
}

// Handle Settings Update
if (isset($_POST['update_settings'])) {
    $theme = mysqli_real_escape_string($conn, $_POST['theme']);
    $accent_color = mysqli_real_escape_string($conn, $_POST['accent_color']);
    $notes_per_page = (int)$_POST['notes_per_page'];

    if ($notes_per_page < 1) $notes_per_page = 6;
    if ($notes_per_page > 50) $notes_per_page = 50;

    $update_query = "UPDATE users SET theme = ?, accent_color = ?, notes_per_page = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt_update, "ssii", $theme, $accent_color, $notes_per_page, $user_data['id']);
    
    if (mysqli_stmt_execute($stmt_update)) {
        $success_msg = "Pengaturan berhasil diperbarui!";
        $user_data['theme'] = $theme;
        $user_data['accent_color'] = $accent_color;
        $user_data['notes_per_page'] = $notes_per_page;
        $_SESSION['accent_color'] = $accent_color; // Update session
        logActivity($user_session, "Update Pengaturan", "Tema: $theme, Warna: $accent_color, Catatan per halaman: $notes_per_page");
    } else {
        $error_msg = "Terjadi kesalahan saat memperbarui pengaturan.";
    }
}

// Handle Account Deletion
if (isset($_POST['delete_account'])) {
    $confirm_password = $_POST['confirm_password_del'];

    if (password_verify($confirm_password, $user_data['password'])) {
        // Delete user's notes first
        $del_notes_query = "DELETE FROM notes WHERE user = ?";
        $stmt_del_notes = mysqli_prepare($mysqli, $del_notes_query);
        mysqli_stmt_bind_param($stmt_del_notes, "s", $user_session);
        mysqli_stmt_execute($stmt_del_notes);

        // Delete user account
        $del_user_query = "DELETE FROM users WHERE id = ?";
        $stmt_del_user = mysqli_prepare($conn, $del_user_query);
        mysqli_stmt_bind_param($stmt_del_user, "i", $user_data['id']);
        
        if (mysqli_stmt_execute($stmt_del_user)) {
            session_destroy();
            header("Location: auth/?msg=account_deleted");
            exit();
        } else {
            $error_msg = "Gagal menghapus akun.";
        }
    } else {
        $error_msg = "Konfirmasi password salah. Akun tidak dihapus.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Pengaturan - BS Paste">
    <meta name="author" content="Syahda Fahreza">

    <title>Pengaturan | BS Paste</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.css" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .color-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        .color-circle:hover {
            transform: scale(1.1);
            border-color: #888;
        }
        .color-circle.active {
            border-color: #333;
            box-shadow: 0 0 5px rgba(0,0,0,0.3);
        }
    </style>

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php include 'sidebar.php'; include 'topbar.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Pengaturan</h1>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Preference Card -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Preferensi Aplikasi</h6>
                                </div>
                                <div class="card-body">
                                    <form action="" method="POST">
                                        <div class="form-group">
                                            <label for="theme">Tema Aplikasi</label>
                                            <select class="form-control" id="theme" name="theme">
                                                <option value="light" <?php echo ($user_data['theme'] == 'light') ? 'selected' : ''; ?>>Terang (Default)</option>
                                                <option value="dark" <?php echo ($user_data['theme'] == 'dark') ? 'selected' : ''; ?>>Gelap</option>
                                            </select>
                                            <small class="text-muted">Pilih tema yang paling nyaman untuk mata Anda.</small>
                                        </div>
                                        <div class="form-group">
                                            <label>Warna Aksen (Accent Color)</label>
                                            <div class="d-flex flex-wrap mb-3">
                                                <div class="color-circle" style="background-color: #e74a3b;" data-color="#e74a3b" title="Merah"></div>
                                                <div class="color-circle" style="background-color: #fd7e14;" data-color="#fd7e14" title="Jingga"></div>
                                                <div class="color-circle" style="background-color: #f6c23e;" data-color="#f6c23e" title="Kuning"></div>
                                                <div class="color-circle" style="background-color: #1cc88a;" data-color="#1cc88a" title="Hijau"></div>
                                                <div class="color-circle" style="background-color: #4e73df;" data-color="#4e73df" title="Biru"></div>
                                                <div class="color-circle" style="background-color: #6610f2;" data-color="#6610f2" title="Nila"></div>
                                                <div class="color-circle" style="background-color: #6f42c1;" data-color="#6f42c1" title="Ungu"></div>
                                            </div>
                                            <div class="input-group" style="max-width: 200px;">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Custom</span>
                                                </div>
                                                <input type="color" class="form-control h-auto" id="accent_color_picker" name="accent_color" value="<?php echo htmlspecialchars($user_data['accent_color'] ?? '#4e73df'); ?>">
                                            </div>
                                            <small class="text-muted">Pilih warna dari palet atau tentukan warna kustom Anda sendiri.</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="notes_per_page">Catatan Per Halaman</label>
                                            <input type="number" class="form-control" id="notes_per_page" name="notes_per_page" value="<?php echo htmlspecialchars($user_data['notes_per_page']); ?>" min="1" max="50">
                                            <small class="text-muted">Jumlah catatan yang ditampilkan di halaman daftar catatan.</small>
                                        </div>
                                        <button type="submit" name="update_settings" class="btn btn-primary">Simpan Pengaturan</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Danger Zone Card -->
                            <div class="card shadow mb-4 border-left-danger">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-danger">Danger Zone</h6>
                                </div>
                                <div class="card-body">
                                    <p>Aksi di bawah ini bersifat permanen dan tidak dapat dibatalkan.</p>
                                    <button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#deleteAccountModal">
                                        Hapus Akun Saya
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Informasi</h6>
                                </div>
                                <div class="card-body">
                                    <p>Versi Aplikasi: <strong>1.2.0-stable</strong></p>
                                    <p>Status Enkripsi: <span class="badge badge-success">Aktif (AES-128)</span></p>
                                    <hr>
                                    <p class="small text-gray-500">Gunakan pengaturan ini untuk mempersonalisasi pengalaman Anda di BS Paste.</p>
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

    <!-- Delete Account Modal-->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger font-weight-bold" id="exampleModalLabel">Hapus Akun Permanen?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus akun Anda? Semua catatan Anda akan <strong>dihapus secara permanen</strong> dan tidak dapat dipulihkan kembali.</p>
                        <div class="form-group">
                            <label for="confirm_password_del">Konfirmasi Password</label>
                            <input type="password" class="form-control" id="confirm_password_del" name="confirm_password_del" placeholder="Masukkan password Anda untuk konfirmasi" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                        <button type="submit" name="delete_account" class="btn btn-danger">Ya, Hapus Akun Saya</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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

    <script>
        $(document).ready(function() {
            // Handle color circle selection
            $('.color-circle').click(function() {
                var color = $(this).data('color');
                $('#accent_color_picker').val(color);
                $('.color-circle').removeClass('active');
                $(this).addClass('active');
            });

            // Set active circle on load
            var currentColor = $('#accent_color_picker').val().toLowerCase();
            $('.color-circle').each(function() {
                if ($(this).data('color').toLowerCase() === currentColor) {
                    $(this).addClass('active');
                }
            });
        });
    </script>

</body>

</html>
