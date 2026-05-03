<?php

include 'configdb-main.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: auth/");
    exit();
}

$user = $_SESSION['username'];

// Fetch Total Notes
$query_count = "SELECT COUNT(*) as total FROM notes WHERE user = ?";
$stmt_count = mysqli_prepare($mysqli, $query_count);
mysqli_stmt_bind_param($stmt_count, "s", $user);
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$total_notes = mysqli_fetch_assoc($result_count)['total'];

// Fetch Latest Note Title (Decrypted)
$query_latest = "SELECT title FROM notes WHERE user = ? ORDER BY id DESC LIMIT 1";
$stmt_latest = mysqli_prepare($mysqli, $query_latest);
mysqli_stmt_bind_param($stmt_latest, "s", $user);
mysqli_stmt_execute($stmt_latest);
$result_latest = mysqli_stmt_get_result($stmt_latest);
$latest_note_title = "Belum ada catatan";

if ($row_latest = mysqli_fetch_assoc($result_latest)) {
    $decrypted = sakti_decrypt($row_latest['title']);
    if ($decrypted === false) {
        $decrypted = legacy_decrypt($row_latest['title']);
    }
    $latest_note_title = $decrypted ?: "[Decryption Failed]";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Dashboard | Bootstrap Paste</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

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
                        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                        <a href="notes.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i
                                class="fas fa-sticky-note fa-sm text-white-50"></i> Lihat Semua Catatan</a>
                    </div>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Total Catatan Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Catatan</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $total_notes; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Terbaru Card -->
                        <div class="col-xl-9 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Catatan Terbaru</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800 text-truncate"
                                                style="max-width: 500px;">
                                                <?php echo htmlspecialchars($latest_note_title); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">

                        <div class="col-lg-6 mb-4">

                            <!-- Approach -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Akses Cepat</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-wrap justify-content-center justify-content-md-start">
                                        <a href="notes.php" class="btn btn-primary btn-icon-split mr-3 mb-2">
                                            <span class="icon text-white-50">
                                                <i class="fas fa-sticky-note"></i>
                                            </span>
                                            <span class="text">Buka Catatan</span>
                                        </a>
                                        <a href="#" class="btn btn-success btn-icon-split mb-2" data-toggle="modal"
                                            data-target="#newnoteModal">
                                            <span class="icon text-white-50">
                                                <i class="fas fa-plus"></i>
                                            </span>
                                            <span class="text">Buat Baru</span>
                                        </a>
                                    </div>
                                    <p class="mt-3">Gunakan tombol di atas untuk mengelola catatan Anda dengan lebih
                                        efisien.</p>
                                </div>
                            </div>

                            <!-- Illustrations -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Tentang BS Paste</h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center">
                                        <img class="img-fluid px-3 px-sm-4 mt-3 mb-4" style="width: 15rem;"
                                            src="img/undraw_posting_photo.svg" alt="...">
                                    </div>
                                    <p>Bootstrap Paste adalah tempat aman untuk menyimpan ide, pemikiran, dan catatan
                                        penting Anda. Semua data Anda dienkripsi untuk privasi maksimal.</p>
                                </div>
                            </div>

                        </div>

                        <div class="col-lg-6 mb-4">
                            <!-- Information -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Keamanan Data</h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        <i class="fas fa-shield-alt fa-5x text-primary"></i>
                                    </div>
                                    <p>Kami menerapkan arsitektur <strong>Hybrid Vault (Zero-Knowledge)</strong> untuk privasi total. Setiap catatan dienkripsi menggunakan <strong>Master Vault Key (AES-256-CBC)</strong> yang unik untuk Anda.</p>
                                    <p class="mb-0 small text-gray-600">Hanya Anda yang memegang kunci akses melalui password atau Kunci Pemulihan. Bahkan administrator sistem pun tidak memiliki cara untuk membaca isi catatan Anda.</p>
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
                        <span>Copyright &copy;
                            <script
                                type="text/javascript">var creditsyear = new Date(); document.write(creditsyear.getFullYear());</script>
                        </span>
                        <a href="javascript:window.location.href=window.location.href">Syahda Fahreza</a>.
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

    <!-- Zoom FAB Button -->
    <div class="zoom">
        <a class="zoom-fab zoom-btn-large bg-primary" id="zoomBtn" title="Menu Cepat"><i class="fa fa-bars"></i></a>
        <ul class="zoom-menu">
            <li><a class="zoom-fab-create zoom-btn-sm zoom-btn-success scale-transition scale-out" data-toggle="modal"
                    data-target="#newnoteModal" title="Buat Catatan Baru"><i class="fa fa-plus"></i></a></li>
        </ul>
    </div>

    <!-- New Note Modal -->
    <div class="modal fade" id="newnoteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Buat Catatan Baru</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <form action="createnote.php" method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="title">Judul Catatan</label>
                            <input type="text" name="title" class="form-control" placeholder="Masukkan judul..."
                                required>
                        </div>
                        <div class="form-group">
                            <label for="text">Isi Catatan</label>
                            <textarea name="text" class="form-control" rows="5"
                                placeholder="Tuliskan isi catatan Anda di sini..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                        <button type="submit" name="submit" class="btn btn-primary">Simpan Catatan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Yakin ingin Logout?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Pilih "Logout" di bawah jika Anda siap untuk mengakhiri sesi Anda saat ini.
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                    <form action="" method="POST">
                        <a class="btn btn-primary" href="auth/logout.php">Logout</a>
                    </form>
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
    <script src="vendor/chart.js/Chart.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/chart-area-demo.js"></script>
    <script src="js/demo/chart-pie-demo.js"></script>

    <!-- Zoom FAB Button Scripts -->
    <script>
        $('#zoomBtn').click(function () {
            $('.zoom-btn-sm').toggleClass('scale-out');
        });
    </script>

</body>

</html>