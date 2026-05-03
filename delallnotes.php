<?php
include 'configdb-main.php';
session_start();

// Keamanan: Pastikan user sudah login sebelum melakukan aksi
if (!isset($_SESSION['username'])) {
    // Jika tidak ada sesi, redirect ke halaman login
    header("Location: auth/");
    exit(); // Hentikan eksekusi script
}

// Ambil username dari sesi
$username = $_SESSION['username'];

// Gunakan prepared statement untuk keamanan dari SQL Injection
$query = "DELETE FROM notes WHERE user = ?";
$stmt = mysqli_prepare($mysqli, $query);

if ($stmt) {
    // Ikat parameter (s = string)
    mysqli_stmt_bind_param($stmt, "s", $username);

    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, buat session untuk SweetAlert sukses
        $_SESSION['delallnotesukses'] = 'Semua catatan Anda berhasil dihapus!';
    } else {
        // Jika gagal, buat session untuk SweetAlert gagal
        $_SESSION['delallnotegagal'] = 'Terjadi kesalahan saat menghapus catatan.';
    }
    // Tutup statement
    mysqli_stmt_close($stmt);
} else {
    // Jika persiapan statement gagal
    $_SESSION['delallnotegagal'] = 'Terjadi kesalahan pada database.';
}

// Redirect kembali ke halaman notes
header("location: notes.php");
exit();

?>