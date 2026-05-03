<?php
include 'configdb-main.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: auth/");
    exit();
}

if (isset($_GET['act']) && $_GET['act'] == 'tambahnotes') {
    $namauser = $_SESSION['username'];
    $judulbaru = $_POST['judul_note_baru'];
    $isinotebaru = $_POST['isi_note_baru'];

    // Sakti Encryption
    $ciphertext_judulbaru = sakti_encrypt($judulbaru);
    $ciphertext_isinotebaru = sakti_encrypt($isinotebaru);

    // query buat dengan prepared statement
    $query = "INSERT INTO `notes` (id, user, title, text) VALUES (NULL, ?, ?, ?)";
    $stmt = mysqli_prepare($mysqli, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sss", $namauser, $ciphertext_judulbaru, $ciphertext_isinotebaru);
        $querytambah = mysqli_stmt_execute($stmt);

        if ($querytambah) {
            $_SESSION['buatnotesukses'] = 'Notes berhasil dibuat';
            logActivity($namauser, "Tambah Catatan", "Judul: " . htmlspecialchars($judulbaru));
            header("location: notes.php");
            exit();
        } else {
            // error_log("Database error: " . mysqli_error($mysqli));
            echo "ERROR, data gagal disimpan.";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "ERROR, sistem bermasalah.";
    }
}
?>