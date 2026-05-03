# 🚀 BSPaste (Bootstrap Paste)

**BSPaste** adalah aplikasi web manajemen catatan (pastebin-style) yang aman, modern, dan sepenuhnya modular. Dibangun menggunakan PHP native dengan integrasi keamanan tinggi, sistem audit log, dan antarmuka pengguna yang sangat fleksibel (berbasis SB Admin 2).

![BSPaste Preview](https://github.com/user-attachments/assets/a950fb7c-5cbb-4f04-97c9-a5a627b4cf35)

---

## ✨ Fitur Utama

- **🛡️ Keamanan Zero-Knowledge (Hybrid Vault)**: BSPaste menggunakan arsitektur keamanan tingkat dewa yang menggabungkan *Master Vault Key* (MVK) dengan *Password-Derived Key* (PDK).
    - **Master Vault Key (MVK)**: Kunci acak 256-bit yang mengenkripsi seluruh catatan Anda.
    - **Key Wrapping**: MVK Anda dibungkus (dienkripsi) oleh password Anda. Saat login, password Anda membuka "bungkusan" ini untuk mendapatkan akses ke catatan.
    - **Recovery Key**: Kunci darurat yang memungkinkan Anda memulihkan akses ke catatan jika lupa password.
- **🎨 Personalisasi Penuh**:
    - Mode **Terang** dan **Gelap**.
    - **Warna Aksen Kustom**: Pilih dari palet pelangi atau tentukan warna kustom Anda sendiri.
- **📜 Log Aktivitas (Audit Trail)**: Mencatat setiap aksi krusial seperti login, pembuatan catatan, pengeditan, hingga perubahan pengaturan.
- **🧩 Arsitektur Modular**: Navigasi (Sidebar dan Topbar) dipisahkan ke dalam komponen modular untuk kemudahan pemeliharaan.
- **🔗 Clean URLs**: Alamat website yang rapi dan aman tanpa menampilkan ekstensi `.php` di address bar.
- **📧 Sistem Otentikasi Lengkap**:
    - Registrasi dengan verifikasi email (PHPMailer).
    - Lupa password dengan token aman yang memiliki masa kedaluwarsa.
- **📱 Responsif**: Optimal untuk desktop, tablet, maupun perangkat mobile.

---

## 🔐 Arsitektur Keamanan: Hybrid Vault

BSPaste mengimplementasikan metode hibrida antara **Recovery Key** dan **Wrapped Vault Key**, serupa dengan arsitektur yang digunakan oleh aplikasi sekelas **Bitwarden** dan **LastPass**.

### Bagaimana Cara Kerjanya?

1.  **Enkripsi Catatan**: Semua catatan user di database dienkripsi menggunakan **MVK** ini (Bukan langsung dari password). MVK adalah kunci acak 256-bit yang sangat kuat.
2.  **Bungkus 1 (Skenario Normal)**: MVK tersebut kita enkripsi menggunakan **Password-Derived Key** (hasil PBKDF2 dari password user). Hasilnya kita simpan di database sebagai `master_vault_key_enc_pass`.
3.  **Bungkus 2 (Skenario Recovery)**: MVK yang sama juga kita enkripsi menggunakan **Recovery Key** (kode rahasia 32 karakter hex). Hasilnya kita simpan sebagai `master_vault_key_enc_recovery`.

### Keunggulan Utama:

-   **Pemulihan Catatan (Lupa Password)**: Jika user lupa password, mereka cukup memasukkan **Recovery Key**. Sistem akan membuka bungkus `master_vault_key_enc_recovery` untuk mengambil kembali **MVK**. Setelah MVK didapat, akses ke seluruh catatan terselamatkan!
-   **Efisiensi Ganti Password**: Saat ganti password, sistem **tidak perlu menyentuh catatan sama sekali**. Kita hanya perlu mengenkripsi ulang satu kunci kecil (MVK) saja dengan password baru. Ini jauh lebih cepat dan aman.
-   **Privasi Total**: Server tetap tidak tahu apa-apa (*Zero-Knowledge*). Server hanya menyimpan "bungkusan" kunci yang tidak bisa dibuka tanpa password atau tanpa Recovery Key fisik milik user.

---

## 🛠️ Persyaratan Sistem

- **PHP**: v7.4 atau lebih baru.
- **Database**: MySQL / MariaDB.
- **Web Server**: Apache (dengan modul `mod_rewrite` aktif untuk Clean URLs).
- **Composer**: Untuk dependensi PHPMailer.

---

## 🚀 Memulai (Get Started)

### 1. Klon Repositori
```bash
git clone https://github.com/username/bspaste.git
cd bspaste
```

### 2. Instalasi Dependensi
```bash
composer install
```

### 3. Konfigurasi Database
1. Buat database baru di MySQL (misal: `bspaste_db`).
2. Import file skema dari `/database/schema.sql` ke database Anda.
3. Sesuaikan kredensial database Anda di file:
    - `configdb-main.php` (untuk aplikasi utama)
    - `auth/configdb-login.php` (untuk sistem otentikasi)

### 4. Konfigurasi Awal
Saat pertama kali login, sistem akan meminta Anda untuk memigrasikan data ke arsitektur **Hybrid Vault**. Pastikan Anda menyimpan **Recovery Key** yang muncul di layar dengan aman.

---

## 📁 Struktur Direktori

```text
bspaste/
├── auth/               # Sistem login, register, dan reset password
├── css/                # File gaya (SB Admin 2, Custom CSS)
├── database/           # File skema SQL
├── img/                # Aset gambar dan ilustrasi
├── js/                 # File JavaScript (SB Admin 2, DataTables)
├── vendor/             # Dependensi Composer (PHPMailer)
├── .htaccess           # Konfigurasi Clean URLs & Keamanan
├── configdb-main.php   # Konfigurasi DB utama & Helper Log
├── index.php           # Dashboard Utama
├── notes.php           # Manajemen Catatan
├── sidebar.php         # Komponen Modular Sidebar & Migrasi Logika
├── topbar.php          # Komponen Modular Topbar
├── profile.php         # Manajemen Profil & Re-wrapping MVK
├── pengaturan.php      # Pengaturan Tema & Warna Aksen
└── log_aktivitas.php   # Riwayat Aktivitas Pengguna
```

---

## 🗄️ Skema Database

Aplikasi ini menggunakan tiga tabel utama dengan struktur yang telah dioptimalkan:

- **`users`**: Menyimpan data akun, preferensi UI (`theme`, `accent_color`), dan kunci enkripsi yang terbungkus (`master_vault_key_enc_pass`, `master_vault_key_enc_recovery`).
- **`notes`**: Menyimpan catatan dalam format ciphertext terenkripsi menggunakan *Master Vault Key*.
- **`activity_log`**: Menyimpan riwayat aksi setiap user untuk tujuan audit.

---

## 🎨 UI/UX & Desain

Aplikasi ini menggunakan pendekatan **Modular UI**:
- **`sidebar.php`**: Berisi navigasi samping dengan deteksi status "Active" otomatis.
- **`topbar.php`**: Berisi fitur pencarian global dan menu dropdown profil.
- **Dynamic Styling**: CSS diinjeksi secara dinamis ke dalam header untuk menerapkan **Warna Aksen** pilihan pengguna tanpa memerlukan file CSS tambahan yang besar.

---

## 🔐 Keamanan

1. **Prepared Statements**: Seluruh query SQL menggunakan `mysqli_prepare` untuk mencegah serangan SQL Injection.
2. **Password Hashing**: Menggunakan `password_hash()` dengan algoritma `PASSWORD_DEFAULT`.
3. **Data Encryption**: Judul dan isi catatan dienkripsi secara dua arah menggunakan `openssl_encrypt`.
4. **XSS Protection**: Seluruh output data pengguna diproses melalui `htmlspecialchars()`.

---

## 👨‍💻 Kontribusi

Kontribusi selalu diterima! Silakan buka *issue* atau kirimkan *pull request* untuk perbaikan dan fitur baru.

---

## 📝 Lisensi

Proyek ini berada di bawah lisensi **MIT**. Silakan gunakan dan modifikasi sesuai kebutuhan.

---

*Dikembangkan dengan ❤️ oleh **Syahda Fahreza***
