<?php
require '../configdb-main.php'; // Sesuaikan path jika perlu
session_start();
date_default_timezone_set('Asia/Jakarta');

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['login_error'] = "Token verifikasi tidak ditemukan.";
    header("Location: index.php");
    exit();
}

// Cari user berdasarkan token
$query = "SELECT * FROM users WHERE verification_token = ? LIMIT 1";
$stmt = mysqli_prepare($mysqli, $query);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    // Jika token ditemukan, update status verifikasi dan hapus token
    $update_query = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?";
    $stmt_update = mysqli_prepare($mysqli, $update_query);
    mysqli_stmt_bind_param($stmt_update, "i", $user['id']);
    
    if (mysqli_stmt_execute($stmt_update)) {
        $_SESSION['login_sukses'] = "Verifikasi email berhasil! Silakan login.";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['login_error'] = "Gagal memperbarui status verifikasi.";
        header("Location: index.php");
        exit();
    }
} else {
    // Jika token tidak valid
    $_SESSION['login_error'] = "Token verifikasi tidak valid atau sudah digunakan.";
    header("Location: index.php");
    exit();
}
?>