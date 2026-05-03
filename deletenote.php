<?php
include 'configdb-main.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: auth/");
    exit();
}

$id = $_POST['id_note'];
$user = $_SESSION['username'];

// query delete dengan check ownership
$query = "DELETE FROM `notes` WHERE id=? AND user=?";
$stmt = mysqli_prepare($mysqli, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "is", $id, $user);
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        $_SESSION['delnotesukses'] = 'Notes berhasil dihapus';
        logActivity($user, "Hapus Catatan", "ID: $id");
        header("location: notes.php");
        exit();
    } else {
        echo "ERROR, data gagal dihapus.";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "ERROR, sistem bermasalah.";
}
?>