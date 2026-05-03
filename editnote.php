<?php
include 'configdb-main.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: auth/");
    exit();
}

$id = $_POST['id_note'];
$judul = $_POST['judul_note'];
$isinote = $_POST['isi_note'];
$user = $_SESSION['username'];

// Sakti Encryption
$ciphertext_juduledit = sakti_encrypt($judul);
$ciphertext_isinoteedit = sakti_encrypt($isinote);

// query update dengan check ownership
$query = "UPDATE `notes` SET title=?, text=? WHERE id=? AND user=?";
$stmt = mysqli_prepare($mysqli, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ssis", $ciphertext_juduledit, $ciphertext_isinoteedit, $id, $user);
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        $_SESSION['editnotesukses'] = 'Notes berhasil diedit';
        logActivity($user, "Edit Catatan", "ID: $id, Judul: " . htmlspecialchars($judul));
        header("location: notes.php");
        exit();
    } else {
        echo "ERROR, data gagal diupdate.";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "ERROR, sistem bermasalah.";
}
?>