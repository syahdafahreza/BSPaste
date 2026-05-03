<?php

include 'configdb-main.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: auth/");
}

// --- AWAL LOGIKA PENCARIAN & PAGINATION ---

include 'auth/configdb-login.php';
$user = $_SESSION['username'];

// Fetch user settings
$query_settings = "SELECT notes_per_page FROM users WHERE username = ?";
$stmt_settings = mysqli_prepare($conn, $query_settings);
mysqli_stmt_bind_param($stmt_settings, "s", $user);
mysqli_stmt_execute($stmt_settings);
$result_settings = mysqli_stmt_get_result($stmt_settings);
$user_settings = mysqli_fetch_assoc($result_settings);

$catatan_per_halaman = ($user_settings && $user_settings['notes_per_page']) ? $user_settings['notes_per_page'] : 6;
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$semua_catatan = [];
$catatan_untuk_ditampilkan = [];

// Query untuk mengambil SEMUA catatan milik user
$query_all = "SELECT * FROM notes WHERE user = ? ORDER BY id DESC";
$stmt_all = mysqli_prepare($mysqli, $query_all);
mysqli_stmt_bind_param($stmt_all, "s", $user);
mysqli_stmt_execute($stmt_all);
$result_all = mysqli_stmt_get_result($stmt_all);

// Lakukan dekripsi dan filter jika ada kata kunci pencarian
while ($catatan = mysqli_fetch_assoc($result_all)) {
    // Try Sakti Decrypt first
    $original_judul = sakti_decrypt($catatan['title']);
    $original_isitext = sakti_decrypt($catatan['text']);

    // Fallback to Legacy for unmigrated data
    if ($original_judul === false) {
        $original_judul = legacy_decrypt($catatan['title']);
        $original_isitext = legacy_decrypt($catatan['text']);
    }

    $catatan['decrypted_title'] = $original_judul ?: "[Decryption Failed]";
    $catatan['decrypted_text'] = $original_isitext ?: "[Decryption Failed]";

    // Jika ada pencarian, filter berdasarkan judul atau isi
    if ($search_term) {
        if (stripos($catatan['decrypted_title'], $search_term) !== false || stripos($catatan['decrypted_text'], $search_term) !== false) {
            $semua_catatan[] = $catatan;
        }
    } else {
        $semua_catatan[] = $catatan;
    }
}

// Logika Pagination untuk data yang sudah difilter
$total_catatan = count($semua_catatan);
$total_halaman = ceil($total_catatan / $catatan_per_halaman);
$halaman_aktif = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

if ($halaman_aktif < 1) {
    $halaman_aktif = 1;
} elseif ($halaman_aktif > $total_halaman && $total_halaman > 0) {
    $halaman_aktif = $total_halaman;
}

$offset = ($halaman_aktif - 1) * $catatan_per_halaman;

// "Slice" array untuk mendapatkan catatan di halaman yang aktif
$catatan_untuk_ditampilkan = array_slice($semua_catatan, $offset, $catatan_per_halaman);

// --- AKHIR LOGIKA PENCARIAN & PAGINATION ---

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Notes | Bootstrap Paste</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" rel="stylesheet"
        type="text/css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.css" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        #viewModal .modal-body {
            max-height: 60vh; /* Batasi tinggi maksimal ke 60% dari tinggi layar */
            overflow-y: auto; /* Tambahkan scrollbar vertikal jika konten melebihi batas */
        }
    </style>

</head>

<body id="page-top" class="sidebar-toggled">

    <div class="zoom">
        <a class="zoom-fab zoom-btn-large bg-primary" id="zoomBtn" title="Menu Cepat"><i class="fa fa-bars"></i></a>
        <ul class="zoom-menu">
            <li><a class="zoom-fab-create zoom-btn-sm zoom-btn-success scale-transition scale-out" data-toggle="modal"
                    data-target="#newnoteModal" title="Buat Catatan Baru"><i class="fa fa-plus"></i></a></li>
            <li><a class="zoom-fab zoom-btn-sm zoom-btn-danger scale-transition scale-out" data-toggle="modal" data-target="#deleteAllModal" title="Hapus Semua"><i
                        class="fa fa-trash"></i></a></li>
        </ul>
    </div>

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php include 'sidebar.php'; include 'topbar.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Catatan Saya</h1>
                        <!-- style="flex: 0 0 27%;max-width: 27%;" -->
                        <div class="d-sm-flex justify-content-center">
                            <a href="#" class="mr-4 d-none d-sm-inline-block btn btn-sm btn-success shadow-sm" data-toggle="modal" data-target="#newnoteModal"><i class="fas fa-plus fa-sm text-white"></i> Buat Catatan Baru</a>
                            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-danger shadow-sm" data-toggle="modal" data-target="#deleteAllModal"><i class="fas fa-trash fa-sm text-white"></i> Hapus Semua Catatan</a>
                        </div>
                    </div>

                    <div class="row">

                        <!-- <div class="col-lg-6"> -->
                        <!-- <div class="card shadow mb-4"> -->
                        <!-- <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between"> -->
                        <!-- <h6 class="m-0 font-weight-bold text-primary">Dropdown Card Example</h6> -->
                        <!-- <div class="dropdown no-arrow"> -->
                        <!-- <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i></a> -->
                        <!-- <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink"> -->
                        <!-- <div class="dropdown-header">Dropdown Header:</div> -->
                        <!-- <a class="dropdown-item" href="#">Action</a> -->
                        <!-- <a class="dropdown-item" href="#">Another action</a> -->
                        <!-- <div class="dropdown-divider"></div> -->
                        <!-- <a class="dropdown-item" href="#">Something else here</a> -->
                        <!-- </div> -->
                        <!-- </div> -->
                        <!-- </div> -->
                        <!-- <div class="card-body">Dropdown menus can be placed in the card header in order to extend the functionality of a basic card. In this dropdown card example, the Font Awesome vertical ellipsis icon in the card header can be clicked on in order to toggle a dropdown menu.</div> -->
                        <!-- </div> -->

                        <?php
                        if ($total_catatan == 0) {
                            echo '<div class="col-lg-12 text-center py-5">';
                            if ($search_term) {
                                echo '<img src="img/undraw_posting_photo.svg" style="width: 200px; opacity: 0.5;" class="mb-4">';
                                echo '<h4 class="text-gray-500">Hasil tidak ditemukan untuk "<strong>' . htmlspecialchars($search_term) . '</strong>"</h4>';
                                echo '<a href="notes.php" class="btn btn-primary mt-3">Lihat Semua Catatan</a>';
                            } else {
                                echo '<img src="img/undraw_rocket.svg" style="width: 250px; opacity: 0.8;" class="mb-4">';
                                echo '<h4 class="text-gray-800 font-weight-bold">Belum ada catatan</h4>';
                                echo '<p class="text-gray-500">Mulailah dengan membuat catatan pertama Anda hari ini!</p>';
                                echo '<button class="btn btn-success mt-3 shadow-sm" data-toggle="modal" data-target="#newnoteModal"><i class="fas fa-plus fa-sm mr-2"></i>Buat Catatan Sekarang</button>';
                            }
                            echo '</div>';
                        } else {
                            foreach ($catatan_untuk_ditampilkan as $user_data) {
                                $display_title = htmlspecialchars($user_data['decrypted_title']);
                                $display_text = htmlspecialchars($user_data['decrypted_text']);

                                echo '<div class="col-lg-6 mb-4">';
                                echo '<div class="card shadow h-100 border-left-primary">';
                                echo '<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white border-bottom-0">';
                                echo '<h6 class="m-0 font-weight-bold text-primary text-truncate pr-3" title="' . $display_title . '">' . $display_title . '</h6>';
                                echo '<div class="dropdown no-arrow">';
                                echo '<a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i></a>';
                                echo '<div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">';
                                echo '<div class="dropdown-header">Opsi:</div>';
                                echo '<a class="dropdown-item" href="#" data-toggle="modal" data-target="#viewModal" data-id="' . $user_data['id'] . '" data-title="' . $display_title . '" data-text="' . $display_text . '"> <i class="fas fa-eye fa-sm fa-fw mr-2 text-gray-400"></i> Buka</a>';
                                echo '<a class="dropdown-item" href="#" data-toggle="modal" data-target="#editModal' . $user_data['id'] . '"><i class="fas fa-pen fa-sm fa-fw mr-2 text-gray-400"></i> Edit</a>';
                                echo '<a class="dropdown-item copy-btn" href="#" data-text="' . $display_text . '"><i class="fas fa-copy fa-sm fa-fw mr-2 text-gray-400"></i> Salin</a>';
                                echo '<div class="dropdown-divider"></div>';
                                echo '<a class="dropdown-item text-danger" href="#" data-toggle="modal" data-target="#delconfirmModalCenter' . $user_data['id'] . '"><i class="fas fa-trash fa-sm fa-fw mr-2 text-danger-400"></i> Hapus</a>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                                echo '<div class="card-body py-2">';
                                echo '<div class="text-gray-800 overflow-hidden" style="max-height: 150px; display: -webkit-box; -webkit-line-clamp: 5; -webkit-box-orient: vertical;">' . nl2br($display_text) . '</div>';
                                echo '</div>';
                                echo '<div class="card-footer bg-white border-top-0 pt-0 text-right">';
                                echo '<hr class="my-2">';
                                echo '<button class="btn btn-link btn-sm text-primary p-0 font-weight-bold" data-toggle="modal" data-target="#viewModal" data-title="' . $display_title . '" data-text="' . $display_text . '">Baca Selengkapnya</button>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                                ?>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?php echo $user_data['id'] ?>" tabindex="-1" role="dialog"
                                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel">Edit Catatan</h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            </div>
                                            <div class="modal-body">
                                                <form role="form" action="editnote.php" method="post">
                                                    <input type="hidden" name="id_note" value="<?php echo $user_data['id']; ?>">
                                                    <div class="form-group">
                                                        <label for="recipient-name" class="col-form-label">Judul</label>
                                                        <input type="text" name="judul_note" class="form-control"
                                                            id="recipient-name" value="<?php echo $user_data['decrypted_title'] ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="message-text" class="col-form-label">Teks</label>
                                                        <textarea class="form-control" name="isi_note"
                                                            id="message-text"><?php echo $user_data['decrypted_text'] ?></textarea>
                                                    </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Confirmation Modal -->
                                <div class="modal fade" id="delconfirmModalCenter<?php echo $user_data['id'] ?>" tabindex="-1"
                                    role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLongTitle">Cuma mengingatkan :)</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                Catatan ini akan hilang selama-lamanya, sama seperti kenangan indah sang mantan.
                                                Ingin tetap menghapus catatan ini?
                                            </div>
                                            <form role="form" action="deletenote.php" method="post">
                                                <input type="hidden" name="id_note" value="<?php echo $user_data['id']; ?>">
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">Hapus</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                        <?php
                            }
                        }
                        ?>

                    </div> <?php if ($total_halaman > 1): ?>
                        <div class="row">
                            <div class="col-lg-12">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center mt-4">

                                        <li class="page-item <?php if ($halaman_aktif <= 1) {
                                                                    echo 'disabled';
                                                                } ?>">
                                            <a class="page-link" href="?search=<?php echo urlencode($search_term); ?>&page=<?php echo $halaman_aktif - 1; ?>">Previous</a>
                                        </li>

                                        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                                            <li class="page-item <?php if ($halaman_aktif == $i) {
                                                                        echo 'active';
                                                                    } ?>">
                                                <a class="page-link" href="?search=<?php echo urlencode($search_term); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?php if ($halaman_aktif >= $total_halaman) {
                                                                    echo 'disabled';
                                                                } ?>">
                                            <a class="page-link" href="?search=<?php echo urlencode($search_term); ?>&page=<?php echo $halaman_aktif + 1; ?>">Next</a>
                                        </li>

                                    </ul>
                                </nav>
                            </div>
                        </div>
                    <?php endif; ?>

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
                                type="text/javascript">
                                var creditsyear = new Date();
                                document.write(creditsyear.getFullYear());
                            </script>
                        </span> Syahda Fahreza
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded-circle" href="#page-top">
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

    <!-- View notes modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Judul Catatan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary copy-btn" id="copyBtnModal" data-text="">
                        <i class="fas fa-copy fa-sm"></i> Salin
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete all modal -->
    <div class="modal fade" id="deleteAllModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Konfirmasi Hapus Semua Catatan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Anda yakin ingin menghapus <strong>SEMUA</strong> catatan Anda secara permanen? Aksi ini tidak dapat dibatalkan.
                </div>
                <div class="modal-footer">
                    <form action="delallnotes.php" method="POST">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Hapus Semua</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- New Note Modal -->
    <div class="modal fade" id="newnoteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Buat Catatan Baru</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form role="form" action="createnote.php?act=tambahnotes" method="post">
                        <input type="hidden" name="nama_user" value="<?php echo $_SESSION['username']; ?>"></input>
                        <input type="hidden" name="tanggal_dibuat" value="">
                        <script>
                            var dt = new Date();
                            document.getElementById("datetime").innerHTML = dt.toLocaleTimeString('en-US', {
                                hour: 'numeric',
                                minute: 'numeric',
                                hour12: true
                            });
                        </script>
                        </input>
                        <div class="form-group">
                            <label for="recipient-name" class="col-form-label">Judul</label>
                            <input type="text" name="judul_note_baru" class="form-control" id="recipient-name" required>
                        </div>
                        <div class="form-group">
                            <label for="message-text" class="col-form-label">Teks</label>
                            <textarea class="form-control" name="isi_note_baru" id="message-text" required></textarea>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
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

    <!-- Sweet Alert CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.js"></script>

    <!-- Zoom FAB Button Scripts -->
    <script>
        $('#zoomBtn').click(function() {
            $('.zoom-btn-sm').toggleClass('scale-out');
            if (!$('.zoom-card').hasClass('scale-out')) {
                $('.zoom-card').toggleClass('scale-out');
            }
        });
    </script>



    <!-- Memanggil Sweet Alert Buat Note Sukses -->
    <?php if (@$_SESSION['buatnotesukses']) { ?>
        <script>
            swal("Berhasil!", "<?php echo $_SESSION['buatnotesukses']; ?>", "success");
        </script>
        <!-- jangan lupa untuk menambahkan unset agar sweet alert tidak muncul lagi saat di refresh -->
    <?php unset($_SESSION['buatnotesukses']);
    } ?>

    <!-- Memanggil Sweet Alert Edit Note Sukses -->
    <?php if (@$_SESSION['editnotesukses']) { ?>
        <script>
            swal("Berhasil!", "<?php echo $_SESSION['editnotesukses']; ?>", "success");
        </script>
        <!-- jangan lupa untuk menambahkan unset agar sweet alert tidak muncul lagi saat di refresh -->
    <?php unset($_SESSION['editnotesukses']);
    } ?>

    <!-- Memanggil Sweet Alert Delete Note Sukses -->
    <?php if (@$_SESSION['delnotesukses']) { ?>
        <script>
            swal("Berhasil!", "<?php echo $_SESSION['delnotesukses']; ?>", "success");
        </script>
        <!-- jangan lupa untuk menambahkan unset agar sweet alert tidak muncul lagi saat di refresh -->
    <?php unset($_SESSION['delnotesukses']);
    } ?>

    <!-- Memanggil Sweet Alert Delete All Note Sukses -->
    <?php if (@$_SESSION['delallnotesukses']) { ?>
        <script>
            swal("Berhasil!", "<?php echo $_SESSION['delallnotesukses']; ?>", "success");
        </script>
        <!-- jangan lupa untuk menambahkan unset agar sweet alert tidak muncul lagi saat di refresh -->
    <?php unset($_SESSION['delallnotesukses']);
    } ?>

    <script>
        $('#viewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var title = button.data('title');
            var text = button.data('text');

            var modal = $(this);
            modal.find('.modal-title').text(title);
            modal.find('.modal-body').html(text.replace(/\n/g, '<br>'));
            modal.find('#copyBtnModal').data('text', text); // Set text for copy button
        });

        // Fitur Salin ke Clipboard
        $('.copy-btn').click(function(e) {
            e.preventDefault();
            var text = $(this).data('text');
            var $temp = $("<textarea>");
            $("body").append($temp);
            $temp.val(text).select();
            document.execCommand("copy");
            $temp.remove();
            
            swal({
                title: "Tersalin!",
                text: "Isi catatan berhasil disalin ke clipboard.",
                type: "success",
                timer: 1500,
                showConfirmButton: false
            });
        });
    </script>

</body>

</html>