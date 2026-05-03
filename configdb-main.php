<?php
$databaseHost = 'localhost';
$databaseName = 'epiz_33317858_np';
$databaseUsername = 'epiz_33317858';
$databasePassword = 'aRodYdr6aMF7L';

$mysqli = mysqli_connect($databaseHost, $databaseUsername, $databasePassword, $databaseName);

define('OLD_ENCRYPTION_KEY', 'x7A9f2C4b1E8d0G3h6J5k4M2n9P1q0R3'); 

// --- HYBRID VAULT FUNCTIONS ---

// Encrypt data using the Master Vault Key (MVK)
function sakti_encrypt($plaintext) {
    if (!isset($_SESSION['master_key'])) return false;
    $key = base64_decode($_SESSION['master_key']);
    $cipher = "AES-256-CBC";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
    return base64_encode($iv . $hmac . $ciphertext_raw);
}

// Decrypt data using the Master Vault Key (MVK)
function sakti_decrypt($ciphertext_base64) {
    if (!isset($_SESSION['master_key'])) return false;
    $key = base64_decode($_SESSION['master_key']);
    $cipher = "AES-256-CBC";
    $c = base64_decode($ciphertext_base64);
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, 32);
    $ciphertext_raw = substr($c, $ivlen + 32);
    $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
    if (hash_equals($hmac, $calcmac)) {
        return openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    }
    return false;
}

// Key Wrapping: Encrypt one key with another (e.g., MVK with PDK)
function wrap_key($key_to_wrap_raw, $wrapping_key_raw) {
    $cipher = "AES-256-CBC";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($key_to_wrap_raw, $cipher, $wrapping_key_raw, OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, $wrapping_key_raw, true);
    return base64_encode($iv . $hmac . $ciphertext_raw);
}

// Key Unwrapping: Decrypt one key with another
function unwrap_key($wrapped_key_base64, $wrapping_key_raw) {
    $cipher = "AES-256-CBC";
    $c = base64_decode($wrapped_key_base64);
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, 32);
    $ciphertext_raw = substr($c, $ivlen + 32);
    $calcmac = hash_hmac('sha256', $ciphertext_raw, $wrapping_key_raw, true);
    if (hash_equals($hmac, $calcmac)) {
        return openssl_decrypt($ciphertext_raw, $cipher, $wrapping_key_raw, OPENSSL_RAW_DATA, $iv);
    }
    return false;
}

// --- LEGACY FUNCTIONS (For Migration) ---

// Decrypt notes that were encrypted with Password-Derived Key directly
function sakti_legacy_decrypt($ciphertext_base64) {
    if (!isset($_SESSION['user_key'])) return false;
    $key = base64_decode($_SESSION['user_key']);
    $cipher = "AES-256-CBC";
    $c = base64_decode($ciphertext_base64);
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, 32);
    $ciphertext_raw = substr($c, $ivlen + 32);
    $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
    if (hash_equals($hmac, $calcmac)) {
        return openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    }
    return false;
}

function legacy_decrypt($ciphertext_base64) {
    $key = OLD_ENCRYPTION_KEY;
    $cipher = "AES-128-CBC";
    $c = base64_decode($ciphertext_base64);
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, 32);
    $ciphertext_raw = substr($c, $ivlen + 32);
    $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
    if (hash_equals($hmac, $calcmac)) {
        return openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    }
    return false;
}

function logActivity($user, $action, $details = "") {
    global $mysqli;
    $query = "INSERT INTO activity_log (user, action, details) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($mysqli, $query);
    mysqli_stmt_bind_param($stmt, "sss", $user, $action, $details);
    mysqli_stmt_execute($stmt);
}
?>