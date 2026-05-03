<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch accent color if not in session
if (!isset($_SESSION['accent_color'])) {
    $user_for_accent = $_SESSION['username'];
    // Use $mysqli or $conn depending on which is available (configdb-main uses $mysqli)
    $query_accent = "SELECT accent_color, is_migrated, is_vault_active FROM users WHERE username = ?";
    $stmt_accent = mysqli_prepare($mysqli, $query_accent);
    mysqli_stmt_bind_param($stmt_accent, "s", $user_for_accent);
    mysqli_stmt_execute($stmt_accent);
    $result_accent = mysqli_stmt_get_result($stmt_accent);
    if ($row_accent = mysqli_fetch_assoc($result_accent)) {
        $_SESSION['accent_color'] = $row_accent['accent_color'] ?? '#4e73df';
        $_SESSION['is_migrated'] = $row_accent['is_migrated'];
        $_SESSION['is_vault_active'] = $row_accent['is_vault_active'];
    } else {
        $_SESSION['accent_color'] = '#4e73df';
        $_SESSION['is_migrated'] = 0;
        $_SESSION['is_vault_active'] = 0;
    }
}

// --- HYBRID VAULT MIGRATION LOGIC ---
if (isset($_SESSION['is_vault_active']) && $_SESSION['is_vault_active'] == 0 && isset($_SESSION['user_key'])) {
    $user_mig = $_SESSION['username'];
    $pdk_raw = base64_decode($_SESSION['user_key']);
    
    // 1. Generate MVK (Master Vault Key)
    $mvk_raw = random_bytes(32);
    $_SESSION['master_key'] = base64_encode($mvk_raw);
    
    // 2. Generate Recovery Key (32 chars hex)
    $recovery_key_plain = bin2hex(random_bytes(16)); // 32 chars
    $rk_raw = hash_pbkdf2("sha256", $recovery_key_plain, "BSPasteRecoverySalt", 50000, 32, true);
    
    // 3. Wrap MVK
    $mvk_enc_pass = wrap_key($mvk_raw, $pdk_raw);
    $mvk_enc_recovery = wrap_key($mvk_raw, $rk_raw);
    
    // 4. Re-encrypt Notes
    $query_notes = "SELECT id, title, text FROM notes WHERE user = ?";
    $stmt_notes = mysqli_prepare($mysqli, $query_notes);
    mysqli_stmt_bind_param($stmt_notes, "s", $user_mig);
    mysqli_stmt_execute($stmt_notes);
    $result_notes = mysqli_stmt_get_result($stmt_notes);
    
    $success_mig = true;
    while ($row = mysqli_fetch_assoc($result_notes)) {
        // Try Sakti Legacy first, then Legacy Static
        $dec_title = sakti_legacy_decrypt($row['title']);
        $dec_text = sakti_legacy_decrypt($row['text']);
        
        if ($dec_title === false) {
            $dec_title = legacy_decrypt($row['title']);
            $dec_text = legacy_decrypt($row['text']);
        }
        
        if ($dec_title !== false && $dec_text !== false) {
            $new_title = sakti_encrypt($dec_title);
            $new_text = sakti_encrypt($dec_text);
            
            $upd_note = "UPDATE notes SET title = ?, text = ? WHERE id = ?";
            $stmt_upd = mysqli_prepare($mysqli, $upd_note);
            mysqli_stmt_bind_param($stmt_upd, "ssi", $new_title, $new_text, $row['id']);
            mysqli_stmt_execute($stmt_upd);
        }
    }
    
    if ($success_mig) {
        $upd_user = "UPDATE users SET is_vault_active = 1, is_migrated = 1, master_vault_key_enc_pass = ?, master_vault_key_enc_recovery = ? WHERE username = ?";
        $stmt_user_mig = mysqli_prepare($mysqli, $upd_user);
        mysqli_stmt_bind_param($stmt_user_mig, "sss", $mvk_enc_pass, $mvk_enc_recovery, $user_mig);
        mysqli_stmt_execute($stmt_user_mig);
        
        $_SESSION['is_vault_active'] = 1;
        $_SESSION['is_migrated'] = 1;
        $_SESSION['show_recovery_key'] = $recovery_key_plain; // Store for modal
        logActivity($user_mig, "Migrasi Hybrid Vault", "User berhasil migrasi ke arsitektur Hybrid Vault (MVK + Recovery Key)");
    }
}

$accent_color = $_SESSION['accent_color'];
?>
<style>
    :root {
        --primary-color: <?php echo $accent_color; ?>;
        --primary-color-dark: <?php echo $accent_color; ?>cc;
    }
    /* Backgrounds */
    .bg-primary, .bg-gradient-primary, .zoom-btn-large.bg-primary {
        background-color: var(--primary-color) !important;
    }
    .bg-gradient-primary {
        background-image: linear-gradient(180deg, var(--primary-color) 10%, var(--primary-color-dark) 100%) !important;
    }

    /* Buttons */
    .btn-primary, .zoom-fab.bg-primary {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }
    .btn-primary:hover, .btn-primary:focus, .btn-primary:active, .zoom-fab.bg-primary:hover {
        background-color: var(--primary-color-dark) !important;
        border-color: var(--primary-color-dark) !important;
        filter: brightness(90%);
    }
    .btn-outline-primary {
        color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }
    .btn-outline-primary:hover {
        background-color: var(--primary-color) !important;
        color: #fff !important;
    }

    /* Text & Borders */
    .text-primary {
        color: var(--primary-color) !important;
    }
    .border-left-primary {
        border-left: .25rem solid var(--primary-color) !important;
    }
    .border-bottom-primary {
        border-bottom: .25rem solid var(--primary-color) !important;
    }

    /* Dropdowns & Navigation */
    .dropdown-item.active, .dropdown-item:active {
        background-color: var(--primary-color) !important;
    }
    .nav-pills .nav-link.active, .nav-pills .show > .nav-link {
        background-color: var(--primary-color) !important;
    }

    /* Pagination & Progress */
    .page-item.active .page-link, .progress-bar {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }
    .page-link {
        color: var(--primary-color);
    }

    /* Badges */
    .badge-primary {
        background-color: var(--primary-color) !important;
    }

    /* Custom Forms */
    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }

    /* Sidebar tweaks */
    .sidebar-dark .nav-item.active .nav-link {
        font-weight: 700;
    }
    .sidebar-dark .nav-item.active .nav-link i {
        color: #fff !important;
    }
</style>
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="./">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">BS Paste</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="./">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Nav Item - Catatan -->
    <li class="nav-item <?php echo ($current_page == 'notes.php') ? 'active' : ''; ?>">
        <a class="nav-link" href="notes">
            <i class="fas fa-fw fa-sticky-note"></i>
            <span>Catatan</span></a>
    </li>

    <!-- Nav Item - Beranda -->
    <li class="nav-item">
        <a class="nav-link" href="/">
            <i class="fas fa-fw fa-home"></i>
            <span>Beranda</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->

<!-- Recovery Key Modal -->
<?php if (isset($_SESSION['show_recovery_key'])): ?>
<div class="modal fade" id="recoveryKeyModal" tabindex="-1" role="dialog" aria-labelledby="recoveryKeyModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-bottom-primary shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="recoveryKeyModalLabel"><i class="fas fa-shield-alt mr-2"></i>Simpan Kunci Pemulihan Anda!</h5>
            </div>
            <div class="modal-body text-center">
                <p>BSPaste sekarang menggunakan arsitektur <strong>Hybrid Vault</strong> untuk keamanan maksimal.</p>
                <div class="alert alert-warning small">
                    <i class="fas fa-exclamation-triangle mr-1"></i> <strong>PENTING:</strong> Jika Anda lupa password, kunci ini adalah <u>satu-satunya cara</u> untuk menyelamatkan catatan Anda.
                </div>
                <div class="bg-light p-3 border rounded mb-3">
                    <code class="h4 text-primary font-weight-bold" id="recoveryKeyText"><?php echo $_SESSION['show_recovery_key']; ?></code>
                </div>
                <button class="btn btn-outline-primary btn-sm mb-3" onclick="copyRecoveryKey()">
                    <i class="fas fa-copy mr-1"></i> Salin Kunci
                </button>
                <p class="text-muted small">Simpan kode ini di tempat aman (seperti Password Manager atau catatan fisik). Jangan berikan kode ini kepada siapa pun.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-block" data-dismiss="modal">Saya Sudah Menyimpannya</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#recoveryKeyModal').modal('show');
    });
    function copyRecoveryKey() {
        const text = document.getElementById('recoveryKeyText').innerText;
        navigator.clipboard.writeText(text).then(() => {
            swal("Berhasil!", "Kunci pemulihan telah disalin ke clipboard.", "success");
        });
    }
</script>
<?php unset($_SESSION['show_recovery_key']); ?>
<?php endif; ?>
