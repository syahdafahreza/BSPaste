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

// Handle Clear Logs
if (isset($_POST['clear_logs'])) {
    $clear_query = "DELETE FROM activity_log WHERE user = ?";
    $stmt_clear = mysqli_prepare($mysqli, $clear_query);
    mysqli_stmt_bind_param($stmt_clear, "s", $user_session);
    
    if (mysqli_stmt_execute($stmt_clear)) {
        $success_msg = "Log aktivitas berhasil dibersihkan!";
        logActivity($user_session, "Bersihkan Log", "User menghapus semua riwayat aktivitas");
    } else {
        $error_msg = "Gagal membersihkan log.";
    }
}

// Fetch Activity Logs
$query_logs = "SELECT * FROM activity_log WHERE user = ? ORDER BY timestamp DESC";
$stmt_logs = mysqli_prepare($mysqli, $query_logs);
mysqli_stmt_bind_param($stmt_logs, "s", $user_session);
mysqli_stmt_execute($stmt_logs);
$result_logs = mysqli_stmt_get_result($stmt_logs);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Log Aktivitas - BS Paste">
    <meta name="author" content="Syahda Fahreza">

    <title>Log Aktivitas | BS Paste</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.css" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- Custom styles for this page -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php include 'sidebar.php'; include 'topbar.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Log Aktivitas</h1>
                        <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua log aktivitas?');">
                            <button type="submit" name="clear_logs" class="btn btn-sm btn-danger shadow-sm">
                                <i class="fas fa-trash fa-sm text-white-50"></i> Bersihkan Log
                            </button>
                        </form>
                    </div>

                    <!-- Activity Log Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Riwayat Aktivitas Anda</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Aksi</th>
                                            <th>Detail</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($log = mysqli_fetch_assoc($result_logs)): ?>
                                        <tr>
                                            <td style="width: 20%;"><?php echo date('d M Y, H:i:s', strtotime($log['timestamp'])); ?></td>
                                            <td style="width: 20%;">
                                                <?php 
                                                $badge_class = 'badge-info';
                                                if (strpos($log['action'], 'Tambah') !== false) $badge_class = 'badge-success';
                                                if (strpos($log['action'], 'Hapus') !== false || strpos($log['action'], 'Bersihkan') !== false) $badge_class = 'badge-danger';
                                                if (strpos($log['action'], 'Edit') !== false || strpos($log['action'], 'Update') !== false) $badge_class = 'badge-warning';
                                                if ($log['action'] == 'Login') $badge_class = 'badge-primary';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?> p-2 w-100"><?php echo htmlspecialchars($log['action']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if (mysqli_num_rows($result_logs) == 0): ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Belum ada aktivitas tercatat.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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

    <!-- Page level plugins -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": [[ 0, "desc" ]]
            });
        });
    </script>

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
